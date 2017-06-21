<?php

namespace Charcoal\Translator\Middleware;

// Dependencies from 'PSR-7' (HTTP Messaging)
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// Dependencies from 'pimple/pimple'
use Pimple\Container;

// Dependencies from `charcoal-translator`
use Charcoal\Translator\LocalesManager;
use Charcoal\Translator\TranslatorAwareTrait;

/**
 * Class LanguageMiddleware
 * @package Charcoal\App\Middleware
 */
class LanguageMiddleware
{
    use TranslatorAwareTrait;

    /**
     * @var string
     */
    private $defaultLanguage;

    /**
     * @var string
     */
    private $browserLanguage;

    /**
     * @var array
     */
    private $excludedPath;

    /**
     * @var boolean
     */
    private $usePath;


    /**
     * @var string
     */
    private $pathRegexp;

    /**
     * @var boolean
     */
    private $useBrowser;

    /**
     * @var boolean
     */
    private $useSession;

    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @var boolean
     */
    private $useParams;

    /**
     * @var string
     */
    private $paramKey;


    /**
     * @param array $data The middleware options.
     */
    public function __construct(array $data)
    {
        $this->setTranslator($data['translator']);

        $defaults = [
            'default_language' => null,

            'browser_language' => null,

            'use_path'      => true,
            'included_path' => null,
            'excluded_path' => [
                '~^/admin\b~'
            ],
            'path_regexp'   => '~^/(fr|en|es)\b~',

            'use_params'    => false,
            'param_key'     => 'current_language',

            'use_session'   => true,
            'session_key'   => 'current_language',

            'use_browser'   => true,

            'set_locale'    => true
        ];
        $data = array_replace($defaults, $data);

        $this->defaultLanguage = $data['default_language'];

        $this->browserLanguage = $data['browser_language'];

        $this->usePath = !!$data['use_path'];
        $this->includedPath = $data['included_path'];
        $this->excludedPath = $data['excluded_path'];
        $this->pathRegexp = $data['path_regexp'];

        $this->useParams = !!$data['use_params'];
        $this->paramKey = $data['param_key'];

        $this->useSession = !!$data['use_session'];
        $this->sessionKey = $data['session_key'];

        $this->useBrowser = !!$data['use_browser'];

        $this->setLocale = !!$data['set_locale'];
    }

    /**
     * @param RequestInterface  $request  The PSR-7 HTTP request.
     * @param ResponseInterface $response The PSR-7 HTTP response.
     * @param callable          $next     The next middleware callable in the stack.
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        // Test if path is excluded from middleware.
        $uri = $request->getUri();
        $path = $uri->getPath();
        foreach ($this->excludedPath as $excluded) {
            if (preg_match($excluded, $path)) {
                return $next($request, $response);
            }
        }

        $language = $this->getLanguage($request);
        $this->setLanguage($language);
        return $next($request, $response);
    }

    /**
     * @param RequestInterface $request The PSR-7 HTTP request.
     * @return null|string
     */
    private function getLanguage(RequestInterface $request)
    {
        if ($this->usePath === true) {
            $lang = $this->getLanguageFromPath($request);
            if ($lang) {
                return $lang;
            }
        }

        if ($this->useParams === true) {
            $lang = $this->getLanguageFromParams($request);
            if ($lang) {
                return $lang;
            }
        }

        if ($this->useSession === true) {
            $lang = $this->getLanguageFromSession();
            if ($lang) {
                return $lang;
            }
        }

        if ($this->useBrowser === true) {
            $lang = $this->getLanguageFromBrowser();
            if ($lang) {
                return $lang;
            }
        }

        return $this->defaultLanguage;
    }

    /**
     * @param RequestInterface $request The PSR-7 HTTP request.
     * @return null|string
     */
    private function getLanguageFromPath(RequestInterface $request)
    {
        if (preg_match($this->pathRegexp, $path, $matches)) {
            $lang = $matches[0];
        } else {
            return '';
        }

        if (in_array($lang, $this->translator()->availableLocales())) {
            return $lang;
        } else {
            return '';
        }
    }

    /**
     * @param RequestInterface $request The PSR-7 HTTP request.
     * @return string
     */
    private function getLanguageFromParams(RequestInterface $request)
    {
        $params = $request->getParams();
        $key = $this->paramKey;
        if (isset($params[$key]) && in_array($params[$key], $this->translator()->availableLocales())) {
            $lang = $params[$key];
            return $params[$key];
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    private function getLanguageFromSession()
    {
        $locales = $this->translator()->availableLocales();
        if (isset($_SESSION[$this->sessionKey]) && in_array($_SESSION[$this->sessionKey], $locales)) {
            return $_SESSION[$this->sessionKey];
        } else {
            return '';
        }
    }

    /**
     * @return mixed
     */
    private function getLanguageFromBrowser()
    {
        return $this->browserLanguage;
    }

    /**
     * @param string $lang The language code to set.
     * @return void
     */
    private function setLanguage($lang)
    {
        $this->translator()->setLocale($lang);

        if ($this->useSession === true) {
            $_SESSION[$this->sessionKey] = $this->translator()->getLocale();
        }

        if ($this->setLocale === true) {
            $this->setLocale($lang);
        }
    }

    /**
     * @param string $lang The language code to set.
     * @return void
     */
    private function setLocale($lang)
    {
        $translator = $this->translator();

        $available  = $translator->locales();
        $fallbacks  = $translator->getFallbackLocales();
        array_unshift($fallbacks, $translator->getLocale());
        array_unique($fallbacks);
        $locales = [];
        foreach ($fallbacks as $code) {
            if (isset($available[$code])) {
                $locale = (array)$available[$code]['locale'];
                array_push($locales, ...$locale);
            }
        }
        if ($locales) {
            setlocale(LC_ALL, ...$locales);
        }
    }
}