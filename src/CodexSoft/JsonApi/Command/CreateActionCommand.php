<?php /** @noinspection SlowArrayOperationsInLoopInspection */

namespace CodexSoft\JsonApi\Command;

use CodexSoft\Code\Shortcuts;
use CodexSoft\JsonApi\JsonApiTools;
use CodexSoft\JsonApi\Operations\CreateActionOperation;
use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SwagenGenerateApiDocumentation;
use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SwagenLib;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function CodexSoft\Code\str;

class CreateActionCommand extends Command
{

    /** @var JsonApiTools */
    private $jsonApiTools;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:swagen')

            // the short description shown while running "php bin/console list"
            ->setDescription('Generate new action with request and response forms')

            // the full command description shown when running the command with
            // the "--help" option
            //->setHelp('This command allows you to automatically generate swagger documentation from symfony forms')
            ->addArgument('actionName',InputArgument::REQUIRED,'new action name, like app.action.product.add')
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
        Shortcuts::register();
        $output->writeln('Generating action...');

        $this->jsonApiTools->generateAction()->setNewActionName($input->getArgument('actionName'))->execute();
        //(new CreateActionOperation)
        //    ->setNewActionName($input->getArgument('actionName'))
        //    ->setJsonApiSchema()
    }

    /**
     * @param JsonApiTools $jsonApiTools
     *
     * @return CreateActionCommand
     */
    public function setJsonApiTools(JsonApiTools $jsonApiTools): CreateActionCommand
    {
        $this->jsonApiTools = $jsonApiTools;
        return $this;
    }

}