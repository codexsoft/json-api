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
 *      class: AppBundle\Form\Extension\AppFormTypeExtension
 *      tags:
 *        - { name: form.type_extension, alias: form }
 */
class FormFieldDefaultValueExtension extends AbstractTypeExtension
{

    public const UNDEFINED = 'UNDEFINED_DEFAULT_VALUE_IS_NOT_SET'.PHP_INT_MAX.PHP_EOL;

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // makes it legal for FormType fields to have an "default" option
        $resolver->setDefined('default');
        $resolver->setDefault('default', self::UNDEFINED);

        // makes it legal for FormType fields to have an "example" option
        $resolver->setDefined('example');

        // todo: add additional speciefic array-type field to store another non-standard options?

    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }

    /**
     * {@inheritdoc}
     * ATTENTION! THIS FUNCTION IS CALLED AFTER VALIDATORS!!! IT CANNOT BE RUN BEFORE THEM!
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['default'] !== self::UNDEFINED) {
            $type = $builder->getFormConfig()->getType();
            $innerType = $type->getInnerType();

            if ($innerType instanceof CheckboxType) {
                $default = $options['default'] ? '1' : null;
            } elseif ($innerType instanceof BooleanType) {
                $default = $options['default'];
                if (\is_bool($default)) {
                    $default = $default ? BooleanType::VALUE_TRUE : BooleanType::VALUE_FALSE;
                }
            } elseif (\is_bool($options['default'])) {
                $default = $options['default'] ? '1' : '0';
            } elseif (\is_array($options['default'])) {
                $default = $options['default'];
            } else {
                $default = (string) $options['default'];
            }

            $builder->addEventListener(
                //FormEvents::PRE_SET_DATA,
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event) use ($default) {
                    // if data is not set, setting default data
                    if ($event->getData() === null) {
                        $event->setData($default);
                    }
                }
            );
        }
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            FormType::class,
        ];
    }

}
