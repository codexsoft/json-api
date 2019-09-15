<?php

namespace CodexSoft\JsonApi\Form\Fields;

use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class BooleanField extends AbstractField
{

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        $builder->add($name, BooleanType::class, $this->options);
        return $this;
    }

    /**
     * @return static
     * @deprecated notNull should be used instead:
     * boolean value is true|false|null, with notNull valid values are restricted to true|false
     */
    public function notBlank()
    {
        $this->addConstraint(new Constraints\NotBlank());
        return $this;
    }

}