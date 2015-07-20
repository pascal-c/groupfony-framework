# Groupfony Framework
A lightweight php framework based on Symfony Components

## Main goals
The main goal of this framework is to get rid of the usage of service containers. I want controller actions to be automatically injected with services by type hinting the parameters. In Symfony, it is possible to inject your controller constructor method by defining your controller as a service. So, each time you need a new service, you have to edit your routing file. It is not possible to inject other methods than the constructor. I want all my controller methods to be injected automatically with the suiting service.  
See [Usage->Controller Section](#service_injection) for usage of Dependency Injection in Groupfony Framework.
 
Groupfony Framewok is inspirated by [Silex](http://silex.sensiolabs.org/), but takes more usage of Symfony Components, especially the Routing and Dependency Injection Components.  
It is designed for the Groupfony project, that will offer different components for group communication (group management, mailing lists, cms, calendar, etc.). These components can be used independently and will communicate with each other in a service orientated architecture. So the framework might come with some support for web hooks in future versions.

## Installation
via Composer. Add this to your composer.json file:
```JSON
{
    "require": {
        "groupfony/framework":          "~0.1"
    }
}
```

## Configuration
Your directory structure should look a little bit like this:
```
App/
   Controller
   ...
config/
    services.yml
    routing.yml
vendor/
    ...
web/
    index.php
    .htaccess
bootstrap.php
composer.json
```

### web/.htaccess
Put an .htaccess file to your desired web directory for redirection to index.php. For Apache>=2.2.16 you can use
```htaccess
FallbackResource /path/to/your/web-folder/index.php
```
Or have a look in [Silex Documentation](http://silex.sensiolabs.org/doc/web_servers.html) for other possibilities.

### web/index.php
```PHP
<?php
// index.php
$loader = require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../bootstrap.php';

$app->run();
```
### bootstrap.php
The setBefore method will be called before execution of controller action. 
If it returns a Symfony\Component\HttpFoundation\Response or false, the controller action will NOT be executed and the response will be send directly to the client. The before-method will be injected with services and Request in the same way as described as the controller actions. (see section below)
```PHP
<?php
// bootstrap.php - example
use \Groupfony\Framework\Application;

// init Application - $configDir path to config dir, 
// it must contain: services.yml and routes.yml
$app = new Application( __DIR__ . '/config' );

// Class and method, that will be called before execution of controller action
$app->setBefore('\\Groupfony\\Calendar\\Services\\App', 'before');

// basePath
$container->setParameter('basePath', __DIR__);

return $app;
```
### config/services.yml
Here is an example of a services.yml file. (No other file formats are supported.)
Groupfony Framework comes with no other components than the components needed by the framework itself.
As you see, you can easily include services like doctrine or twig. You also have to add them to your composer.json, of cause.
See [Symfony Documentation](http://symfony.com/doc/current/components/dependency_injection/introduction.html#setting-up-the-container-with-configuration-files) for more information about how to configure this file.
```YAML
parameters:
    devMode:      true
    basePath:     "to be set by bootstrap"
    cachePath:    "%basePath%/cache"
    
    twig.templatePath:  "%basePath%/Views"
    twig.cachePath:     "%cachePath%/twig"
    
    orm.path:
        - "%basePath%/Domain/Entities"
    orm.db:
        driver:     pdo_mysql
        user:       db_user
        password:   '***'
        dbname:     my_db

services:
    orm.configuration:
        class:   Doctrine\ORM\Configuration
        factory: [Doctrine\ORM\Tools\Setup, createAnnotationMetadataConfiguration]
        arguments: ['%orm.path%', '%devMode%']
    orm.entityManager:
        class:   Doctrine\ORM\EntityManager
        factory: [Doctrine\ORM\EntityManager, create]
        arguments: ["%orm.db%", "@orm.configuration"]
    entityManager: "@orm.entityManager"
    
    twig.loader:
        class: Twig_Loader_Filesystem
        arguments: ["%twig.templatePath%"]
    twig.environment: 
        class: Twig_Environment
        arguments: [@twig.loader, {cache: false}]
    twig: "@twig.environment"
```
Here is an example of a convenient composer.json file:
```JSON
{
    "require": {
        "groupfony/framework":          "~0.1",
        
        "doctrine/orm":                 "2.5.*",
        "swiftmailer/swiftmailer":      "5.*",
        "twig/twig":                    "~1.18"
        
    }
}
```

### config/routing.yml
Here is an example of routing.yml file. The value of the placeholders and also all parameters of the defaults section will be injected to the controller action by using the parameter name. See Usage->Controller.
Please see [Symfony Documentation](http://symfony.com/doc/current/components/routing/introduction.html#load-routes-from-a-file) for details how to configure it.
```YAML
index:
    path:       /
    defaults:   
        _controller: 'Groupfony\Calendar\Controller\showCalendar::showAction'
    
list:
    path:       /events/{id}/{otherPlaceholder}
    defaults:   
        _controller: 'Groupfony\Calendar\Controller\api::listAction'
        id: 5
```
## Usage
### Controller
#### <a name="service_injection"></a>Service Injection 
The main goal of this framework is to get rid of the usage of a service container everywhere. I want the framework to inject services to my controller actions automatically:
```PHP
use Doctrine\ORM\EntityManager;

public function listAction(EntityManager $entityManager) {
    // get something from db...
}
```
You just have to type hint your parameter and the framework will pass the convenient service for you.
The type hint can also be a subclass of the given service class. It is recommended to use the service name (as defined in services.yml) as parameter name for performance and clarity reasons. You can also let inject your constructor:
```PHP
use Doctrine\ORM\EntityManager;

private $entityManager;
public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
}
```

#### Parameter Injection
All placeholders and all parameters from the defaults-array defined in routes.yml will be injected as well:
```PHP
use Doctrine\ORM\EntityManager;

// route: /events/{id}/{otherPlaceholder}
public function listAction(EntityManager $entityManager, $id) {
    // get something with id $id from db...
}
```
#### Request Injection
And, like in Symfony, you can get the Request by the type hint:
```PHP
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

public function listAction(EntityManager $entityManager, $id, Request $request) {
    // get something with id $id and some query parameters from db...
}
```

#### Service Parameter Injection
You can also get serviceParameters, just by name:
```PHP
public function listAction($devMode) {
    // devMode was defined as a serviceParameter in services.yml
}
```

#### Possible return values
Your controller can return an instance of Symfony\Component\HttpFoundation\Response or simply a string.
The framework will convert it to a Response for you.
```PHP
use Symfony\Component\HttpFoundation\Response;

public function listAction() {
    return 'Hello World';
}
```

See [Symfony Documentation of HttpFoundation](http://symfony.com/doc/current/components/http_foundation/introduction.html) for more Details about Request and Response classes.

### Testing
You can use Groupfony\Framework\FunctionalTestCase.php to write functional tests.
You have to immplement the createApplication method like here:

```PHP
<?php

use Symfony\Component\HttpKernel\HttpKernel;
use Groupfony\Framework\FunctionalTestCase;

abstract class GroupfonyTestCase extends FunctionalTestCase
{
    /**
     * Creates the application.
     *
     * @return HttpKernel
     */
    public function createApplication() {
        $app = require __DIR__ . "/../bootstrap.php";
        return $app;
    }

}
```
You will have to add this to your composer.json to use it:
```JSON
{
    "require-dev": {
        "phpunit/phpunit":              "4.6.8",
        "symfony/css-selector":         "~2.7",
        "symfony/browser-kit":          "~2.7"
    }
}
```
See the [Silex Documentation about Testing](http://silex.sensiolabs.org/doc/testing.html) for more information about unit tests and functional tests.
