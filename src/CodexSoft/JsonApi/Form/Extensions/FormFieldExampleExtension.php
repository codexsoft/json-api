<?php

namespace CodexSoft\JsonApi\Form\Extensions;

use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Register this class as service using form.type_extension tag
 *
 * services:
 *    app.form.extension:
 *      class: CodexSoft\JsonApi\Form\FormFieldExampleExtension
 *      tags:
 *        - { name: form.type_extension, alias: form }
 */
class FormFieldExampleExtension extends AbstractTypeExtension
{

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // makes it legal for FormType fields to have an "example" option
        $resolver->setDefined('example');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            FormType::class,
        ];
    }

}
