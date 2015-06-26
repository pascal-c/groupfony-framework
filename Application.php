<?php
namespace Groupfony\Framework;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader as DIYamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\HttpKernelInterface;


class Application implements HttpKernelInterface
{
    /** Symfony\Component\DependencyInjection\ContainerBuilder $containter */
    private $container;
    
    private $beforeClass;
    private $beforeMethod;
    
    /**
     * @param string $configDir path to config dir containing services.yml and routes.yml
     */
    public function __construct($configDir) {
        $this->container = $this->initDI($configDir);
    }
    
    /**
     * @param string $configDir
     * @return Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    private function initDI($configDir) {
        $container = new ContainerBuilder();
        $container->setParameter('configDir', $configDir); // @todo
        
        $loader = new DIYamlFileLoader($container, new FileLocator($configDir));
        $loader->load('services.yml');
        
        return $container;
    }
    
    public function setBefore($class, $method) {
        $this->beforeClass = $class;
        $this->beforeMethod = $method;
    }
    
    public function getContainer() {
        return $this->container;
    }
    
    /** run the application
     * 
     */
    public function run() {
        $request = Request::createFromGlobals();
        $response = $this->handle($request);
        $response->send();
    }
    
    /** implementation of Symfony\Component\HttpKernel\HttpKernelInterface::handle()
     * 
     * @param Request $request
     * @param type $type
     * @param bool $catch
     * @return Response
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true) {
        $routing = new Routing(new RequestContext($request), $this->container);
        $response = $routing->run($request, $this->beforeClass, $this->beforeMethod);
        if (!$response instanceof Response) {
            $response = Response::create($response);
        }
        return $response;
    }
    
}
