<?php

use CodexSoft\Code\Helpers\Cli;
use CodexSoft\JsonApi\Operations\CreateActionOperation;
use CodexSoft\OperationsSystem\Command\ExecuteOperationCommand;
use Symfony\Component\Console\Command\Command;

require_once __DIR__.'/findautoloader.php';
$jsonApiConfigFile = Cli::getFirstArgumentOrDie();
$jsonApiSchema = \CodexSoft\JsonApi\JsonApiTools::getFromConfigFile($jsonApiConfigFile);

$console = new \Symfony\Component\Console\Application('CodexSoft JSON API tools CLI');

$jsonApi = new \CodexSoft\JsonApi\JsonApiTools;
$jsonApi->setConfig($jsonApiSchema);

$commandList = [

    //'add-action' => (new \CodexSoft\WebServer\Operations\CreateActionOperation)
    //    ->setWebServerSchema($jsonApiSchema),

    'add-action' => new ExecuteOperationCommand($jsonApi->generateAction(), function(array $options, CreateActionOperation $operation) {
        $operation->setNewActionName($options[0]);
        $operation->setRoute($options[1] ?? '');
    }),
    //'add-action' => (new ExecuteOperationCommand($jsonApi->generateAction()))
    //    ->setConfigureCallback(function(array $options, \CodexSoft\WebServer\Operations\CreateActionOperation $operation) {
    //        $operation->setNewActionName($options[0]);
    //    }),

    //'selfcheck' => (new \CodexSoft\OperationsSystem\Command\SelfCheckCommand)
    //    ->setDomainSchema($domainSchema),

    //'repos' => new ExecuteOperationCommand(
    //    (new \CodexSoft\DatabaseFirst\Operation\GenerateReposOperation)->setDoctrineOrmSchema($ormSchema)
    //),
    //
    //'models' => new ExecuteOperationCommand(
    //    (new \CodexSoft\DatabaseFirst\Operation\GenerateEntitiesOperation)->setDoctrineOrmSchema($ormSchema)
    //),
    //
    //'add-migration' => new ExecuteOperationCommand(
    //    (new \CodexSoft\DatabaseFirst\Operation\GenerateMigrationOperation)->setDoctrineOrmSchema($ormSchema)
    //),
    //
    //'mapping' => new ExecuteShellCommand([
    //    'php '.$cliDir.'/doctrine.orm.php '.$ormConfigFile.' orm:convert-mapping '
    //    .Constants::CUSTOM_CODEXSOFT_BUILDER.' '
    //    .$ormSchema->getPathToMapping().' '
    //    .'--force --from-database --namespace='.$ormSchema->getNamespaceModels().'\\'
    //]),
    //
    //'migrate' => new ExecuteShellCommand([
    //    'php '.$cliDir.'/doctrine.migrate.php '.$ormConfigFile.' migrations:migrate',
    //]),
    //
    //'check' => new ExecuteShellCommand([
    //    'php '.$cliDir.'/doctrine.orm.php '.$ormConfigFile.' orm:validate-schema --skip-sync',
    //]),
    //
    //'review' => new ExecuteShellCommand([
    //    'php '.$cliFile.' '.$ormConfigFile.' mapping',
    //    'php '.$cliFile.' '.$ormConfigFile.' models',
    //    'php '.$cliFile.' '.$ormConfigFile.' repos',
    //]),
    //
    //'regenerate' => new ExecuteShellCommand([
    //    'php '.$cliFile.' '.$ormConfigFile.' db-clean',
    //    'php '.$cliDir.'/doctrine.migrate.php '.$ormConfigFile.' migrations:migrate --no-interaction',
    //    'php '.$cliFile.' '.$ormConfigFile.' review',
    //    'php '.$cliFile.' '.$ormConfigFile.' check',
    //]),

    //'db-clean' => new ExecuteClosureCommand(function(Command $cmd, InputInterface $input, OutputInterface $output) use ($ormSchema) {
    //    Database::deleteAllUserTables($ormSchema->getEntityManager()->getConnection());
    //}),
    //
    //'db-truncate' => new ExecuteClosureCommand(function(Command $cmd, InputInterface $input, OutputInterface $output) use ($ormSchema) {
    //    Database::truncateAllUserTables($ormSchema->getEntityManager()->getConnection());
    //}),
    //
    //'uuid' => \App\Command\GetUuidCommand::class, // ok
];

foreach ($commandList as $command => $commandClass) {
    try {

        if ($commandClass instanceof Command) {
            $commandInstance = $commandClass;
        } else {
            $commandInstance = new $commandClass($command);
        }
        $console->add($commandInstance->setName($command));

    } catch ( \Throwable $e ) {
        echo "\nSomething went wrong: ".$e->getMessage();
    };

}

//\App\Shortcuts::register();

/** @noinspection PhpUnhandledExceptionInspection */
$console->run();
