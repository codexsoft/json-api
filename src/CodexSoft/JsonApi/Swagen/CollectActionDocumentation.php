<?php /** @noinspection DuplicatedCode */


namespace CodexSoft\JsonApi\Swagen;

use CodexSoft\Code\Helpers\Arrays;
use CodexSoft\JsonApi\Response\DefaultErrorResponse;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionDescriptionInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionExternalFormInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionProducesSimpleErrorResponsesInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionTagsInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
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

        $defaultResponsesNamespace = $lib->getResponsesDefaultNamespace();
        $actionNamespace = $lib->getActionDefaultNamespace();

        $path = $route->getPath();

        if ($this->pathPrefixToRemove) {
            $path = (string) str($path)->removeLeft($this->pathPrefixToRemove);
        }

        $methods = $route->getMethods();
        $method = Request::METHOD_POST;

        /* Допущение, что экшн заточен под один конкретный метод */
        if (\count($methods)) {
            $method = Arrays::tool()->getFirst($methods);
        }

        /* Вытаскиваем название класса экшна */
        $defaultController = $route->getDefault('_controller');
        $actionClass = null;
        if ($defaultController) {
            $actionClass = (string) str($defaultController)->removeRight('::__invoke');
        }

        /* skip generating definitions if class does not implement auto-generating interface */
        //if (!$actionClass || !\class_exists($actionClass) || !Classes::implement($actionClass, SwagenInterface::class)) {
        //    $logger->debug($actionClass.' action SKIPPING because it does not implement '.Classes::short(SwagenInterface::class));
        //    return null;
        //}

        try {
            $actionClassReflection = new \ReflectionClass($actionClass);
        } catch (\ReflectionException $e) {
            $logger->warning('Failed to create ReflectionClass for '.$actionClass.': '.$e);
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

        /*
         * Узнаем, если это возможно:
         * - форму для валидации данных, которую использует экшен
         * - респонзы, которые он отдает
         *
         * Респонзы было бы чудесно вытаскивать из @return, однако это нетривиальная задача,
         * с учетом того что имена классов могут быть импортированы (а обычно это так).
         */

        //$shortActionClass = Classes::short($actionClass);

        /* ВЫЧИСЛЯЕМ ТЕГИ, которые будут использованы для группировки документации к ендпойнтам */

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

        //if (Classes::implement($actionClass, SwagenActionTagsInterface::class)) {
        //    /** @var SwagenActionTagsInterface $actionClass */
        //    $docAction->tags = $actionClass::tagsForSwagger() ?: $docAction->tags;
        //}

        ///* Обрамляем теги в двойные кавычки */
        //\array_walk($requestTags, function (&$tag) {
        //    $tag = '"'.$tag.'"';
        //});
        //$requestTagsString = \implode(',', $requestTags);

        /* ВЫЧИСЛЯЕМ ОПИСАНИЕ ЭКШНА */

        //$lines = [
        //    ' * @SWG\\'.str($method)->toTitleCase().'(',
        //    ' *     path="'.$path.'",',
        //    ' *     tags={'.$requestTagsString.'},',
        //    ' *     summary="'.$path.'",',
        //    ' *     description="'.$routeDescription.'",',
        //];

        /* ВЫЧИСЛЯЕМ ПАРАМЕТРЫ ЭКШНА */

        /**
         * Поддержка path-параметров реализована на зачаточном уровне и поддерживает только
         * обязательные path-параметры, которые жестко считаются integer-овскими.
         *
         * if any path vars, assuming that is GET request
         * Скрестить описание GET-параметров и ссылку на структуру POST-JSON-а пока не удалось
         * todo: возможно ли совмещение path-переменных и формы в swagger-openAPI?
         */

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

        //$pathVars = $compiledRoute->getPathVariables();

        //if ($pathVars) {
        //    foreach ($pathVars as $pathVar) {
        //        \array_push($lines, ...[
        //            ' *     @SWG\Parameter(',
        //            ' *         type="integer",', // assuming is integer
        //            ' *         description="'.str($pathVar)->toTitleCase().'",',
        //            ' *         in="path",',
        //            ' *         name="'.$pathVar.'",',
        //            ' *         required=true,', // assuming is required
        //            ' *     ),',
        //        ]);
        //    }
        //} else if (Classes::implement($actionClass, SwagenActionExternalFormInterface::class)) {
        //    /* @var SwagenActionExternalFormInterface $actionClass */
        //    $actionFormClass = $actionClass::getFormClass();
        //    $docAction->inputFormClass = $actionFormClass;
        //    try {
        //        $actionFormReflectionClass = new \ReflectionClass($actionFormClass);
        //        $formTitleUnderscored = $lib->formTitle($actionFormReflectionClass);
        //        $lines[] = ' *     @SWG\Parameter(ref="#/parameters/'.$formTitleUnderscored.'"),';
        //        $this->getLogger()->info('Action '.$actionClass.' uses custom form '.$actionFormClass);
        //    } catch (\ReflectionException $e) {
        //        $this->getLogger()->warning('Action '.$actionClass.' says that its form is '.$actionFormClass.' but there was exception: '.$e->getMessage());
        //    }
        //
        //}

        /* ВЫЧИСЛЯЕМ ОТВЕТЫ, ГЕНЕРИРУЕМЫЕ ЭКШНОМ */

        // todo: responses can be parsed from actionClass
        // todo: maybe Zend ClassReflection can properly parse @return tag?

        /**
         * Все новые экшны имплементируют SwagenActionInterface.
         *
         * Класс экшна в producesResponses возвращает массив классов ответов, которые он может продуцировать.
         * Опционально, в качестве ключей передаются соответствующие HTTP-коды. Если HTTP-код не передан,
         * а класс ответа имлементирует SwagenResponseDefaultHttpCodeInterface, то будет использован
         * HTTP-код, возвращаемый в $responseClass->getSwaggerResponseDefaultHttpCode().
         *
         */

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

            //$responseHttpCode = 200; // default fallback...
            //
            ///**
            // * Для случая, когда producesResponses возвращает массив вида
            // * [ResponseA::class, ResponseB::class]
            // */
            //if (\class_exists($responseClass) && Classes::implement($responseClass, SwagenResponseInterface::class)) {
            //    /** @var SwagenResponseInterface $responseClass */
            //
            //    if (\is_int($responseHttpStatusCode) && ($responseHttpStatusCode >= 100)) {
            //        /**
            //         * Для случая, когда producesResponses возвращает массив вида
            //         * [200 => ResponseA::class, 404 => ResponseB::class]
            //         */
            //        $responseHttpCode = $responseHttpStatusCode;
            //    } elseif (Classes::implement($responseClass, SwagenResponseDefaultHttpCodeInterface::class)) {
            //        /** @var SwagenResponseDefaultHttpCodeInterface $responseClass */
            //        $responseHttpCode = $responseClass::getSwaggerResponseDefaultHttpCode();
            //        $logger->info("$actionClass action produced response $responseClass http code was not provided, used default $responseHttpCode from getSwaggerResponseDefaultHttpCode()");
            //    } else {
            //        $logger->warning("$actionClass produced response $responseClass http code is wrong or not set (and no default). Used hardcoded $responseHttpCode!");
            //    }
            //
            //    $suggestedResponseTitle = $responseClass;
            //
            //} else if (\is_int($responseHttpStatusCode)) {
            //
            //    if ($responseHttpStatusCode >= 100) {
            //        $responseHttpCode = $responseHttpStatusCode;
            //    }
            //
            //    $suggestedResponseTitle = $responseClass;
            //
            //} else {
            //    /* если строка, то это swagger-название класса */
            //    $suggestedResponseTitle = $responseHttpStatusCode;
            //    $responseHttpCode = Classes::getIsSameOrExtends($responseClass, DefaultErrorResponse::class) ? 400 : 200;
            //}
            //
            //$suggestedResponseTitle = (string) str($suggestedResponseTitle)
            //    ->replace('\\', '_')
            //    ->trimLeft('_');
            //
            //$logger->info('Action '.$routePath.' ('.$actionClassWithoutPrefix.') produces '.$responseHttpCode.' => '.$responseClass.' response');
            //$lines[] = ' *     @SWG\Response(response="'.$responseHttpCode.'", ref="#/responses/'.$suggestedResponseTitle.'"),';
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