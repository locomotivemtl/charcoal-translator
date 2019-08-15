<?php

namespace Charcoal\Translator\Factory;

use RuntimeException;

// From 'charcoal-translator'
use Charcoal\Translator\Factory\TranslationFactoryInterface;

/**
 * Provides translation factory features.
 */
trait TranslationFactoryAwareTrait
{
    /**
     * Store the factory instance.
     *
     * @var TranslationFactoryInterface
     */
    private $translationFactory;

    /**
     * Set a translation factory.
     *
     * @param  TranslationFactoryInterface $factory The factory to create translations.
     * @return void
     */
    protected function setTranslationFactory(TranslationFactoryInterface $factory)
    {
        $this->translationFactory = $factory;
    }

    /**
     * Retrieve the translation factory.
     *
     * @throws RuntimeException If the translation factory is missing.
     * @return TranslationFactoryInterface
     */
    public function getTranslationFactory()
    {
        if (!isset($this->translationFactory)) {
            throw new RuntimeException(sprintf(
                'Translation Factory is not defined for [%s]',
                get_class($this)
            ));
        }

        return $this->translationFactory;
    }
}
