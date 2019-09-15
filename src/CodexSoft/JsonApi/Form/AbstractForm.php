<?php

namespace CodexSoft\JsonApi\Form;

use CodexSoft\JsonApi\Form\Extensions\SetDefaultValuesToFormSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

abstract class AbstractForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new SetDefaultValuesToFormSubscriber);
    }

    public function getBlockPrefix()
    {
        return false;
    }

    protected function getFormBuilderHelper(FormBuilderInterface $builder): FormBuilderHelper
    {
        return FormBuilderHelper::create($builder);
    }

    /**
     * Just a helper to transform array of entities
     * @param array $entities
     * @param mixed ...$context
     *
     * @return array
     */
    public static function transformCollection(array $entities, ...$context): array
    {
        $result = [];
        foreach ($entities as $entity) {
            /** @noinspection PhpUndefinedMethodInspection */
            $result[] = static::transform($entity, ...$context);
        }
        return $result;
    }

}