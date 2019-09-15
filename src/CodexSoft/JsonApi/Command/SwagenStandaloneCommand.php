<?php /** @noinspection SlowArrayOperationsInLoopInspection */

namespace CodexSoft\JsonApi\Command;

use CodexSoft\Code\Shortcuts;
use CodexSoft\JsonApi\Swagen\SwagenGenerateApiDocumentation;
use CodexSoft\JsonApi\Swagen\SwagenLib;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function CodexSoft\Code\str;

class SwagenStandaloneCommand extends Command
{

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:swagen')

            // the short description shown while running "php bin/console list"
            ->setDescription('Generate swagger documentation from forms')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to automatically generate swagger documentation from symfony forms')
            ->addArgument('destinationFile',InputArgument::OPTIONAL,'destination file path','/src/Definitions/Definitions.php')
            ->addOption('strict','s',InputOption::VALUE_NONE,'if set, any exceptions will stop generation process')
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
        Shortcuts::register();
        $output->writeln('Generating swagger documentation...');

        $strictMode = $input->getOption('strict') ?: false;

        /** @var \Symfony\Bundle\FrameworkBundle\Console\Application $app */
        $app = $this->getApplication();
        $kernel = $app->getKernel();
        $container = $kernel->getContainer();

        if (!$container instanceof ContainerInterface) {
            throw new \RuntimeException('Failed to get container!');
        }

        $rootDir = $app->getKernel()->getProjectDir(); // todo

        /** @var \Symfony\Component\Form\FormFactory $formFactory */
        $formFactory = $container->get('form.factory'); // todo

        /** @var \Symfony\Component\Routing\Router $router */
        $router = $container->get('router'); // todo

        $destinationPathFromRoot = (string) str($input->getArgument('destinationFile'))->removeLeft('.')->ensureLeft('/');

        $lib = (new SwagenLib)
            ->setFormFactory($formFactory)
            ->setLogger(new ConsoleLogger($output))
            ->setFormFactory($formFactory)
            ->setRouter($router);

        (new SwagenGenerateApiDocumentation($lib))
            //->setPathPrefixToRemove('/v1')
            ->setStrictMode($strictMode)

            // в нашем проекте формы лежат в нескольких местах, прописываем их:
            ->setResponsesClassesMap([
                //$rootDir.'/Response' => 'App\\Response\\',
                $rootDir.'/src/App/Action' => 'App\\Action\\',
            ])

            // в нашем проекте формы лежат в нескольких местах, прописываем их:
            ->setFormsClassesMap([
                $rootDir.'/src/App/Form' => 'App\\Form\\',
                $rootDir.'/src/App/Action' => 'App\\Action\\',
            ])
            ->setDestinationFile($destinationPathFromRoot)
            ->setRootDir($rootDir)
            ->execute();

    }

}