<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Credenciales del primer administrador
    |--------------------------------------------------------------------------
    |
    | Estos valores se leen del .env de cada instalación. No se definen aquí
    | credenciales públicas: en producción la instalación falla si no se
    | configuran. En local/testing se usan los valores de desarrollo.
    |
    */

    'admin_email' => env('BAGISTO_ADMIN_EMAIL'),

    'admin_password' => env('BAGISTO_ADMIN_PASSWORD'),

];
