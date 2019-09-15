<?php

namespace CodexSoft\JsonApi\Form\Fields;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class FormField extends AbstractField
{

    /** @var string */
    protected $formClass;

    public function __construct(string $formClass, string $label = '', array $constraints = [], array $options = [])
    {
        parent::__construct($label, $constraints, $options);
        $this->formClass = $formClass;
    }

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        $builder->add($name, $this->formClass, $this->options);
    }
}