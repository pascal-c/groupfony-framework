<?php
namespace Groupfony\Framework;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Routing {
    
    private $container;
    private $requestContext;
    
    /**
     * @param RequestContext $requestContext
     * @param ContainerBuilder $container
     */
    public function __construct(RequestContext $requestContext, ContainerBuilder $container) {
        $this->requestContext = $requestContext;
        $this->container = $container;
    }
    
    /**
     * 
     * @param Request $request
     * @param string $beforeClass
     * @param string $beforeMethod
     * @return Response
     */
    public function run(Request $request, $beforeClass=null, $beforeMethod=null) {
        $routeParameters = $this->matchRoute($this->getRoutePath());
        list($controllerClassName, $action) = explode('::', $routeParameters['_controller'] );
        
        $serviceInjection = new ServiceInjection($this->container, $request);
        if ($beforeClass && $beforeMethod) {
            $beforeResponse = $serviceInjection->run($beforeClass, $beforeMethod, $routeParameters);
            if ($beforeResponse instanceof Response || $beforeResponse === false) {
                return $beforeResponse;
            }
        }
        return $serviceInjection->run($controllerClassName, $action, $routeParameters, true);
    }
    
    private function getRoutePath() {
        return $this->requestContext->getBaseUrl()->getPathInfo();
    }
    
    private function matchRoute($routePath) {
        $locator = new FileLocator(array($this->container->getParameter('configDir')));

        $router = new Router(
            new YamlFileLoader($locator),
            'routes.yml',
            array('cache_dir' => null),
            $this->requestContext
        );
        $parameters = $router->match($routePath);

        return $parameters;
    }
}