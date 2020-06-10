<?php

namespace CodexSoft\JsonApi\Documentation\Collector;

use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use Symfony\Component\Validator\Constraints;

class FormElementDoc
{
    public const VALUE_UNDEFINED = 'VALUE-UNDEFINED-e81b8bc7-d134-4818-b435-1ke92c067261';

    public const TYPE_COLLECTION = 1;
    public const TYPE_FORM = 2;
    public const TYPE_SCALAR = 3;

    public ?int $type = null;

    public ?string $fieldFormTypeClass = null;

    /** @var \Symfony\Component\Validator\Constraint[] */
    public array $constraints = [];

    // analyzed constraints

    public $minLength;
    public $maxLength;
    public $choices;
    public $scalarRestrictedToType;
    public $collectionCountMin;
    public $collectionCountMax;

    /** @var \Symfony\Component\Validator\Constraint[] */
    public $collectionConstraints = [];

    public ?bool $exclusiveMinimum = null;
    public $minimum;

    public ?bool $exclusiveMaximum = null;
    public $maximum;

    public ?bool $isRequired = null;

    public ?bool $isNotBlank = null;

    public ?bool $isNotNull = null;

    // options

    public $example = self::VALUE_UNDEFINED;
    public $label = self::VALUE_UNDEFINED;
    public $defaultValue = self::VALUE_UNDEFINED;

    /** @var string  */
    public $collectionItemsClass; // can be scalar or form

    public ?string $fieldReferencesToFormClass = null; // must be form

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

    public function isValueDefined($value)
    {
        return $value !== self::VALUE_UNDEFINED;
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
