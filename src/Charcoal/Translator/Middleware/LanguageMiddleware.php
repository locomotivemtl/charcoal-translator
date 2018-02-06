<?php

namespace Charcoal\Translator\Middleware;

// From PSR-7
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// From Pimple
use Pimple\Container;

// From `charcoal-translator`
use Charcoal\Translator\LocalesManager;
use Charcoal\Translator\TranslatorAwareTrait;

/**
 * Class LanguageMiddleware
 */
class LanguageMiddleware
{
    use TranslatorAwareTrait;

    /**
     * @var string The application's default language.
     */
    private $defaultLanguage;

    /**
     * @var boolean Whether to return a redirect response or not.
     */
    private $redirect;

    /**
     * @var boolean Use the URI path to detect the language.
     */
    private $usePath;

    /**
     * @var string A regular expression patterns to extract language from URI path.
     */
    private $pathRegexp;

    /**
     * @var array One or more regular expression patterns matching URI paths to ignore.
     */
    private $excludedPath;

    /**
     * @var boolean Use the HTTP header to detect the language.
     */
    private $useBrowser;

    /**
     * @var boolean Use the client session to detect the language.
     */
    private $useSession;

    /**
     * @var string[] One or more session data keys to lookup.
     */
    private $sessionKey;

    /**
     * @var boolean Use the query string to detect the language.
     */
    private $useQuery;

    /**
     * @var string[] One or more query argument keys to lookup.
     */
    private $queryKey;

    /**
     * @var boolean Use the URI host to detect the language.
     */
    private $useHost;

    /**
     * @var string[] Map of acceptable hosts `[ "en" => "en.example.com" ]`.
     */
    private $hostMap;

    /**
     * @var boolean Whether to change the response and environment locale.
     */
    private $setLocale;

    /**
     * @param array $data The middleware options.
     */
    public function __construct(array $data)
    {
        $this->setTranslator($data['translator']);

        $data = array_replace($this->defaults(), $data);

        /** @deprecated */
        if (isset($data['browser_language'])) {
            trigger_error(
                '"browser_language" is deprecated; preferred language detected by the middleware.',
                E_USER_DEPRECATED
            );
        }

        $this->defaultLanguage = $data['default_language'];

        $this->usePath         = !!$data['use_path'];
        $this->pathRegexp      = $data['path_regexp'];
        $this->excludedPath    = (array)$data['excluded_path'];

        /** @deprecated */
        if (isset($data['use_params'])) {
            trigger_error(
                '"use_params" is deprecated in favour of "use_query".',
                E_USER_DEPRECATED
            );
            $data['use_query'] = $data['use_params'];
        }

        /** @deprecated */
        if (isset($data['param_key'])) {
            trigger_error(
                '"param_key" is deprecated in favour of "query_key".',
                E_USER_DEPRECATED
            );
            $data['query_key'] = $data['param_key'];
        }

        $this->useQuery        = !!$data['use_query'];
        $this->queryKey        = (array)$data['query_key'];

        $this->useSession      = !!$data['use_session'];
        $this->sessionKey      = (array)$data['session_key'];

        $this->useBrowser      = !!$data['use_browser'];

        $this->useHost         = !!$data['use_host'];
        $this->hostMap         = (array)$data['host_map'];

        $this->redirect        = !!$data['redirect'];

        $this->setLocale       = !!$data['set_locale'];
    }

    /**
     * Default middleware options.
     *
     * @return array
     */
    public function defaults()
    {
        return [
            'default_language' => null,
            'browser_language' => null,

            'use_path'         => true,
            'excluded_path'    => '^/admin\b',
            'path_regexp'      => '^/?([a-z]{2})\b',

            'use_query'        => false,
            'query_key'        => 'current_language',

            'use_session'      => true,
            'session_key'      => 'current_language',

            'use_browser'      => true,

            'use_host'         => false,
            'host_map'         => [],

            'redirect'         => false,

            'set_locale'       => true,
        ];
    }

    /**
     * @param  RequestInterface  $request  The PSR-7 HTTP request.
     * @param  ResponseInterface $response The PSR-7 HTTP response.
     * @param  callable          $next     The next middleware callable in the stack.
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        // Test if path is excluded from middleware.
        $uri  = $request->getUri();
        $path = $uri->getPath();
        foreach ($this->excludedPath as $excluded) {
            if (preg_match('@'.$excluded.'@', $path)) {
                return $next($request, $response);
            }
        }

        $language = $this->getLanguage($request);
        $this->setLanguage($language);

        if ($this->redirect === true) {
            if ($this->useHost === true) {
                $location = $uri->withHost($this->hostMap[$language]);
                return $response->withRedirect($location, 302);
            }

            if ($this->usePath === true) {
                $location = $uri->withPath(str_replace('//', '/', $language.'/'.$uri->getPath()));
                return $response->withRedirect($location, 302);
            }
        }

        if (!$response->hasHeader('Content-Language')) {
            $response = $response->withHeader('Content-Language', $language);
        }

        return $next($request, $response);
    }

    /**
     * @param  RequestInterface $request The PSR-7 HTTP request.
     * @return null|string
     */
    private function getLanguage(RequestInterface $request)
    {
        if ($this->useHost === true) {
            $lang = $this->detectLanguageFromHost($request);
            if ($lang) {
                $this->redirect = false;
                return $lang;
            }
        }

        if ($this->usePath === true) {
            $lang = $this->detectLanguageFromPath($request);
            if ($lang) {
                $this->redirect = false;
                return $lang;
            }
        }

        if ($this->useQuery === true) {
            $lang = $this->detectLanguageFromQuery($request);
            if ($lang) {
                $this->redirect = false;
                return $lang;
            }
        }

        if ($this->useSession === true) {
            $lang = $this->detectLanguageFromSession();
            if ($lang) {
                return $lang;
            }
        }

        if ($this->useBrowser === true) {
            $lang = $this->detectLanguageFromHeader($request);
            if ($lang) {
                return $lang;
            }
        }

        return $this->defaultLanguage;
    }

