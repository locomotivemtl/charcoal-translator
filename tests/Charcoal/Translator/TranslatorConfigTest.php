<?php

namespace Charcoal\Tests\Translator;

use InvalidArgumentException;

// From 'charcoal-translator'
use Charcoal\Translator\TranslatorConfig;
use Charcoal\Tests\AbstractTestCase;

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
     * Set up the test.
     *
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
        $this->assertEquals('../cache/translator', $this->obj['cache_dir']);
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
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testSetUnavailableLoaders()
    {
        $this->obj['loaders'] = [ 'foo' ];
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testSetInvalidPaths()
    {
        $this->obj['paths'] = [ false ];
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testSetInvalidDomainTranslations()
    {
        $this->obj['translations'] = [ false ];
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testSetInvalidMessageTranslations()
    {
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
        $this->assertEquals('../cache/translator', $this->obj->cacheDir());
        $ret = $this->obj->setCacheDir('foo');
        $this->assertSame($ret, $this->obj);
        $this->assertEquals('foo', $this->obj->cacheDir());

        $this->obj['cache_dir'] = 'bar';
        $this->assertEquals('bar', $this->obj['cache_dir']);
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testSetInvalidCacheDir()
    {
        $this->obj['cache_dir'] = false;
    }
}
