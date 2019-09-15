<?php

namespace CodexSoft\JsonApi\Form\Fields;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormBuilderInterface;

class DateField extends AbstractField
{

    protected function getDefaultOptions(): array
    {
        return [
            'example' => '2018-12-31',
            'widget'  => 'single_text',
        ];
    }

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        $builder->add($name, Type\DateType::class, $this->options);
    }
}