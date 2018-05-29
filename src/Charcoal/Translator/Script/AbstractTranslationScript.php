<?php

namespace Charcoal\Translator\Script;

// From PSR-7
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// From 'symfony/translation'

// From 'charcoal-admin'
use Charcoal\Admin\AdminScript;

/**
 * Base Translation Script
 */
abstract class AbstractTranslationScript extends AdminScript
{
    /**
     * Supported command arguments.
     *
     * @var array
     */
    protected $args;

    /**
     * Run the script.
     *
     * @param  RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param  ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        unset($request);

        try {
            $this->execute();
        } catch (Exception $e) {
            $this->climate()->error($e->getMessage());
        }

        return $response;
    }

    /**
     * Alias of {@see \Charcoal\Translator\Translator::availableLocales()}.
     *
     * @return string[] The available language codes.
     */
    public function getLocales()
    {
        return $this->translator()->availableLocales();
    }


    // CLI Arguments
    // =========================================================================

    /**
     * Retrieve the script's primary arguments.
     *
     * @return array
     */
    abstract protected function providedArguments();

    /**
     * Retrieve the script's supported arguments.
     *
     * @return array
     */
    final public function defaultArguments()
    {
        if ($this->args === null) {
            $this->args = array_merge(parent::defaultArguments(), $this->providedArguments());
        }

        return $args;
    }
}
