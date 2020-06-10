<?php

namespace CodexSoft\JsonApi\Form\Fields;

use Symfony\Component\Form\FormBuilderInterface;

class CustomField extends AbstractField
{
    protected string $fieldClass;

    public function __construct(string $fieldClass, string $label = '', array $constraints = [], array $options = [])
    {
        parent::__construct($label, $constraints, $options);
        $this->fieldClass = $fieldClass;
    }

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        $builder->add($name, $this->fieldClass, $this->options);
    }
}
