<?php

namespace TestApi\Action\Hello;

use CodexSoft\JsonApi\Form\Field;
use Symfony\Component\Form\FormBuilderInterface;
use CodexSoft\JsonApi\Response\DefaultSuccessResponse;

class WorldResponse extends DefaultSuccessResponse
{

    public static function construct(array $data)
    {
        return new static($data);
    }

    public static function getSwaggerResponseDescription(): string
    {
        return ''; // todo: describe what this response mean
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        Field::import($builder, [
            // todo: define response form fields
            'id' => Field::id('Created requirement ID')->notBlank(),
        ]);
            parent::buildForm($builder, $options);
    }

}