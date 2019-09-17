<?php /** @noinspection DuplicatedCode */

namespace CodexSoft\JsonApi\Documentation\Collector;

use CodexSoft\Code\Traits\Loggable;
use CodexSoft\JsonApi\Response\DefaultErrorResponse;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionDescriptionInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionExternalFormInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionProducesSimpleErrorResponsesInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionTagsInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;
use CodexSoft\Code\Helpers\Classes;
use function CodexSoft\Code\str;

class ActionDocCollector
{

    use Loggable;

    /** @var string */
    private $pathPrefixToRemove;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function collect(Route $route): ActionDoc
    {
        $docAction = new ActionDoc;

        $logger = $this->getLogger();

        $routePath = $route->getPath();
        $docAction->route = $route;
        $docAction->path = $routePath;

        $path = $route->getPath();

        if ($this->pathPrefixToRemove) {
            $path = (string) str($path)->removeLeft($this->pathPrefixToRemove);
        }

        $methods = $route->getMethods();
        //$method = Request::METHOD_POST;

        ///* Допущение, что экшн заточен под один конкретный метод */
        //if (\count($methods)) {
        //    $method = Arrays::tool()->getFirst($methods);
        //}

        /* Вытаскиваем название класса экшна */
        $defaultController = $route->getDefault('_controller');
        $actionClass = null;
        if ($defaultController) {
            $actionClass = (string) str($defaultController)->removeRight('::__invoke');
        }

        try {
            $actionClassReflection = new \ReflectionClass($actionClass);
        } catch (\ReflectionException $e) {
            throw new \Exception("SKIPPING action $actionClass: Failed to create ReflectionClass ".$e);
            //$logger->warning('Failed to create ReflectionClass for '.$actionClass.': '.$e);
            //return null;
        }

        if ($actionClassReflection->isAbstract()) {
            throw new \Exception("SKIPPING action $actionClassReflection: class is abstract");
            //$logger->notice("SKIPPING action $actionClassReflection: class is abstract");
            //return null;
        }

        if ($actionClassReflection->implementsInterface(SwagenActionDescriptionInterface::class)) {
            /** @var SwagenActionDescriptionInterface $actionClass */
            $docAction->description = $actionClass::descriptionForSwagger() ?: $actionClass;
        }

        if (!$actionClassReflection->implementsInterface(SwagenInterface::class)) {
            throw new \Exception("SKIPPING action $actionClass: it does not implement ".Classes::short(SwagenInterface::class));
            //$logger->debug("SKIPPING action $actionClass: it does not implement ".Classes::short(SwagenInterface::class));
            //return null;
        }

        $logger->debug($actionClass.' action implements '.Classes::short(SwagenInterface::class));

        if (!$actionClassReflection->implementsInterface(SwagenActionInterface::class)) {
            throw new \Exception("SKIPPING action $actionClass does not implement ".SwagenActionInterface::class);
            //$logger->warning("SKIPPING action $actionClass does not implement ".SwagenActionInterface::class);
            //return null;
        }

        $logger->debug($actionClass.' action implements '.Classes::short(SwagenActionInterface::class));

        $docAction->actionClass = $actionClass;

        //$actionNamespace = $lib->getActionDefaultNamespace();
        //$actionClassWithoutPrefix = (string) str($actionClass)->removeLeft($actionNamespace);
        $docAction->tags = [
            //(string) str($actionClassWithoutPrefix)
            (string) str($actionClass)
                ->removeRight(Classes::short($actionClass))
                ->replace('\\', '-')
                ->trimRight('-')
                ->delimit('-'),
        ];

        if ($actionClassReflection->implementsInterface(SwagenActionTagsInterface::class)) {
            /** @var SwagenActionTagsInterface $actionClass */
            $docAction->tags = $actionClass::tagsForSwagger() ?: $docAction->tags;
        }

        $compiledRoute = $route->compile();
        $docAction->compiledRoute = $compiledRoute;

        if (Classes::implement($actionClass, SwagenActionExternalFormInterface::class)) {
            /* @var SwagenActionExternalFormInterface $actionClass */
            $actionFormClass = $actionClass::getFormClass();
            if (!\class_exists($actionFormClass)) {
                throw new \Exception("SKIPPED action $actionClass says that its form is $actionFormClass but this class is not exists");
                //$logger->warning('Action '.$actionClass.' says that its form is '.$actionFormClass.' but this class is not exists');
                //return null;
            }

            $docAction->inputFormClass = $actionFormClass;
            $logger->info('Action '.$actionClass.' uses custom form '.$actionFormClass);
        }

        /* @var SwagenActionInterface $actionClass */
        $responseClasses = $actionClass::producesResponses();
        foreach ($responseClasses as $responseHttpStatusCode => $responseClass) {

            if (!\is_int($responseHttpStatusCode) || $responseHttpStatusCode < 100) {
                $logger->warning("PROBLEM action $actionClass: Bad http code $responseHttpStatusCode in response $responseClass");
                //return null;
            }

            if (!\class_exists($responseClass)) {
                $logger->warning("Response $responseClass is not exists, in action $actionClass");
                $logger->warning("PROBLEM action $actionClass: Response $responseClass is not exists");
                continue;
                //return null;
            }

            $docAction->responses[$responseHttpStatusCode] = $responseClass;
        }

        if ($actionClassReflection->implementsInterface(SwagenActionProducesSimpleErrorResponsesInterface::class)) {
            /* @var SwagenActionProducesSimpleErrorResponsesInterface $actionClass */
            foreach ($actionClass::producesErrorResponses() as $code => $errorResponseDescription) {
                $docAction->responses[$code] = DefaultErrorResponse::class; // todo: how about $errorResponseDescription?
            }
        }
        return $docAction;
    }

    /**
     * @param string $pathPrefixToRemove
     *
     * @return static
     */
    public function setPathPrefixToRemove(?string $pathPrefixToRemove): self
    {
        $this->pathPrefixToRemove = $pathPrefixToRemove;
        return $this;
    }

}