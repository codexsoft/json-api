<?php

namespace CodexSoft\JsonApi\Form;

use CodexSoft\JsonApi\Form\Extensions\FormFieldDefaultValueExtension;
use CodexSoft\JsonApi\Form\Extensions\FormFieldExampleExtension;
use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

class DefaultFormFactoryGenerator
{

    /**
     * @return FormFactoryInterface
     */
    public static function generate(): FormFactoryInterface
    {
        $validator = Validation::createValidator();
        $validatorExtension = new ValidatorExtension($validator);
        return Forms::createFormFactoryBuilder()
            ->addType(new BooleanType)
            ->addTypeExtensions([
                new FormFieldDefaultValueExtension,
                new FormFieldExampleExtension
            ])
            ->addExtension($validatorExtension)
            ->getFormFactory();
    }
}