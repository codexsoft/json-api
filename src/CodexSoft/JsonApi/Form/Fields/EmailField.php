<?php

namespace CodexSoft\JsonApi\Form\Fields;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class EmailField extends AbstractField
{

    protected function getDefaultOptions(): array
    {
        return [
            'example' => 'john@doe.com',
        ];
    }

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        $builder->add($name, Type\EmailType::class, $this->options);
        return $this;
    }
}