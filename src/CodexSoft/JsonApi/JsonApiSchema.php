<?php

namespace CodexSoft\JsonApi;

use CodexSoft\Code\AbstractModuleSchema;
use CodexSoft\Code\Helpers\Strings;
use CodexSoft\JsonApi\Form\Field;

class JsonApiSchema extends AbstractModuleSchema
{

    /** @var string */
    private $namespaceActions;

    /** @var string */
    private $pathToActions;

    /** @var string */
    private $namespaceForms;

    /** @var string */
    private $pathToForms;

    /** @var string  */
    private $fieldHelperClass = Field::class;

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
     * @return string
     */
    public function getFieldHelperClass(): string
    {
        return $this->fieldHelperClass;
    }

}