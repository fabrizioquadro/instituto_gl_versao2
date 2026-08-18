<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Autoload do dompdf (workaround)
|--------------------------------------------------------------------------
|
| O composer ficou lento/travado no dump-autoload após instalar o
| barryvdh/laravel-dompdf. Este registro manual garante o carregamento dos
| namespaces do dompdf sem depender da regeneração do autoload do composer.
| Pode ser removido quando o `composer dump-autoload` voltar a funcionar.
|
*/
spl_autoload_register(function ($class) {
    $base = dirname(__DIR__).'/vendor/';

    // classmap do dompdf (lib/)
    if ($class === 'Dompdf\\Cpdf') {
        $file = $base.'dompdf/dompdf/lib/Cpdf.php';
        if (is_file($file)) {
            require $file;

            return;
        }
    }

    $map = [
        'Barryvdh\\DomPDF\\' => 'barryvdh/laravel-dompdf/src',
        'Dompdf\\' => 'dompdf/dompdf/src',
        'FontLib\\' => 'dompdf/php-font-lib/src/FontLib',
        'Svg\\' => 'dompdf/php-svg-lib/src/Svg',
        'Masterminds\\' => 'masterminds/html5/src',
    ];
    foreach ($map as $prefix => $rel) {
        if (str_starts_with($class, $prefix)) {
            $file = $base.$rel.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
            if (is_file($file)) {
                require $file;

                return;
            }
        }
    }
}, true, true);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
