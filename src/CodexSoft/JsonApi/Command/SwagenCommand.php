<?php /** @noinspection SlowArrayOperationsInLoopInspection */

namespace CodexSoft\JsonApi\Command;

use CodexSoft\JsonApi\Documentation\Collector\ApiDocCollector;
use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SwaggerGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class SwagenCommand extends Command
{

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('api:swagger')

            // the short description shown while running "php bin/console list"
            ->setDescription('Generate swagger documentation from forms')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to automatically generate swagger documentation from symfony forms')
            //->addArgument('paths', InputArgument::IS_ARRAY, 'destination file path (MUST be PSR4 roots!)')
            ->addArgument('paths', InputArgument::IS_ARRAY, 'source path in format Namespace\\To\\Api=./src/Namespace/To/Api')
            ->addOption('destinationFile','d',InputArgument::OPTIONAL,'destination file path')
            //->addOption('strict','s',InputOption::VALUE_NONE,'if set, any exceptions will stop generation process')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Generating swagger documentation...');

        //$strictMode = $input->getOption('strict') ?: false;

        /** @var \Symfony\Bundle\FrameworkBundle\Console\Application $app */
        $app = $this->getApplication();
        $kernel = $app->getKernel();
        $container = $kernel->getContainer();

        if (!$container instanceof ContainerInterface) {
            throw new \RuntimeException('Failed to get container!');
        }

        /** @var \Symfony\Component\Form\FormFactory $formFactory */
        $formFactory = $container->get('form.factory');

        /** @var \Symfony\Component\Routing\Router $router */
        $router = $container->get('router');

        $logger = new ConsoleLogger($output);

        $paths = [];
        $pathsRaw = $input->getArgument('paths') ?: [];
        if ($pathsRaw) {
            foreach ($pathsRaw as $pathRaw) {
                [$namespaceArg, $pathArg] = explode('=', $pathRaw);
                $paths[$namespaceArg] = $pathArg;
            }

            \array_walk($paths, function(&$val) {
                $val = realpath($val);
            });

            $paths = \array_flip($paths);
            \array_walk($paths, function(&$val) {
                $val = is_int($val) ? '' : $val;
            });
        }
        $paths[realpath(dirname(__DIR__))] = 'CodexSoft\\JsonApi\\';

        $apiDoc = (new ApiDocCollector($router, $formFactory, $logger))->collect($paths);

        $generator = new SwaggerGenerator($apiDoc);
        $lines = $generator->generate();
        $generatedCode = "\n * ".implode("\n * ", $lines);
        $code = implode("\n", [
                '<?php',
                'namespace App\Definitions;',
                '',
                '/**',
            ]).$generatedCode."*/\nclass Definitions {}";

        $destFile = $input->getOption('destinationFile') ?? $kernel->getProjectDir().'/var/swagger/Definitions.php';

        $fs = new Filesystem();
        $fs->dumpFile($destFile, $code);
        $output->writeln("Swagger definitions written in $destFile");

        return 0;
    }

}
