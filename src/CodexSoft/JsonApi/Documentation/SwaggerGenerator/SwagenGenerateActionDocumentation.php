<?php

namespace CodexSoft\JsonApi\Documentation\SwaggerGenerator;

use CodexSoft\JsonApi\Documentation\Collector\ActionDoc;
use CodexSoft\JsonApi\Documentation\Collector\ResponseDoc;
use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SwagenLib;
use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SymfonyGenerateFormDocumentation;
use CodexSoft\JsonApi\Form\AbstractForm;
use CodexSoft\Code\Helpers\Classes;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionDescriptionInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionTagsInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionExternalFormInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionProducesErrorCodesInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenResponseDefaultHttpCodeInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenResponseInterface;
use CodexSoft\JsonApi\Response\DefaultErrorResponse;
use CodexSoft\Code\Helpers\Arrays;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use function CodexSoft\Code\str;

/**
 * Генерация документации к запросам (экшнам)
 */
class SwagenGenerateActionDocumentation
{

    /** @var SwagenLib */
    private $lib;

    /** @var string */
    private $pathPrefixToRemove;

    private function getLogger(): LoggerInterface
    {
        return $this->lib->getLogger();
    }

    public function __construct(SwagenLib $lib)
    {
        $this->lib = $lib;
    }

