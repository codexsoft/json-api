<?php

namespace CodexSoft\JsonApi\Form\Type\BooleanType;

use Symfony\Component\Form\DataTransformerInterface;

class BooleanTypeToBooleanTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value === null) {
            return BooleanType::VALUE_NULL;
        }

        if ($value === true || BooleanType::VALUE_TRUE === (int) $value) {
            return BooleanType::VALUE_TRUE;
        }

        return BooleanType::VALUE_FALSE;
    }
    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {

        if ($value === BooleanType::VALUE_NULL) {
            return null;
        }

        return BooleanType::VALUE_TRUE === (int) $value;
    }
}