<?php
namespace CodexSoft\JsonApi\Operations;

use CodexSoft\Code\Classes\Classes;
use CodexSoft\Code\Strings\Strings;
use CodexSoft\JsonApi\JsonApiSchema;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use function Stringy\create as str;
use const CodexSoft\Shortcut\TAB;

class CreateActionOperation implements LoggerAwareInterface
{
    public const STYLE_HANDLE = 'handle';
    public const STYLE_INVOKE = 'invoke';

    // arguments
    private string $newActionName;
    private ?string $route;
    private JsonApiSchema $jsonApiSchema;
    private bool $allowEmptyForm = false;
    private string $style = self::STYLE_HANDLE;
    private LoggerInterface $logger;

    // privates
    private string $fqnActionClass;
    private string $fqnActionFormClass;
    private string $fqnActionResponseClass;
    private string $actionNamespace;
    private string $actionDir;
    private Filesystem $fs;

    /**
     * @return void
     */
    public function execute(): void
    {
        if (!isset($this->newActionName) || !$this->newActionName) {
            throw new \InvalidArgumentException('Action name cannot be blank');
        }

        if (!isset($this->jsonApiSchema)) {
            throw new \InvalidArgumentException('Required jsonApiSchema is not provided');
        }

        if (!isset($this->logger)) {
            $this->logger = new NullLogger();
        }

        $this->fs = new Filesystem();
        $this->logger->debug('Actions path: '.$this->jsonApiSchema->getPathToActions());

        $actionClass = (string) str($this->newActionName)->replace('/','\\')->replace('.','\\');
        $actionClassParts = explode('\\',$actionClass);
        \array_walk($actionClassParts, function (&$part) {
            $part = \ucfirst($part);
        });
        $actionClass = implode('\\',$actionClassParts);

        $actionNamespaceParts = $actionClassParts;
        \array_pop($actionNamespaceParts);
        $actionNamespace = implode('\\',$actionNamespaceParts);

        $baseActionsNamespace = $this->jsonApiSchema->getNamespaceActions();

        if ($actionNamespace) {
            $this->fqnActionClass = $baseActionsNamespace.'\\'.$actionClass;
        } else {
            $this->fqnActionClass = $baseActionsNamespace.$actionClass;
        }

        $this->fqnActionFormClass = JsonApiSchema::generateActionFormClass($this->fqnActionClass);
        $this->fqnActionResponseClass = JsonApiSchema::generateResponseFormClass($this->fqnActionClass);

        $this->actionDir = $this->jsonApiSchema->getPathToActions().'/'.Strings::bs2s($actionNamespace);
        $this->actionNamespace = Classes::getNamespace($this->fqnActionClass);

        $this->logger->debug('Action class: '.$this->fqnActionClass);

        $this->writeActionClassFile();
        $this->writeActionFormClassFile();
        $this->writeActionResponseClassFile();
    }

    /**
     * @param string $route
     *
     * @return CreateActionOperation
     */
    public function setRoute(?string $route): CreateActionOperation
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @param bool $allowEmptyForm
     *
     * @return CreateActionOperation
     */
    public function setAllowEmptyForm(bool $allowEmptyForm): CreateActionOperation
    {
        $this->allowEmptyForm = $allowEmptyForm;
        return $this;
    }

    /**
     * @param string $style
     *
     * @return CreateActionOperation
     */
    public function setStyle(string $style): CreateActionOperation
    {
        $this->style = $style;
        return $this;
    }

    /**
     * @param string $newActionName
     *
     * @return CreateActionOperation
     */
    public function setNewActionName(string $newActionName): CreateActionOperation
    {
        $this->newActionName = $newActionName;
        return $this;
    }

    /**
     * @param JsonApiSchema $jsonApiSchema
     *
     * @return static
     */
    public function setJsonApiSchema(JsonApiSchema $jsonApiSchema): self
    {
        $this->jsonApiSchema = $jsonApiSchema;
        return $this;
    }

