<?php

namespace CodexSoft\JsonApi;

use CodexSoft\Code\Strings\Strings;
use CodexSoft\JsonApi\Form\AbstractForm;
use CodexSoft\JsonApi\Form\Field;
use CodexSoft\JsonApi\Response\DefaultSuccessResponse;
use function Stringy\create as str;

class JsonApiSchema
{

    private ?string $namespaceActions = null;
    private ?string $pathToActions = null;
    private ?string $namespaceForms = null;
    private ?string $pathToForms = null;
    public string $baseActionClass = DocumentedFormAction::class;
    public string $baseActionFormClass = AbstractForm::class;
    public string $fieldHelperClass = Field::class;
    public string $baseSuccessResponseClass = DefaultSuccessResponse::class;
    protected string $namespaceBase = 'App\\Domain';
    protected string $pathToPsrRoot = '/src';

    /**
     * @param string $domainConfigFile
     *
     * @return static
     * @throws \Exception
     */
    public static function getFromConfigFile(string $domainConfigFile): self
    {
        ob_start();
        $domainSchema = include $domainConfigFile;
        ob_end_clean();

        if (!$domainSchema instanceof static) {
            throw new \Exception("File $domainConfigFile does not return valid ".static::class."!\n");
        }

        return $domainSchema;
    }

    /**
     * @return string
     */
    public function getPathToPsrRoot(): string
    {
        return $this->pathToPsrRoot;
    }

    /**
     * @param string $pathToPsrRoot
     *
     * @return static
     */
    public function setPathToPsrRoot(string $pathToPsrRoot): self
    {
        $this->pathToPsrRoot = $pathToPsrRoot;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespaceBase(): string
    {
        return $this->namespaceBase;
    }

    /**
     * @param string $namespaceBase
     *
     * @return static
     */
    public function setNamespaceBase(string $namespaceBase): self
    {
        $this->namespaceBase = $namespaceBase;
        return $this;
    }

    ///**
    // * @var \Closure function(string $actionFqnClass): string
    // * for example, Action\MyAction -> Action\MyActionRequestForm
    // */
    //private $generateRequestFormClassFromActionClass;

    //public function __construct()
    //{
    //    $this->generateRequestFormClassFromActionClass = function(string $actionFqnClass): string {
    //        return $actionFqnClass.'RequestForm';
    //    };
    //}

    public static function generateActionFormClass(string $actionFqnClass): string
    {
        return str($actionFqnClass)->removeRight('Action').'RequestForm';
    }

    public static function generateResponseFormClass(string $actionFqnClass): string
    {
        return str($actionFqnClass)->removeRight('Action').'ResponseForm';
    }

    /**
     * @param string $namespaceActions
     *
     * @return JsonApiSchema
     */
    public function setNamespaceActions(string $namespaceActions): JsonApiSchema
    {
        $this->namespaceActions = $namespaceActions;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespaceActions(): string
    {
        return $this->namespaceActions ?: $this->getNamespaceBase().'\\Action';
    }

    /**
     * @param string $pathToActions
     *
     * @return JsonApiSchema
     */
    public function setPathToActions(string $pathToActions): JsonApiSchema
    {
        $this->pathToActions = $pathToActions;
        return $this;
    }

    /**
     * @return string
     */
    public function getPathToActions(): string
    {
        return $this->pathToActions ?: $this->pathToPsrRoot.'/'.Strings::bs2s($this->getNamespaceActions());
    }

    /**
     * @param string $namespaceForms
     *
     * @return JsonApiSchema
     */
    public function setNamespaceForms(string $namespaceForms): JsonApiSchema
    {
        $this->namespaceForms = $namespaceForms;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespaceForms(): string
    {
        return $this->namespaceForms ?: $this->getNamespaceBase().'\\Form';
    }

    /**
     * @param string $pathToForms
     *
     * @return JsonApiSchema
     */
    public function setPathToForms(string $pathToForms): JsonApiSchema
    {
        $this->pathToForms = $pathToForms;
        return $this;
    }

    /**
     * @return string
     */
    public function getPathToForms(): string
    {
        return $this->pathToForms ?: $this->pathToPsrRoot.'/'.Strings::bs2s($this->getNamespaceForms());
    }

    /**
     * Default to \CodexSoft\JsonApi\Form\Field::class
     * @param string $fieldHelperClass
     *
     * @return JsonApiSchema
     */
    public function setFieldHelperClass(string $fieldHelperClass): JsonApiSchema
    {
        $this->fieldHelperClass = $fieldHelperClass;
        return $this;
    }

    /**
     * @param string $baseActionClass
     *
     * @return JsonApiSchema
     */
    public function setBaseActionClass(string $baseActionClass): JsonApiSchema
    {
        $this->baseActionClass = $baseActionClass;
        return $this;
    }

    /**
     * @param string $baseSuccessResponseClass
     *
     * @return JsonApiSchema
     */
    public function setBaseSuccessResponseClass(string $baseSuccessResponseClass): JsonApiSchema
    {
        $this->baseSuccessResponseClass = $baseSuccessResponseClass;
        return $this;
    }

}
