<p align="center">
  <img src="public/themes/shop/default/build/assets/logo-CZWQQgOF.svg" alt="LaLomita" width="220">
</p>

<p align="center">
  <strong>LaLomita</strong> · Tienda online basada en Bagisto, lista para personalizar y entregar a un cliente.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/license-MIT-green" alt="Licencia MIT">
  <img src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white" alt="PHP 8.4">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
</p>

---

## 1. Presentación

LaLomita es una plataforma de comercio electrónico construida sobre Bagisto (Laravel + Vue.js). Incluye tienda pública, panel de administración, catálogo, inventario, pedidos, pagos, envíos, cupones, reportes y multilenguaje, con identidad visual propia.

**Repositorio oficial:** https://github.com/geoelcuriosocu/mi-tienda-online

## 2. Características principales

- Catálogo con categorías, atributos, variantes, precios especiales e inventario.
- Carrito, checkout, cupones, reglas de descuento y gestión de pedidos.
- Pasarelas de pago: Stripe, PayPal, Razorpay, PayU, PhonePe y más.
- Envíos, zonas, tarifas, impuestos y gestores de contenido.
- Panel de administración con dashboard, reportes, usuarios y respaldos.
- Multimoneda y multilenguaje; **español por defecto**.
- Zona horaria configurable desde `.env`.
- Soporte para Laravel Sail (Docker) y para instalación clásica con Apache/Nginx.

## 3. Requisitos mínimos

| Software | Versión comprobada en este proyecto |
|---|---|
| PHP | 8.4 o superior (`composer.json` declara `^8.4`) |
| Composer | 2.x |
| Node.js | 20 o superior (Vite 6 / `package.json`) |
| npm | 10 o superior |
| Base de datos | MySQL 8.0 o MariaDB 10.11 (los perfiles Docker usan esas versiones) |

Extensiones PHP requeridas: `intl`, `pdo_mysql` (o el driver de tu base), `curl`, `mbstring`, `openssl`, `zip`, `gd`, `fileinfo`, `calendar`, `tokenizer`.

## 4. Instalación en Windows (paso a paso)

```powershell
git clone https://github.com/geoelcuriosocu/mi-tienda-online.git
cd mi-tienda-online
composer install
npm install
npm run build
copy .env.example .env
php artisan key:generate
```

Editá `.env` y configurá al menos:

```dotenv
APP_URL=http://localhost:8000
APP_TIMEZONE=America/Havana
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lalomita
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

Si es una instalación nueva y querés elegir las credenciales del administrador, agregá también:

```dotenv
BAGISTO_ADMIN_EMAIL=tu-correo@ejemplo.com
BAGISTO_ADMIN_PASSWORD=una-contraseña-larga-y-única
```

Luego instalás:

```powershell
php artisan bagisto:install --force
php artisan serve
```

> `bagisto:install` **borra todas las tablas** de la base indicada. Usalo solo en una base de instalación. En modo interactivo pregunta correo y contraseña del administrador (la contraseña no se muestra en pantalla). Con `--no-interaction` toma las variables `BAGISTO_ADMIN_*` del `.env`.

- Tienda: http://localhost:8000
- Administración: http://localhost:8000/admin

## 5. Instalación en Linux (paso a paso)

```bash
git clone https://github.com/geoelcuriosocu/mi-tienda-online.git
cd mi-tienda-online
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

Configurá `.env` como en la sección anterior (usá la zona horaria de tu país) y ejecutá:

```bash
php artisan bagisto:install --force
php artisan serve --host=0.0.0.0 --port=8000
```

Para un servidor web real (Nginx/Apache), el document root debe ser `public/` y la configuración de producción está en `docker/production/`.

## 6. Instalación con Docker

El `docker-compose.yml` del proyecto está basado en **Laravel Sail** y usa los perfiles `mysql`, `mariadb`, `pgsql`, `nginx`, `apache` y `litespeed`. Primero instalá las dependencias y creá el `.env`:

```bash
composer install
cp .env.example .env
# configurá DB_* y las variables BAGISTO_ADMIN_*
docker compose up -d
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan bagisto:install --force --no-interaction
docker compose exec laravel.test npm install
# Solo si modificaste los temas:
docker compose exec laravel.test sh -c "cd packages/Webkul/Shop && npm install && npm run build"
```

La documentación de la imagen de producción (Supervisor, colas, cron, SSL) está en [`docker/production/README.md`](docker/production/README.md).

## 7. Base de datos

