<?php

namespace Webkul\Installer\Helpers;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Webkul\Core\Concerns\SyncsPostgresSequences;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as BagistoDatabaseSeeder;
use Webkul\Installer\Database\Seeders\ProductTableSeeder;
use Webkul\Product\Console\Commands\Indexer;

class DatabaseManager
{
    use SyncsPostgresSequences;

    /**
     * Admin User ID for which the admin user will be created during installation.
     */
    const int USER_ID = 1;

    /**
     * Default admin name for which the admin user will be created during installation.
     */
    const string DEFAULT_ADMIN_NAME = 'Admin';

    /**
     * Default admin email for which the admin user will be created during installation.
     */
    const string DEFAULT_ADMIN_EMAIL = 'admin@example.com';

    /**
     * Default admin password for which the admin user will be created during installation.
     */
    const string DEFAULT_ADMIN_PASSWORD = 'admin123';

    /**
     * Minimum length required for the first administrator password.
     */
    const int MIN_ADMIN_PASSWORD_LENGTH = 8;

    /**
     * Resolve and validate the credentials used for the first administrator.
     *
     * Explicit values (interactive installer or web installer) win. Otherwise the
     * values from config/installer.php, sourced from BAGISTO_ADMIN_* in .env, are
     * used. Public development defaults are only allowed outside production.
     *
     * @throws InvalidArgumentException
     */
    public function getAdminCredentials(array $data = []): array
    {
        $name = $data['name'] ?? self::DEFAULT_ADMIN_NAME;

        $email = $data['email'] ?? config('installer.admin_email');

        $password = $data['password'] ?? config('installer.admin_password');

        if ($email === null || $password === null) {
            if (app()->environment('production')) {
                throw new InvalidArgumentException('La instalación en producción requiere BAGISTO_ADMIN_EMAIL y BAGISTO_ADMIN_PASSWORD en el archivo .env.');
            }

            $email = $email ?? self::DEFAULT_ADMIN_EMAIL;

            $password = $password ?? self::DEFAULT_ADMIN_PASSWORD;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo del administrador no es válido.');
        }

        if (mb_strlen((string) $password) < self::MIN_ADMIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException('La contraseña del administrador debe tener al menos '.self::MIN_ADMIN_PASSWORD_LENGTH.' caracteres.');
        }

        if (app()->environment('production') && hash_equals(self::DEFAULT_ADMIN_PASSWORD, (string) $password)) {
            throw new InvalidArgumentException('La contraseña pública de desarrollo no puede usarse en producción.');
        }

        return [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ];
    }

    /**
     * Check if the application is installed.
     */
    public function isInstalled(): bool
    {
        if (! file_exists(base_path('.env'))) {
            return false;
        }

        try {
            DB::connection()->getPDO();

            $isConnected = (bool) DB::connection()->getDatabaseName();

            if (! $isConnected) {
                return false;
            }

            $hasTable = Schema::hasTable('admins');

            if (! $hasTable) {
                return false;
            }

            $userCount = DB::table('admins')->count();

            if (! $userCount) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check database connection.
     */
    public function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Drop all the tables and migrate in the database.
     */
    public function migrateFresh(): bool
    {
        try {
            Artisan::call('migrate:fresh');

            return true;
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Seed the database.
     */
    public function seed($parameter): bool
    {
        try {
            app(BagistoDatabaseSeeder::class)->run($parameter);

            return true;
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Generate fake product data.
     *
     * @return void|string
     */
    public function seedSampleProducts($parameters): bool
    {
        try {
            app(ProductTableSeeder::class)->run($parameters);

            Artisan::registerCommand(app(Indexer::class));

            Artisan::call('indexer:index', [
                '--mode' => ['full'],
                '--quiet' => true,
            ]);

            return true;
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Create admin user.
     */
    public function createAdminUser(array $data = []): bool
    {
        try {
            $credentials = $this->getAdminCredentials($data);

            DB::table('admins')->insert([
                'id' => self::USER_ID,
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'password' => bcrypt($credentials['password']),
                'role_id' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncPostgresSequences(['admins']);

            return true;
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }
}
