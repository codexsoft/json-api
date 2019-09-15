<?php /** @noinspection SlowArrayOperationsInLoopInspection */

namespace CodexSoft\JsonApi\Swagen;

use CodexSoft\JsonApi\Swagen\Interfaces\SwagenResponseDefaultHttpCodeInterface;
use CodexSoft\Code\Helpers\Files;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Routing\Router;
use function CodexSoft\Code\str;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * Class SwagenLib
 * Generate swagger documentation from forms
 */
class SwagenLib
{

    public const CONVERTER = [
        Type\CheckboxType::class => 'boolean',
        Type\ChoiceType::class => 'mixed',
        Type\CollectionType::class => 'array',
        Type\DateType::class => 'string',
        Type\EmailType::class => 'string',
        Type\IntegerType::class => 'integer',
        Type\NumberType::class => 'integer',
        Type\PercentType::class => 'integer',
        Type\TextareaType::class => 'string',
        Type\TextType::class => 'string',
        Type\TimeType::class => 'string',
        Type\UrlType::class => 'string',
        \CodexSoft\JsonApi\Form\Type\JsonType\JsonType::class => 'object',
    ];

    private $responsesDefaultNamespace = 'App\\Response\\';
    private $formsDefaultNamespace = 'App\\Form\\';
    private $actionDefaultNamespace = 'App\\Action\\';

    /** @var FormFactory */
    private $formFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var Router */
    private $router;

    /**
     * @var bool
     * TRUE: генерировать HTTP-коды ошибок FALSE: подставлять код ошибки в качестве HTTP-кода
     *     генерируемого Error-response
     */
    private $generateFakeHttpErrorCode = true;

    /**
     * Сгенерировать документацию
     *
     * @param string $rootDir
     * @param string $destinationPathFromRoot
     * @param null|string $pathPrefixToRemove
     *
     * @throws \Throwable
     */
    public function generateApiDocumentation(string $rootDir, string $destinationPathFromRoot, ?string $pathPrefixToRemove): void
    {
        (new SwagenGenerateApiDocumentation($this))
            ->setPathPrefixToRemove($pathPrefixToRemove)
            ->setDestinationFile($destinationPathFromRoot)
            ->setRootDir($rootDir)
            ->execute();
    }

    public function getResponsesDefaultNamespace(): string
    {
        return $this->responsesDefaultNamespace;
    }

    /**
     * @param FormFactory $formFactory
     *
     * @return SwagenLib
     */
    public function setFormFactory(FormFactory $formFactory): SwagenLib
    {
        $this->formFactory = $formFactory;
        return $this;
    }

    /**
     * @return FormFactory
     */
    public function getFormFactory(): FormFactory
    {
        return $this->formFactory;
    }

    public function getFormsDefaultNamespace(): string
    {
        return $this->formsDefaultNamespace;
    }

    public function getActionDefaultNamespace(): string
    {
        return $this->actionDefaultNamespace;
    }

    /**
     * Найти все классы, расположенные в заданном пути
     *
     * @param array $files массив файлов, где, предположительно, располагаются классы
     * @param $namespace
     * @param $path
     *
     * @return array
     */
    public function findClassesInFiles(array $files, $namespace, string $path): array
    {
        $classes = [];
        foreach ($files as $key => $value) {

            if (\is_array($value)) {
                $classes = \array_merge($classes, $this->findClassesInFiles($files[$key], $namespace.$key.'\\', $path.'/'.$key));
            } else {
                $fullFilePath = $path.'/'.$value;
                if (!(pathinfo($fullFilePath, PATHINFO_EXTENSION) === 'php')) {
                    continue;
                }
                $className = Files::removeExtension($value);
                //$fqnClassName = $namespace.'\\'.$className;
                $fqnClassName = $namespace.$className;
                $classes[] = $fqnClassName;
            }
        }
        return $classes;
    }

