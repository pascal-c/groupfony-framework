<?php

namespace Groupfony\Framework\Tests;

use Symfony\Component\HttpFoundation\Request;

/**
 * A Controller Class for Testing 
 *
 * @author pascal
 */
class Controller {
    private $id;
    private $service;
    
    public function __construct(Service $service, $id=317) {
        $this->service = $service;
        $this->id = $id;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function helloWorld() {
        return $this->service->helloWorld();
    }
    
    public function before($ok=true) {
        return $ok;
    }
    
    public function someAction(Request $request, ServiceSub $service, $id, $baseUrl) {
        $this->service = $service;
        $this->id = $id;
        
        return $request->query->get('some');
    }
}
