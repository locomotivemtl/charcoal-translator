<?php

namespace Charcoal\Tests\Translator;

use InvalidArgumentException;

// From `charcoal-translator`
use Charcoal\Translator\Testing\AbstractTestCase;
use Charcoal\Translator\TranslatorConfig;

/**
 *
 */
class TranslatorConfigTest extends AbstractTestCase
{
    /**
     * Tested Class.
     *
     * @var TranslatorConfig
     */
    private $obj;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->obj = new TranslatorConfig();
    }

    /**
     * @return void
     */
    public function testDefaultsArrayAccess()
    {
        $this->assertEquals([ 'csv' ], $this->obj['loaders']);
        $this->assertContains('translations/', $this->obj['paths']);
        $this->assertFalse($this->obj['debug']);
        $this->assertEquals('translator_cache', $this->obj['cache_dir']);
    }

    /**
     * @return void
     */
    public function testSetLoaders()
    {
        $this->assertEquals([ 'csv' ], $this->obj->loaders());

        $ret = $this->obj->setLoaders([ 'csv', 'xliff' ]);
        $this->assertSame($ret, $this->obj);
        $this->assertEquals([ 'csv', 'xliff' ], $this->obj->loaders());

        $this->obj['loaders'] = [ 'php' ];
        $this->assertEquals([ 'php' ], $this->obj['loaders']);
    }

    /**
     * @return void
     */
    public function testSetUnavailableLoaders()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->obj['loaders'] = [ 'foo' ];
    }

    /**
     * @return void
     */
    public function testSetInvalidPaths()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->obj['paths'] = [ false ];
    }

    /**
     * @return void
     */
    public function testSetInvalidDomainTranslations()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->obj['translations'] = [ false ];
    }

    /**
     * @return void
     */
    public function testSetInvalidMessageTranslations()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->obj['translations'] = [ [ false ] ];
    }

    /**
     * @return void
     */
    public function testSetDebug()
    {
        $this->assertFalse($this->obj->debug());
        $ret = $this->obj->setDebug(true);
        $this->assertSame($ret, $this->obj);
        $this->assertTrue($this->obj->debug());

        $this->obj['debug'] = 0;
        $this->assertFalse($this->obj['debug']);
    }

    /**
     * @return void
     */
    public function testSetCacheDir()
    {
        $this->assertEquals('translator_cache', $this->obj->cacheDir());
        $ret = $this->obj->setCacheDir('foo');
        $this->assertSame($ret, $this->obj);
        $this->assertEquals('foo', $this->obj->cacheDir());

        $this->obj['cache_dir'] = 'bar';
        $this->assertEquals('bar', $this->obj['cache_dir']);
    }

    /**
     * @return void
     */
    public function testSetInvalidCacheDir()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->obj['cache_dir'] = false;
    }
}