    /**
     * Найти все классы, расположенные в заданном пути
     *
     * @param string $path
     * @param $namespace
     *
     * @return array
     */
    public function findClassesInPath(string $path, $namespace = ''): array
    {
        $files = Files::listFiles($path, false); // ищем не рекурсивно

        $classes = [];

        foreach ($files as $fileName) {

            $fullFilePath = $path.'/'.$fileName;
            if (\is_dir($fullFilePath)) {
                $classes = \array_merge($classes, $this->findClassesInPath($path.'/'.$fileName, $namespace.$fileName.'\\'));
            } else {

                // убедимся что файл — PHP
                if (!(pathinfo($fileName, PATHINFO_EXTENSION) === 'php')) {
                    continue;
                }

                $className = Files::removeExtension($fileName);
                $fqnClassName = $namespace.$className;
                $classes[] = $fqnClassName;
            }

        }

        return $classes;
    }

    public function formTitle(\ReflectionClass $reflectionClass): string
    {
        return (string) str($reflectionClass->getName())
            ->replace('\\', '_')
            ->removeLeft('_')
            ->removeRight('_');
    }

    public function referenceToDefinition(\ReflectionClass $reflectionClass): string
    {
        return '#/definitions/'.$this->formTitle($reflectionClass);
    }

    public function detectSwaggerTypeFromNativeType($class): ?string
    {
        if (\array_key_exists($class, self::CONVERTER)) {
            return self::CONVERTER[$class];
        }
        return null;
    }

    public function typeClassToSwaggerType($class): ?string
    {
        $detected = $this->detectSwaggerTypeFromNativeType($class);
        return $detected ?? 'string';
    }

    public function generateResponseRef(string $responseClass, &$routeLines, LoggerInterface $logger, int $defaultHttpCode): void
    {
        //$suggestedResponseClass = $this->getResponsesDefaultNamespace().$suggestedResponseShortClass;

        if (class_exists($responseClass)) {
            try {
                $responseReflectionClass = new \ReflectionClass($responseClass);
            } catch (\ReflectionException $e) {
                return;
            }
            $logger->info('- produces default '.$responseClass.' response');
            $responseHttpCode = $defaultHttpCode;
            if ($responseReflectionClass->implementsInterface(SwagenResponseDefaultHttpCodeInterface::class)) {
                /** @var SwagenResponseDefaultHttpCodeInterface $responseClass */
                $responseHttpCode = $responseClass::getSwaggerResponseDefaultHttpCode();
            }
            $suggestedResponseTitle = (string) str($responseClass)->replace('\\', '_');
            $routeLines[] = ' *     @SWG\Response(response="'.$responseHttpCode.'", ref="#/responses/'.$suggestedResponseTitle.'"),';
        }
    }

    /**
     * @param string $responsesDefaultNamespace
     *
     * @return SwagenLib
     */
    public function setResponsesDefaultNamespace(string $responsesDefaultNamespace): SwagenLib
    {
        $this->responsesDefaultNamespace = $responsesDefaultNamespace;
        return $this;
    }

    /**
     * @param string $formsDefaultNamespace
     *
     * @return SwagenLib
     */
    public function setFormsDefaultNamespace(string $formsDefaultNamespace): SwagenLib
    {
        $this->formsDefaultNamespace = $formsDefaultNamespace;
        return $this;
    }

    /**
     * @param string $actionDefaultNamespace
     *
     * @return SwagenLib
     */
    public function setActionDefaultNamespace(string $actionDefaultNamespace): SwagenLib
    {
        $this->actionDefaultNamespace = $actionDefaultNamespace;
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return SwagenLib
     */
    public function setLogger(LoggerInterface $logger): SwagenLib
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        if (!$this->logger instanceof LoggerInterface) {
            $this->logger = new NullLogger;
        }
        return $this->logger;
    }

    /**
     * @param Router $router
     *
     * @return SwagenLib
     */
    public function setRouter(Router $router): SwagenLib
    {
        $this->router = $router;
        return $this;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * @param bool $generateFakeHttpErrorCode
     *
     * @return SwagenLib
     */
    public function setGenerateFakeHttpErrorCode(bool $generateFakeHttpErrorCode): SwagenLib
    {
        $this->generateFakeHttpErrorCode = $generateFakeHttpErrorCode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isGenerateFakeHttpErrorCode(): bool
    {
        return $this->generateFakeHttpErrorCode;
    }

}