<?php

namespace Charcoal\Translator\Testing;

use ReflectionMethod;

// From PHPUnit
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 *
 */
abstract class AbstractTestCase extends BaseTestCase
{
    /**
     * Gets a {@see ReflectionMethod} for a class method.
     *
     * The method will be made accessible in the process.
     *
     * @param  mixed  $class The class name or object that contains the method.
     * @param  string $name  The method name to reflect.
     * @return ReflectionMethod
     */
    final public function getMethod($class, $name)
    {
        $reflected = new ReflectionMethod($class, $name);
        $reflected->setAccessible(true);
        return $reflected;
    }

    /**
     * Invoke the requested method, via the Reflection API.
     *
     * @param  object $object  The object that contains the method.
     * @param  string $name    The method name to invoke.
     * @param  mixed  ...$args The parameters to be passed to the function.
     * @return mixed Returns the method result.
     */
    final public function callMethod($object, $name, ...$args)
    {
        return $this->getMethod($object, $name)->invoke($object, ...$args);
    }

    /**
     * Invoke the requested method with arguments, via the Reflection API.
     *
     * @param  object  $object The object that contains the method.
     * @param  string  $name   The method name to invoke.
     * @param  mixed[] $args   The parameters to be passed to the function.
     * @return mixed Returns the method result.
     */
    final public function callMethodArgs($object, $name, array $args = [])
    {
        return $this->getMethod($object, $name)->invokeArgs($object, $args);
    }
}
