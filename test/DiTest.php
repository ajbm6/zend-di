<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Di;

use Zend\Di\Config;
use Zend\Di\Definition;
use Zend\Di\DefinitionList;
use Zend\Di\Di;
use Zend\Di\InstanceManager;

class DiTest extends \PHPUnit_Framework_TestCase
{
    public function testDiHasBuiltInImplementations()
    {
        $di = new Di();
        $this->assertInstanceOf('Zend\Di\InstanceManager', $di->instanceManager());

        $definitions = $di->definitions();

        $this->assertInstanceOf('Zend\Di\DefinitionList', $definitions);
        $this->assertInstanceOf('Zend\Di\Definition\RuntimeDefinition', $definitions->top());
    }

    public function testDiConstructorCanTakeDependencies()
    {
        $dl = new DefinitionList([]);
        $im = new InstanceManager();
        $cg = new Config([]);
        $di = new Di($dl, $im, $cg);

        $this->assertSame($dl, $di->definitions());
        $this->assertSame($im, $di->instanceManager());

        $di->setDefinitionList($dl);
        $di->setInstanceManager($im);

        $this->assertSame($dl, $di->definitions());
        $this->assertSame($im, $di->instanceManager());
    }

    public function testGetRetrievesObjectWithMatchingClassDefinition()
    {
        $di = new Di();
        $obj = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj);
    }

    public function testGetRetrievesSameInstanceOnSubsequentCalls()
    {
        $config = new Config([
            'instance' => [
                'ZendTest\Di\TestAsset\BasicClass' => [
                    'shared' => true,
                    ],
                ],
        ]);
        $di = new Di(null, null, $config);
        $obj1 = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $obj2 = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj1);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj2);
        $this->assertSame($obj1, $obj2);
    }

    public function testGetRetrievesDifferentInstanceOnSubsequentCallsIfSharingDisabled()
    {
        $config = new Config([
            'instance' => [
                'ZendTest\Di\TestAsset\BasicClass' => [
                    'shared' => false,
                ],
            ],
        ]);
        $di = new Di(null, null, $config);
        $obj1 = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $obj2 = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj1);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj2);
        $this->assertNotSame($obj1, $obj2);
    }

    public function testGetRetrievesSameSharedInstanceOnUsingInConstructor()
    {
        $config = new Config([
            'instance' => [
                'ZendTest\Di\TestAsset\BasicClass' => [
                    'shared' => true,
                ],
            ],
        ]);
        $di = new Di(null, null, $config);
        $obj1 = $di->get('ZendTest\Di\TestAsset\BasicClassWithParent', ['foo' => 0]);
        $obj2 = $di->get('ZendTest\Di\TestAsset\BasicClassWithParent', ['foo' => 1]);
        $obj3 = $di->get('ZendTest\Di\TestAsset\BasicClassWithParent', ['foo' => 2, 'non_exists' => 1]);
        $objParent1 = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $objParent2 = $di->get('ZendTest\Di\TestAsset\BasicClass', ['foo' => 1]);

        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClassWithParent', $obj1);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClassWithParent', $obj2);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClassWithParent', $obj3);
        $this->assertSame($obj1->parent, $obj2->parent);
        $this->assertSame($obj2->parent, $obj3->parent);
        $this->assertSame($obj3->parent, $objParent1);
        $this->assertSame($obj3->parent, $objParent2);
    }

    public function testGetThrowsExceptionWhenUnknownClassIsUsed()
    {
        $di = new Di();

        $this->setExpectedException('Zend\Di\Exception\ClassNotFoundException', 'could not be located in');
        $obj1 = $di->get('ZendTest\Di\TestAsset\NonExistentClass');
    }

    public function testGetThrowsExceptionWhenMissingParametersAreEncountered()
    {
        $di = new Di();

        $this->setExpectedException('Zend\Di\Exception\MissingPropertyException', 'Missing instance/object for ');
        $obj1 = $di->get('ZendTest\Di\TestAsset\BasicClassWithParam');
    }

    public function testNewInstanceReturnsDifferentInstances()
    {
        $di = new Di();
        $obj1 = $di->newInstance('ZendTest\Di\TestAsset\BasicClass');
        $obj2 = $di->newInstance('ZendTest\Di\TestAsset\BasicClass');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj1);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj2);
        $this->assertNotSame($obj1, $obj2);
    }

    public function testNewInstanceReturnsInstanceThatIsSharedWithGet()
    {
        $di = new Di();
        $obj1 = $di->newInstance('ZendTest\Di\TestAsset\BasicClass');
        $obj2 = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj1);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj2);
        $this->assertSame($obj1, $obj2);
    }

    public function testNewInstanceReturnsInstanceThatIsNotSharedWithGet()
    {
        $di = new Di();
        $obj1 = $di->newInstance('ZendTest\Di\TestAsset\BasicClass', [], false);
        $obj2 = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj1);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj2);
        $this->assertNotSame($obj1, $obj2);
    }

    public function testNewInstanceCanHandleClassesCreatedByCallback()
    {
        $definitionList = new DefinitionList([
            $classdef = new Definition\ClassDefinition('ZendTest\Di\TestAsset\CallbackClasses\A'),
            new Definition\RuntimeDefinition()
        ]);
        $classdef->setInstantiator('ZendTest\Di\TestAsset\CallbackClasses\A::factory');

        $di = new Di($definitionList);
        $a = $di->get('ZendTest\Di\TestAsset\CallbackClasses\A');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\CallbackClasses\A', $a);
    }

    public function testNewInstanceCanHandleComplexCallback()
    {
        $definitionList = new DefinitionList([
            $classdefB = new Definition\ClassDefinition('ZendTest\Di\TestAsset\CallbackClasses\B'),
            $classdefC = new Definition\ClassDefinition('ZendTest\Di\TestAsset\CallbackClasses\C'),
            new Definition\RuntimeDefinition()
        ]);

        $classdefB->setInstantiator('ZendTest\Di\TestAsset\CallbackClasses\B::factory');
        $classdefB->addMethod('factory', true);
        $classdefB->addMethodParameter('factory', 'c', ['type' => 'ZendTest\Di\TestAsset\CallbackClasses\C', 'required' => true]);
        $classdefB->addMethodParameter('factory', 'params', ['type' => 'Array', 'required'=>false]);

        $di = new Di($definitionList);
        $b = $di->get('ZendTest\Di\TestAsset\CallbackClasses\B', ['params'=>['foo' => 'bar']]);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\CallbackClasses\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\CallbackClasses\C', $b->c);
        $this->assertEquals(['foo' => 'bar'], $b->params);
    }


