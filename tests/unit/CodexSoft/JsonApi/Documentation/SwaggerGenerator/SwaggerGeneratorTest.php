<?php

namespace CodexSoft\JsonApi\Documentation\SwaggerGenerator;

use CodexSoft\JsonApi\AbstractWebServer;
use CodexSoft\JsonApi\Documentation\Collector\ApiDocCollector;
use CodexSoft\JsonApi\JsonApiSchema;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Routing\Router;

class SwaggerGeneratorTest extends TestCase
{

    public function testGenerate()
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
        //$apiDoc = (new ApiDocCollector($router, $formFactory, $jsonApiSchema, $logger))->collect($paths);

        $generator = new SwaggerGenerator($apiDoc);
        $lines = $generator->generate();
        $generatedCode = "\n * ".implode("\n * ", $lines);
        $code = implode("\n", [
            '<?php',
            'namespace App\Definitions;',
            '',
            '/**',
        ]).$generatedCode."*/\nclass Definitions {}";

        $fs = new Filesystem();
        $fs->mkdir($kernel->getProjectDir().'/var/swagger');
        $fs->dumpFile($kernel->getProjectDir().'/var/swagger/Definitions.php', $code);
        $x=1;
    }
}
