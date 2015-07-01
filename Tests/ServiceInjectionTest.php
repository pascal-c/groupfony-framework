<?php

namespace Groupfony\Framework\Tests;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Groupfony\Framework\ServiceInjection;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @author pascal
 */
class ServiceInjectionTest  extends \PHPUnit_Framework_TestCase {
    
    public function testCreateServiceInjection()
    {
        $container = new ContainerBuilder();
        $container->register('serviceSub', "\\Groupfony\\Framework\\Tests\\ServiceSub");
        $container->setParameter('baseUrl', '/home');
        $_GET['some'] = 'foo';
        $serviceInjection = new ServiceInjection($container, Request::createFromGlobals());
        
        return $serviceInjection;
    }
    
    /**
     * 
     * @depends testCreateServiceInjection
     * @param \Groupfony\Framework\ServiceInjection $serviceInjection
     */
    public function testConstruct(ServiceInjection $serviceInjection) {
        
        $controllerWithDefaultId = $serviceInjection->construct("\\Groupfony\\Framework\\Tests\\Controller");
        $this->assertEquals(317, $controllerWithDefaultId->getId());
        
        $controller = $serviceInjection->construct("\\Groupfony\\Framework\\Tests\\Controller", array('id'=>42));
        $this->assertEquals(42, $controller->getId());
        $this->assertEquals('Hello World Sub', $controller->helloWorld());
        
        return $controller;
    }
    
    /**
     * @depends testCreateServiceInjection
     * @depends testConstruct
     * 
     * @param \Groupfony\Framework\ServiceInjection $serviceInjection
     * @param \Groupfony\Framework\Tests\Controller $controller
     */
    public function testCallBefore(ServiceInjection $serviceInjection, \Groupfony\Framework\Tests\Controller $controller) {
        $resultTrue = $serviceInjection->callMethod($controller, 'before');
        $this->assertTrue($resultTrue);
        
        $resultFalse = $serviceInjection->callMethod($controller, 'before', array('ok' => false));
        $this->assertFalse($resultFalse);
    }
    
    /**
     * @depends testCreateServiceInjection
     * @depends testConstruct
     * 
     * @param \Groupfony\Framework\ServiceInjection $serviceInjection
     * @param \Groupfony\Framework\Tests\Controller $controller
     */
    public function testCallMethod(ServiceInjection $serviceInjection, \Groupfony\Framework\Tests\Controller $controller) {
        
        $result = $serviceInjection->callMethod($controller, 'someAction', array('id' => 155));
        $this->assertEquals('foo', $result);
        
        #$client = 
        #$client->request('GET', '/blubb/2');
    }
}

