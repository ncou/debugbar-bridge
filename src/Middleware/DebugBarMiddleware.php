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

use DebugBar\StandardDebugBar;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;


use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\TimeDataCollector;
use Chiron\DebugBar\Collector\ChironCollector;
use Chiron\DebugBar\Collector\FilesCollector;

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
    /** @var ResponseFactoryInterface */
    private $responseFactory;
    /** @var StreamFactoryInterface */
    private $streamFactory;

/*
    public function __construct(DebugBar $debugbar,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory)
    {*/
    public function __construct(StandardDebugBar $debugbar,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory)
    {
        $this->debugbar = $debugbar;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;





        $this->debugbar->addCollector(new ChironCollector());


        //$this->debugbar->addCollector(new PhpInfoCollector());
        //$this->debugbar->addCollector(new MessagesCollector());

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
        $renderer = $this->debugbar->getJavascriptRenderer('/nano5/public/debugbar');

        //Asset response
        $path = $request->getUri()->getPath();
        $baseUrl = $renderer->getBaseUrl();

        //die(var_dump($baseUrl));
        //die(var_dump($path));

        // TODO : faire un Str::startsWith au lieu de strpos !!!!
        if (strpos($path, $baseUrl) === 0) {
            $file = $renderer->getBasePath().substr($path, strlen($baseUrl));

            if (is_file($file)) {

                //die(var_dump($file));

                $response = $this->responseFactory->createResponse();
                $response->getBody()->write(file_get_contents($file));
                // TODO : utiliser la classe de MimeType pour récupérer la valeur à utiliser dans le header. https://github.com/guzzle/psr7/blob/c0dcda9f54d145bd4d062a6d15f54931a67732f9/src/MimeType.php#L116
                $extension = pathinfo($file, PATHINFO_EXTENSION);

                if (isset(self::$mimes[$extension])) {
                    return $response->withHeader('Content-Type', self::$mimes[$extension]);
                }

                return $response; //@codeCoverageIgnore
            }
        }






        $response = $handler->handle($request);

        $isAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';

        // TODO : utiliser la méthode StatusCode::isRedirect()
        //Redirection response
        if (in_array($response->getStatusCode(), [302, 301])) {
            return $this->handleRedirect($response);
        }

        //Html response
        if (stripos($response->getHeaderLine('Content-Type'), 'text/html') === 0) {
            return $this->handleHtml($response, $isAjax);
        }

        return $response;
    }

    /**
     * Handle redirection responses
     */
    private function handleRedirect(ResponseInterface $response): ResponseInterface
    {
        if ($this->debugbar->isDataPersisted() || session_status() === PHP_SESSION_ACTIVE) {
            $this->debugbar->stackData();
        }

        return $response;
    }

    /**
     * Handle html responses
     */
    private function handleHtml(ResponseInterface $response, bool $isAjax): ResponseInterface
    {
        $html = (string) $response->getBody();
        $renderer = $this->debugbar->getJavascriptRenderer('/nano5/public/debugbar');

        $code = $renderer->renderHead();
        $html = self::injectHtml($html, $code, '</head>');

        $html = self::injectHtml($html, $renderer->render(!$isAjax), '</body>');

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
