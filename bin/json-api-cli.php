<?php

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

//$jsonApi = new \CodexSoft\JsonApi\JsonApiTools;
//$jsonApi->setConfig($jsonApiSchema);

$console->add(new \CodexSoft\JsonApi\Command\CreateActionCommand($jsonApiSchema, 'action'));
$console->add(new \CodexSoft\JsonApi\Command\SwagenCommand('swagen'));

/** @noinspection PhpUnhandledExceptionInspection */
$console->run();
