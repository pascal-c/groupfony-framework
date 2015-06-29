# Groupfony Framework
A lightweight php framework based on Symfony Components

## Main goals
1. for small and medium sized projects
2. I wanted controller actions to be automatically injected with services by Type Hinting the parameters.  
See Usage->Controller Section for details.

Groupfony Framewok is inspirated by [Silex](http://silex.sensiolabs.org/), but takes more usage of Symfony Components, especially the Routing and Dependency Injection Components.

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
### web/.htaccess
Put an .htaccess file to your desired web directory for redirectino to index.php. For Apache>=2.2.16 you can use
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
```PHP
<?php
// bootstrap.php
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
As you see, you can easily include doctrine or twig. You also have to add them to your composer.json, of cause.
See [Symfony Documentation](http://symfony.com/doc/current/components/dependency_injection/introduction.html#setting-up-the-container-with-configuration-files) for more information of how to configure this file.
```YAML
parameters:
    devMode:      true
    basePath:     "to be set by bootstrap"
    cachePath:    "%basePath%/cache"
    baseUrl:      "/groupfony/calendar/Groupfony/Calendar/web"
    
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
### Testing
