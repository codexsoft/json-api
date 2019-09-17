<?php

namespace CodexSoft\JsonApi\Documentation\Collector;


use CodexSoft\JsonApi\AbstractWebServer;
use CodexSoft\JsonApi\JsonApiSchema;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Routing\Router;

class ApiDocCollectorTest extends TestCase
{

    public function testCollect()
    {
        //$kernelClass = new class extends AbstractWebServer {};
        $kernel = new AbstractWebServer('dev', false);
        $kernel->boot();

        $container = $kernel->getContainer();

        /** @var FormFactory $formFactory */
        $formFactory = $container->get('form.factory');

        /** @var Router $router */
        $router = $container->get('router');

        //$router = '';
        //$formFactory = '';
        $jsonApiSchema = (new JsonApiSchema)
            ->setNamespaceBase('TestApi')
            ->setPathToPsrRoot($kernel->getProjectDir().'/tests/unit');

        $paths = [
            $kernel->getProjectDir().'/src' => '',
        ];

        $logger = new Logger('main', [new StreamHandler('php://stderr')]);

        $apiDoc = (new ApiDocCollector($router, $formFactory, $jsonApiSchema, $logger))->collect($paths);
        $x=1;
    }
}
