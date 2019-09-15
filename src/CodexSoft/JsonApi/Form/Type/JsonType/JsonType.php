<?php

namespace CodexSoft\JsonApi\Form\Type\JsonType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JsonType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetModelTransformers();
        $builder->addModelTransformer(new JsonTransformer);
        //$builder->resetClientTransformers();
        //$builder->appendClientTransformer(new JsonTransformer());
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
        return 'codexsoft_jsonapi_json_type';
    }
}