    /**
     * @return string
     */
    protected function generateActionClassCode(): string
    {
        $documentedFormActionClass = $this->jsonApiSchema->baseActionClass;
        $jsonResponseClass = \Symfony\Component\HttpFoundation\JsonResponse::class;
        $responseClass = \Symfony\Component\HttpFoundation\Response::class;
        $routeAnnotationClass = \Symfony\Component\Routing\Annotation\Route::class;

        // todo: $use array
        $code = [
            '<?php',
            '',
            "namespace {$this->actionNamespace};",
            '',
            "use {$documentedFormActionClass};", // todo: $this->actionNamespace.DocumentedFormAction::class ?
            "use {$responseClass};",
            "use {$jsonResponseClass};",
            "use {$routeAnnotationClass};",
            'use '.$this->fqnActionResponseClass.';',
            '',
            '/**',
            ' * @Route("'.$this->route.'")',
            ' */',
            'class '.Classes::short($this->fqnActionClass).' extends '.Classes::short($documentedFormActionClass),
            '{',
            TAB."protected static \$inputStatus = self::STATE_INPUT_NOT_IMPLEMENTED;",
            TAB."protected static \$outputStatus = self::STATE_OUTPUT_NOT_IMPLEMENTED;",
            TAB."protected static \$swagenDescription = ''; // todo",
            TAB.'protected static $allowEmptyForm = '.($this->allowEmptyForm ? 'true' : 'false').';',
            TAB.'',
            TAB.'public function handle(array $data, array $extraData = []): Response',
            TAB.'{',
            TAB.TAB.'return new JsonResponse([\'data\' => []]);',
            TAB.'}',
            TAB.'',
            '}',
        ];

        return implode("\n", $code);
    }

    /**
     * @return string
     */
    protected function generateOldActionClassCode(): string
    {
        $documentedFormActionClass = $this->jsonApiSchema->baseActionClass;
        $jsonResponseClass = \Symfony\Component\HttpFoundation\JsonResponse::class;
        $responseClass = \Symfony\Component\HttpFoundation\Response::class;
        $routeAnnotationClass = \Symfony\Component\Routing\Annotation\Route::class;

        // todo: $use array
        $code = [
            '<?php',
            '',
            "namespace {$this->actionNamespace};",
            '',
            "use {$documentedFormActionClass};", // todo: $this->actionNamespace.DocumentedFormAction::class ?
            "use {$responseClass};",
            "use {$jsonResponseClass};",
            "use {$routeAnnotationClass};",
            'use '.$this->fqnActionResponseClass.';',
            '',
            '/**',
            ' * @Route("'.$this->route.'")',
            ' */',
            'class '.Classes::short($this->fqnActionClass).' extends '.Classes::short($documentedFormActionClass),
            '{',
            TAB."protected static \$inputStatus = self::STATE_INPUT_NOT_IMPLEMENTED;",
            TAB."protected static \$outputStatus = self::STATE_OUTPUT_NOT_IMPLEMENTED;",
            TAB."protected static \$swagenDescription = ''; // todo",
            TAB.'protected static $allowEmptyForm = '.($this->allowEmptyForm ? 'true' : 'false').';',
            TAB.'',
            TAB.'/**',
            TAB.' * @return \\'.JsonResponse::class,
            TAB.' */',
            TAB.'public function __invoke(): '.Classes::short($responseClass),
            TAB.'{',
            TAB.TAB.'$data = $this->getJsonData();',
            TAB.TAB.'if ($data instanceof '.Classes::short($responseClass).') { return $data; }',
            TAB.TAB.'if ($this->isResponseExampleRequested()) { return $this->generateResponseExample(); }',
            TAB.TAB.'',
            TAB.TAB.'return new '.Classes::short($this->fqnActionResponseClass).'([]);',
            TAB.'}',
            TAB.'',
            '}',
        ];

        return implode("\n", $code);
    }

    /**
     *
     */
    protected function writeActionClassFile(): void
    {
        if (\class_exists($this->fqnActionClass)) {
            throw new \RuntimeException("Action class {$this->fqnActionClass} already exists!");
        }

        $actionFile = $this->actionDir.'/'.Classes::short($this->fqnActionClass).'.php';
        if (\file_exists($actionFile)) {
            throw new \RuntimeException("Action file $actionFile already exists!");
        }
        $this->logger->debug("Will be written to $actionFile");

        switch ($this->style) {
            case self::STYLE_INVOKE:
                $this->fs->dumpFile($actionFile, $this->generateOldActionClassCode());
                break;

            case self::STYLE_HANDLE:
            default:
                $this->fs->dumpFile($actionFile, $this->generateActionClassCode());
                break;
        }

    }

