<?php

namespace Charcoal\Tests\Translator;

use ReflectionClass;

// From 'symfony/translation'
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\ArrayLoader;

// From 'charcoal-translator'
use Charcoal\Translator\Factory\TranslationFactory;
use Charcoal\Translator\LocalesManager;
use Charcoal\Translator\Translation;
use Charcoal\Translator\Translator;
use Charcoal\Tests\AbstractTestCase;
use Charcoal\Tests\Translator\Mock\StringClass;

/**
 *
 */
class TranslatorTest extends AbstractTestCase
{
    /**
     * The 'symfony/config' cache factory to ignore.
     *
     * @const string
     */
    const SYMFONY_CACHE_PATH = 'vendor/symfony/config/ConfigCacheFactory.php';

    /**
     * Tested Class.
     *
     * @var Translator
     */
    private $obj;

    /**
     * The language manager.
     *
     * @var LocalesManager
     */
    private $localesManager;

    /**
     * Set up the test.
     *
     * @return void
     */
    public function setUp()
    {
        $manager = $this->localesManager();
        $factory = new TranslationFactory([
            'manager' => $manager,
        ]);

        $this->obj = new Translator([
            'locale'              => 'en',
            'cache_dir'           => null,
            'debug'               => false,
            'manager'             => $manager,
            'translation_factory' => $factory,
        ]);

        $this->obj->addLoader('array', new ArrayLoader());
    }

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        $path = realpath(__DIR__.'/../../../'.static::SYMFONY_CACHE_PATH);
        if ($path !== false) {
            rename($path, $path.'.txt');
        }
    }

    /**
     * @return void
     */
    public static function tearDownAfterClass()
    {
        $path = realpath(__DIR__.'/../../../'.static::SYMFONY_CACHE_PATH.'.txt');
        if ($path !== false) {
            rename($path, str_replace('.php.txt', '.php', $path));
        }
    }

    /**
     * @return LocalesManager
     */
    private function localesManager()
    {
        if ($this->localesManager === null) {
            $this->localesManager = new LocalesManager([
                'locales' => [
                    'en' => [
                        'locale' => 'en_US.UTF8',
                    ],
                    'fr' => [
                        'locale' => 'fr_FR.UTF8',
                    ],
                ],
                'default_language'   => 'en',
                'fallback_languages' => [ 'en' ],
            ]);
        }

        return $this->localesManager;
    }

    /**
     * @return void
     */
    public function testConstructorWithMessageFormatter()
    {
        $manager    = $this->localesManager();
        $formatter1 = new MessageFormatter();
        $formatter2 = new MessageFormatter();
        $factory    = new TranslationFactory([
            'manager'           => $manager,
            'message_formatter' => $formatter1,
        ]);

        $translator = new Translator([
            'locale'              => 'en',
            'cache_dir'           => null,
            'debug'               => false,
            'manager'             => $manager,
            'translation_factory' => $factory,
            'message_formatter'   => $formatter2,
        ]);

        $this->assertNotSame($formatter1, $this->callMethod($translator, 'getFormatter'));
        $this->assertSame($formatter2, $this->callMethod($translator, 'getFormatter'));
    }

    /**
     * @return void
     */
    public function testConstructorWithoutMessageFormatter()
    {
        $manager    = $this->localesManager();
        $formatter1 = new MessageFormatter();
        $factory    = new TranslationFactory([
            'manager'           => $manager,
            'message_formatter' => $formatter1,
        ]);

        $translator = new Translator([
            'locale'              => 'en',
            'cache_dir'           => null,
            'debug'               => false,
            'manager'             => $manager,
            'translation_factory' => $factory,
            'message_formatter'   => null,
        ]);

        $this->assertSame($formatter1, $this->callMethod($translator, 'getFormatter'));
    }

    /**
     * @return void
     */
    public function testAvailableDomains()
    {
        $domains = $this->obj->availableDomains();
        $this->assertInternalType('array', $domains);
        $this->assertEquals([ 'messages' ], $domains);
    }

    /**
     * @return void
     */
    public function testTranslation()
    {
        $ret = $this->obj->translation('Hello!');
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('Hello!', (string)$ret);

        $translation = clone($ret);
        $ret = $this->obj->translation($translation);
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('Hello!', (string)$ret);

        $ret = $this->obj->translation([
            'en' => 'Hello!',
            'fr' => 'Bonjour!',
        ]);
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('Hello!', (string)$ret);
    }

    /**
     * @dataProvider invalidTransTests
     *
     * @param  mixed $val The message ID.
     * @return void
     */
    public function testTranslationInvalidValuesReturnNull($val)
    {
        $this->assertNull($this->obj->translation($val));
    }

    /**
     * @dataProvider validTransTests
     *
     * @param  string $expected    The expected translation.
     * @param  string $id          The message ID.
     * @param  string $translation The translation of $id.
     * @param  string $parameters  An array of parameters for the message.
     * @param  string $locale      The locale to use.
     * @param  string $domain      The domain for the message.
     * @return void
     */
    public function testTranslate($expected, $id, $translation, $parameters, $locale, $domain)
    {
        if (!($id instanceof Translation || is_array($id)) && $locale) {
            $this->obj->addResource('array', [ (string)$id => $translation ], $locale, $domain);
        }

        $this->assertEquals($expected, $this->obj->translate($id, $parameters, $domain, $locale));
    }

    /**
     * @dataProvider invalidTransTests
     *
     * @param  mixed $val The message ID.
     * @return void
     */
    public function testTranslateInvalidValuesReturnEmptyString($val)
    {
        $this->assertEquals('', $this->obj->translate($val));
    }

    /**
     * @return void
     */
    public function testTranslationChoice()
    {
        $ret = $this->obj->translationChoice('There is one apple|There is %count% apples', 2);
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('There is 2 apples', (string)$ret);

        $translation = clone($ret);
        $ret = $this->obj->translationChoice($translation, 2);
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('There is 2 apples', (string)$ret);

        $ret = $this->obj->translationChoice([
            'en' => 'There is one apple|There is %count% apples',
            'fr' => 'Il y a %count% pomme|Il y a %count% pommes',
        ], 1);
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('There is one apple', (string)$ret);
    }

    /**
     * @dataProvider invalidTransTests
     *
     * @param  mixed $val The message ID.
     * @return void
     */
    public function testTranslationChoiceInvalidValuesReturnNull($val)
    {
        $this->assertNull($this->obj->translationChoice($val, 1));
    }

    /**
     * @dataProvider validTransChoiceTests
     *
     * @param  string  $expected    The expected translation.
     * @param  string  $id          The message ID.
     * @param  string  $translation The translation of $id.
     * @param  integer $number      The number to use to find the indice of the message.
     * @param  string  $parameters  An array of parameters for the message.
     * @param  string  $locale      The locale to use.
     * @param  string  $domain      The domain for the message.
     * @return void
     */
    public function testTranslateChoice($expected, $id, $translation, $number, $parameters, $locale, $domain)
    {
        if (!($id instanceof Translation || is_array($id)) && $locale) {
            $this->obj->addResource('array', [ (string)$id => $translation ], $locale, $domain);
        }

        $this->assertEquals($expected, $this->obj->translateChoice($id, $number, $parameters, $domain, $locale));
    }

    /**
     * @return void
     */
    public function testSetLocaleSetLocalesManagerCurrentLanguage()
    {
        $this->obj->setLocale('fr');
        $this->assertEquals('fr', $this->localesManager()->currentLocale());
    }

    /**
     * @return void
     */
    public function testLocales()
    {
        $this->assertArrayHasKey('en', $this->obj->locales());
        $this->assertArrayHasKey('fr', $this->obj->locales());
        $this->assertArrayNotHasKey('jp', $this->obj->locales());
    }

    /**
     * @return void
     */
    public function testAvailableLocales()
    {
        $this->assertEquals([ 'en', 'fr' ], $this->obj->availableLocales());
    }

    /**
     * @return void
     */
    public function testHasTranslation()
    {
        $data = [
            'en' => [
                'hello'   => 'Hello!',
                'goodbye' => 'Goodbye!',
                'super'   => 'Super!',
            ],
            'fr' => [
                'hello'   => 'Bonjour!',
                'goodbye' => 'Au revoir!',
                'great'   => 'Génial!',
            ],
            'es' => [
                'hello'   => '¡Hola!',
                'goodbye' => '¡Adiós!',
                'great'   => '¡Estupendo!',
                'super'   => '¡Súper!',
            ],
        ];

        foreach ($data as $locale => $messages) {
            $this->obj->addResource('array', $messages, $locale, 'messages');
        }

        $this->obj->setFallbackLocales([ 'es' ]);

        $this->assertTrue($this->obj->hasTrans('hello'));
        $this->assertTrue($this->obj->hasTrans('great', 'messages', 'en'));
        $this->assertFalse($this->obj->hasTrans('missing'));

        $this->assertTrue($this->obj->transExists('hello'));
        $this->assertFalse($this->obj->transExists('great', 'messages', 'en'));
        $this->assertFalse($this->obj->transExists('missing'));
    }

    /**
     * @link https://github.com/symfony/translation/blob/v3.2.3/Tests/TranslatorTest.php
     *
     * @return array
     */
    public function validTransTests()
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            [ 'Charcoal est super !', 'Charcoal is great!', 'Charcoal est super !', [], 'fr', '' ],
            [ 'Charcoal est awesome !', 'Charcoal is %what%!', 'Charcoal est %what% !', [ '%what%' => 'awesome' ], 'fr', '' ],
            [ 'Charcoal is great!', [ 'en' => 'Charcoal is great!', 'fr' => 'Charcoal est super !'], 'Charcoal est super !', [], null, '' ],
            [ 'Charcoal est super !', new Translation([ 'en' => 'Charcoal is great!', 'fr' => 'Charcoal est super !'], $this->localesManager()), 'Charcoal est super !', [], 'fr', '' ],
            [ 'Charcoal est super !', new StringClass('Charcoal is great!'), 'Charcoal est super !', [], 'fr', '' ],
        ];
        // phpcs:enable
    }

    /**
     * @return array
     */
    public function invalidTransTests()
    {
        return [
            [ null ],
            [ 0 ],
            [ 1 ],
            [ true ],
            [ false ],
            [ [] ],
            [ [ 'foo', 'bar' ] ],
            [ [ [ ] ] ],
            [ '' ],
        ];
    }

    /**
     * @link https://github.com/symfony/translation/blob/v3.2.3/Tests/TranslatorTest.php
     *
     * @return array
     */
    public function validTransChoiceTests()
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            [ 'Il y a 0 pomme', '{0} There are no appless|{1} There is one apple|]1,Inf] There is %count% apples', '[0,1] Il y a %count% pomme|]1,Inf] Il y a %count% pommes', 0, [], 'fr', '' ],
            [ 'Il y a 1 pomme', '{0} There are no appless|{1} There is one apple|]1,Inf] There is %count% apples', '[0,1] Il y a %count% pomme|]1,Inf] Il y a %count% pommes', 1, [], 'fr', '' ],
            [ 'Il y a 10 pommes', '{0} There are no appless|{1} There is one apple|]1,Inf] There is %count% apples', '[0,1] Il y a %count% pomme|]1,Inf] Il y a %count% pommes', 10, [], 'fr', '' ],

            [ 'Il y a 0 pomme', 'There is one apple|There is %count% apples', 'Il y a %count% pomme|Il y a %count% pommes', 0, [], 'fr', '' ],
            [ 'Il y a 1 pomme', 'There is one apple|There is %count% apples', 'Il y a %count% pomme|Il y a %count% pommes', 1, [], 'fr', '' ],
            [ 'Il y a 10 pommes', 'There is one apple|There is %count% apples', 'Il y a %count% pomme|Il y a %count% pommes', 10, [], 'fr', '' ],

            [ 'Il y a 0 pomme', 'one: There is one apple|more: There is %count% apples', 'one: Il y a %count% pomme|more: Il y a %count% pommes', 0, [], 'fr', '' ],
            [ 'Il y a 1 pomme', 'one: There is one apple|more: There is %count% apples', 'one: Il y a %count% pomme|more: Il y a %count% pommes', 1, [], 'fr', '' ],
            [ 'Il y a 10 pommes', 'one: There is one apple|more: There is %count% apples', 'one: Il y a %count% pomme|more: Il y a %count% pommes', 10, [], 'fr', '' ],

            [ 'Il n\'y a aucune pomme', '{0} There are no apples|one: There is one apple|more: There is %count% apples', '{0} Il n\'y a aucune pomme|one: Il y a %count% pomme|more: Il y a %count% pommes', 0, [], 'fr', '' ],
            [ 'Il y a 1 pomme', '{0} There are no apples|one: There is one apple|more: There is %count% apples', '{0} Il n\'y a aucune pomme|one: Il y a %count% pomme|more: Il y a %count% pommes', 1, [], 'fr', '' ],
            [ 'Il y a 10 pommes', '{0} There are no apples|one: There is one apple|more: There is %count% apples', '{0} Il n\'y a aucune pomme|one: Il y a %count% pomme|more: Il y a %count% pommes', 10, [], 'fr', '' ],

            [ 'There are no appless', [ 'en' => '{0} There are no appless|{1} There is one apple|]1,Inf] There is %count% apples', 'fr' => '[0,1] Il y a %count% pomme|]1,Inf] Il y a %count% pommes' ], '[0,1] Il y a %count% pomme|]1,Inf] Il y a %count% pommes', 0, [], null, '' ],
            [ 'Il y a 0 pomme', new Translation([ 'en' => '{0} There are no appless|{1} There is one apple|]1,Inf] There is %count% apples', 'fr' => '[0,1] Il y a %count% pomme|]1,Inf] Il y a %count% pommes' ], $this->localesManager()), '[0,1] Il y a %count% pomme|]1,Inf] Il y a %count% pommes', 0, [], 'fr', '' ],

            [ 'Il y a 0 pomme', new StringClass('{0} There are no appless|{1} There is one apple|]1,Inf] There is %count% apples'), '[0,1] Il y a %count% pomme|]1,Inf] Il y a %count% pommes', 0, [], 'fr', '' ],

            // Override %count% with a custom value
            [ 'Il y a quelques pommes', 'one: There is one apple|more: There are %count% apples', 'one: Il y a %count% pomme|more: Il y a %count% pommes', 2, [ '%count%' => 'quelques' ], 'fr', '' ],
        ];
        // phpcs:enable
    }
}
