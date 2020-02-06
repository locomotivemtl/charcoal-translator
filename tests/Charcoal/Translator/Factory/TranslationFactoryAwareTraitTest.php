<?php

namespace Charcoal\Tests\Translator\Factory;

use RuntimeException;
use ReflectionClass;

// From 'charcoal-translator'
use Charcoal\Translator\Factory\TranslationFactoryAwareTrait;
use Charcoal\Translator\Factory\TranslationFactory;
use Charcoal\Tests\AbstractTestCase;

/**
 *
 */
class TranslationFactoryAwareTraitTest extends AbstractTestCase
{
    /**
     * Tested Class.
     *
     * @var TranslationFactoryAwareTrait
     */
    private $obj;

    /**
     * Set up the test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->obj = $this->getMockForTrait(TranslationFactoryAwareTrait::class);
    }

    /**
     * @expectedException RuntimeException
     *
     * @return void
     */
    public function testTranslatorWithoutSettingThrowsException()
    {
        $this->obj->getTranslationFactory();
    }

    /**
     * @return void
     */
    public function testSetTranslator()
    {
        $factory = $this->getMockBuilder(TranslationFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->callMethod($this->obj, 'setTranslationFactory', [ $factory ]);
        $this->assertEquals($factory, $this->obj->getTranslationFactory());
    }
}
