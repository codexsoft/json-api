<?php


namespace CodexSoft\JsonApi\Documentation\Collector;


use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;

class ActionDoc
{

    /** @var Route */
    public $route;

    /** @var CompiledRoute */
    public $compiledRoute;

    /** @var string */
    public $actionClass;

    /** @var string */
    public $inputFormClass;

    /** @var string[] */
    public $tags = [];

    /** @var string */
    public $description;

    /** @var string todo: $route->getPath()? */
    public $path;

    /** @var array [httpStatusCode => Description] */
    public $responses = [];

}