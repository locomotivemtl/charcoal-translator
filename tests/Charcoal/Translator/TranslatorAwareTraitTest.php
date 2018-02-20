<?php

namespace Charcoal\Tests\Translator;

use Exception;

// From `charcoal-translator`
use Charcoal\Translator\Testing\AbstractTestCase;
use Charcoal\Translator\TranslatorAwareTrait;
use Charcoal\Translator\Translator;

/**
 *
 */
class TranslatorAwareTraitTest extends AbstractTestCase
{
    /**
     * Tested Class.
     *
     * @var TranslatorAwareTrait
     */
    private $obj;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->obj = $this->getMockForTrait(TranslatorAwareTrait::class);
    }

    /**
     * @return void
     */
    public function testTranslatorWithoutSettingThrowsException()
    {
        $this->setExpectedException(Exception::class);
        $this->callMethod($this->obj, 'translator');
    }

    /**
     * @return void
     */
    public function testSetTranslator()
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->callMethod($this->obj, 'setTranslator', [$translator]);
        $this->assertEquals($translator, $this->callMethod($this->obj, 'translator'));
    }
}
