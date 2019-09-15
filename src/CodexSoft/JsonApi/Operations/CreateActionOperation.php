<?php
namespace CodexSoft\JsonApi\Operations;

use CodexSoft\Code\Helpers\Classes;
use CodexSoft\Code\Helpers\Strings;
use CodexSoft\Code\Shortcuts;
use CodexSoft\Code\Traits\Loggable;
use CodexSoft\JsonApi\Form\AbstractForm;
use CodexSoft\JsonApi\Form\BaseField;
use CodexSoft\JsonApi\Response\DefaultSuccessResponse;
use CodexSoft\OperationsSystem\Exception\OperationException;
use CodexSoft\OperationsSystem\Operation;
use CodexSoft\JsonApi\JsonApiSchema;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use function CodexSoft\Code\str;
use const CodexSoft\Code\TAB;

/**
 * Class CreateActionOperation
 * todo: Write description — what this operation for
 * @method void execute() todo: change void to handle() method return type if other
 */
class CreateActionOperation extends Operation
{
    use Loggable;

    public const ID = 'e115e45a-aa2a-4473-a4e0-9e4398cb3215';
    protected const ERROR_PREFIX = 'CreateActionOperation cannot be completed: ';

    /** @var string */
    private $newActionName;

    /** @var string */
    private $fqnActionClass;

    /** @var string */
    private $fqnActionFormClass;

    /** @var string */
    private $fqnActionResponseClass;

    /** @var string */
    private $actionNamespace;

    /** @var string */
    private $actionDir;

    /** @var string */
    private $route;

    /** @var JsonApiSchema */
    private $webServerSchema;

    /** @var Filesystem */
    private $fs;

    /**
     * @param string $route
     *
     * @return CreateActionOperation
     */
    public function setRoute(string $route): CreateActionOperation
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @throws OperationException
     */
    protected function validateInputData(): void
    {
        $this->assert($this->newActionName, 'Action name cannot be blank');
    }

    /**
     * @return void
     * @throws OperationException
     */
    protected function handle(): void
    {
        Shortcuts::register();

        $this->fs = new Filesystem();
        $this->logger->debug('Actions path: '.$this->webServerSchema->getPathToActions());

        $actionClass = (string) str($this->newActionName)->replace('/','\\')->replace('.','\\');
        $actionClassParts = explode('\\',$actionClass);
        \array_walk($actionClassParts, function (&$part) {
            $part = \ucfirst($part);
        });
        $actionClass = implode('\\',$actionClassParts);

        $actionNamespaceParts = $actionClassParts;
        \array_pop($actionNamespaceParts);
        $actionNamespace = implode('\\',$actionNamespaceParts);

        $baseActionsNamespace = $this->webServerSchema->getNamespaceActions();

        if ($actionNamespace) {
            $this->fqnActionClass = $baseActionsNamespace.'\\'.$actionClass;
        } else {
            $this->fqnActionClass = $baseActionsNamespace.$actionClass;
        }

        //$this->shortActionClass = Classes::short($this->fqnActionClass);
        //$this->shortActionResponseClass = $this->shortActionClass.'Response';
        //$this->shortActionFormClass = $this->shortActionClass.'Form';
        $this->fqnActionFormClass = $this->fqnActionClass.'Form';
        $this->fqnActionResponseClass = $this->fqnActionClass.'Response';
        $this->actionDir = $this->webServerSchema->getPathToActions().'/'.Strings::bs2s($actionNamespace);
        $this->actionNamespace = Classes::getNamespace($this->fqnActionClass);

        $this->logger->debug('Action class: '.$this->fqnActionClass);

        $this->writeActionClassFile();
        $this->writeActionFormClassFile();
        $this->writeActionResponseClassFile();
    }

    /**
     * @param string $actionNamespace
     *
     * @return CreateActionOperation
     */
    public function setActionNamespace(string $actionNamespace): CreateActionOperation
    {
        $this->actionNamespace = $actionNamespace;
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
     * @param JsonApiSchema $webServerSchema
     *
     * @return static
     */
    public function setWebServerSchema(JsonApiSchema $webServerSchema): self
    {
        $this->webServerSchema = $webServerSchema;
        return $this;
    }

    /**
     * @return string
     */
    protected function generateActionClassCode(): string
    {
        $documentedFormActionClass = \App\Api\Action\DocumentedFormAction::class;
        $responseClass = \Symfony\Component\HttpFoundation\Response::class;
        $routeAnnotationClass = \Symfony\Component\Routing\Annotation\Route::class;

        $code = [
            '<?php',
            '',
            "namespace {$this->actionNamespace};",
            '',
            "use {$documentedFormActionClass};", // todo: $this->actionNamespace.DocumentedFormAction::class ?
            "use {$responseClass};",
            "use {$routeAnnotationClass};",
            'use '.$this->fqnActionResponseClass.';',
            '',
            'class '.Classes::short($this->fqnActionClass).' extends '.Classes::short($documentedFormActionClass),
            '{',
            TAB.'',
            TAB.'/**',
            TAB.' * @'.Classes::short($routeAnnotationClass).'("'.$this->route.'", methods={"POST"})',
            //TAB.' * @return RequirementAddResponse|ErrorResponse|array|\Symfony\Component\HttpFoundation\JsonResponse',
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

        $this->fs->dumpFile($actionFile, $this->generateActionClassCode());
    }

    protected function generateActionFormClassCode()
    {
        $baseFormClass = AbstractForm::class;
        $fieldClass = BaseField::class;
        $swagenInterface = \CodexSoft\JsonApi\Swagen\Interfaces\SwagenInterface::class;
        $formBuilderInterface = \Symfony\Component\Form\FormBuilderInterface::class;

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
        $fieldClass = BaseField::class;
        $formBuilderInterface = \Symfony\Component\Form\FormBuilderInterface::class;
        $baseSuccessResponseClass = DefaultSuccessResponse::class;

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

}