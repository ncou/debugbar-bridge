<?php

declare(strict_types=1);

namespace Chiron\DebugBar\Controller;

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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

use Chiron\Http\Message\MimeType;

use Chiron\DebugBar\DebugBar;

final class AssetController
{
    /** @var DebugBar */
    private $debugbar;
    /** @var ResponseFactoryInterface */
    private $responseFactory;

    public function __construct(DebugBar $debugbar, ResponseFactoryInterface $responseFactory)
    {
        $this->debugbar = $debugbar;
        $this->responseFactory = $responseFactory;
    }
    /**
     * Return the javascript for the Debugbar
     *
     * @return ResponseInterface
     */
    public function js(): ResponseInterface
    {
        return $this->createAssetResponse('js');
    }

    /**
     * Return the stylesheets for the Debugbar
     *
     * @return ResponseInterface
     */
    public function css(): ResponseInterface
    {
        return $this->createAssetResponse('css');
    }

    private function createAssetResponse(string $extension): ResponseInterface
    {
        $content = $this->debugbar->getJavascriptRenderer()->dumpAssetsToString($extension);
        $mime = MimeType::fromExtension($extension);

        // TODO : utiliser plutot un middleware cache qu'on ajoute à la route debugbar pour cacher la réponse pendant 1 année. => https://github.com/barryvdh/laravel-httpcache/blob/master/src/Middleware/SetTtl.php
        $response = $this->responseFactory->createResponse();
        $response = $response->withHeader('Content-Type', $mime);
        $response = $response->withHeader('Cache-Control', 'max-age=31536000, s-maxage=31536000, public');
        $response = $response->withHeader('Expires', 'Wed, 21 Oct 2025 07:28:00 GMT');

        $response->getBody()->write($content);

        return $response;
    }

    /**
     * Cache the response 1 year (31536000 sec)
     */
    // TODO : exemple dans code igniter pour mettre le cache : https://github.com/codeigniter4/CodeIgniter4/blob/6af5370fcf6534ec3d262e0cb0f2fbc5a9954e0b/system/HTTP/ResponseTrait.php#L383
    // https://github.com/mikecao/flight/blob/master/flight/net/Response.php#L199
    //https://github.com/symfony/http-foundation/blob/31f25d99b329a1461f42bcef8505b54926a30be6/Response.php#L739
    /*
    protected function cacheResponse(Response $response)
    {
        $response->setSharedMaxAge(31536000);
        $response->setMaxAge(31536000);
        $response->setExpires(new \DateTime('+1 year'));

        return $response;
    }*/
}
