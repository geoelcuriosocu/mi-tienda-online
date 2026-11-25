<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identidad comercial de LaLomita
    |--------------------------------------------------------------------------
    | Colores extraídos del logotipo oficial (mascota del campesino).
    | Centralizados para no duplicar valores en vistas, correos ni estilos.
    |
    */

    'name' => env('APP_NAME', 'LaLomita'),

    'colors' => [
        'naranja' => '#F28C11',
        'amarillo' => '#FFC245',
        'verde' => '#409C06',
        'rojo' => '#CE1616',
        'crema' => '#FFF3E2',
        'blanco' => '#FFFFFF',
        'marron' => '#2B1406',
    ],

    'assets' => [
        'base' => 'assets/branding/',
        'mascota' => 'assets/branding/mascota-circular-1024.png',
        'mascota_movil' => 'assets/branding/mascota-movil-256.png',
        'logo' => 'assets/branding/logo-principal.svg',
        'logo_horizontal' => 'assets/branding/logo-horizontal.svg',
        'logo_claro' => 'assets/branding/logo-claro.svg',
        'logo_oscuro' => 'assets/branding/logo-oscuro.svg',
        'isotipo' => 'assets/branding/isotipo.svg',
        'favicon' => 'assets/branding/favicon.ico',
    ],

];
