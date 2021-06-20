<?php

declare(strict_types=1);

namespace Chiron\DebugBar;

use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Http\Config\HttpConfig;
use Chiron\Core\Config\SettingsConfig;
use Chiron\Http\Http;
use Chiron\Http\Middleware\ErrorHandlerMiddleware;
use Chiron\Http\Middleware\AllowedHostsMiddleware;
use Chiron\Http\Middleware\NotFoundDebugMiddleware;
use Chiron\Http\Middleware\TagRequestMiddleware;
use Chiron\Core\Exception\BootException;
use Chiron\Routing\Route;
use Chiron\Routing\RouteCollection;
use Chiron\DebugBar\DebugBar;
use Psr\Http\Message\ResponseInterface;
use DebugBar\DebugBar as OriginalDebugBar;

use Closure;
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\TimeDataCollector;
//use DebugBar\JavascriptRenderer;

//https://github.com/top-think/think-debugbar/blob/master/src/DebugBar.php

final class DebugBar extends OriginalDebugBar
{
    public function getJavascriptRenderer($baseUrl = null, $basePath = null)
    {
        if ($this->jsRenderer === null) {
            $this->jsRenderer = new JavascriptRenderer($this, $baseUrl, $basePath);
        }
        return $this->jsRenderer;
    }

    /**
     * Starts a measure
     *
     * @param string $name  Internal name, used to stop the measure
     * @param string $label Public name
     */
    public function startMeasure($name, $label = null)
    {
        if ($this->hasCollector('time')) {
            /** @var TimeDataCollector $collector */
            $collector = $this->getCollector('time');
            $collector->startMeasure($name, $label);
        }
    }

    /**
     * Stops a measure
     *
     * @param string $name
     */
    public function stopMeasure($name)
    {
        if ($this->hasCollector('time')) {
            /** @var TimeDataCollector $collector */
            $collector = $this->getCollector('time');
            try {
                $collector->stopMeasure($name);
            } catch (\Exception $e) {
                //  $this->addThrowable($e);
            }
        }
    }

    /**
     * Adds a measure
     *
     * @param string $label
     * @param float  $start
     * @param float  $end
     */
    public function addMeasure($label, $start, $end)
    {
        if ($this->hasCollector('time')) {
            /** @var TimeDataCollector $collector */
            $collector = $this->getCollector('time');
            $collector->addMeasure($label, $start, $end);
        }
    }

    /**
     * Utility function to measure the execution of a Closure
     *
     * @param string  $label
     * @param Closure $closure
     */
    public function measure($label, Closure $closure)
    {
        if ($this->hasCollector('time')) {
            /** @var TimeDataCollector $collector */
            $collector = $this->getCollector('time');
            $collector->measure($label, $closure);
        } else {
            $closure();
        }
    }

    public function addMessage($message, $label = 'info')
    {
        if ($this->hasCollector('messages')) {
            /** @var MessagesCollector $collector */
            $collector = $this->getCollector('messages');
            $collector->addMessage($message, $label);
        }
    }

    // TODO : prendre exemple ici pour ajouter uniquement les collector si par exemple le composant session ou router est installé : https://github.com/barryvdh/laravel-debugbar/blob/6a4bf30a965447268aa23bc4b2456e021762134a/src/LaravelDebugbar.php#L141
    public function init()
    {
        /*
        $this->addCollector(new ThinkCollector($this->app));
        $this->addCollector(new PhpInfoCollector());
        $this->addCollector(new MessagesCollector());

        $logger = new MessagesCollector('log');
        $this['messages']->aggregate($logger);

        $this->app->log->listen(function (LogWrite $event) use ($logger) {
            foreach ($event->log as $channel => $logs) {
                foreach ($logs as $log) {
                    $logger->addMessage(
                        '[' . date('H:i:s') . '] ' . $log,
                        $channel,
                        false
                    );
                }
            }
        });

        $this->addCollector(new RequestDataCollector($this->app->request));
        $this->addCollector(new TimeDataCollector($this->app->request->time()));
        $this->addCollector(new MemoryCollector());

        //配置
        $configCollector = new ConfigCollector();
        $configCollector->setData($this->app->config->get());
        $this->addCollector($configCollector);

        //文件
        $this->addCollector(new FilesCollector($this->app));
        */
    }

/*
    public function addCollector(DataCollectorInterface $collector)
    {
        parent::addCollector($collector);

        if (method_exists($collector, 'useHtmlVarDumper')) {
            $collector->useHtmlVarDumper();
        }

        return $this;
    }
*/

// TODO : méthode à virer !!!
    public function inject(ResponseInterface $response)
    {
        $content = $response->getBody();

        $renderer = $this->getJavascriptRenderer();

        $renderedContent = $renderer->renderHead() . $renderer->render();

        // trace调试信息注入
        $pos = strripos($content, '</body>');
        if (false !== $pos) {
            $content = substr($content, 0, $pos) . $renderedContent . substr($content, $pos);
        } else {
            $content = $content . $renderedContent;
        }
        $response->content($content);
    }







// https://github.com/barryvdh/laravel-debugbar/blob/70b89754913fd89fef16d0170a91dbc2a5cd633a/src/LaravelDebugbar.php#L1031

    /**
     * Magic calls for adding messages
     *
     * @param string $method
     * @param array $args
     * @return mixed|void
     */
    /*
    public function __call($method, $args)
    {
        $messageLevels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log'];
        if (in_array($method, $messageLevels)) {
            foreach ($args as $arg) {
                $this->addMessage($arg, $method);
            }
        }
    }*/

    /**
     * Adds a message to the MessagesCollector
     *
     * A message can be anything from an object to a string
     *
     * @param mixed $message
     * @param string $label
     */
    /*
    public function addMessage($message, $label = 'info')
    {
        if ($this->hasCollector('messages')) {
            $collector = $this->getCollector('messages');
            $collector->addMessage($message, $label);
        }
    }
    */
}
