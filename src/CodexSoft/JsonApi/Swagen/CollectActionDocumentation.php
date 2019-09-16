<?php /** @noinspection DuplicatedCode */


namespace CodexSoft\JsonApi\Swagen;

use CodexSoft\JsonApi\Response\DefaultErrorResponse;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionDescriptionInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionExternalFormInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionProducesSimpleErrorResponsesInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionTagsInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;
use CodexSoft\Code\Helpers\Classes;
use function CodexSoft\Code\str;

class CollectActionDocumentation extends AbstractCollector
{

    /** @var string */
    private $pathPrefixToRemove;

    public function collect(Route $route): ?ActionDocumentation
    {
        $docAction = new ActionDocumentation;

        $logger = $this->getLogger();
        $lib = $this->lib;

        $routePath = $route->getPath();
        $docAction->path = $routePath;

        //$defaultResponsesNamespace = $lib->getResponsesDefaultNamespace();
        $actionNamespace = $lib->getActionDefaultNamespace();

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
            $logger->warning('Failed to create ReflectionClass for '.$actionClass.': '.$e);
            return null;
        }

        if ($actionClassReflection->isAbstract()) {
            $logger->notice("$actionClassReflection action SKIPPING: class is abstract");
            return null;
        }

        if ($actionClassReflection->implementsInterface(SwagenActionDescriptionInterface::class)) {
            /** @var SwagenActionDescriptionInterface $actionClass */
            $docAction->description = $actionClass::descriptionForSwagger() ?: $actionClass;
        }

        if (!$actionClassReflection->implementsInterface(SwagenInterface::class)) {
            $logger->debug($actionClass.' action SKIPPING because it does not implement '.Classes::short(SwagenInterface::class));
            return null;
        }

        $logger->debug($actionClass.' action implements '.Classes::short(SwagenInterface::class));

        if (!$actionClassReflection->implementsInterface(SwagenActionInterface::class)) {
            $logger->warning($actionClass.' does not implement '.SwagenActionInterface::class);
            return null;
        }

        $logger->debug($actionClass.' action implements '.Classes::short(SwagenActionInterface::class));

        $docAction->actionClass = $actionClass;

        $actionClassWithoutPrefix = (string) str($actionClass)->removeLeft($actionNamespace);
        $docAction->tags = [
            (string) str($actionClassWithoutPrefix)
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
                $logger->warning('Action '.$actionClass.' says that its form is '.$actionFormClass.' but this class is not exists');
                return null;
            }

            $docAction->inputFormClass = $actionFormClass;
            $logger->info('Action '.$actionClass.' uses custom form '.$actionFormClass);
        }

        /* @var SwagenActionInterface $actionClass */
        $responseClasses = $actionClass::producesResponses();
        foreach ($responseClasses as $responseHttpStatusCode => $responseClass) {

            if (!\is_int($responseHttpStatusCode) || $responseHttpStatusCode < 100) {
                $logger->warning("Bad http code $responseHttpStatusCode for response $responseClass in action $actionClass");
                return null;
            }

            if (!\class_exists($responseClass)) {
                $logger->warning("Response $responseClass is not exists, in action $actionClass");
                return null;
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

    private function getLogger(): LoggerInterface
    {
        return $this->lib->getLogger();
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