La conexión se define solo en `.env` (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_PREFIX`). No hay credenciales reales en el repositorio. El instalador ejecuta `db:wipe`, `migrate:fresh` y los seeders de Bagisto.

## 8. Archivo `.env`

- `.env.example`: plantilla segura para desarrollo (`APP_DEBUG=false`, `APP_ENV=local`).
- `.env.production.example`: plantilla para producción (`APP_ENV=production`, HTTPS, cookies seguras, `APP_DEBUG=false`).
- `.env` está excluido de Git por `.gitignore`. **Nunca lo subas.**

## 9. Generación de `APP_KEY`

```bash
php artisan key:generate
```

Cada instalación genera su propia clave. No reuses una clave de otro proyecto.

## 10. Dependencias

```bash
composer install
npm install
```

`composer.json` incluye los scripts `post-autoload-dump` (descubrimiento de paquetes) y `post-create-project-cmd` (copia `.env.example` y genera clave).

## 11. Migraciones y seeders

```bash
php artisan bagisto:install --force
```

Si ya tenés la base migrada y solo querés sembrar datos sin borrar tablas:

```bash
php artisan db:seed
```

Con `APP_ENV=production`, el seeder del administrador exige `BAGISTO_ADMIN_EMAIL` y `BAGISTO_ADMIN_PASSWORD`.

## 12. Administrador inicial

No existe una cuenta pública. Elegí una de estas dos formas:

1. **Interactiva:** `php artisan bagisto:install --force` y completá correo y contraseña cuando se pidan.
2. **Desatendida:** definí `BAGISTO_ADMIN_EMAIL` y `BAGISTO_ADMIN_PASSWORD` en `.env` y ejecutá `php artisan bagisto:install --force --no-interaction`.

La contraseña debe tener al menos **8 caracteres** y no puede ser la contraseña de desarrollo en producción. La contraseña nunca se imprime en consola.

## 13. Recursos frontend

El build de la raíz compila solo los recursos globales (`resources/css/app.css` y `resources/js/app.js`):

```bash
npm install
npm run build      # producción
npm run dev        # desarrollo con Vite
```

Los temas (Admin, Shop e Installer) tienen builds Vite independientes y sus recursos ya compilados están incluidos en `public/themes/*/default/build/`. Si modificás un tema, compilalo desde su paquete:

```bash
(cd packages/Webkul/Admin && npm install && npm run build)
(cd packages/Webkul/Shop && npm install && npm run build)
(cd packages/Webkul/Installer && npm install && npm run build)
```

## 14. Inicio del servidor

```bash
php artisan serve              # http://localhost:8000
```

En producción configurá Nginx/Apache con `public/` como raíz y HTTPS.

## 15. Acceso al panel administrativo

`http://localhost:8000/admin` (o el valor de `APP_ADMIN_URL`). Ingresá con el correo y la contraseña definidos durante la instalación.

## 16. Idioma y zona horaria

- `APP_LOCALE=es` y `APP_FALLBACK_LOCALE=es`: idioma del panel y de la tienda nueva (el canal se siembra con ese idioma).
- Las traducciones españolas están en `lang/es/` y en `packages/Webkul/*/src/Resources/lang/es/`.
- `APP_TIMEZONE`: zona horaria IANA de pedidos, informes y tareas programadas. Ejemplos: `UTC`, `America/Havana`, `America/Mexico_City`, `Europe/Madrid`.
- Después de cambiar `.env`: `php artisan optimize:clear`.

## 17. Correo

La tienda usa el transporte `bagisto-dynamic-smtp`, configurable desde el panel de administración. En `.env` podés definir `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` y `MAIL_FROM_NAME`, además de `ADMIN_MAIL_ADDRESS` y `CONTACT_MAIL_ADDRESS`.

## 18. Preparación para producción

1. Copiá `.env.production.example` a `.env` y completá valores reales.
2. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tu-dominio.com`.
3. `php artisan key:generate`.
4. Definí `BAGISTO_ADMIN_EMAIL` y `BAGISTO_ADMIN_PASSWORD`.
5. `composer install --no-dev --optimize-autoloader` y, si modificaste temas, compilá cada paquete según la sección 13.
6. `php artisan bagisto:install --force --no-interaction`.
7. `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
8. HTTPS con certificado válido; `SESSION_SECURE_COOKIE=true`.
9. Permisos: `storage/` y `bootstrap/cache/` escribibles por el servidor web.
10. Nunca subas `.env`, claves API ni contraseñas al repositorio.

## 19. Verificaciones y pruebas

```bash
composer validate
composer check-platform-reqs
php artisan about
vendor/bin/pest
vendor/bin/pint --test
npm run build
```

## 20. Solución de errores frecuentes

- **"No such file or directory vendor/autoload.php"** → ejecutá `composer install`.
- **Error de conexión a base de datos** → revisá `DB_*` en `.env` y luego `php artisan optimize:clear`.
- **Pantalla blanca o error 500 sin detalle** → revisá `storage/logs/laravel.log`; activá `APP_DEBUG=true` solo en local.
- **La instalación dice que las credenciales de administrador faltan** → configurá `BAGISTO_ADMIN_EMAIL` y `BAGISTO_ADMIN_PASSWORD` en `.env`.
- **Estilos sin cargar** → verificá `public/themes/*/default/build/`; si modificaste un tema, compilalo desde su paquete (sección 13) y ejecutá `php artisan storage:link`.
- **Cambios de `.env` que no se aplican** → `php artisan optimize:clear`.
- **Zona horaria incorrecta en pedidos** → verificá `APP_TIMEZONE` y despejá caché de configuración.

## Créditos

Basado en **Bagisto**, de Webkul Software, bajo licencia MIT. Se conserva el aviso de copyright original en [`LICENSE`](LICENSE). Las personalizaciones y la identidad visual son de LaLomita.

## Licencia

MIT. Podés usar, modificar y distribuir este software, incluso con fines comerciales, manteniendo el aviso de copyright original. Ver [`LICENSE`](LICENSE).
