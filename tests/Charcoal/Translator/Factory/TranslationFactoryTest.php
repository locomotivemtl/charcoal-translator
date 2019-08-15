<?php

namespace Charcoal\Tests\Translator\Factory;

use ReflectionClass;

// From 'symfony/translation'
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\ArrayLoader;

// From 'charcoal-translator'
use Charcoal\Translator\Factory\TranslationFactory;
use Charcoal\Translator\Factory\TranslationFactoryInterface;
use Charcoal\Translator\LocalesManager;
use Charcoal\Translator\Translation;
use Charcoal\Tests\AbstractTestCase;
use Charcoal\Tests\Translator\Mock\StringClass;

/**
 *
 */
class TranslationFactoryTest extends AbstractTestCase
{
    /**
     * Tested Class.
     *
     * @var TranslationFactory
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
        $this->obj = new TranslationFactory([
            'manager' => $this->localesManager(),
        ]);
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
        $formatter = new MessageFormatter();
        $factory   = new TranslationFactory([
            'manager'           => $this->localesManager(),
            'message_formatter' => $formatter,
        ]);

        $this->assertSame($formatter, $factory->getFormatter());
    }

    /**
     * @return void
     */
    public function testConstructorWithoutMessageFormatter()
    {
        $factory = new TranslationFactory([
            'manager'           => $this->localesManager(),
            'message_formatter' => null,
        ]);

        $this->assertInstanceOf(MessageFormatter::class, $factory->getFormatter());
    }

    /**
     * @return void
     */
    public function testCreateTranslationFormatted()
    {
        $ret = $this->obj->createTranslationFormatted('Hello!');
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('Hello!', (string)$ret);

        $translation = clone($ret);
        $ret = $this->obj->createTranslationFormatted($translation);
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('Hello!', (string)$ret);

        $ret = $this->obj->createTranslationFormatted([
            'en' => 'Hello!',
            'fr' => 'Bonjour!',
        ]);
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('Hello!', (string)$ret);

        $ret = $this->obj->createTranslationFormatted([
            'en' => 'Charcoal is %what%!',
            'fr' => 'Charcoal est %what% !',
        ], [
            '%what%' => 'xyzzy',
        ]);
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('Charcoal is xyzzy!', $ret['en']);
        $this->assertEquals('Charcoal est xyzzy !', $ret['fr']);
    }

    /**
     * @dataProvider invalidTransTests
     *
     * @param  mixed $val The message ID.
     * @return void
     */
    public function testCreateTranslationInvalidValuesReturnNull($val)
    {
        $this->assertNull($this->obj->createTranslationFormatted($val));
    }

    /**
     * @return void
     */
    public function testCreateTranslationChoice()
    {
        $ret = $this->obj->createTranslationChoice('There is one apple|There is %count% apples', 2);
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('There is 2 apples', (string)$ret);

        $translation = clone($ret);
        $ret = $this->obj->createTranslationChoice($translation, 2);
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('There is 2 apples', (string)$ret);

        $ret = $this->obj->createTranslationChoice([
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
    public function testCreateTranslationChoiceInvalidValuesReturnNull($val)
    {
        $this->assertNull($this->obj->createTranslationChoice($val, 1));
    }

    /**
     * @return void
     */
    public function testCreateTranslation()
    {
        $ret = $this->obj->createTranslation();
        $this->assertInstanceOf(Translation::class, $ret);
        $this->assertEquals('', (string)$ret);
    }

    /**
     * @expectedException RuntimeException
     *
     * @return void
     */
    public function testCreateTranslationWithInvalidTranslationClass()
    {
        $this->obj->setTranslationClass('\\Missing\\LanguageMap');
        $this->obj->createTranslation();
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testSetTranslationClass()
    {
        $ret = $this->obj->setTranslationClass('\\Missing\\LanguageMap');
        $this->assertSame($ret, $this->obj);
        $this->assertEquals('\\Missing\\LanguageMap', $this->obj->getTranslationClass());

        $this->obj->setTranslationClass(5);
    }

    /**
     * @return void
     */
    public function testInvalidArrayTranslation()
    {
        $method = $this->getMethod($this->obj, 'isValidTranslation');
        $method->setAccessible(true);

        $this->assertFalse($method->invokeArgs($this->obj, [ [ 0 => 'Hello!' ] ]));
        $this->assertFalse($method->invokeArgs($this->obj, [ [ 'hello' => 0 ] ]));
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
}
