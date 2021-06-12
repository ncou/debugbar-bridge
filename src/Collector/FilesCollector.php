<?php

declare(strict_types=1);

namespace Chiron\DebugBar\Collector;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Http\Helper\Uri;
use Chiron\Core\Config\SettingsConfig;
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

final class FilesCollector extends DataCollector implements Renderable
{
    //protected $app;

    protected $ignored = [
        'vendor/maximebf/debugbar',
        'vendor/chiron/debugbar-bridge',
    ];

/*
    public function __construct(App $app)
    {
        $this->app = $app;
    }
*/

    /**
     * {@inheritDoc}
     */
    public function collect(): array
    {
        $files = $this->getIncludedFiles();

        $included = [];
        foreach ($files as $file) {

            if (Str::contains($file, $this->ignored)) {
                continue;
            }

            $included[] = [
                'message'   => "'" . $this->stripBasePath($file) . "',",
                // Use PHP syntax so we can copy-paste to compile config file.
                'is_string' => true,
            ];
        }

        return [
            'messages' => $included,
            'count'    => count($included),
        ];
    }

    /**
     * Get the files included on load.
     *
     * @return array
     */
    protected function getIncludedFiles()
    {
        return get_included_files();
    }

    /**
     * Remove the basePath from the paths, so they are relative to the base
     *
     * @param $path
     * @return string
     */
    protected function stripBasePath($path)
    {
        //return ltrim(str_replace($this->app->getRootPath(), '', $path), '/');
        return $path;
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets(): array
    {
        $name = $this->getName();
        return [
            "$name"       => [
                "icon"    => "files-o",
                "widget"  => "PhpDebugBar.Widgets.MessagesWidget",
                "map"     => "$name.messages",
                "default" => "{}",
            ],
            "$name:badge" => [
                "map"     => "$name.count",
                "default" => "null",
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'files';
    }
}
