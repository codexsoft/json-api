<?php

namespace CodexSoft\JsonApi\Form\Fields;

use CodexSoft\JsonApi\Form\Type\MixedType\MixedType;
use Symfony\Component\Form\FormBuilderInterface;

class MixedField extends AbstractField
{

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        $builder->add($name, MixedType::class, $this->options);
    }

}
