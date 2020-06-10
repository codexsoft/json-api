<?php

namespace CodexSoft\JsonApi\Form\Fields;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormBuilderInterface;

class CollectionField extends AbstractField
{

    protected string $itemClass;

    public function __construct(string $itemClass, string $label = '', array $constraints = [], array $options = [])
    {
        parent::__construct($label, $constraints, $options);
        $this->itemClass = $itemClass;
        $this->options['entry_type'] = $this->itemClass;
    }

    protected function getDefaultOptions(): array
    {
        return [
            'allow_add' => true,
        ];
    }

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        $builder->add($name, Type\CollectionType::class, $this->options);
        return $this;
    }

    /**
     * @param \Symfony\Component\Validator\Constraint[] $itemConstraints
     *
     * @return static
     */
    public function all(array $itemConstraints)
    {
        $this->addConstraint(new Assert\All($itemConstraints));
        return $this;
    }

    /**
     * @return static
     */
    public function count($options)
    {
        $this->addConstraint(new Assert\Count($options));
        return $this;
    }

    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return $this->itemClass;
    }

}