//    public function testCanSetInstantiatorToStaticFactory()
//    {
//        $config = new Config(array(
//            'definition' => array(
//                'class' => array(
//                    'ZendTest\Di\TestAsset\DummyParams' => array(
//                        'instantiator' => array('ZendTest\Di\TestAsset\StaticFactory', 'factory'),
//                    ),
//                    'ZendTest\Di\TestAsset\StaticFactory' => array(
//                        'methods' => array(
//                            'factory' => array(
//                                'struct' => array(
//                                    'type' => 'ZendTest\Di\TestAsset\Struct',
//                                    'required' => true,
//                                ),
//                                'params' => array(
//                                    'required' => true,
//                                ),
//                            ),
//                        ),
//                    ),
//                ),
//            ),
//            'instance' => array(
//                'ZendTest\Di\TestAsset\DummyParams' => array(
//                    'parameters' => array(
//                        'struct' => 'ZendTest\Di\TestAsset\Struct',
//                        'params' => array(
//                            'foo' => 'bar',
//                        ),
//                    ),
//                ),
//                'ZendTest\Di\TestAsset\Struct' => array(
//                    'parameters' => array(
//                        'param1' => 'hello',
//                        'param2' => 'world',
//                    ),
//                ),
//            ),
//        ));
//        $di = new Di();
//        $di->configure($config);
//        $dummyParams = $di->get('ZendTest\Di\TestAsset\DummyParams');
//        $this->assertEquals($dummyParams->params['param1'], 'hello');
//        $this->assertEquals($dummyParams->params['param2'], 'world');
//        $this->assertEquals($dummyParams->params['foo'], 'bar');
//        $this->assertArrayNotHasKey('methods', $di->definitions()->hasMethods('ZendTest\Di\TestAsset\StaticFactory'));
//    }

    /**
     * @group ConstructorInjection
     */
    public function testGetWillResolveConstructorInjectionDependencies()
    {
        $di = new Di();
        $b = $di->get('ZendTest\Di\TestAsset\ConstructorInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\A', $b->a);
    }

    /**
     * @group ConstructorInjection
     */
    public function testGetWillResolveConstructorInjectionDependenciesAndInstanceAreTheSame()
    {
        $di = new Di();
        $b = $di->get('ZendTest\Di\TestAsset\ConstructorInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\A', $b->a);

        $b2 = $di->get('ZendTest\Di\TestAsset\ConstructorInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\B', $b2);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\A', $b2->a);

        $this->assertSame($b, $b2);
        $this->assertSame($b->a, $b2->a);
    }

    /**
     * @group ConstructorInjection
     */
    public function testNewInstanceWillResolveConstructorInjectionDependencies()
    {
        $di = new Di();
        $b = $di->newInstance('ZendTest\Di\TestAsset\ConstructorInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\A', $b->a);
    }

    /**
     * @group ConstructorInjection
     */
    public function testNewInstanceWillResolveConstructorInjectionDependenciesWithProperties()
    {
        $di = new Di();

        $im = $di->instanceManager();
        $im->setParameters('ZendTest\Di\TestAsset\ConstructorInjection\X', ['one' => 1, 'two' => 2]);

        $y = $di->newInstance('ZendTest\Di\TestAsset\ConstructorInjection\Y');
        $this->assertEquals(1, $y->x->one);
        $this->assertEquals(2, $y->x->two);
    }

    /**
     * @group ConstructorInjection
     */
    public function testNewInstanceWillThrowExceptionOnConstructorInjectionDependencyWithMissingParameter()
    {
        $di = new Di();

        $this->setExpectedException('Zend\Di\Exception\MissingPropertyException', 'Missing instance/object for parameter');
        $b = $di->newInstance('ZendTest\Di\TestAsset\ConstructorInjection\X');
    }

    /**
     * @group ConstructorInjection
     */
    public function testNewInstanceWillResolveConstructorInjectionDependenciesWithMoreThan2Dependencies()
    {
        $di = new Di();

        $im = $di->instanceManager();
        $im->setParameters('ZendTest\Di\TestAsset\ConstructorInjection\X', ['one' => 1, 'two' => 2]);

        $z = $di->newInstance('ZendTest\Di\TestAsset\ConstructorInjection\Z');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\Y', $z->y);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\X', $z->y->x);
    }

    /**
     * @group SetterInjection
     */
    public function testGetWillResolveSetterInjectionDependencies()
    {
        $di = new Di();
        // for setter injection, the dependency is not required, thus it must be forced
        $di->instanceManager()->setParameters(
            'ZendTest\Di\TestAsset\SetterInjection\B',
            ['a' => new TestAsset\SetterInjection\A]
        );
        $b = $di->get('ZendTest\Di\TestAsset\SetterInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $b->a);
    }

    /**
     * @group SetterInjection
     */
    public function testGetWillResolveSetterInjectionDependenciesAndInstanceAreTheSame()
    {
        $di = new Di();
        // for setter injection, the dependency is not required, thus it must be forced
        $di->instanceManager()->setParameters(
            'ZendTest\Di\TestAsset\SetterInjection\B',
            ['a' => $a = new TestAsset\SetterInjection\A]
        );

        $b = $di->get('ZendTest\Di\TestAsset\SetterInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $b->a);

        $b2 = $di->get('ZendTest\Di\TestAsset\SetterInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\B', $b2);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $b2->a);

        $this->assertSame($b, $b2);
        $this->assertSame($b->a, $a);
        $this->assertSame($b2->a, $a);
    }

    /**
     * @group SetterInjection
     */
    public function testNewInstanceWillResolveSetterInjectionDependencies()
    {
        $di = new Di();
        // for setter injection, the dependency is not required, thus it must be forced
        $di->instanceManager()->setParameters(
            'ZendTest\Di\TestAsset\SetterInjection\B',
            ['a' => new TestAsset\SetterInjection\A]
        );

        $b = $di->newInstance('ZendTest\Di\TestAsset\SetterInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $b->a);
    }

    /**
     * @todo Setter Injections is not automatic, find a way to test this logically
     *
     * @group SetterInjection
     */
    public function testNewInstanceWillResolveSetterInjectionDependenciesWithProperties()
    {
        $di = new Di();

        $im = $di->instanceManager();
        $im->setParameters('ZendTest\Di\TestAsset\SetterInjection\X', ['one' => 1, 'two' => 2]);

        $x = $di->get('ZendTest\Di\TestAsset\SetterInjection\X');
        $y = $di->newInstance('ZendTest\Di\TestAsset\SetterInjection\Y', ['x' => $x]);

        $this->assertEquals(1, $y->x->one);
        $this->assertEquals(2, $y->x->two);
    }

    /**
     * Test for Circular Dependencies (case 1)
     *
     * A->B, B->A
     * @group CircularDependencyCheck
     */
    public function testNewInstanceThrowsExceptionOnBasicCircularDependency()
    {
        $di = new Di();

        $this->setExpectedException('Zend\Di\Exception\CircularDependencyException');
        $di->newInstance('ZendTest\Di\TestAsset\CircularClasses\A');
    }

    /**
     * Test for Circular Dependencies (case 2)
     *
     * C->D, D->E, E->C
     * @group CircularDependencyCheck
     */
    public function testNewInstanceThrowsExceptionOnThreeLevelCircularDependency()
    {
        $di = new Di();

        $this->setExpectedException(
            'Zend\Di\Exception\CircularDependencyException',
            'Circular dependency detected: ZendTest\Di\TestAsset\CircularClasses\E depends on ZendTest\Di\TestAsset\CircularClasses\C and viceversa'
        );
        $di->newInstance('ZendTest\Di\TestAsset\CircularClasses\C');
    }

    /**
     * Test for Circular Dependencies (case 2)
     *
     * C->D, D->E, E->C
     * @group CircularDependencyCheck
     */
    public function testNewInstanceThrowsExceptionWhenEnteringInMiddleOfCircularDependency()
    {
        $di = new Di();

        $this->setExpectedException(
            'Zend\Di\Exception\CircularDependencyException',
            'Circular dependency detected: ZendTest\Di\TestAsset\CircularClasses\C depends on ZendTest\Di\TestAsset\CircularClasses\D and viceversa'
        );
        $di->newInstance('ZendTest\Di\TestAsset\CircularClasses\D');
    }

    protected function configureNoneCircularDependencyTests()
    {
        $di = new Di();

        $di->instanceManager()->addAlias('YA', 'ZendTest\Di\TestAsset\CircularClasses\Y');
        $di->instanceManager()->addAlias('YB', 'ZendTest\Di\TestAsset\CircularClasses\Y', ['y' => 'YA']);
        $di->instanceManager()->addAlias('YC', 'ZendTest\Di\TestAsset\CircularClasses\Y', ['y' => 'YB']);

        return $di;
    }

    /**
     * Test for correctly identifying no Circular Dependencies (case 1)
     *
     * YC -> YB, YB -> YA
     * @group CircularDependencyCheck
     */
    public function testNoCircularDependencyDetectedIfWeGetIntermediaryClass()
    {
        $di = $this->configureNoneCircularDependencyTests();

        $yb = $di->get('YB');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\CircularClasses\Y', $yb);
        $yc = $di->get('YC');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\CircularClasses\Y', $yc);
    }

    /**
     * Test for correctly identifying no Circular Dependencies (case 2)
     *
     * YC -> YB, YB -> YA
     * @group CircularDependencyCheck
     */
    public function testNoCircularDependencyDetectedIfWeDontGetIntermediaryClass()
    {
        $di = $this->configureNoneCircularDependencyTests();

        $yc = $di->get('YC');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\CircularClasses\Y', $yc);
    }

    /**
     * Test for correctly identifying a Circular Dependency in aliases (case 3)
     *
     * YA -> YB, YB -> YA
     * @group CircularDependencyCheck
     */
    public function testCircularDependencyDetectedInAliases()
    {
        $di = new Di();

        $di->instanceManager()->addAlias('YA', 'ZendTest\Di\TestAsset\CircularClasses\Y', ['y' => 'YC']);
        $di->instanceManager()->addAlias('YB', 'ZendTest\Di\TestAsset\CircularClasses\Y', ['y' => 'YA']);
        $di->instanceManager()->addAlias('YC', 'ZendTest\Di\TestAsset\CircularClasses\Y', ['y' => 'YB']);

        $this->setExpectedException(
            'Zend\Di\Exception\CircularDependencyException',
            'Circular dependency detected: ZendTest\Di\TestAsset\CircularClasses\Y depends on ZendTest\Di\TestAsset\CircularClasses\Y and viceversa (Aliased as YA)'
        );

        $yc = $di->get('YC');
    }

    /**
     * Test for correctly identifying a Circular Dependency with a self referencing alias
     *
     * YA -> YA
     * @group CircularDependencyCheck
     */
    public function testCircularDependencyDetectedInSelfReferencingAlias()
    {
        $di = new Di();

        $di->instanceManager()->addAlias(
            'YA',
            'ZendTest\Di\TestAsset\CircularClasses\Y',
            ['y' => 'YA']
        );

        $this->setExpectedException(
            'Zend\Di\Exception\CircularDependencyException',
            'Circular dependency detected: ZendTest\Di\TestAsset\CircularClasses\Y depends on ZendTest\Di\TestAsset\CircularClasses\Y and viceversa (Aliased as YA)'
        );

        $y = $di->get('YA');
    }

    /**
     * Test for correctly identifying a Circular Dependency with mixture of classes and aliases
     *
     * Y -> YA, YA -> Y
     * @group CircularDependencyCheck
     */
    public function testCircularDependencyDetectedInMixtureOfAliasesAndClasses()
    {
        $di = new Di();

        $di->instanceManager()->addAlias(
            'YA',
            'ZendTest\Di\TestAsset\CircularClasses\Y',
            ['y' => 'ZendTest\Di\TestAsset\CircularClasses\Y']
        );

        $this->setExpectedException(
            'Zend\Di\Exception\CircularDependencyException',
            'Circular dependency detected: ZendTest\Di\TestAsset\CircularClasses\Y depends on ZendTest\Di\TestAsset\CircularClasses\Y and viceversa (Aliased as YA)'
        );

        $y = $di->get('ZendTest\Di\TestAsset\CircularClasses\Y', ['y' => 'YA']);
    }

    /**
     * Fix for PHP bug in is_subclass_of
     *
     * @see https://bugs.php.net/bug.php?id=53727
     */
    public function testNewInstanceWillUsePreferredClassForInterfaceHints()
    {
        $definitionList = new DefinitionList([
            $classdef = new Definition\ClassDefinition('ZendTest\Di\TestAsset\PreferredImplClasses\C'),
            new Definition\RuntimeDefinition()
        ]);
        $classdef->addMethod('setA', Di::METHOD_IS_EAGER);
        $di = new Di($definitionList);

        $di->instanceManager()->addTypePreference(
            'ZendTest\Di\TestAsset\PreferredImplClasses\A',
            'ZendTest\Di\TestAsset\PreferredImplClasses\BofA'
        );

        $c = $di->get('ZendTest\Di\TestAsset\PreferredImplClasses\C');
        $a = $c->a;
        $this->assertInstanceOf('ZendTest\Di\TestAsset\PreferredImplClasses\BofA', $a);
        $d = $di->get('ZendTest\Di\TestAsset\PreferredImplClasses\D');
        $this->assertSame($a, $d->a);
    }

    public function testNewInstanceWillThrowAnClassNotFoundExceptionWhenClassIsAnInterface()
    {
        $definitionArray = [
            'ZendTest\Di\TestAsset\ConstructorInjection\D' => [
                'supertypes' => [],
                'instantiator' => '__construct',
                'methods' => ['__construct' => 3],
                'parameters' => [
                    '__construct' =>
                    [
                        'ZendTest\Di\TestAsset\ConstructorInjection\D::__construct:0' => [
                            0 => 'd',
                            1 => 'ZendTest\Di\TestAsset\DummyInterface',
                            2 => true,
                            3 => null,
                        ],
                    ],
                ],
            ],
            'ZendTest\Di\TestAsset\DummyInterface' => [
                'supertypes' => [],
                'instantiator' => null,
                'methods' => [],
                'parameters' => [],
            ],
        ];
        $definitionList = new DefinitionList([
            new Definition\ArrayDefinition($definitionArray)
        ]);
        $di = new Di($definitionList);

        $this->setExpectedException('Zend\Di\Exception\ClassNotFoundException', 'Cannot instantiate interface');
        $di->get('ZendTest\Di\TestAsset\ConstructorInjection\D');
    }

    public function testMatchPreferredClassWithAwareInterface()
    {
        $di = new Di();

        $di->instanceManager()->addTypePreference(
            'ZendTest\Di\TestAsset\PreferredImplClasses\A',
            'ZendTest\Di\TestAsset\PreferredImplClasses\BofA'
        );

        $e = $di->get('ZendTest\Di\TestAsset\PreferredImplClasses\E');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\PreferredImplClasses\BofA', $e->a);
    }

    public function testWillNotUsePreferredClassForInterfaceHints()
    {
        $di = new Di();

        $di->instanceManager()->addTypePreference(
            'ZendTest\Di\TestAsset\PreferredImplClasses\A',
            'ZendTest\Di\TestAsset\PreferredImplClasses\BofA'
        );

        $c = $di->get('ZendTest\Di\TestAsset\PreferredImplClasses\C');
        $a = $c->a;
        $this->assertNull($a);
        $d = $di->get('ZendTest\Di\TestAsset\PreferredImplClasses\D');
        $this->assertNull($d->a);
    }

    public function testInjectionInstancesCanBeInjectedMultipleTimes()
    {
        $definitionList = new DefinitionList([
            $classdef = new Definition\ClassDefinition('ZendTest\Di\TestAsset\InjectionClasses\A'),
            new Definition\RuntimeDefinition()
        ]);
        $classdef->addMethod('addB');
        $classdef->addMethodParameter('addB', 'b', ['required' => true, 'type' => 'ZendTest\Di\TestAsset\InjectionClasses\B']);

        $di = new Di($definitionList);
        $di->instanceManager()->setInjections(
            'ZendTest\Di\TestAsset\InjectionClasses\A',
            [
                'ZendTest\Di\TestAsset\InjectionClasses\B'
            ]
        );
        $a = $di->newInstance('ZendTest\Di\TestAsset\InjectionClasses\A');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\InjectionClasses\B', $a->bs[0]);

        $di = new Di($definitionList);
        $di->instanceManager()->addAlias('my-b1', 'ZendTest\Di\TestAsset\InjectionClasses\B');
        $di->instanceManager()->addAlias('my-b2', 'ZendTest\Di\TestAsset\InjectionClasses\B');

        $di->instanceManager()->setInjections(
            'ZendTest\Di\TestAsset\InjectionClasses\A',
            [
                'my-b1',
                'my-b2'
            ]
        );
        $a = $di->newInstance('ZendTest\Di\TestAsset\InjectionClasses\A');

        $this->assertInstanceOf('ZendTest\Di\TestAsset\InjectionClasses\B', $a->bs[0]);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\InjectionClasses\B', $a->bs[1]);
        $this->assertNotSame(
            $a->bs[0],
            $a->bs[1]
        );
    }

    public function testInjectionCanHandleDisambiguationViaPositions()
    {
        $definitionList = new DefinitionList([
            $classdef = new Definition\ClassDefinition('ZendTest\Di\TestAsset\InjectionClasses\A'),
            new Definition\RuntimeDefinition()
        ]);
        $classdef->addMethod('injectBOnce');
        $classdef->addMethod('injectBTwice');
        $classdef->addMethodParameter('injectBOnce', 'b', ['required' => true, 'type' => 'ZendTest\Di\TestAsset\InjectionClasses\B']);
        $classdef->addMethodParameter('injectBTwice', 'b', ['required' => true, 'type' => 'ZendTest\Di\TestAsset\InjectionClasses\B']);

        $di = new Di($definitionList);
        $di->instanceManager()->setInjections(
            'ZendTest\Di\TestAsset\InjectionClasses\A',
            [
                'ZendTest\Di\TestAsset\InjectionClasses\A::injectBOnce:0' => new \ZendTest\Di\TestAsset\InjectionClasses\B('once'),
                'ZendTest\Di\TestAsset\InjectionClasses\A::injectBTwice:0' => new \ZendTest\Di\TestAsset\InjectionClasses\B('twice')
            ]
        );
        $a = $di->newInstance('ZendTest\Di\TestAsset\InjectionClasses\A');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\InjectionClasses\B', $a->bs[0]);
        $this->assertEquals('once', $a->bs[0]->id);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\InjectionClasses\B', $a->bs[1]);
        $this->assertEquals('twice', $a->bs[1]->id);
    }

    public function testInjectionCanHandleDisambiguationViaNames()
    {
        $definitionList = new DefinitionList([
            $classdef = new Definition\ClassDefinition('ZendTest\Di\TestAsset\InjectionClasses\A'),
            new Definition\RuntimeDefinition()
        ]);
        $classdef->addMethod('injectBOnce');
        $classdef->addMethod('injectBTwice');
        $classdef->addMethodParameter('injectBOnce', 'b', ['required' => true, 'type' => 'ZendTest\Di\TestAsset\InjectionClasses\B']);
        $classdef->addMethodParameter('injectBTwice', 'b', ['required' => true, 'type' => 'ZendTest\Di\TestAsset\InjectionClasses\B']);

        $di = new Di($definitionList);
        $di->instanceManager()->setInjections(
            'ZendTest\Di\TestAsset\InjectionClasses\A',
            [
                'ZendTest\Di\TestAsset\InjectionClasses\A::injectBOnce:b' => new \ZendTest\Di\TestAsset\InjectionClasses\B('once'),
                'ZendTest\Di\TestAsset\InjectionClasses\A::injectBTwice:b' => new \ZendTest\Di\TestAsset\InjectionClasses\B('twice')
            ]
        );
        $a = $di->newInstance('ZendTest\Di\TestAsset\InjectionClasses\A');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\InjectionClasses\B', $a->bs[0]);
        $this->assertEquals('once', $a->bs[0]->id);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\InjectionClasses\B', $a->bs[1]);
        $this->assertEquals('twice', $a->bs[1]->id);
    }

    public function testInjectionCanHandleMultipleInjectionsWithMultipleArguments()
    {
        $definitionList = new DefinitionList([
            $classdef = new Definition\ClassDefinition('ZendTest\Di\TestAsset\InjectionClasses\A'),
            new Definition\RuntimeDefinition()
        ]);
        $classdef->addMethod('injectSplitDependency');
        $classdef->addMethodParameter('injectSplitDependency', 'b', ['required' => true, 'type' => 'ZendTest\Di\TestAsset\InjectionClasses\B']);
        $classdef->addMethodParameter('injectSplitDependency', 'somestring', ['required' => true, 'type' => null]);

        /**
         * First test that this works with a single call
         */
        $di = new Di($definitionList);
        $di->instanceManager()->setInjections(
            'ZendTest\Di\TestAsset\InjectionClasses\A',
            [
                'injectSplitDependency' => ['b' => 'ZendTest\Di\TestAsset\InjectionClasses\B', 'somestring' => 'bs-id']
            ]
        );
        $a = $di->newInstance('ZendTest\Di\TestAsset\InjectionClasses\A');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\InjectionClasses\B', $a->bs[0]);
        $this->assertEquals('bs-id', $a->bs[0]->id);

        /**
         * Next test that this works with multiple calls
         */
        $di = new Di($definitionList);
        $di->instanceManager()->setInjections(
            'ZendTest\Di\TestAsset\InjectionClasses\A',
            [
                'injectSplitDependency' => [
                    ['b' => 'ZendTest\Di\TestAsset\InjectionClasses\B', 'somestring' => 'bs-id'],
                    ['b' => 'ZendTest\Di\TestAsset\InjectionClasses\C', 'somestring' => 'bs-id-for-c']
                ]
            ]
        );
        $a = $di->newInstance('ZendTest\Di\TestAsset\InjectionClasses\A');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\InjectionClasses\B', $a->bs[0]);
        $this->assertEquals('bs-id', $a->bs[0]->id);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\InjectionClasses\C', $a->bs[1]);
        $this->assertEquals('bs-id-for-c', $a->bs[1]->id);
    }

    /**
     * @group SetterInjection
     * @group SupertypeResolution
     */
    public function testInjectionForSetterInjectionWillConsultSupertypeDefinitions()
    {
        $di = new Di();
        // for setter injection, the dependency is not required, thus it must be forced
        $di->instanceManager()->setParameters(
            'ZendTest\Di\TestAsset\SetterInjection\C',
            ['a' => new TestAsset\SetterInjection\A]
        );
        $c = $di->get('ZendTest\Di\TestAsset\SetterInjection\C');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\C', $c);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $c->a);
    }

    /**
     * @group SetterInjection
     * @group SupertypeResolution
     */
    public function testInjectionForSetterInjectionWillConsultSupertypeDefinitionInClassDefinition()
    {
        $di = new Di();

        // for setter injection, the dependency is not required, thus it must be forced
        $classDef = new Definition\ClassDefinition('ZendTest\Di\TestAsset\SetterInjection\B');
        $classDef->addMethod('setA', true);
        $di->definitions()->addDefinition($classDef, false); // top of stack b/c Runtime is already there

        $c = $di->get('ZendTest\Di\TestAsset\SetterInjection\C');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\C', $c);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $c->a);
    }

    /**
     * @group SharedInstance
     */
    public function testMarkingClassAsNotSharedInjectsNewInstanceIntoAllRequestersButDependentsAreShared()
    {
        $di = new Di();
        $di->configure(new Config([
            'instance' => [
                'ZendTest\Di\TestAsset\SharedInstance\Lister' => [
                    'shared' => false
                ]
            ]
        ]));
        $movie = $di->get('ZendTest\Di\TestAsset\SharedInstance\Movie');
        $venue = $di->get('ZendTest\Di\TestAsset\SharedInstance\Venue');

        $this->assertNotSame($movie->lister, $venue->lister);
        $this->assertSame($movie->lister->sharedLister, $venue->lister->sharedLister);
    }

    public function testDiWillInjectDependenciesForInstance()
    {
        $di = new Di;

        // for setter injection, the dependency is not required, thus it must be forced
        $classDef = new Definition\ClassDefinition('ZendTest\Di\TestAsset\SetterInjection\B');
        $classDef->addMethod('setA', true);
        $di->definitions()->addDefinition($classDef, false); // top of stack b/c Runtime is already there

        $b = new TestAsset\SetterInjection\B;
        $di->injectDependencies($b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $b->a);
    }

    public function testDiWillInjectDependenciesForAlias()
    {
        $di = new Di;

        // for setter injection, the dependency is not required, thus it must be forced
        $classDef = new Definition\ClassDefinition('ZendTest\Di\TestAsset\SetterInjection\B');
        $classDef->addMethod('setA', false);
        $classDef->addMethodParameter('setA', 'a', ['type' => 'ZendTest\Di\TestAsset\SetterInjection\A', 'required' => false]);
        $di->definitions()->addDefinition($classDef, false);
        $di->instanceManager()->addAlias('b_alias', 'ZendTest\Di\TestAsset\SetterInjection\B');
        $di->instanceManager()->setInjections('b_alias', ['ZendTest\Di\TestAsset\SetterInjection\A']);

        $b = $di->get('b_alias');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $b->a);
    }

    /*
     * @group SetterInjection
     * @group SupertypeResolution
     */
    public function testInjectionForSetterInjectionWillNotUseSupertypeWhenChildParamIsExplicitlyDefined()
    {
        $di = new Di();
        // for setter injection, the dependency is not required, thus it must be forced
        $di->instanceManager()->setParameters(
            'ZendTest\Di\TestAsset\InheritanceClasses\B',
            ['test' => 'b']
        );
        $di->instanceManager()->setParameters(
            'ZendTest\Di\TestAsset\InheritanceClasses\A',
            ['test' => 'a']
        );

        $b = $di->get('ZendTest\Di\TestAsset\InheritanceClasses\B');
        $this->assertEquals('b', $b->test);

        $c = $di->get('ZendTest\Di\TestAsset\InheritanceClasses\C');
        $this->assertEquals('b', $c->test);
    }

    /**
     * @group ZF2-260
     */
    public function testDiWillInjectClassNameAsStringAtCallTime()
    {
        $di = new Di;

        $classDef = new Definition\ClassDefinition('ZendTest\Di\TestAsset\SetterInjection\D');
        $classDef->addMethod('setA', true);
        $classDef->addMethodParameter('setA', 'a', ['type' => false, 'required' => true]);
        $di->definitions()->addDefinition($classDef, false);

        $d = $di->get(
            'ZendTest\Di\TestAsset\SetterInjection\D',
            ['a' => 'ZendTest\Di\TestAsset\SetterInjection\A']
        );

        $this->assertSame($d->a, 'ZendTest\Di\TestAsset\SetterInjection\A');
    }

    /**
     * @group ZF2-308
     */
    public function testWillNotCallStaticInjectionMethods()
    {
        $di = new Di;
        $di->definitions()->addDefinition(new Definition\RuntimeDefinition(), false);
        $di->newInstance('ZendTest\Di\TestAsset\SetterInjection\StaticSetter', ['name' => 'testName']);

        $this->assertSame(\ZendTest\Di\TestAsset\SetterInjection\StaticSetter::$name, 'originalName');
    }

    /**
     * @group ZF2-142
     */
    public function testDiWillInjectDefaultParameters()
    {
        $di = new Di;

        $classDef = new Definition\ClassDefinition('ZendTest\Di\TestAsset\ConstructorInjection\OptionalParameters');
        $classDef->addMethod('__construct', true);
        $classDef->addMethodParameter(
            '__construct',
            'a',
            ['type' => false, 'required' => false, 'default' => null]
        );
        $classDef->addMethodParameter(
            '__construct',
            'b',
            ['type' => false, 'required' => false, 'default' => 'defaultConstruct']
        );
        $classDef->addMethodParameter(
            '__construct',
            'c',
            ['type' => false, 'required' => false, 'default' => []]
        );

        $di->definitions()->addDefinition($classDef, false);

        $optionalParams = $di->newInstance('ZendTest\Di\TestAsset\ConstructorInjection\OptionalParameters');

        $this->assertSame(null, $optionalParams->a);
        $this->assertSame('defaultConstruct', $optionalParams->b);
        $this->assertSame([], $optionalParams->c);
    }

    /**
     * @group SharedInstance
     */
    public function testGetWithParamsWillUseSharedInstance()
    {
        $di = new Di;

        $sharedInstanceClass = 'ZendTest\Di\TestAsset\ConstructorInjection\A';
        $retrievedInstanceClass = 'ZendTest\Di\TestAsset\ConstructorInjection\C';

        // Provide definitions for $retrievedInstanceClass, but not for $sharedInstanceClass.
        $arrayDefinition = [$retrievedInstanceClass => [
            'supertypes' => [ ],
            'instantiator' => '__construct',
            'methods' => ['__construct' => true],
            'parameters' => [ '__construct' => [
                "$retrievedInstanceClass::__construct:0" => ['a', $sharedInstanceClass, true, null],
                "$retrievedInstanceClass::__construct:1" => ['params', null, false, []],
            ]],
        ]];

        // This also disables scanning of class A.
        $di->setDefinitionList(new DefinitionList(new Definition\ArrayDefinition($arrayDefinition)));

        $di->instanceManager()->addSharedInstance(new $sharedInstanceClass, $sharedInstanceClass);
        $returnedC = $di->get($retrievedInstanceClass, ['params' => ['test']]);
        $this->assertInstanceOf($retrievedInstanceClass, $returnedC);
    }

    public function testGetInstanceWithParamsHasSameNameAsDependencyParam()
    {
        $config = new Config([
            'definition' => [
                'class' => [
                    'ZendTest\Di\TestAsset\AggregateClasses\AggregateItems' => [
                        'addItem' => [
                            'item' => ['type'=>'ZendTest\Di\TestAsset\AggregateClasses\ItemInterface',
                                            'required'=>true]
                        ]
                    ]
                ]
            ],
            'instance' => [
                'ZendTest\Di\TestAsset\AggregateClasses\AggregateItems' => [
                    'injections' => [
                        'ZendTest\Di\TestAsset\AggregateClasses\Item'
                    ]
                ],
                'ZendTest\Di\TestAsset\AggregatedParamClass' => [
                    'parameters' => [
                        'item' => 'ZendTest\Di\TestAsset\AggregateClasses\AggregateItems'
                    ]
                ]
            ]
        ]);

        $di = new Di(null, null, $config);
        $this->assertCount(1, $di->get('ZendTest\Di\TestAsset\AggregatedParamClass')->aggregator->items);
    }

    public function hasInstanceProvider()
    {
        $config = new Config(['instance' => [
            TestAsset\BasicClassWithParam::class => [
                'params' => ['foo' => 'bar'],
            ],
        ]]);

        $classDefB = new Definition\ClassDefinition(TestAsset\CallbackClasses\B::class);
        $classDefC = new Definition\ClassDefinition(TestAsset\CallbackClasses\C::class);
        $classDefB->setInstantiator(TestAsset\CallbackClasses\B::class . '::factory');
        $classDefB->addMethod('factory', true);
        $classDefB->addMethodParameter('factory', 'c', [
            'type' => TestAsset\CallbackClasses\C::class,
            'required' => true,
        ]);
        $classDefB->addMethodParameter('factory', 'params', ['type' => 'Array', 'required' => false]);
        $definitionList = new DefinitionList([
                $classDefB,
                $classDefC,
                new Definition\RuntimeDefinition(),
        ]);

        $instanceManager = new InstanceManager();
        $instanceManager->setParameters(TestAsset\ConstructorInjection\X::class, ['one' => 1, 'two' => 2]);

        return [
            'no-config' => [null, null, null, TestAsset\BasicClass::class],
            'config-instance' => [null, null, $config, TestAsset\BasicClassWithParam::class],
            'definition-list' => [$definitionList, null, null, TestAsset\CallbackClasses\B::class],
            'instance-manager' => [null, $instanceManager, null, TestAsset\ConstructorInjection\X::class],
        ];
    }

    /**
     * @dataProvider hasInstanceProvider
     */
    public function testCanQueryToSeeIfContainerHasOrCanCreateAnInstance(
        $definitionList,
        $instanceManager,
        $config,
        $testFor
    ) {
        $di = new Di($definitionList, $instanceManager, $config);
        $this->assertTrue($di->has($testFor), sprintf('Failed to find instance for %s', $testFor));
    }
}
