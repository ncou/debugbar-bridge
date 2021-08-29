<?php

declare(strict_types=1);

namespace Chiron\DebugBar\Bootloader;

use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Http\Config\HttpConfig;
use Chiron\Http\Http;
use Chiron\Http\Middleware\ErrorHandlerMiddleware;
use Chiron\Http\Middleware\AllowedHostsMiddleware;
use Chiron\Http\Middleware\NotFoundDebugMiddleware;
use Chiron\Http\Middleware\TagRequestMiddleware;
use Chiron\Core\Exception\BootException;
use Chiron\DebugBar\Controller\AssetController;
use Chiron\DebugBar\Middleware\DebugBarMiddleware;
use Chiron\Routing\Map;

use Symfony\Component\ErrorHandler\DebugClassLoader;

final class DebugBarBootloader extends AbstractBootloader
{
    public function boot(Http $http, Map $map): void
    {
        // TODO : il faudrait seulement ajouter la DebugBar si on est en mode Debug=true, récupérer le SettingsConfig pour vérifier ce point et conditionner l'ajout du moddleware !!!
        $http->addMiddleware(DebugBarMiddleware::class, Http::PRIORITY_MAX - 4);

        $map->get('debugbar/assets/stylesheets')->to([AssetController::class, 'css'])->name('debugbar.assets.css');
        $map->get('debugbar/assets/javascript')->to([AssetController::class, 'js'])->name('debugbar.assets.js');
    }
}
