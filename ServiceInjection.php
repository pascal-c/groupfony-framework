<?php
namespace Groupfony\Framework;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * injects method calls with the service of the container by comparing the class of the type hint with service class name
 */
class ServiceInjection {
    
    private $container;
    private $request;
    
    public function __construct(ContainerBuilder $container, Request $request) {
        $this->container = $container;
        $this->request = $request;
    }
    
    /** constructs an object of the given class and call the given method by adding service parameters to the given parameters
     * -> construct
     * -> callBeforeMethod, if there is one
     * -> callMethod
     * 
     * @param string $class The classname
     * @param string $method The method of the class to be called
     * @param array $parameters An array of parameters array(PARAM_NAME => PARAM_VALUE)
     * @param bool $callBefore - if a method named 'before' should be called before the call of $method
     * @return Response
     */
    public function run($class, $method, array $parameters=array(), $callBefore = false) {
        $controller = $this->construct($class);
        if ($callBefore && method_exists($controller, 'before')) {
            $beforeResponse = $this->callMethod($controller, 'before', $parameters); 
            if ($beforeResponse instanceof Response || $beforeResponse === false) {
                return $beforeResponse;
            }
        }
        return $this->callMethod($controller, $method, $parameters);
    }
    
    /** injects the object to instantiate with services
     * 
     * @param string $classname
     * @return object instance of $classname
     */
    public function construct($classname, array $parameters=array()) {
        if (!method_exists($classname, '__construct')) {
            return new $classname;
        }
        
        $reflectionMethod = new \ReflectionMethod($classname, '__construct');
        $reflectionParams = $reflectionMethod->getParameters();
        
        $constructorParams = array();
        foreach ($reflectionParams as $k=>$reflectionParameter)  {
            $parameterName = $reflectionParameter->getName();
            if (array_key_exists($parameterName, $parameters)) {
                $constructorParams[] = $this->getParam($reflectionParameter, $parameterName, $parameters[$parameterName]);
            }
            else {
                $constructorParams[] = $this->getService($reflectionParameter);
            }
        }
        
        $reflectionClass = new \ReflectionClass($classname);
        return $reflectionClass->newInstanceArgs($constructorParams);
    }
    
    /** calls the method of the object and adds necessary services to the parameters
     * if a parameter name is in $parameters, we take this value, 
     * otherwise we check, if there is a service with the type hinted class
     * if not, we try to take the default vaule
     * if there is no default value throw an exception
     * 
     * @param object $object 
     * @param string $method the method to call
     * @param array $parameters additional parameters for the method i.e. array('id' => 3, ...)
     */
    public function callMethod($object, $method, array $parameters=array()) {
        // call method
        $reflectionMethod = new \ReflectionMethod(get_class($object), $method);
        $reflectionParams = $reflectionMethod->getParameters();
        $methodParams = array();
        foreach ($reflectionParams as $k=>$reflectionParameter) {
            $parameterName = $reflectionParameter->getName();
            if (array_key_exists($parameterName, $parameters)) {
                $methodParams[] = $this->getParam($reflectionParameter, $parameterName, $parameters[$parameterName]);
            }
            else {
                $methodParams[] = $this->getService($reflectionParameter);
            }
        }
        
        return $reflectionMethod->invokeArgs($object, $methodParams);
    }
    
    /** returns the service of the container
     * if there is none, return the default value of the given parameter
     * 
     * @param \ReflectionParameter $reflectionParameter
     * @return mixed
     * @throws \InvalidArgumentException if there is no matching service and no matching parameter
     */
    private function getService(\ReflectionParameter $reflectionParameter) {
        $reflectionParameterClass = $reflectionParameter->getClass();
        $parameterName = $reflectionParameter->getName();

        if ($reflectionParameterClass && $this->container->has($parameterName) && $this->isInstanceOf($this->container->findDefinition($parameterName)->getClass(), $reflectionParameterClass->getName())) {
            return $this->container->get($reflectionParameter->getName());
        }
        elseif ($reflectionParameterClass && $this->isInstanceOf('\\Symfony\\Component\\HttpFoundation\\Request', $reflectionParameterClass->getName())) {
            return $this->request;
        }
        elseif ($reflectionParameterClass && $service=$this->getServiceByClass($reflectionParameterClass->getName())) {
            return $service;
        }
        elseif ($this->container->hasParameter($parameterName)) {
            return $this->container->getParameter($parameterName);
        }
        elseif ($reflectionParameter->isOptional()) {
            return $reflectionParameter->getDefaultValue();
        }
        else {
            throw new \InvalidArgumentException("cannot pass parameter ".$parameterName." to ".$reflectionParameter->getDeclaringClass()->getName()."::" . $reflectionParameter->getDeclaringFunction()->getName());
        }        
    }
    
    private function getServiceByClass($classname) {
        $definitions = $this->container->getDefinitions();
        
        foreach ($definitions as $serviceName=>$definition) {
            if ($this->isInstanceOf($definition->getClass(), $classname)) {
                return $this->container->get($serviceName);
            }
        }
        
        return false;
    }
    
    /**
     * 
     * @param string $class
     * @param string $parentClass
     * @return boolean
     */
    private function isInstanceOf($class, $parentClass) {
        // add \ as prefix
        if ($class[0] != "\\") {
            $class = '\\' . $class;
        }
        if ($parentClass[0] != "\\") {
            $parentClass = '\\' . $parentClass;
        }
        if ($class == $parentClass) {
            return true;
        }
        
        return is_subclass_of($class, $parentClass);
    }
    
    private function getParam(\ReflectionParameter $reflectionParameter, $name, $value) {
        $reflectionParameterClass = $reflectionParameter->getClass();
        if ($reflectionParameterClass) {
            return $this->getEntity($reflectionParameterClass->getName(), $name, $value);
        }
        return $value;
    }
    
    private function getEntity($classname, $key, $value) {
        if (!$this->container->has('entityManager')) {
            //@todo logging
            return $value;
        }
        $entityManager = $this->container->get('entityManager');
        $rep = $entityManager->getRepository($classname); // @todo check, if repository exists
        
        $entity = $rep->{"findOneBy".$key}($value);
        #var_dump( );
        return $entity;
    }
}
