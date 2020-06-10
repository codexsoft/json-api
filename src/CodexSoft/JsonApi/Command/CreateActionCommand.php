<?php

namespace CodexSoft\JsonApi\Command;

use CodexSoft\JsonApi\JsonApiSchema;
use CodexSoft\JsonApi\Operations\CreateActionOperation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateActionCommand extends Command
{
    private JsonApiSchema $jsonApiSchema;

    public function __construct(JsonApiSchema $jsonApiSchema, string $name = null)
    {
        parent::__construct($name);
        $this->jsonApiSchema = $jsonApiSchema;
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('api:action')

            // the short description shown while running "php bin/console list"
            ->setDescription('Generate new action with request and response forms')

            ->addArgument('actionName', InputArgument::REQUIRED, 'new action name, like app.action.product.add')
            ->addArgument('route', InputArgument::OPTIONAL, 'route for action, like /hello/world')
            ->addOption('style', 's', InputOption::VALUE_REQUIRED, 'style of POST action: handle|invoke', CreateActionOperation::STYLE_HANDLE)
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
        $output->writeln('Generating action...');

        (new CreateActionOperation)
            ->setJsonApiSchema($this->jsonApiSchema)
            ->setNewActionName($input->getArgument('actionName'))
            ->setRoute($input->getArgument('route'))
            ->setStyle($input->getOption('style'))
            ->execute();

        return 0;
    }

}
