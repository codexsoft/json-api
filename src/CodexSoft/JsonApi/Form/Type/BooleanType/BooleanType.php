<?php

namespace CodexSoft\JsonApi\Form\Type\BooleanType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Nullable boolean type, missing in SymfonyForms out-of-the-box.
 * Null values can be modified via 'default' option
 * NotNull assertion can be used to deny null values
 *
 * Class BooleanType
 */
class BooleanType extends AbstractType
{
    public const VALUE_FALSE = 0;
    public const VALUE_TRUE = 1;
    public const VALUE_NULL = null;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new BooleanTypeToBooleanTransformer());
    }
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => false,
        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'codexsoft_jsonapi_boolean_type';
    }
}