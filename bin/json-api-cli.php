<?php

use CodexSoft\JsonApi\Operations\CreateActionOperation;
use CodexSoft\OperationsSystem\Command\ExecuteOperationCommand;
use Symfony\Component\Console\Command\Command;

/** @var string $jsonApiConfigFile */
$jsonApiSchema = \CodexSoft\JsonApi\JsonApiSchema::getFromConfigFile($jsonApiConfigFile);

//$console = new \Symfony\Component\Console\Application("CodexSoft JSON API tools CLI\nConfig file: $jsonApiConfigFile");
$console = new \Symfony\Component\Console\Application(implode("\n", [
    'CodexSoft JSON API tools CLI',
    "Config file: $jsonApiConfigFile",
    '',
    'PSR root path:     '.$jsonApiSchema->getPathToPsrRoot(),
    'Actions path:      '.$jsonApiSchema->getPathToActions(),
    'Forms path:        '.$jsonApiSchema->getPathToForms(),
    '',
    'Base namespace:    '.$jsonApiSchema->getNamespaceBase(),
    'Actions namespace: '.$jsonApiSchema->getNamespaceActions(),
    'Forms namespace:   '.$jsonApiSchema->getNamespaceForms(),

]));

$jsonApi = new \CodexSoft\JsonApi\JsonApiTools;
$jsonApi->setConfig($jsonApiSchema);

$commandList = [
    'action' => (new \CodexSoft\JsonApi\Command\CreateActionCommand)->setJsonApiTools($jsonApi),

    'add-action' => new ExecuteOperationCommand($jsonApi->generateAction(), function(array $options, CreateActionOperation $operation) {
        $operation->setNewActionName($options[0]);
        $operation->setRoute($options[1] ?? '');
    }),

    'swagen' => new \CodexSoft\JsonApi\Command\SwagenCommand(),
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
