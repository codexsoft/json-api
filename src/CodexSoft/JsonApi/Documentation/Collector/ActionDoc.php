<?php


namespace CodexSoft\JsonApi\Documentation\Collector;


use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;

class ActionDoc
{
    public ?Route $route = null;
    public ?CompiledRoute $compiledRoute = null;
    public ?string $actionClass = null;
    public ?string $inputFormClass = null;

    /** @var string[] */
    public array $tags = [];

    public ?string $description = null;

    /** todo: $route->getPath()? */
    public ?string $path = null;

    /** @var array [httpStatusCode => Description] */
    public array $responses = [];

}
