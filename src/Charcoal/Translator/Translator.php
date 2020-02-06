<?php

namespace Charcoal\Translator;

use RuntimeException;

// From 'symfony/translation'
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Formatter\MessageFormatterInterface;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator as SymfonyTranslator;

// From 'charcoal-translator'
use Charcoal\Translator\Factory\TranslationFactoryAwareTrait;
use Charcoal\Translator\LocalesManager;
use Charcoal\Translator\Translation;

/**
 * Charcoal Translator.
 *
 * Extends the Symfony translator to allow returned values in a "Translation" oject,
 * containing localizations for all locales.
 *
 * A note about the Translator's behaviour on Translation objects:
 *
 * - Once a Translation object is instantiated, any value assigned to the current locale
 *   will serve as the catalogue's "message id" and will be translated for any missing locales.
 */
class Translator extends SymfonyTranslator
{
    use TranslationFactoryAwareTrait;

    /**
     * The locales manager.
     *
     * @var LocalesManager
     */
    private $manager;

    /**
     * The message formatter.
     *
     * @var MessageFormatterInterface
     */
    private $formatter;

    /**
     * The loaded domains.
     *
     * @var string[]
     */
    private $domains = [ 'messages' ];

    /**
     * @param array $data Translator dependencies.
     */
    public function __construct(array $data)
    {
        $this->setManager($data['manager']);
        $this->setTranslationFactory($data['translation_factory']);

        // Ensure Charcoal has control of the message formatter.
        if (!isset($data['message_formatter'])) {
            $data['message_formatter'] = $data['translation_factory']->getFormatter();
        }
        $this->setFormatter($data['message_formatter']);

        $defaults = [
            'locale'    => $this->getManager()->currentLocale(),
            'cache_dir' => null,
            'debug'     => false,
        ];
        $data = array_merge($defaults, $data);

        // If 'symfony/config' is not installed, DON'T use cache.
        if (!class_exists('\Symfony\Component\Config\ConfigCacheFactory', false)) {
            $data['cache_dir'] = null;
        }

        parent::__construct(
            $data['locale'],
            $data['message_formatter'],
            $data['cache_dir'],
            $data['debug']
        );
    }

    /**
     * Adds a resource.
     *
     * @see    SymfonyTranslator::addResource() Keep track of the translation domains.
     * @param  string      $format   The name of the loader (@see addLoader()).
     * @param  mixed       $resource The resource name.
     * @param  string      $locale   The locale.
     * @param  string|null $domain   The domain.
     * @return void
     */
    public function addResource($format, $resource, $locale, $domain = null)
    {
        if (null !== $domain) {
            $this->domains[] = $domain;
        }

        parent::addResource($format, $resource, $locale, $domain);
    }

    /**
     * Retrieve the loaded domains.
     *
     * @return string[]
     */
    public function availableDomains()
    {
        return $this->domains;
    }

    /**
     * Retrieve a new Translation object from a (mixed) message.
     *
     * The $val will be {@see SymfonyTranslator::trans() translated}.
     *
     * @param  mixed       $val        A string or language-map.
     * @param  array       $parameters An array of parameters for the message.
     * @param  string|null $domain     The domain for the message or NULL to use the default.
     * @return Translation|null A new Translation object or NULL if the value is not translatable.
     */
    public function translation($val, array $parameters = [], $domain = null)
    {
        $factory = $this->getTranslationFactory();

        if ($factory->isValidTranslation($val) === false) {
            return null;
        }

        $translation = $factory->createTranslation($val);
        $localized   = (string)$translation;
        $isMessageId = is_string($val);
        foreach ($this->availableLocales() as $lang) {
            if (!isset($translation[$lang]) || ($isMessageId && $translation[$lang] === $val)) {
                $translation[$lang] = $this->trans($localized, $parameters, $domain, $lang);
            } else {
                $translation[$lang] = $this->formatter->format($translation[$lang], $lang, $parameters);
            }
        }

        return $translation;
    }

