<?php

namespace CodexSoft\JsonApi\Form;

use Symfony\Component\Form\FormFactoryInterface;

trait ExampleGeneratorTrait
{
    /**
     * @param FormFactoryInterface|null $formFactory
     *
     * @return array
     * @throws \ReflectionException
     */
    public static function generateExample(FormFactoryInterface $formFactory = null)
    {
        $generator = new SymfonyFormExampleGenerator;
        if ($formFactory) {
            $generator->setFormFactory($formFactory);
        }
        return $generator->generateExample(static::class);
    }
}