<?php

declare(strict_types=1);

namespace Chiron\DebugBar\Bootloader;

use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Http\Config\HttpConfig;
use Chiron\Core\Config\SettingsConfig;
use Chiron\Http\Http;
use Chiron\Http\Middleware\ErrorHandlerMiddleware;
use Chiron\Http\Middleware\AllowedHostsMiddleware;
use Chiron\Http\Middleware\NotFoundDebugMiddleware;
use Chiron\Http\Middleware\TagRequestMiddleware;
use Chiron\Core\Exception\BootException;
use Chiron\DebugBar\Controller\AssetController;
use Chiron\DebugBar\Middleware\DebugBarMiddleware;

use Symfony\Component\ErrorHandler\DebugClassLoader;

final class DebugBarBootloader extends AbstractBootloader
{
    public function boot(Http $http): void
    {
        $http->addMiddleware(DebugBarMiddleware::class, Http::PRIORITY_MAX - 4);

        //$routeCollection->get("debugbar/:path", AssetController::class . "@index")->pattern(['path' => '[\w\.\/\-_]+']);
        //$map->get("bar/debugbar/{path}")->to([AssetController::class, 'index'])->assert('path', '[\w\.\/\-_]+');
    }
}