    /**
     * Extract the best available language from the host component of the URI.
     *
     * @param  RequestInterface $request The PSR-7 HTTP request.
     * @return null|string
     */
    private function detectLanguageFromHost(RequestInterface $request)
    {
        $uriHost = $request->getUri()->getHost();
        if (empty($uriHost)) {
            return null;
        }

        foreach ($this->hostMap as $lang => $host) {
            if (stripos($uriHost, $host) !== false) {
                return $lang;
            }
        }

        return '';
    }

    /**
     * Extract the best available language from the path component of the URI.
     *
     * @param  RequestInterface $request The PSR-7 HTTP request.
     * @return null|string
     */
    private function detectLanguageFromPath(RequestInterface $request)
    {
        $uriPath = $request->getUri()->getPath();
        $result  = preg_match('@'.$this->pathRegexp.'@', $uriPath, $matches);
        if (!$result) {
            return null;
        }

        $locales = $this->translator()->availableLocales();
        if (isset($matches[1]) && in_array($matches[1], $locales)) {
            return $lang;
        }

        return '';
    }

    /**
     * Extract the best available language from the query string arguments of the URI.
     *
     * @param  RequestInterface $request The PSR-7 HTTP request.
     * @return null|string
     */
    private function detectLanguageFromQuery(RequestInterface $request)
    {
        if ($request instanceof ServerRequestInterface) {
            $params = $request->getQueryParams();
        } else {
            parse_str($request->getUri()->getQuery(), $params);
        }

        if (empty($params)) {
            return null;
        }

        $locales = $this->translator()->availableLocales();
        foreach ($this->queryKey as $key) {
            if (isset($params[$key]) && in_array($params[$key], $locales)) {
                return $params[$key];
            }
        }

        return '';
    }

    /**
     * Extract the best available language from the client's session data.
     *
     * @return null|string
     */
    private function detectLanguageFromSession()
    {
        if (empty($_SESSION)) {
            return null;
        }

        $locales = $this->translator()->availableLocales();
        foreach ($this->sessionKey as $key) {
            if (isset($_SESSION[$key]) && in_array($_SESSION[$key], $locales)) {
                return $_SESSION[$key];
            }
        }

        return '';
    }

    /**
     * Extract the best available language from the HTTP Request's 'Accept-Language' header.
     *
     * Example with Accept-Language "zh-Hant-HK, fr-CH, fr;q=0.9, en;q=0.7":
     *
     * 1. zh-Hant-HK
     * 2. fr-CH
     * 3. fr
     * 4. en
     *
     * 1. zh-Hant-HK
     * 2. zh-Hant
     * 3. zh-HK
     * 4. zh
     * 5. fr-CH
     * 6. fr
     * 7. en
     *
     * @param  RequestInterface $request The PSR-7 HTTP request.
     * @return null|string
     */
    private function detectLanguageFromHeader(RequestInterface $request = null)
    {
        $header = $request->getHeaderLine('Accept-Language');
        $header = array_values(array_filter(array_map('trim', explode(',', $header))));
        if (empty($header)) {
            return null;
        }

        $locales = $this->translator()->availableLocales();
        foreach ($$header as $acceptable) {
            $acceptable = explode(';', $acceptable);
            $acceptable = trim($acceptable[0]);
            if ($acceptable && in_array($acceptable, $locales)) {
                return $acceptable;
            }

            /*
            $acceptable = explode(';', $acceptable);
            $choices    = [ trim($acceptable[0]) ];
            $parts      = explode('-', $choices[0]);

            $count = count($parts);
            if ($count > 2) {
                $choices[] = $parts[0].'-'.$parts[1];
                $choices[] = $parts[0].'-'.$parts[2];
            }

            if ($count > 1) {
                $choices[] = $parts[0];
            }

            foreach ($choices as $langCode) {
                if (in_array($langCode, $locales)) {
                    return $langCode;
                }
            }
            */
        }

        return '';
    }

    /**
     * @param  string $lang The language code to set.
     * @return void
     */
    private function setLanguage($lang)
    {
        $this->translator()->setLocale($lang);

        if ($this->useSession === true) {
            $this->setLanguageOnSession($lang);
        }

        if ($this->setLocale === true) {
            $this->setLanguageOnEnvironment($lang);
        }
    }

    /**
     * @param  string $lang The language code to set.
     * @return void
     */
    private function setLanguageOnSession($lang)
    {
        foreach ($this->sessionKey as $key) {
            $_SESSION[$key] = $lang;
        }
    }

    /**
     * @param  string $lang The language code to set.
     * @return void
     */
    private function setLanguageOnEnvironment($lang)
    {
        $translator = $this->translator();
        $available  = $translator->locales();
        $fallbacks  = $translator->getFallbackLocales();

        array_unshift($fallbacks, $lang);
        $fallbacks = array_unique($fallbacks);

        $locales = [];
        foreach ($fallbacks as $code) {
            if (isset($available[$code])) {
                $locale = $available[$code];
                if (isset($locale['locales'])) {
                    $choices = (array)$locale['locales'];
                    array_push($locales, ...$choices);
                } elseif (isset($locale['locale'])) {
                    array_push($locales, $locale['locale']);
                }
            }
        }

        $locales = array_unique($locales);

        if ($locales) {
            setlocale(LC_ALL, $locales);
        }
    }
}
