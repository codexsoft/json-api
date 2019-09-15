<?php

namespace CodexSoft\JsonApi\Form\Fields;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormBuilderInterface;

class IntegerField extends AbstractField
{

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        $builder->add($name, Type\IntegerType::class, $this->options);
        return $this;
    }
}