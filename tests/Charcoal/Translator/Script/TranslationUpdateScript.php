<?php

namespace Charcoal\Tests\Translation\Script;

use ReflectionClass;

// From PSR-7
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// From Pimple
use Pimple\Container;

// From 'charcoal-translator'
use Charcoal\Translator\Script\TranslationUpdateScript;
use Charcoal\Tests\Translator\ContainerProvider;
use Charcoal\Tests\AbstractTestCase;

/**
 * @coversDefaultClass Charcoal\Translator\Script\TranslationUpdateScript
 */
class TranslationUpdateScriptTest extends AbstractTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }



    // CLImate Helpers
    // =========================================================================

    /**
     * @param  string  $content The expected content.
     * @param  integer $times   The number of times this expectation should occur.
     * @return mixed
     */
    protected function shouldWrite($content, $times = 1)
    {
        return $this->output->shouldReceive('write')->times($times)->with($content);
    }

    /**
     * @param  integer $times The number of times this expectation should occur.
     * @return void
     */
    protected function shouldHavePersisted($times = 1)
    {
        $this->shouldStartPersisting($times);
        $this->shouldStopPersisting($times);
    }

    /**
     * @param  integer $times The number of times this expectation should occur.
     * @return void
     */
    protected function shouldStartPersisting($times = 1)
    {
        $this->output->shouldReceive('persist')->withNoArgs()->times($times)->andReturn($this->output);
    }

    /**
     * @param  integer $times The number of times this expectation should occur.
     * @return void
     */
    protected function shouldStopPersisting($times = 1)
    {
        $this->output->shouldReceive('persist')->with(false)->times($times)->andReturn($this->output);
    }
}