    /**
     * Сгенерирует массив строк, описывающих экшн через SwaggerPHP аннотации. Предполагается, что
     * эти строки будут вставлены в Definitions.php в числе прочих API аннотаций.
     *
     * @param Route $route
     *
     * @return string[]
     * @throws \ReflectionException
     */
    public function generate(Route $route): ?array
    {
        $routePath = $route->getPath();
        $logger = $this->getLogger();
        $lib = $this->lib;
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
        if (!$actionClass || !\class_exists($actionClass) || !Classes::implement($actionClass, SwagenInterface::class)) {
            $this->getLogger()->debug($actionClass.' action SKIPPING because it does not implement SwagenInterface');
            return null;
        }

        $logger->debug($actionClass.' action implements '.Classes::short(SwagenInterface::class));

        /*
         * Узнаем, если это возможно:
         * - форму для валидации данных, которую использует экшен
         * - респонзы, которые он отдает
         *
         * Респонзы было бы чудесно вытаскивать из @return, однако это нетривиальная задача,
         * с учетом того что имена классов могут быть импортированы (а обычно это так).
         */

        $shortActionClass = Classes::short($actionClass);

        /* ВЫЧИСЛЯЕМ ТЕГИ, которые будут использованы для группировки документации к ендпойнтам */

        $actionClassWithoutPrefix = (string) str($actionClass)->removeLeft($actionNamespace);
        $requestTags = [
            (string) str($actionClassWithoutPrefix)
                ->removeRight($shortActionClass)
                ->replace('\\', '-')
                ->trimRight('-')
                ->delimit('-'),
        ];
        if (Classes::implement($actionClass, SwagenActionTagsInterface::class)) {
            /** @var SwagenActionTagsInterface $actionClass */
            $requestTags = $actionClass::tagsForSwagger() ?: $requestTags;
        }

        /* Обрамляем теги в двойные кавычки */
        \array_walk($requestTags, function (&$tag) {
            $tag = '"'.$tag.'"';
        });
        $requestTagsString = \implode(',', $requestTags);

        /* ВЫЧИСЛЯЕМ ОПИСАНИЕ ЭКШНА */

        $routeDescription = $actionClass;
        if (Classes::implement($actionClass, SwagenActionDescriptionInterface::class)) {
            /** @var SwagenActionDescriptionInterface $actionClass */
            $routeDescription = $actionClass::descriptionForSwagger() ?: $routeDescription;
        }

        $lines = [
            ' * @SWG\\'.str($method)->toTitleCase().'(',
            ' *     path="'.$path.'",',
            ' *     tags={'.$requestTagsString.'},',
            ' *     summary="'.$path.'",',
            ' *     description="'.$routeDescription.'",',
        ];

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
        $pathVars = $compiledRoute->getPathVariables();

        if ($pathVars) {
            foreach ($pathVars as $pathVar) {
                \array_push($lines, ...[
                    ' *     @SWG\Parameter(',
                    ' *         type="integer",', // assuming is integer
                    ' *         description="'.str($pathVar)->toTitleCase().'",',
                    ' *         in="path",',
                    ' *         name="'.$pathVar.'",',
                    ' *         required=true,', // assuming is required
                    ' *     ),',
                ]);
            }
        } else if (Classes::implement($actionClass, SwagenActionExternalFormInterface::class)) {

            /* @var SwagenActionExternalFormInterface $actionClass */
            $actionFormClass = $actionClass::getFormClass();
            try {
                $actionFormReflectionClass = new \ReflectionClass($actionFormClass);
                $formTitleUnderscored = $lib->formTitle($actionFormReflectionClass);
                $lines[] = ' *     @SWG\Parameter(ref="#/parameters/'.$formTitleUnderscored.'"),';
                $this->getLogger()->info('Action '.$actionClass.' uses custom form '.$actionFormClass);
            } catch (\ReflectionException $e) {
                $this->getLogger()->warning('Action '.$actionClass.' says that its form is '.$actionFormClass.' but there was exception: '.$e->getMessage());
            }

        } else {

            /*
             * Форма явно не указана, fallback до соглашения что форма Action-а App\Action\Foo\Bar\Baz
             * располагается в App\Form\Foo\Bar\Baz
             *
             * Предполагаем, что для валидации входных данных используется форма
             * @SWG\Parameter(ref="#/parameters/TransportRequest_PublishForm"),
             */
            $formNamespace = $lib->getFormsDefaultNamespace();
            $actionFormClass = $formNamespace.$actionClassWithoutPrefix.'Form';
            if (\class_exists($actionFormClass) && \is_subclass_of($actionFormClass, AbstractForm::class)) {
                $formTitleUnderscored = str($formNamespace.$actionClassWithoutPrefix)->replace('\\', '_').'Form';
                $lines[] = ' *     @SWG\Parameter(ref="#/parameters/'.$formTitleUnderscored.'"),';
                $this->getLogger()->info('Action '.$actionClass.' uses name-based form '.$formTitleUnderscored);
            } else {
                $this->getLogger()->notice("$actionClass action: Unable to detect which form is used as input data by action! (checked fallback $actionFormClass)");
            }

        }

        /* ВЫЧИСЛЯЕМ ОТВЕТЫ, ГЕНЕРИРУЕМЫЕ ЭКШНОМ */

        // todo: responses can be parsed from actionClass
        // todo: maybe Zend ClassReflection can properly parse @return tag?

        /**
         * Все новые экшны имплементируют SwagenActionInterface.
         */
        $actionClassReflection = new \ReflectionClass($actionClass);
        //if (Classes::implement($actionClass, SwagenActionInterface::class)) {
        if ($actionClassReflection->implementsInterface(SwagenActionInterface::class)) {

            /* @var SwagenActionInterface $actionClass */

            /**
             * Класс экшна в producesResponses возвращает массив классов ответов, которые он может продуцировать.
             * Опционально, в качестве ключей передаются соответствующие HTTP-коды. Если HTTP-код не передан,
             * а класс ответа имлементирует SwagenResponseDefaultHttpCodeInterface, то будет использован
             * HTTP-код, возвращаемый в $responseClass->getSwaggerResponseDefaultHttpCode().
             *
             */
            $responseClasses = $actionClass::producesResponses();
            foreach ($responseClasses as $responseTitle => $responseClass) {

                $responseHttpCode = 200; // default fallback...

                /**
                 * Для случая, когда producesResponses возвращает массив вида
                 * [ResponseA::class, ResponseB::class]
                 */
                if (\class_exists($responseClass) && Classes::implement($responseClass, SwagenResponseInterface::class)) {
                    /** @var SwagenResponseInterface $responseClass */

                    if (\is_int($responseTitle) && ($responseTitle >= 100)) {
                        /**
                         * Для случая, когда producesResponses возвращает массив вида
                         * [200 => ResponseA::class, 404 => ResponseB::class]
                         */
                        $responseHttpCode = $responseTitle;
                    } elseif (Classes::implement($responseClass, SwagenResponseDefaultHttpCodeInterface::class)) {
                        /** @var SwagenResponseDefaultHttpCodeInterface $responseClass */
                        $responseHttpCode = $responseClass::getSwaggerResponseDefaultHttpCode();
                        $logger->info("$actionClass action produced response $responseClass http code was not provided, used default $responseHttpCode from getSwaggerResponseDefaultHttpCode()");
                    } else {
                        $logger->warning("$actionClass produced response $responseClass http code is wrong or not set (and no default). Used hardcoded $responseHttpCode!");
                    }

                    $suggestedResponseTitle = $responseClass;

                } else if (\is_int($responseTitle)) {

                    if ($responseTitle >= 100) {
                        $responseHttpCode = $responseTitle;
                    }

                    $suggestedResponseTitle = $responseClass;

                } else {
                    // если строка, то это swagger-название класса
                    $suggestedResponseTitle = $responseTitle;
                    $responseHttpCode = Classes::getIsSameOrExtends($responseClass, DefaultErrorResponse::class) ? 400 : 200;
                }

                $suggestedResponseTitle = (string) str($suggestedResponseTitle)
                    ->replace('\\', '_')
                    ->trimLeft('_');

                $logger->info('Action '.$routePath.' ('.$actionClassWithoutPrefix.') produces '.$responseHttpCode.' => '.$responseClass.' response');
                $lines[] = ' *     @SWG\Response(response="'.$responseHttpCode.'", ref="#/responses/'.$suggestedResponseTitle.'"),';
            }

            /**
             * Документирование ответов об ошибках на основании кодов ошибок.
             *
             * Этот SwagenActionProducesErrorCodesInterface появился позже, и основан на положении,
             * что экшн можен возвращать только один успешный ответ конкретно заданной структуры и
             * 0..N ответов об ошибках, различающихся только кодом ошибки. Получая возвращаемые коды
             * ошибок, мы документируем ответы об ошибках, последовательно присваивая каждому HTTP
             * код, начиная от 400.
             *
             * Ранее HTTP код присваивался аналогичным коду ошибки, от него ушли, поскольку кодов
             * ошибок было много (это глобальный список на все API личного кабинета, и, нумеруя их
             * также от 400, быстро доходили до 500 и далее, а HTTP коды 5xx, 6xx — это уже не про
             * ошибки, они в документации и выглядят иначе. Плюс к этому, мы все равно оставались
             * ограниченными набором HTTP-кодов, от 100 до 599 — другие генератором
             * HTML-документации не принимаются). Но такое старое поведение можно вернуть, если в
             * настройках библиотеки указать $this->lib->setGenerateFakeHttpErrorCode(true).
             */
            if (Classes::implement($actionClass, SwagenActionProducesErrorCodesInterface::class)) {

                /* @var SwagenActionProducesErrorCodesInterface $actionClass */
                $errorCodes = $actionClass::producesErrorCodes();

                /* @var DefaultErrorResponse $errorCodesClass */
                $errorCodesClass = $actionClass::errorCodesClass();

                $parser = new SymfonyGenerateFormDocumentation($lib);
                $responseSchemaContent = $parser->parseIntoSchema($errorCodesClass);

                $fakeErrorHttpCode = 400;
                foreach ($errorCodes as $errorCode) {

                    $responseSchemaLinesWithSpecifiedErrorCode = $responseSchemaContent;
                    array_walk($responseSchemaLinesWithSpecifiedErrorCode, function (&$line) use ($errorCode) {
                        if (str($line)->contains('property="error_no"')) {
                            $line = (string) str($line)->replace('minimum=0', 'enum={'.$errorCode.'}');
                        }
                    });

                    $httpErrorCode = $this->lib->isGenerateFakeHttpErrorCode() ? $fakeErrorHttpCode : $errorCode;

                    $errorCodeDescription = $errorCodesClass::getErrorMessageByStatusCode($errorCode) ?? 'No description';
                    // reference cannot be used here: we'll miss description, as referenced schema will OVERRIDE it
                    $lines[] = ' *       @SWG\Response(response="'.$httpErrorCode.'", description="'.$errorCodeDescription.' ('.$errorCode.')",';
                    $lines[] = ' *       @SWG\Schema(';
                    \array_push($lines, ...$responseSchemaLinesWithSpecifiedErrorCode);
                    $lines[] = ' *       )';
                    $lines[] = ' *     ),';

                    $fakeErrorHttpCode++;
                }

            }

        } else {

            /**
             * Legacy: fallback до соглашения что формы респонзов для Action-а App\Action\Foo\Bar\Baz лежат в:
             * App\Form\Foo\Bar\BazOkResponse
             * App\Form\Foo\Bar\BazFailResponse
             */

            $logger->notice("$actionClass action responses are not described, assuming 200 => ".$defaultResponsesNamespace.$actionClassWithoutPrefix.'OkResponse');
            $lib->generateResponseRef($defaultResponsesNamespace.$actionClassWithoutPrefix.'OkResponse', $lines, $logger, 200);

            $logger->notice("$actionClass action responses are not described, assuming 400 => ".$defaultResponsesNamespace.$actionClassWithoutPrefix.'FailResponse');
            $lib->generateResponseRef($defaultResponsesNamespace.$actionClassWithoutPrefix.'FailResponse', $lines, $logger, 400);
        }

        $lines[] = ' * )';
        $lines[] = ' *';

        // todo: search examples in /docs/requests using known actionClass?
        return $lines;
    }

    /**
     * @param string $pathPrefixToRemove
     *
     * @return SwagenGenerateActionDocumentation
     */
    public function setPathPrefixToRemove(?string $pathPrefixToRemove): SwagenGenerateActionDocumentation
    {
        $this->pathPrefixToRemove = $pathPrefixToRemove;
        return $this;
    }

}