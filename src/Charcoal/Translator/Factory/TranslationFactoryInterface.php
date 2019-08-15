<?php

namespace Charcoal\Translator\Factory;

// From 'charcoal-translator'
use Charcoal\Translator\Translation;

/**
 * Translation factory interface.
 */
interface TranslationFactoryInterface
{
    /**
     * Create a new Translation object from a (mixed) message.
     *
     * @param  mixed $value      A string or language-map.
     * @param  array $parameters An array of parameters for the message.
     * @return Translation|null  A new Translation object or NULL if the value is not acceptable.
     */
    public function createTranslationFormatted($value, array $parameters = []);

    /**
     * Create a new Translation object from a (mixed) message by choosing a translation according to a number.
     *
     * @param  mixed   $value      A string or language-map.
     * @param  integer $number     The number to use to find the indice of the message.
     * @param  array   $parameters An array of parameters for the message.
     * @return Translation|null A new Translation object or NULL if the value is not acceptable.
     */
    public function createTranslationChoice($value, $number, array $parameters = []);

    /**
     * Create a new Translation object that is empty or from a (mixed) message.
     *
     * @param  mixed $value A string or language-map.
     * @return Translation A new Translation object.
     */
    public function createTranslation($value = null);
}
