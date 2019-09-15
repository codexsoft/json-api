<?php


namespace CodexSoft\JsonApi\Swagen;


class FormElementDocumentation
{
    public $example;
    public $minLength;
    public $maxLength;
    public $exclusiveMinimum;
    public $minimum;
    public $exclusiveMaximum;
    public $maximum;
    public $enum;
    public $label;
    public $description;
    public $default;

    /** @var bool */
    public $isCollection;

    /** @var string  */
    public $typeClass;

    /** @var string  */
    public $collectionElementsType;

    public $swaggerReferencesToClass;

    /** @var string */
    public $swaggerType;

    /** @var boolean */
    public $isForm;

    /** @var string */
    public $referenceToDefinition;
}