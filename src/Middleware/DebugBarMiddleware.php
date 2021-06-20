<?php

declare(strict_types=1);

namespace Chiron\DebugBar\Middleware;

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

use Chiron\Http\Message\StatusCode;

use DebugBar\StandardDebugBar;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;


use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use Chiron\DebugBar\Collector\ChironCollector;
use Chiron\DebugBar\Collector\FilesCollector;

use Chiron\Routing\UrlGeneratorInterface;

use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;

//https://github.com/lekoala/silverstripe-debugbar/blob/master/code/Middleware/DebugBarMiddleware.php

//https://github.com/snowair/phalcon-debugbar/blob/master/src/Controllers/AssetController.php
//https://github.com/top-think/think-debugbar/blob/master/src/controller/AssetController.php
//https://github.com/barryvdh/laravel-debugbar/blob/cae0a8d1cb89b0f0522f65e60465e16d738e069b/src/Controllers/AssetController.php

// Functions
//https://github.com/snowair/phalcon-debugbar/blob/5e1917f17f0a3ecb40110d389ed557bf7dedf552/src/Debug.php
// JS Render avec un UrlGenerator pour prendre en compte le basePath
//https://github.com/snowair/phalcon-debugbar/blob/5e1917f17f0a3ecb40110d389ed557bf7dedf552/src/JsRender.php#L16
//https://github.com/barryvdh/laravel-debugbar/blob/master/src/JavascriptRenderer.php

//https://github.com/snowair/phalcon-debugbar/blob/master/src/PhalconDebugbar.php#L106
//https://github.com/snowair/phalcon-debugbar/blob/master/src/Whoops/DebugbarHandler.php
//https://github.com/snowair/phalcon-debugbar/blob/master/src/Monolog/Handler/Debugbar.php

// TODO : créer deux middleware pour faire du profiling de request et pour logger la request+response : exemple : https://github.com/hannesvdvreken/guzzle-debugbar

/**
 * Inject the debugbar in the html response.
 */
final class DebugBarMiddleware implements MiddlewareInterface
{
    private static $mimes = [
        'css' => 'text/css',
        'js' => 'text/javascript',
    ];

    /** @var DebugBar */
    private $debugbar;
    /** @var StreamFactoryInterface */
    private $streamFactory;

/*
    public function __construct(DebugBar $debugbar,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory)
    {*/
    public function __construct(DebugBar $debugbar,
        StreamFactoryInterface $streamFactory,
        UrlGeneratorInterface $url)
    {

        $this->debugbar = $debugbar;
        $this->debugbar->getJavascriptRenderer()->setUrlGenerator($url); // TODO : créer un serviceprovider pour initialiser la construction de cette classe !!!!



        $this->streamFactory = $streamFactory;

        // TODO : déplacer ces collecteur de base directement dans le constructeur de la classe Chiron\DebugBar::class
        $this->debugbar->addCollector(new PhpInfoCollector());
        $this->debugbar->addCollector(new MessagesCollector());
        $this->debugbar->addCollector(new RequestDataCollector());
        $this->debugbar->addCollector(new TimeDataCollector());
        $this->debugbar->addCollector(new MemoryCollector());
        $this->debugbar->addCollector(new ExceptionsCollector());


        $this->debugbar->addCollector(new ChironCollector());

/*
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
*/

        //$this->debugbar->addCollector(new RequestDataCollector($this->app->request));
        //$this->debugbar->addCollector(new TimeDataCollector($this->app->request->time()));
        //$this->debugbar->addCollector(new MemoryCollector());

        //配置
        //$configCollector = new ConfigCollector();
        //$configCollector->setData($this->app->config->get());
        //$this->debugbar->addCollector($configCollector);

        //文件
        $this->debugbar->addCollector(new FilesCollector());

    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Redirection response.
        if (StatusCode::isRedirection($response->getStatusCode())) {
            return $response;
        }

        // Html response.
        if (stripos($response->getHeaderLine('Content-Type'), 'text/html') === 0) {
            return $this->handleHtml($response);
        }

        return $response;
    }

    /**
     * Handle html responses
     */
    // TODO : Vérifier qu'il y a bien une balise <body> dans le html avant de faire l'injection du code de la debugbar !!!!!
    // TODO : améliorer le code pour ne pas utiliser de StreamFactory !!!! https://github.com/cakephp/debug_kit/blob/8fbe61b46db5289a8c9219e4158622b4e672122d/src/ToolbarService.php#L335
    // TODO : exemple https://github.com/symfony/web-profiler-bundle/blob/5.3/EventListener/WebDebugToolbarListener.php#L136
    // https://github.com/barryvdh/laravel-debugbar/blob/70b89754913fd89fef16d0170a91dbc2a5cd633a/src/LaravelDebugbar.php#L893
    // TODO : renommer en injectDebugBarCodeIntoResponse() ou injectIntoResponse ou injectDebugbarIntoResponse
    private function handleHtml(ResponseInterface $response): ResponseInterface
    {
        $html = (string) $response->getBody();
        $renderer = $this->debugbar->getJavascriptRenderer();

        $code = $renderer->renderHead();
        $html = self::injectHtml($html, $code, '</head>');

        $html = self::injectHtml($html, $renderer->render(), '</body>');

        $body = $this->streamFactory->createStream();
        $body->write($html);

        return $response
            ->withBody($body)
            ->withoutHeader('Content-Length');
    }

    /**
     * Inject html code before a tag.
     */
    private static function injectHtml(string $html, string $code, string $before): string
    {
        $pos = strripos($html, $before);

        if ($pos === false) {
            return $html.$code;
        }

        return substr($html, 0, $pos).$code.substr($html, $pos);
    }
}
