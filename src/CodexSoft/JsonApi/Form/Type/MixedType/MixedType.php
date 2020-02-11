<?php

namespace CodexSoft\JsonApi\Form\Type\MixedType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MixedType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetModelTransformers();
        $builder->addModelTransformer(new MixedTransformer());
    }

    public function getDefaultOptions(array $options)
    {
        return [];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('allow_extra_fields', true);
    }

    public function getName()
    {
        return 'codexsoft_jsonapi_mixed_type';
    }
}
