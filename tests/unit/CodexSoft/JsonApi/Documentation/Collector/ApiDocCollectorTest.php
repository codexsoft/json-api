<?php

namespace CodexSoft\JsonApi\Documentation\Collector;


use CodexSoft\JsonApi\AbstractWebServer;
use CodexSoft\JsonApi\JsonApiSchema;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Routing\Router;

class ApiDocCollectorTest extends TestCase
{

    public function testCollect()
    {
        $kernel = new AbstractWebServer('dev', true);
        $kernel->boot();

        $container = $kernel->getContainer();

        /** @var FormFactory $formFactory */
        $formFactory = $container->get('form.factory');

        /** @var Router $router */
        $router = $container->get('router');

        //$jsonApiSchema = (new JsonApiSchema)
        //    ->setNamespaceBase('TestApi')
        //    ->setPathToPsrRoot($kernel->getProjectDir().'/tests/unit');

        $paths = [
            $kernel->getProjectDir().'/src' => '',
            $kernel->getProjectDir().'/tests/unit' => '',
        ];

        $logger = new Logger('main', [new StreamHandler('php://stderr')]);

        $apiDoc = (new ApiDocCollector($router, $formFactory, $logger))->collect($paths);
        $x=1;
    }
}