    /**
     * Translates the given (mixed) message.
     *
     * The $val will be {@see SymfonyTranslator::trans() translated}.
     *
     * @param  mixed       $val        A string or language-map.
     * @param  array       $parameters An array of parameters for the message.
     * @param  string|null $domain     The domain for the message or NULL to use the default.
     * @param  string|null $locale     The locale or NULL to use the default.
     * @return string The translated string.
     */
    public function translate($val, array $parameters = [], $domain = null, $locale = null)
    {
        if ($locale === null) {
            $locale = $this->getLocale();
        }

        if ($val instanceof Translation) {
            return $this->formatter->format($val[$locale], $locale, $parameters);
        }

        if (is_object($val) && method_exists($val, '__toString')) {
            $val = (string)$val;
        }

        if (is_string($val)) {
            return $this->trans($val, $parameters, $domain, $locale);
        }

        $translation = $this->translation($val, $parameters, $domain);
        return $translation[$locale];
    }

    /**
     * Retrieve a new Translation object from a (mixed) message by choosing a translation according to a number.
     *
     * The $val will be {@see SymfonyTranslator::transChoice() translated}.
     *
     * @param  mixed       $val        A string or language-map.
     * @param  integer     $number     The number to use to find the indice of the message.
     * @param  array       $parameters An array of parameters for the message.
     * @param  string|null $domain     The domain for the message or NULL to use the default.
     * @return Translation|null A new Translation object or NULL if the value is not translatable.
     */
    public function translationChoice($val, $number, array $parameters = [], $domain = null)
    {
        $factory = $this->getTranslationFactory();

        if ($factory->isValidTranslation($val) === false) {
            return null;
        }

        $translation = $factory->createTranslation($val);
        $localized   = (string)$translation;
        $isMessageId = is_string($val);
        foreach ($this->availableLocales() as $lang) {
            if (!isset($translation[$lang]) || ($isMessageId && $translation[$lang] === $val)) {
                $translation[$lang] = $this->transChoice($localized, $number, $parameters, $domain, $lang);
            } else {
                $translation[$lang] = $this->formatter->choiceFormat($translation[$lang], $number, $lang, $parameters);
            }
        }

        return $translation;
    }

    /**
     * Translates the given (mixed) choice message by choosing a translation according to a number.
     *
     * The $val will be {@see SymfonyTranslator::trans() translated}.
     *
     * @param  mixed       $val        A string or language-map.
     * @param  integer     $number     The number to use to find the indice of the message.
     * @param  array       $parameters An array of parameters for the message.
     * @param  string|null $domain     The domain for the message or NULL to use the default.
     * @param  string|null $locale     The locale or NULL to use the default.
     * @return string The translated string.
     */
    public function translateChoice($val, $number, array $parameters = [], $domain = null, $locale = null)
    {
        if ($locale === null) {
            $locale = $this->getLocale();
        }

        if ($val instanceof Translation) {
            return $this->formatter->choiceFormat($val[$locale], $number, $locale, $parameters);
        }

        if (is_object($val) && method_exists($val, '__toString')) {
            $val = (string)$val;
        }

        if (is_string($val)) {
            return $this->transChoice($val, $number, $parameters, $domain, $locale);
        }

        $translation = $this->translationChoice($val, $number, $parameters, $domain);
        return $translation[$locale];
    }

    /**
     * Retrieve the available locales information.
     *
     * @return array
     */
    public function locales()
    {
        return $this->getManager()->locales();
    }

    /**
     * Retrieve the available locales (language codes).
     *
     * @return string[]
     */
    public function availableLocales()
    {
        return $this->getManager()->availableLocales();
    }

    /**
     * Sets the current locale.
     *
     * @see    SymfonyTranslator::setLocale() Ensure that the method also changes the locales manager's language.
     * @param  string $locale The locale.
     * @return void
     */
    public function setLocale($locale)
    {
        parent::setLocale($locale);

        $this->getManager()->setCurrentLocale($locale);
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
    protected function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Checks if a message has a translation.
     *
     * @param  string      $id     The message id.
     * @param  string|null $domain The domain for the message or NULL to use the default.
     * @param  string|null $locale The locale or NULL to use the default.
     * @return boolean TRUE if the message has a translation, FALSE otherwise.
     */
    public function hasTrans($id, $domain = null, $locale = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        return $this->getCatalogue($locale)->has($id, $domain);
    }

    /**
     * Checks if a message has a translation (it does not take into account the fallback mechanism).
     *
     * @param  string      $id     The message id.
     * @param  string|null $domain The domain for the message or NULL to use the default.
     * @param  string|null $locale The locale or NULL to use the default.
     * @return boolean TRUE if the message has a translation, FALSE otherwise.
     */
    public function transExists($id, $domain = null, $locale = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        return $this->getCatalogue($locale)->defines($id, $domain);
    }
}
