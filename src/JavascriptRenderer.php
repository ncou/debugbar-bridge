<?php

declare(strict_types=1);

namespace Chiron\DebugBar;

use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Http\Config\HttpConfig;
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
use Closure;
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\TimeDataCollector;
use Chiron\Routing\UrlGeneratorInterface;
use DebugBar\JavascriptRenderer as BaseJavascriptRenderer;

//https://github.com/Rareloop/lumberjack-debugbar/blob/master/src/JavaScriptRenderer.php

// TODO : passer la classe en final et virer les protected !!!!
class JavascriptRenderer extends BaseJavascriptRenderer
{
    // Use XHR handler by default, instead of jQuery
    protected $ajaxHandlerBindToJquery = false;
    protected $ajaxHandlerBindToXHR = true;

    /** @var UrlGeneratorInterface */
    private $url = null;

    public function __construct(DebugBar $debugBar, $baseUrl = null, $basePath = null)
    {
        parent::__construct($debugBar, $baseUrl, $basePath);


        $this->cssVendors['fontawesome'] = __DIR__ . '/../resources/vendor/font-awesome.css';

        //$this->cssVendors['fontawesome'] = __DIR__ . '/Resources/vendor/font-awesome/style.css';
        //$this->cssFiles['laravel'] = __DIR__ . '/Resources/lumberjack.css';
        //$this->cssFiles['laravel'] = __DIR__ . '/Resources/laravel-debugbar.css';


/*
        $this->cssFiles['laravel'] = __DIR__ . '/Resources/laravel-debugbar.css';
        $this->cssVendors['fontawesome'] = __DIR__ . '/Resources/vendor/font-awesome/style.css';
        $this->jsFiles['laravel-sql'] = __DIR__ . '/Resources/sqlqueries/widget.js';
        $this->jsFiles['laravel-cache'] = __DIR__ . '/Resources/cache/widget.js';

        $theme = config('debugbar.theme', 'auto');
        switch ($theme) {
            case 'dark':
                $this->cssFiles['laravel-dark'] = __DIR__ . '/Resources/laravel-debugbar-dark-mode.css';
                break;
            case 'auto':
                $this->cssFiles['laravel-dark-0'] = __DIR__ . '/Resources/laravel-debugbar-dark-mode-media-start.css';
                $this->cssFiles['laravel-dark-1'] = __DIR__ . '/Resources/laravel-debugbar-dark-mode.css';
                $this->cssFiles['laravel-dark-2'] = __DIR__ . '/Resources/laravel-debugbar-dark-mode-media-end.css';
        }
        */
    }

    /**
     * Set the URL Generator
     *
     * @param \Chiron\Routing\UrlGeneratorInterface $url
     */
    public function setUrlGenerator(UrlGeneratorInterface $url)
    {
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function renderHead()
    {
        if ($this->url === null) {
            return parent::renderHead();
        }

        $cssRoute = $this->url->relativeUrlFor('debugbar.assets.css', [], ['v' => $this->getModifiedTime('css')]);
        $jsRoute = $this->url->relativeUrlFor('debugbar.assets.js', [], ['v' => $this->getModifiedTime('js')]);

        $html  = "<link rel='stylesheet' type='text/css' property='stylesheet' href='{$cssRoute}'>" . "\n";
        $html .= "<script type='text/javascript' src='{$jsRoute}'></script>" . "\n";

        if ($this->isJqueryNoConflictEnabled()) {
            $html .= '<script type="text/javascript">jQuery.noConflict(true);</script>' . "\n";
        }

        $html .= $this->getInlineHtml();

        return $html;
    }

    protected function getInlineHtml()
    {
        $html = '';

        foreach (['head', 'css', 'js'] as $asset) {
            foreach ($this->getAssets('inline_' . $asset) as $item) {
                $html .= $item . "\n";
            }
        }

        return $html;
    }

    /**
     * Get the last modified time of any assets.
     *
     * @param string $type 'js' or 'css'
     * @return int
     */
    protected function getModifiedTime($type)
    {
        $files = $this->getAssets($type);

        $latest = 0;
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime > $latest) {
                $latest = $mtime;
            }
        }
        return $latest;
    }

    /**
     * Return assets as a string
     *
     * @param string $type 'js' or 'css'
     * @return string
     */
    public function dumpAssetsToString($type)
    {
        $files = $this->getAssets($type);

        $content = '';
        foreach ($files as $file) {
            $content .= file_get_contents($file) . "\n";
        }

        return $content;
    }
}
