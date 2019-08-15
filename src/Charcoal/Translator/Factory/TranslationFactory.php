<?php

namespace Charcoal\Translator\Factory;

use InvalidArgumentException;
use RuntimeException;

// From 'symfony/translation'
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Formatter\MessageFormatterInterface;

// From 'charcoal-translator'
use Charcoal\Translator\LocalesManager;
use Charcoal\Translator\Translation;

/**
 * Class that creates translation objects.
 *
 * The translation factory is an alternative to the Translator for creating Translation objects
 * without passing messages through the catalogues where a message might be accidentally translated.
 *
 * A note about Translation objects:
 *
 * - If a string is given, that value is assigned to the current locale.
 * - If an array is given, it is interpreted as a language-map whose keys are language codes
 * and whose values are localized strings.
 */
class TranslationFactory implements TranslationFactoryInterface
{
    /**
     * @var LocalesManager
     */
    private $manager;

    /**
     * @var MessageFormatterInterface
     */
    private $formatter;

    /**
     * The class to use for language-maps.
     *
     * @var string
     */
    protected $translationClass = Translation::class;

    /**
     * @param array $data Factory dependencies.
     */
    public function __construct(array $data)
    {
        $this->setManager($data['manager']);

        // Ensure Charcoal has control of the message formatter.
        if (!isset($data['message_formatter'])) {
            $data['message_formatter'] = new MessageFormatter();
        }
        $this->setFormatter($data['message_formatter']);
    }

    /**
     * Create a new Translation object from a (mixed) message.
     *
     * @param  mixed $value      A string or language-map.
     * @param  array $parameters An array of parameters for the message.
     * @return Translation|null  A new Translation object or NULL if the value is not acceptable.
     */
    public function createTranslationFormatted($value, array $parameters = [])
    {
        if ($this->isValidTranslation($value) === false) {
            return null;
        }

        $translation = $this->createTranslation($value);

        if (!empty($parameters)) {
            $translation->each(function ($message, $locale) use ($parameters) {
                return $this->getFormatter()->format($message, $locale, $parameters);
            });
        }

        return $translation;
    }

    /**
     * Create a new Translation object from a (mixed) message by choosing a translation according to a number.
     *
     * @param  mixed   $value      A string or language-map.
     * @param  integer $number     The number to use to find the indice of the message.
     * @param  array   $parameters An array of parameters for the message.
     * @return Translation|null A new Translation object or NULL if the value is not acceptable.
     */
    public function createTranslationChoice($value, $number, array $parameters = [])
    {
        if ($this->isValidTranslation($value) === false) {
            return null;
        }

        $translation = $this->createTranslation($value);
        $translation->each(function ($message, $locale) use ($number, $parameters) {
            return $this->getFormatter()->choiceFormat($message, $number, $locale, $parameters);
        });

        return $translation;
    }

    /**
     * Create a new Translation object that is empty or from a (mixed) message.
     *
     * @param  mixed $value A string or language-map.
     * @throws RuntimeException If the class name is not a string.
     * @return Translation A new Translation object.
     */
    public function createTranslation($value = null)
    {
        $transClass = $this->getTranslationClass();

        if (class_exists($transClass)) {
            return new $transClass($value, $this->getManager());
        }

        throw new RuntimeException(sprintf(
            'Translation class (%s) could not be instantiated',
            $transClass
        ));
    }

    /**
     * Determine if the value is translatable.
     *
     * @param  mixed $value The value to be checked.
     * @return boolean
     */
    public function isValidTranslation($value)
    {
        if (empty($value) && !is_numeric($value)) {
            return false;
        }

        if (is_string($value)) {
            return !empty(trim($value));
        }

        if ($value instanceof Translation) {
            return true;
        }

        if (is_array($value)) {
            return !!array_filter(
                $value,
                function ($v, $k) {
                    if (is_string($k) && strlen($k) > 0) {
                        if (is_string($v) && strlen($v) > 0) {
                            return true;
                        }
                    }

                    return false;
                },
                ARRAY_FILTER_USE_BOTH
            );
        }
        return false;
    }

    /**
     * Set the locales manager.
     *
     * @param  LocalesManager $manager The locales manager.
     * @return void
     */
    private function setManager(LocalesManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Retrieve the locales manager.
     *
     * @return LocalesManager
     */
    protected function getManager()
    {
        return $this->manager;
    }

    /**
     * Set the message formatter.
     *
     * The {@see SymfonyTranslator} keeps the message formatter private (as of 3.3.2),
     * thus we must explicitly require it in this class to guarantee access.
     *
     * @param  MessageFormatterInterface $formatter The formatter.
     * @return void
     */
    private function setFormatter(MessageFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Retrieve the message formatter.
     *
     * @return MessageFormatterInterface
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Set the class name of the Translation object.
     *
     * @param  string $className The class name of the translation.
     * @throws InvalidArgumentException If the class name is not a string.
     * @return self
     */
    public function setTranslationClass($className)
    {
        if (!is_string($className)) {
            throw new InvalidArgumentException(
                'Translation class name must be a string'
            );
        }

        $this->translationClass = $className;
        return $this;
    }

    /**
     * Retrieve the class name of the Translation object.
     *
     * @return string
     */
    public function getTranslationClass()
    {
        return $this->translationClass;
    }
}
