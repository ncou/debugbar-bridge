<?php

declare(strict_types=1);

namespace Chiron\DebugBar\Collector;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Http\Helper\Uri;
use Chiron\Http\Config\HttpConfig;
use Chiron\Http\Exception\DisallowedHostException;
use Chiron\Http\Exception\SuspiciousOperationException;
use Chiron\Support\Str;
use Chiron\DebugBar\DebugBar;

use DebugBar\StandardDebugBar;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

// TODO : utiliser la classe Framework::version() pour retourner le numéro de version de l'application !!!!

final class ChironCollector extends DataCollector implements Renderable
{
    /**
     * Called by the DebugBar when data needs to be collected
     *
     * @return array Collected data
     */
    public function collect(): array
    {
        return [
            "version" => '1.0.0',
        ];
    }

    /**
     * Returns the unique name of the collector
     *
     * @return string
     */
    public function getName(): string
    {
        return 'chiron';
    }

    /**
     * Returns a hash where keys are control names and their values
     * an array of options as defined in {@see DebugBar\JavascriptRenderer::addControl()}
     *
     * @return array
     */
    public function getWidgets(): array
    {
        return [
            "version" => [
                "icon"    => "github",
                "tooltip" => "Chiron Version",
                "map"     => "chiron.version",
                "default" => "",
            ],
        ];
    }
}
