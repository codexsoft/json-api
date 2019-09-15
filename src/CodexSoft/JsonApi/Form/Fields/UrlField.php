<?php

namespace CodexSoft\JsonApi\Form\Fields;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UrlField extends AbstractField
{

    protected function getDefaultOptions(): array
    {
        return [
            'example' => 'https://google.com',
        ];
    }

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        //$defaultOptions = [
        //    'example' => 'https://google.com',
        //];
        //$options = array_merge($defaultOptions,$this->options);

        $builder->add($name, Type\UrlType::class, $this->options);
        return $this;
    }
}