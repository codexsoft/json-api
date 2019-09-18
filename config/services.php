<?php

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\FileLoader;

/** @var FileLoader $this */

$defaultDefinition = (new Definition)
    ->setAutoconfigured(true)
    ->setAutowired(true)
    ->setPublic(false);

//$webServerSchema = \CodexSoft\JsonApi\JsonApiSchema::getFromConfigFile(__DIR__.'/codexsoft.json-api.php');

//$paths = [
//    $webServerSchema->getNamespaceActions().'\\' => $webServerSchema->getPathToActions().'*',
//    'App\\Command\\' => __DIR__.'/../src/App/Command/*',
//];

//foreach ($paths as $namespace => $path) {
//    $this->registerClasses((clone $defaultDefinition)->setPublic(true), $namespace, $path);
//}

$classes = [
    \CodexSoft\JsonApi\Form\Extensions\FormFieldDefaultValueExtension::class,
    \CodexSoft\JsonApi\Form\Extensions\FormFieldExampleExtension::class,
];

foreach ($classes as $class) {
    $this->setDefinition($class, (clone $defaultDefinition));
}

//$this->registerClasses((clone $defaultDefinition)->setPublic(true), $webServerSchema->getNamespaceActions().'\\', $webServerSchema->getPathToActions().'*');
//$this->registerClasses((clone $defaultDefinition)->setPublic(true), 'App\\Command\\', '../src/App/Command/*');

//$this->setDefinition(\CodexSoft\JsonApi\Form\Extensions\FormFieldDefaultValueExtension::class, (clone $defaultDefinition));

//$this->setDefinition(CodexSoft\JsonApi\EventListener\HttpRequestLogSubscriber::class, (clone $defaultDefinition)
    //->addTag('kernel.event_subscriber')
//);

//$this->setDefinition(CodexSoft\JsonApi\EventListener\ResponseListener::class, clone $defaultDefinition);
//$this->setDefinition(CodexSoft\JsonApi\EventListener\ExceptionListener::class, clone $defaultDefinition);
