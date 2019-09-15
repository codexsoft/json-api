<?php

namespace CodexSoft\JsonApi\Form\Fields;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class TimeField extends AbstractField
{

    protected function getDefaultOptions(): array
    {
        return [
            'example' => '23:59',
            'widget'  => 'single_text',
        ];
    }

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);

        $builder->add($name, Type\TimeType::class, $this->options);
        return $this;
    }
}