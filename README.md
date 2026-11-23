<p align="center">
  <img src="public/themes/shop/default/build/assets/logo-CZWQQgOF.svg" alt="LaLomita" width="220">
</p>

<p align="center">
  <strong>LaLomita</strong> · Plataforma de comercio electrónico lista para vender online.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/license-MIT-green" alt="MIT License">
  <img src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white" alt="PHP 8.4">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
</p>

---

## ¿Qué es LaLomita?

LaLomita es una tienda online completa: catálogo, carrito, checkout, pagos, envíos, panel de administración y una tienda responsive. Viene con una identidad propia (logo, colores y textos) y con todo lo necesario para desplegarla y empezar a vender.

Está construida sobre una base open source madura y probada (ver [Créditos](#créditos)), con personalizaciones propias y una instalación simplificada.

## Características

- 🛍️ Catálogo con categorías, atributos, variantes, precios especiales e inventario.
- 🧾 Carrito, checkout multi-paso, cupones, reglas de descuento y gestión de pedidos.
- 💳 Pasarelas de pago: Stripe, PayPal, Razorpay, PayU y más.
- 🚚 Métodos de envío, tarifas y zonas configurables.
- 👤 Cuentas de cliente, favoritos, reseñas y WhatsApp/AI assistant opcional.
- 📊 Panel de administración con dashboard, reportes y gestión de contenidos.
- 🌐 Multimoneda y multilenguaje (incluido español).
- 🐳 Instalación con Docker o con servidor web clásico (Apache/Nginx).

## Requisitos

- PHP 8.4+
- Composer 2
- Node.js 20+ y npm
- MySQL 8+ (o MariaDB 10.6+)
- Extensiones PHP: `intl`, `pdo_mysql`, `curl`, `mbstring`, `openssl`, `zip`, `gd`

## Instalación rápida

```bash
git clone https://github.com/geoelcurioso/mi-tienda-online.git
cd mi-tienda-online
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Configurá `DB_*` y `APP_URL` en tu `.env`, y luego:

```bash
php artisan migrate --seed
php artisan serve
```

- Tienda: `http://localhost:8000`
- Administración: `http://localhost:8000/admin`

> Usuario inicial: `admin@example.com` / contraseña `admin123` (cambiala después de instalar).

### Con Docker

```bash
docker compose up -d
```

La configuración de producción está en `docker/production/`.

## Estructura

| Ruta | Contenido |
|---|---|
| `app/` | Controladores y providers propios |
| `config/` | Configuración (nombre, temas, cache, pagos…) |
| `packages/` | Módulos de la plataforma (admin, tienda, catálogo, ventas…) |
| `public/themes/` | Assets compilados de los temas admin, shop e installer |
| `resources/` | Vistas, CSS y JS fuente |
| `database/` | Migraciones y seeders |

## Personalización

- **Nombre de la tienda:** variable `APP_NAME` en `.env`.
- **Logo:** reemplazá los SVG en `public/themes/*/default/build/assets/` (y recompilá con `npm run build` si cambiás las fuentes).
- **Colores y estilos:** `resources/themes/` y los archivos `*.css` de cada tema.
- **Idiomas:** `lang/`.

## Créditos

Este proyecto está basado en **Bagisto**, plataforma de comercio electrónico de Webkul Software, distribuida bajo licencia MIT.

- Sitio: https://bagisto.com
- Repositorio original: https://github.com/bagisto/bagisto
- Copyright original: © 2010–2025 Webkul Software

Se conservan el aviso de copyright y la licencia original en [`LICENSE`](LICENSE), según lo exigido por la licencia MIT. Las personalizaciones, la identidad visual y el trabajo de implementación son de LaLomita.

## Licencia

MIT. Podés usar, modificar y distribuir este software, incluso con fines comerciales, manteniendo el aviso de copyright original. Ver [`LICENSE`](LICENSE).
