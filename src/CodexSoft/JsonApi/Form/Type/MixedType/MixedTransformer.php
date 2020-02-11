<?php

namespace CodexSoft\JsonApi\Form\Type\MixedType;

use Symfony\Component\Form\DataTransformerInterface;

class MixedTransformer implements DataTransformerInterface
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
