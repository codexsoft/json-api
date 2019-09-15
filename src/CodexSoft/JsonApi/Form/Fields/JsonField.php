<?php

namespace CodexSoft\JsonApi\Form\Fields;

use CodexSoft\JsonApi\Form\Type\JsonType\JsonType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class JsonField extends AbstractField
{

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        $builder->add($name, JsonType::class, $this->options);
    }

}