    protected function generateActionFormClassCode()
    {
        $baseFormClass = $this->jsonApiSchema->baseActionFormClass;
        $fieldClass = $this->jsonApiSchema->fieldHelperClass;
        $swagenInterface = \CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenInterface::class;
        $formBuilderInterface = FormBuilderInterface::class;

        $code = [
            '<?php',
            '',
            "namespace {$this->actionNamespace};",
            '',
            "use {$baseFormClass};",
            "use {$fieldClass};",
            "use {$swagenInterface};",
            "use {$formBuilderInterface};",
            '',
            'class '.Classes::short($this->fqnActionFormClass).' extends '.Classes::short($baseFormClass).' implements '.Classes::short($swagenInterface),
            '{',
            TAB.'',
            TAB.'public function buildForm('.Classes::short($formBuilderInterface).' $builder, array $options)',
            TAB.'{',
            TAB.TAB.'parent::buildForm($builder, $options);',
            TAB.TAB.Classes::short($fieldClass).'::import($builder, [',
            TAB.TAB.TAB."'name' => ".Classes::short($fieldClass).'::text(),',
            TAB.TAB.']);',
            TAB.'}',
            TAB.'',
            '}',
        ];
        return implode("\n", $code);
    }

    protected function writeActionFormClassFile(): void
    {
        if (\class_exists($this->fqnActionFormClass)) {
            throw new \RuntimeException("Action form class {$this->fqnActionFormClass} already exists!");
        }

        $actionFormFile = $this->actionDir.'/'.Classes::short($this->fqnActionFormClass).'.php';
        if (\file_exists($actionFormFile)) {
            throw new \RuntimeException("Action form file $actionFormFile already exists!");
        }
        $this->logger->debug("Will be written to $actionFormFile");

        $this->fs->dumpFile($actionFormFile, $this->generateActionFormClassCode());
    }

    protected function generateActionResponseFormClassCode(): string
    {
        $fieldClass = $this->jsonApiSchema->fieldHelperClass;
        $formBuilderInterface = FormBuilderInterface::class;
        $baseSuccessResponseClass = $this->jsonApiSchema->baseSuccessResponseClass;

        $code = [
            '<?php',
            '',
            "namespace {$this->actionNamespace};",
            '',
            "use {$fieldClass};",
            "use {$formBuilderInterface};",
            "use {$baseSuccessResponseClass};",
            '',
            'class '.Classes::short($this->fqnActionResponseClass).' extends '.Classes::short($baseSuccessResponseClass),
            '{',
            '',
            TAB.'public static function construct(array $data)',
            TAB.'{',
            TAB.TAB.'return new static($data);',
            TAB.'}',
            '',
            TAB.'public static function getSwaggerResponseDescription(): string',
            TAB.'{',
            TAB.TAB."return ''; // todo: describe what this response mean",
            TAB.'}',
            '',
            TAB.'public function buildForm('.Classes::short($formBuilderInterface).' $builder, array $options)',
            TAB.'{',
            TAB.TAB.Classes::short($fieldClass).'::import($builder, [',
            TAB.TAB.TAB.'// todo: define response form fields',
            TAB.TAB.TAB."'id' => ".Classes::short($fieldClass)."::id('Created requirement ID')->notBlank(),",
            TAB.TAB.']);',
            TAB.TAB.TAB.'parent::buildForm($builder, $options);',
            TAB.'}',
            '',
            '}',
        ];

        return implode("\n", $code);
    }

    protected function writeActionResponseClassFile(): void
    {
        if (\class_exists($this->fqnActionResponseClass)) {
            throw new \RuntimeException("Response form class {$this->fqnActionResponseClass} already exists!");
        }

        $actionResponseFile = $this->actionDir.'/'.Classes::short($this->fqnActionResponseClass).'.php';
        if (\file_exists($actionResponseFile)) {
            throw new \RuntimeException("Action response file $actionResponseFile already exists!");
        }
        $this->logger->debug("Will be written to $actionResponseFile");

        $this->fs->dumpFile($actionResponseFile, $this->generateActionResponseFormClassCode());
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return static
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }
}
