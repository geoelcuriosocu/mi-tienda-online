<?php

namespace Webkul\Installer\Console\Commands;

use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Webkul\Core\Enums\SupportedDatabaseEnum;
use Webkul\Core\Helpers\SupportedCurrencies;
use Webkul\Core\Helpers\SupportedLocales;
use Webkul\Installer\Events\ComposerEvents;
use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Helpers\EnvironmentManager;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

class Installer extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bagisto:install
        { --demo-samples : Seed demo/sample product data (useful with --no-interaction). }
        { --force : Install without asking for confirmation, which an install into a production environment is otherwise refused without. }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instalador de LaLomita. En modo interactivo solicita el correo y la contraseña del administrador. Con --no-interaction (-n) usa BAGISTO_ADMIN_EMAIL y BAGISTO_ADMIN_PASSWORD del .env (obligatorios en producción). Añadí --demo-samples para productos de ejemplo y --force para instalar en producción, lo que borra todas las tablas.';

    /**
     * Environment details.
     *
     * @var array
     */
    protected $envDetails = [];

    /**
     * Fillable environment variables.
     *
     * @var array
     */
    protected $fillableEnvVariables = [
        'APP_NAME',
        'APP_URL',
        'APP_TIMEZONE',
        'APP_LOCALE',
        'APP_CURRENCY',
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_PREFIX',
        'DB_USERNAME',
        'DB_PASSWORD',
    ];

    /**
     * Create a new command instance.
     */
    public function __construct(
        public EnvironmentManager $environmentManager,
        public DatabaseManager $databaseManager
    ) {
        parent::__construct();
    }

    /**
     * Install and configure bagisto.
     */
    public function handle(): int
    {
        if (! $this->confirmToProceed('Installing drops every table in the database.')) {
            return self::FAILURE;
        }

        $noInteraction = (bool) $this->option('no-interaction');

        $hasExistingEnv = file_exists(base_path('.env'));

        if (! $hasExistingEnv) {
            $this->components->info('Creating the environment configuration file.');

            File::copy('.env.example', '.env');
        } else {
            $this->components->info('Great! your environment configuration file already exists.');
        }

        ! $noInteraction
            ? $this->askDetailsAndUpdateEnv()
            : $this->components->warn('Skipping environment check. This will assume that the `.env` file is already configured. If not, please create it manually.');

        $this->updateEnvVariables();

        $this->loadEnvConfigs();

        $unattendedCredentials = [];

        if ($noInteraction) {
            try {
                $unattendedCredentials = $this->databaseManager->getAdminCredentials();
            } catch (\InvalidArgumentException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        $this->warn('Step: Generating key...');
        $this->call('key:generate', ['--force' => true]);

        $this->warn('Step: Migrating all tables...');

        $this->wipeAndMigrate();

        $this->warn('Step: Seeding basic data for Bagisto kickstart...');
        $this->databaseManager->seed($this->getSeederConfiguration());
        $this->components->info('Basic data seeded successfully.');

        $this->warn('Step: Linking storage directory...');
        $this->call('storage:link');

        if (! $noInteraction) {
            $this->warn('Step: Create admin credentials...');
            $this->askForAdminDetails();
        } elseif ($this->databaseManager->createAdminUser($unattendedCredentials)) {
            if ($this->option('demo-samples')) {
                $this->installSampleProducts();
            }

            $this->finalizeInstallation($unattendedCredentials['email']);
        } else {
            $this->error('No se pudo crear el usuario administrador. Revisá los registros de errores.');

            return self::FAILURE;
        }

        $this->warn('Step: Clearing cached bootstrap files...');
        $this->call('optimize:clear');

        if (! $noInteraction) {
            $this->askToExploreCloudHosting();
        }

        ComposerEvents::postCreateProject();

        return self::SUCCESS;
    }

    /**
     * Request environment configuration details and set them in the `.env`
     * file to facilitate the migration to our database.
     */
    protected function askDetailsAndUpdateEnv(): void
    {
        try {
            $this->askForApplicationDetails();

            $this->askForDatabaseDetails();
        } catch (\Exception $e) {
            $this->error('Error in updating `.env` file, please create it manually and then run `php artisan migrate` again.');
        }
    }

    /**
     * Ask for application details.
     */
    protected function askForApplicationDetails(): void
    {
        $this->updateTextTypeEnv(
            'APP_NAME',
            'Please enter the application name',
            $this->getEnvVariable('APP_NAME', 'Bagisto')
        );

        $this->updateTextTypeEnv(
            'APP_URL',
            'Please enter the application URL',
            $this->getEnvVariable('APP_URL', 'http://localhost:8000')
        );

        $this->updateChoiceTypeEnv(
            'APP_TIMEZONE',
            'Please select the application timezone',
            $this->getTimezones(),
            true
        );

        $this->updateChoiceTypeEnv(
            'APP_LOCALE',
            'Please select the default application locale',
            SupportedLocales::options()
        );

        $this->updateChoiceTypeEnv(
            'APP_CURRENCY',
            'Please select the default currency',
            SupportedCurrencies::options()
        );

        $this->updateMultiSelectTypeEnv(
            'APP_ALLOWED_LOCALES',
            'Please choose the allowed locales for your channels',
            SupportedLocales::options(),
            $this->envDetails['APP_LOCALE']
        );

        $this->updateMultiSelectTypeEnv(
            'APP_ALLOWED_CURRENCIES',
            'Please choose the allowed currencies for your channels',
            SupportedCurrencies::options(),
            $this->envDetails['APP_CURRENCY']
        );
    }

    /**
     * Add the database credentials to the `.env` file.
     *
     * @return mixed
     */
    protected function askForDatabaseDetails()
    {
        $dbConnection = select(
            label   : 'Please select the database connection',
            options : SupportedDatabaseEnum::options(),
            default : SupportedDatabaseEnum::MYSQL->value,
        );

        $defaultPort = SupportedDatabaseEnum::from($dbConnection)->defaultPort();

        $databaseDetails = [
            'DB_CONNECTION' => $dbConnection,

            'DB_HOST' => text(
                label    : 'Please enter the database host',
                default  : $this->getEnvVariable('DB_HOST', '127.0.0.1'),
                required : true
            ),

            'DB_PORT' => text(
                label    : 'Please enter the database port',
                default  : $this->getEnvVariable('DB_PORT', $defaultPort),
                required : true
            ),

            'DB_DATABASE' => text(
                label    : 'Please enter the database name',
                default  : $this->getEnvVariable('DB_DATABASE', ''),
                required : true
            ),

            'DB_PREFIX' => text(
                label   : 'Please enter the database prefix',
                default : $this->getEnvVariable('DB_PREFIX', ''),
                hint    : 'or press enter to continue',
                validate: function (string $value) {
                    if (strlen($value) > 0) {
                        if (strlen($value) > 4) {
                            return 'The database prefix must be at most 4 characters long.';
                        }

                        if (! preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
                            return 'The database prefix can only contain letters, numbers, and underscores.';
                        }
                    }

                    return null;
                }
            ),

            'DB_USERNAME' => text(
                label    : 'Please enter your database username',
                default  : $this->getEnvVariable('DB_USERNAME', ''),
                required : true
            ),

            'DB_PASSWORD' => password(
                label    : 'Please enter your database password',
                required : false
            ),
        ];

        if (
            ! $databaseDetails['DB_DATABASE']
            || ! $databaseDetails['DB_USERNAME']
        ) {
            return $this->error('Please enter the database credentials.');
        }

        foreach ($databaseDetails as $key => $value) {
            if ($value) {
                $this->envDetails[$key] = $value;
            }
        }
    }

    /**
     * Create a admin credentials.
     *
     * @return mixed
     */
    protected function askForAdminDetails()
    {
        $adminName = text(
            label    : 'Enter the name of the admin user',
            default  : 'Example',
            required : true
        );

        $adminEmail = text(
            label    : 'Enter the email address of the admin user',
            default  : config('installer.admin_email') ?? (app()->environment('production') ? '' : DatabaseManager::DEFAULT_ADMIN_EMAIL),
            validate : fn (string $value) => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'The email address you entered is not valid please try again.',
                default => null
            }
        );

        $adminPassword = password(
            label    : 'Configure la contraseña del administrador (no se muestra en pantalla)',
            default  : config('installer.admin_password') ?? '',
            required : true,
            validate : fn (string $value) => match (true) {
                mb_strlen($value) < DatabaseManager::MIN_ADMIN_PASSWORD_LENGTH => 'La contraseña debe tener al menos '.DatabaseManager::MIN_ADMIN_PASSWORD_LENGTH.' caracteres.',
                app()->environment('production') && $value === DatabaseManager::DEFAULT_ADMIN_PASSWORD => 'La contraseña pública de desarrollo no puede usarse en producción.',
                default => null
            }
        );

        $sampleProduct = select(
            label   : 'Please select if you want some sample products after installation.',
            options : ['true', 'false'],
            default : 'false',
            hint    : 'The action will create products after installation.',
        );

        try {
            $this->databaseManager->createAdminUser([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => $adminPassword,
            ]);

            if (
                $sampleProduct === 'true'
                || $this->option('demo-samples')
            ) {
                $this->installSampleProducts();
            }

            $this->finalizeInstallation($adminEmail);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Seed the demo/sample product data and index it.
     */
    protected function installSampleProducts(): void
    {
        $this->warn('Step: Seeding sample product data. Please Wait...');

        $this->components->info('Seeding time depends on the number of locales selected. This process may take up to 2 minutes to complete.');

        $this->databaseManager->seedSampleProducts($this->getSeederConfiguration());

        $this->components->info('Now Indexing data...');

        $this->call('indexer:index', ['--mode' => ['full']]);

        $this->components->success('Sample product data seeded successfully.');
    }

    /**
     * Mark the installation as complete. The password is never printed.
     */
    protected function finalizeInstallation(string $adminEmail): void
    {
        File::put(storage_path('installed'), 'LaLomita is successfully installed.');

        $this->info('-----------------------------');
        $this->info('¡Listo! LaLomita se instaló correctamente.');
        $this->info('Ingresá en '.$this->getEnvVariable('APP_URL').'/'.$this->getEnvVariable('APP_ADMIN_URL', 'admin'));
        $this->info('Correo del administrador: '.$adminEmail);
        $this->info('La contraseña no se muestra por seguridad: guardala en un lugar seguro.');

        Event::dispatch('bagisto.installed');
    }

    /**
     * Method for asking the details of `.env` files.
     */
    protected function updateTextTypeEnv(string $key, string $question, string $defaultValue): void
    {
        $input = text(
            label    : $question,
            default  : $defaultValue,
            required : true
        );

        $this->envDetails[$key] = $input ?: $defaultValue;
    }

    /**
     * Method for asking choice based on the list of options.
     *
     * @return string
     */
    protected function updateChoiceTypeEnv(string $key, string $question, array $choices, bool $useSuggest = false): void
    {
        if ($useSuggest) {
            $choice = suggest(
                label: $question,
                options: $choices,
                default: $this->getEnvVariable($key, '')
            );
        } else {
            $choice = select(
                label: $question,
                options: $choices,
                default: $this->getEnvVariable($key, '')
            );
        }

        $this->envDetails[$key] = $choice;
    }

    /**
     * Method for getting allowed choices based on the list of options.
     */
    protected function updateMultiSelectTypeEnv(string $key, string $question, array $choices, string $defaultChoice)
    {
        $choices = array_merge(['all' => 'All'], $choices);

        $selectedValues = multiselect(
            label: $question,
            options: array_values($choices),
        );

        $selectedChoices = [];

        foreach ($selectedValues as $selectedValue) {
            foreach ($choices as $choiceKey => $value) {
                if ($selectedValue === $value) {
                    $selectedChoices[$choiceKey] = $value;
                    break;
                }
            }
        }

        $selectedChoices = array_key_exists('all', $selectedChoices)
            ? array_values(array_unique(array_merge(
                [$defaultChoice],
                array_diff(array_keys($choices), [$defaultChoice, 'all'])
            )))
            : array_values(array_unique(array_merge(
                [$defaultChoice],
                array_diff(array_keys($selectedChoices), [$defaultChoice])
            )));

        $this->envDetails[$key] = $selectedChoices;
    }

    /**
     * Check key in `.env` file because it will help to find values at runtime.
     */
    protected function getEnvVariable(string $key, $default = null): string|bool
    {
        return $this->environmentManager->getEnvVariable($key, $default);
    }

    /**
     * Update the `.env` file with the provided details.
     */
    protected function updateEnvVariables(): void
    {
        foreach ($this->envDetails as $key => $value) {
            if (! in_array($key, $this->fillableEnvVariables)) {
                continue;
            }

            $value = trim($value, '"');

            $this->environmentManager->updateEnvVariable($key, $value, Str::startsWith($key, 'DB_'));
        }
    }

    /**
     * Loaded `.env` configs.
     */
    protected function loadEnvConfigs(): void
    {
        $this->warn('Step: Loading configurations...');

        $this->environmentManager->loadEnvConfigs();

        if ($this->databaseManager->checkDatabaseConnection()) {
            $this->components->info('Database connection established successfully.');

            $this->components->info('Configuration loaded successfully.');
        }
    }

    /**
     * Drop every table and migrate, wiping first because `migrate:fresh` on its own does
     * not clear a database that carries a table prefix.
     */
    protected function wipeAndMigrate(): void
    {
        $this->call('db:wipe', ['--force' => true]);

        $this->call('migrate:fresh', ['--force' => true]);
    }

    /**
     * Get sorted list of timezone abbreviations.
     */
    protected function getTimezones(): array
    {
        $timezoneAbbreviations = DateTimeZone::listAbbreviations();

        $timezones = [];

        foreach ($timezoneAbbreviations as $zones) {
            foreach ($zones as $zone) {
                if (! empty($zone['timezone_id'])) {
                    $timezones[$zone['timezone_id']] = $zone['timezone_id'];
                }
            }
        }

        asort($timezones);

        return $timezones;
    }

    /**
     * Get the seeder configuration.
     */
    protected function getSeederConfiguration(): array
    {
        return [
            'default_locale' => $this->envDetails['APP_LOCALE'] ?? $this->getEnvVariable('APP_LOCALE', 'en'),
            'allowed_locales' => $this->envDetails['APP_ALLOWED_LOCALES'] ?? [$this->getEnvVariable('APP_LOCALE', 'en')],
            'default_currency' => $this->envDetails['APP_CURRENCY'] ?? $this->getEnvVariable('APP_CURRENCY', 'USD'),
            'allowed_currencies' => $this->envDetails['APP_ALLOWED_CURRENCIES'] ?? [$this->getEnvVariable('APP_CURRENCY', 'USD')],
            'skip_admin_creation' => true,
        ];
    }

    /**
     * Ask user to explore Bagisto Cloud hosting.
     */
    protected function askToExploreCloudHosting(): void
    {
        if (! $this->confirm('Would you like to explore managed Bagisto Cloud hosting?', true)) {
            return;
        }

        $cloudUrl = 'https://bagisto.com/en/cloud/';

        if (PHP_OS_FAMILY == 'Darwin') {
            exec("open {$cloudUrl}");
        }

        if (PHP_OS_FAMILY == 'Windows') {
            exec("start {$cloudUrl}");
        }

        if (PHP_OS_FAMILY == 'Linux') {
            exec("xdg-open {$cloudUrl}");
        }
    }
}
