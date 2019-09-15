<?php

namespace CodexSoft\JsonApi\Form\Type\JsonType;

use Symfony\Component\Form\DataTransformerInterface;

class JsonTransformer implements DataTransformerInterface
{

    public function reverseTransform($value)
    {
        return json_decode($value);
    }

    /**
     * @param \DateTime $value
     * @return array|string
     */
    public function transform($value)
    {
        return json_encode($value);
    }

}