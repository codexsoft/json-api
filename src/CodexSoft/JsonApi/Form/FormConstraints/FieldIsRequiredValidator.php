<?php

namespace CodexSoft\JsonApi\Form\FormConstraints;

use CodexSoft\JsonApi\Form\Extensions\FormFieldDefaultValueExtension;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class FieldIsRequiredValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof FieldIsRequired) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\\FieldIsRequired');
        }

        if ($value === FormFieldDefaultValueExtension::UNDEFINED) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                //->setCode(NotBlank::IS_BLANK_ERROR)
                ->addViolation();
        }
    }
}
