<?php

namespace Groupfony\Framework;

use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernel;


abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $container;

    /**
     * PHPUnit setUp for setting up the application.
     *
     * Note: Child classes that define a setUp method must call
     * parent::setUp().
     */
    public function setUp()
    {
        $this->app = $this->createApplication();
        $this->container = $this->app->getContainer();
    }

    /**
     * Creates the application.
     *
     * @return HttpKernel
     */
    abstract public function createApplication();

    /**
     * Creates a Client.
     *
     * @param array $server An array of server parameters
     *
     * @return Client A Client instance
     */
    public function createClient(array $server = array())
    {
        return new Client($this->app, $server);
    }
} 