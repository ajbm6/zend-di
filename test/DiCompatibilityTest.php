<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Di;

use BadMethodCallException;
use Exception;
use PHPUnit_Framework_Error;
use SplStack;
use Zend\Di\Di;

class DiCompatibilityTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @dataProvider providesSimpleClasses
     * @param string $class
     */
    public function testDiSimple($class)
    {
        $di = new Di();

        $bareObject = new $class;

        $diObject = $di->get($class);

        $this->assertInstanceOf($class, $bareObject, 'Test instantiate simple');
        $this->assertInstanceOf($class, $diObject, 'Test $di->get');
    }

    /**
     * provides known classes invokable without parameters
     *
     * @return array
     */
    public function providesSimpleClasses()
    {
        return [
            [Di::class],
            [SplStack::class],
            [TestAsset\BasicClass::class],
        ];
    }

    /**
     *
     * error: Missing argument 1 for $class::__construct()
     * @dataProvider providesClassWithConstructionParameters
     * @param string $class
     */
    public function testRaiseErrorMissingConstructorRequiredParameter($class)
    {
        if (version_compare(PHP_VERSION, '7', '>=')) {
            $this->markTestSkipped('Errors have changed to E_FATAL, no longer allowing test to run');
        }

        $phpunit = $this;
        $caught  = false;
        set_error_handler(function ($errno, $errstr) use ($phpunit, &$caught) {
            if ($errno === E_WARNING && 0 !== strpos($errstr, 'Missing argument')) {
                $phpunit->fail('Unexpected error caught during instantiation');
                return false;
            }

            throw new BadMethodCallException('TRAPPED');
        }, E_WARNING|E_RECOVERABLE_ERROR);
        try {
            $bareObject = new $class;
        } catch (Exception $e) {
            if ($e instanceof PHPUnit_Framework_Error
                || ($e instanceof BadMethodCallException && $e->getMessage() === 'TRAPPED')
            ) {
                $caught = true;
            }
        }
        $this->assertTrue($caught);
    }

    /**
     *
     * @dataProvider providesClassWithConstructionParameters
     * @expectedException \Zend\Di\Exception\MissingPropertyException
     * @param string $class
     */
    public function testWillThrowExceptionMissingConstructorRequiredParameterWithDi($class)
    {
        $di = new Di();
        $diObject = $di->get($class);
        $this->assertInstanceOf($class, $diObject, 'Test $di->get');
    }

    /**
     *
     * @dataProvider providesClassWithConstructionParameters
     * @param string $class
     */
    public function testCanCreateInstanceWithConstructorRequiredParameter($class, $args)
    {
        $reflection = new \ReflectionClass($class);
        $bareObject = $reflection->newInstanceArgs($args);
        $this->assertInstanceOf($class, $bareObject, 'Test instantiate with constructor required parameters');
    }

    /**
     * @dataProvider providesClassWithConstructionParameters
     * @param string $class
     */
    public function testCanCreateInstanceWithConstructorRequiredParameterWithDi($class, $args)
    {
        $di = new Di();
        $diObject = $di->get($class, $args);
        $this->assertInstanceOf($class, $diObject, 'Test $di->get with constructor required paramters');
    }

    public function providesClassWithConstructionParameters()
    {
        return [
            [TestAsset\BasicClassWithParam::class, ['foo' => 'bar']],
            [TestAsset\ConstructorInjection\X::class, ['one' => 1, 'two' => 2]],
        ];
    }
}
