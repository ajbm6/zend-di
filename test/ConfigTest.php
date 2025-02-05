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
use Zend\Di\Di;
use PHPUnit_Framework_TestCase as TestCase;

class ConfigTest extends TestCase
{
    public function testConfigCanConfigureInstanceManagerWithIniFile()
    {
        $ini = include __DIR__ . '/_files/sample-definitions.php';
        $ini = $ini['section-a'];
        $config = new Config($ini['di']);
        $di = new Di();
        $di->configure($config);

        $im = $di->instanceManager();

        $this->assertTrue($im->hasAlias('my-repository'));
        $this->assertEquals('My\RepositoryA', $im->getClassFromAlias('my-repository'));

        $this->assertTrue($im->hasAlias('my-mapper'));
        $this->assertEquals('My\Mapper', $im->getClassFromAlias('my-mapper'));

        $this->assertTrue($im->hasAlias('my-dbAdapter'));
        $this->assertEquals('My\DbAdapter', $im->getClassFromAlias('my-dbAdapter'));

        $this->assertTrue($im->hasTypePreferences('my-repository'));
        $this->assertContains('my-mapper', $im->getTypePreferences('my-repository'));

        $this->assertTrue($im->hasTypePreferences('my-mapper'));
        $this->assertContains('my-dbAdapter', $im->getTypePreferences('my-mapper'));

        $this->assertTrue($im->hasConfig('My\DbAdapter'));
        $expected = ['parameters' => ['username' => 'readonly', 'password' => 'mypassword'], 'injections' => [], 'shared' => true];
        $this->assertEquals($expected, $im->getConfig('My\DbAdapter'));

        $this->assertTrue($im->hasConfig('my-dbAdapter'));
        $expected = ['parameters' => ['username' => 'readwrite'], 'injections' => [], 'shared' => true];
        $this->assertEquals($expected, $im->getConfig('my-dbAdapter'));
    }

    public function testConfigCanConfigureBuilderDefinitionFromIni()
    {
        $this->markTestIncomplete('Builder not updated to new DI yet');
        $ini = include __DIR__ . '/_files/sample-definitions.php';
        $ini = $ini['section-b'];
        $config = new Config($ini['di']);
        $di = new Di($config);
        $definition = $di->getDefinition();

        $this->assertTrue($definition->hasClass('My\DbAdapter'));
        $this->assertEquals('__construct', $definition->getInstantiator('My\DbAdapter'));
        $this->assertEquals(
            ['username' => null, 'password' => null],
            $definition->getInjectionMethodParameters('My\DbAdapter', '__construct')
        );

        $this->assertTrue($definition->hasClass('My\Mapper'));
        $this->assertEquals('__construct', $definition->getInstantiator('My\Mapper'));
        $this->assertEquals(
            ['dbAdapter' => 'My\DbAdapter'],
            $definition->getInjectionMethodParameters('My\Mapper', '__construct')
        );

        $this->assertTrue($definition->hasClass('My\Repository'));
        $this->assertEquals('__construct', $definition->getInstantiator('My\Repository'));
        $this->assertEquals(
            ['mapper' => 'My\Mapper'],
            $definition->getInjectionMethodParameters('My\Repository', '__construct')
        );
    }

    public function testConfigCanConfigureRuntimeDefinitionDefaultFromIni()
    {
        $ini = include __DIR__ . '/_files/sample-definitions.php';
        $ini = $ini['section-c'];
        $config = new Config($ini['di']);
        $di = new Di();
        $di->configure($config);
        $definition = $di->definitions()->getDefinitionByType('Zend\Di\Definition\RuntimeDefinition');
        $this->assertInstanceOf('Zend\Di\Definition\RuntimeDefinition', $definition);
        $this->assertFalse($definition->getIntrospectionStrategy()->getUseAnnotations());
    }

    public function testConfigCanConfigureRuntimeDefinitionDisabledFromIni()
    {
        $ini = include __DIR__ . '/_files/sample-definitions.php';
        $ini = $ini['section-d'];
        $config = new Config($ini['di']);
        $di = new Di();
        $di->configure($config);
        $definition = $di->definitions()->getDefinitionByType('Zend\Di\Definition\RuntimeDefinition');
        $this->assertFalse($definition);
    }

    public function testConfigCanConfigureRuntimeDefinitionUseAnnotationFromIni()
    {
        $ini = include __DIR__ . '/_files/sample-definitions.php';
        $ini = $ini['section-e'];
        $config = new Config($ini['di']);
        $di = new Di();
        $di->configure($config);
        $definition = $di->definitions()->getDefinitionByType('Zend\Di\Definition\RuntimeDefinition');
        $this->assertTrue($definition->getIntrospectionStrategy()->getUseAnnotations());
    }

    public function testConfigCanConfigureCompiledDefinition()
    {
        $config = include __DIR__ . '/_files/sample.php';
        $config = new Config($config['di']);
        $di = new Di();
        $di->configure($config);
        $definition = $di->definitions()->getDefinitionByType('Zend\Di\Definition\ArrayDefinition');
        $this->assertInstanceOf('Zend\Di\Definition\ArrayDefinition', $definition);
        $this->assertTrue($di->definitions()->hasClass('My\DbAdapter'));
        $this->assertTrue($di->definitions()->hasClass('My\EntityA'));
        $this->assertTrue($di->definitions()->hasClass('My\Mapper'));
        $this->assertTrue($di->definitions()->hasClass('My\RepositoryA'));
        $this->assertTrue($di->definitions()->hasClass('My\RepositoryB'));
        $this->assertFalse($di->definitions()->hasClass('My\Foo'));
    }
}
