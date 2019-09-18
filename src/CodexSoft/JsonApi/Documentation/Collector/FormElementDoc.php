<?php

namespace CodexSoft\JsonApi\Documentation\Collector;

use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use Symfony\Component\Validator\Constraints;

class FormElementDoc
{
    public const TYPE_COLLECTION = 1;
    public const TYPE_FORM = 2;
    public const TYPE_SCALAR = 3;

    /** @var int */
    public $type;

    /** @var string  */
    public $fieldFormTypeClass;

    /** @var \Symfony\Component\Validator\Constraint[] */
    public $constraints = [];

    // analyzed constraints

    public $minLength;
    public $maxLength;
    public $choices;
    public $scalarRestrictedToType;
    public $collectionCountMin;
    public $collectionCountMax;

    /** @var \Symfony\Component\Validator\Constraint[] */
    public $collectionConstraints = [];

    /** @var bool */
    public $exclusiveMinimum;
    public $minimum;

    /** @var bool */
    public $exclusiveMaximum;
    public $maximum;

    /** @var bool */
    public $isRequired;

    /** @var bool */
    public $isNotBlank;

    /** @var bool */
    public $isNotNull;

    // options

    public $example;
    public $label;
    public $defaultValue;

    /** @var string  */
    public $collectionItemsClass; // can be scalar or form

    public $fieldReferencesToFormClass; // must be form

    public function isCollection(): bool
    {
        return $this->type === self::TYPE_COLLECTION;
    }

    public function isScalar(): bool
    {
        return $this->type === self::TYPE_SCALAR;
    }

    public function isForm(): bool
    {
        return $this->type === self::TYPE_FORM;
    }

    ///**
    // * @param \Symfony\Component\Validator\Constraint[] $constraints
    // */
    public function analyzeConstraints(): void
    {
        $docElement = $this;
        foreach ($docElement->constraints as $constraint) {

            if ($constraint instanceof Constraints\Length) {
                if ($constraint->min !== null) {
                    $docElement->minLength = $constraint->min;
                }

                if ($constraint->max !== null) {
                    $docElement->maxLength = $constraint->max;
                }

            } elseif ($constraint instanceof Constraints\GreaterThan) {
                $docElement->exclusiveMinimum = true;
                $docElement->minimum = $constraint->value;
            } elseif ($constraint instanceof Constraints\GreaterThanOrEqual) {
                $docElement->minimum = $constraint->value;
            } elseif ($constraint instanceof Constraints\LessThan) {
                $docElement->exclusiveMaximum = true;
                $docElement->maximum = $constraint->value;
            } elseif ($constraint instanceof Constraints\LessThanOrEqual) {
                $docElement->maximum = $constraint->value;
            } elseif ($constraint instanceof Constraints\Choice) {
                $docElement->choices = \array_values($constraint->choices);
            } elseif ($constraint instanceof Constraints\Type) {
                $docElement->scalarRestrictedToType = $constraint->type;
            } elseif ($constraint instanceof Constraints\Count) {
                $docElement->collectionCountMin = $constraint->min;
                $docElement->collectionCountMax = $constraint->max;
            } elseif ($constraint instanceof Constraints\NotBlank) {
                $docElement->isRequired = true;
                $docElement->isNotBlank = true;
            } elseif ($constraint instanceof Constraints\NotNull) {
                $docElement->isRequired = true;
                $docElement->isNotNull = true;
            } elseif ($constraint instanceof Constraints\All) {
                $docElement->collectionConstraints = $constraint->constraints;
                break;
            }
        }

        if ($docElement->fieldFormTypeClass === BooleanType::class) {
            if ($docElement->isNotNull) {
                $docElement->choices = [0,1]; // [true,false]
            } else {
                $docElement->choices = [0,1,null]; // [true,false,null]
            }
        }
    }

}