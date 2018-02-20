<?php

namespace Charcoal\Tests\Translator;

use InvalidArgumentException;

// From `charcoal-translator`
use Charcoal\Translator\Testing\AbstractTestCase;
use Charcoal\Translator\LocalesManager;

/**
 *
 */
class LocalesManagerTest extends AbstractTestCase
{
    /**
     * Tested Class.
     *
     * @var LocalesManager
     */
    private $obj;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->obj = new LocalesManager([
            'locales' => [
                'foo' => [],
                'bar' => [],
                'baz' => [ 'active' => false ]
            ],
            'fallback_languages' => [ 'foo', 'bar' ]
        ]);
    }

    /**
     * @return void
     */
    public function testConstructorWithDefaultLanguage()
    {
        $this->obj = new LocalesManager([
            'locales' => [
                'foo' => [],
                'bar' => [],
                'baz' => [ 'active' => false ]
            ],
            'default_language' => 'bar'
        ]);
        $this->assertEquals('bar', $this->obj->currentLocale());
        $this->assertEquals('bar', $this->obj->defaultLocale());
    }

    /**
     * @return void
     */
    public function testConstructorDefaultLanguageWithInvalidType()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $obj = new LocalesManager([
            'locales' => [
                'foo' => []
            ],
            'default_language' => false
        ]);
    }

    /**
     * @return void
     */
    public function testConstructorDefaultLanguageWithInvalidLocale()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $obj = new LocalesManager([
            'locales' => [
                'foo' => []
            ],
            'default_language' => 'bar'
        ]);
    }

    /**
     * @return void
     */
    public function testConstructorWithoutActiveLocales()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $obj = new LocalesManager([
            'locales' => []
        ]);
    }

    /**
     * @return void
     */
    public function testLocales()
    {
        $locales = $this->obj->locales();
        $this->assertArrayHasKey('foo', $locales);
        $this->assertArrayHasKey('bar', $locales);

        // Also assert that inactive locales are skipped
        $this->assertArrayNotHasKey('baz', $locales);
    }

    /**
     * @return void
     */
    public function testAvailableLocales()
    {
        $this->assertEquals([ 'foo', 'bar' ], $this->obj->availableLocales());
    }

    /**
     * @return void
     */
    public function testSetCurrentLocale()
    {
        $this->assertEquals('foo', $this->obj->currentLocale());

        $this->obj->setCurrentLocale('bar');
        $this->assertEquals('bar', $this->obj->currentLocale());

        $this->obj->setCurrentLocale(null);
        $this->assertEquals('foo', $this->obj->currentLocale());
    }

    /**
     * @return void
     */
    public function testSetCurrentLocaleWithInvalidType()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->obj->setCurrentLocale(false);
    }

    /**
     * @return void
     */
    public function testSetCurrentLocaleWithInvalidLocale()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->obj->setCurrentLocale('qux');
    }
}
