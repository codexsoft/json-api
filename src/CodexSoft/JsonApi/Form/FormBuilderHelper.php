<?php

namespace CodexSoft\JsonApi\Form;

use CodexSoft\JsonApi\Form\Extensions\FormFieldDefaultValueExtension;
use CodexSoft\JsonApi\Form\FormConstraints\FieldIsRequired;
use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type;

class FormBuilderHelper
{
    private ?FormBuilderInterface $builder = null;

    /**
     * @param FormBuilderInterface $builder
     *
     * @return static
     */
    public static function create(FormBuilderInterface $builder): self
    {
        $instance = new static;
        $instance->builder = $builder;
        return $instance;
    }

    /**
     * @param FormBuilderInterface $builder
     *
     * @return static
     */
    public function setBuilder(FormBuilderInterface $builder): self
    {
        $this->builder = $builder;
        return $this;
    }

    /**
     * Adds FieldIsRequired constraint to field, nothing more.
     * In most cases makeFieldNotNull should be used instead.
     *
     * @param $fieldName
     *
     * @return static
     */
    public function makeFieldRequired($fieldName): self
    {
        $this->addConstraint($fieldName,new FieldIsRequired());
        return $this;
    }

    /**
     * Adds notNull constraint to field, nothing more.
     * @param $fieldName
     *
     * @return static
     */
    public function makeFieldNotNull($fieldName): self
    {
        $this->addConstraint($fieldName,new Assert\NotNull());
        return $this;
    }

    /**
     * @param $fieldName
     *
     * @return static
     */
    public function makeFieldNotBlank($fieldName): self
    {
        $this->addConstraint($fieldName,new Assert\NotBlank());
        return $this;
    }

    /**
     * @param string $fieldName
     * @param Constraint $constraint
     *
     * @return static
     */
    public function addConstraint(string $fieldName, Constraint $constraint): self
    {
        $this->addConstraints($fieldName,[$constraint]);
        return $this;
    }

    /**
     * Adds constraints to desired field
     * @param string $fieldName
     * @param array $constraints
     *
     * @return static
     */
    public function addConstraints(string $fieldName, array $constraints): self
    {
        $builder = $this->builder;
        if (!$builder->has($fieldName)) {
            $formTypeClass = \get_class($builder->getType()->getInnerType());
            throw new \LogicException("Failed add constraints to non-existing field $fieldName in form $formTypeClass!");
        }

        $field = $builder->get($fieldName); // get the field
        $fieldOptions = $field->getOptions(); // get the options
        $fieldType = \get_class($field->getType()->getInnerType()); // get the name of the type
        $fieldOptions['constraints'] = array_merge($fieldOptions['constraints'],$constraints);
        $builder->add($fieldName, $fieldType, $fieldOptions); // replace the field
        return $this;
    }

    /**
     * Adds constraints to each field in $fieldNames
     * @param string[] $fieldNames
     * @param Constraint[] $constraints
     *
     * @return static
     */
    public function addConstraintsToFields(array $fieldNames, array $constraints): self
    {
        foreach ($fieldNames as $fieldName) {
            $this->addConstraints($fieldName,$constraints);
        }
        return $this;
    }

    /**
     * @param array $fieldsNames
     *
     * @return static
     */
    public function makeFieldsRequired(array $fieldsNames): self
    {
        foreach ($fieldsNames as $fieldsName) {
            $this->makeFieldRequired($fieldsName);
        }
        return $this;
    }

    /**
     * @param int $defaultPage
     * @param int $itemsPerPage
     *
     * @return static
     */
    public function addPaginator($defaultPage = 1, $itemsPerPage = 10): self
    {

        $builder = $this->builder;
        $builder->add('itemsPerPage', Type\IntegerType::class, [
            'empty_data' => (string) $itemsPerPage,
            //'default' => '10',
            'constraints' => [
                new Assert\GreaterThanOrEqual(1),
            ],
            'label' => 'Количество записей на страницу (не менее 1)',
        ]);

        $builder->add('page', Type\IntegerType::class, [
            //'default' => "1",
            'empty_data' => (string) $defaultPage,
            'constraints' => [
                new Assert\GreaterThanOrEqual(1),
            ],
            'label' => 'Номер страницы (начиная с 1)',
        ]);

        return $this;

    }

    /**
     * @param string|null $defaultOrderingColum
     * @param array $columnsAvailableForSorting
     * @param string $defaultOrderDirection
     *
     * @return static
     */
    public function addOrdering(
        ?string $defaultOrderingColum = null,
        array $columnsAvailableForSorting = [],
        string $defaultOrderDirection = Criteria::ASC
    ): self
    {

        $builder = $this->builder;
        $builder->add('orderBy', Type\TextType::class, [
            'constraints' => [
                new Assert\Choice(['choices' => $columnsAvailableForSorting]),
            ],
            //'empty_data' => $defaultOrderingColum,
            'default' => $defaultOrderingColum,
            'label' => 'По какой колонке производить сортировку ',
        ]);

        $builder->add('orderDirection', Type\TextType::class, [
            'constraints' => [
                new Assert\Choice(['choices' => [Criteria::ASC,Criteria::DESC]]),
            ],
            'empty_data' => $defaultOrderDirection,
            //'default' => $defaultOrderDirection,
            'label' => 'Порядок сортировки по выбранной колонке (от меньшего к большему — ASC, от большего к меньшему — DESC)',
        ]);

        return $this;

    }

    /**
     * Nullable boolean type, missing in SymfonyForms out-of-the-box.
     *
     * todo: check if this doc is correct
     * { true, '1', 1 } values inerpreted as true.
     * { '0', 0 } values inerpreted as false.
     * { null, false } fallback to default value if setted
     *
     * @param string $fieldName
     * @param string $label
     * @param bool $notNull
     * @param string $default
     *
     * @return static
     */
    public function addBooleanField(
        string $fieldName,
        string $label = '',
        bool $notNull = false,
        $default = FormFieldDefaultValueExtension::UNDEFINED
    ): self
    {
        $data = [
            'default' => $default,
            'label' => $label,
        ];

        if ($notNull === true) {
            $data['constraints'] = [
                new Assert\NotNull,
            ];
        }

        $this->builder->add($fieldName, BooleanType::class,$data);
        return $this;
    }

    /**
     * @return static
     */
    public function makeAllFieldsRequired(): self
    {
        $builder = $this->builder;
        /** @var FormBuilderInterface[] $formFields */
        $formFields = $builder->all();
        foreach ($formFields as $fieldName => $field) {
            $this->makeFieldRequired($fieldName);
        }
        return $this;
    }

    /**
     * @return static
     * @deprecated
     * seems to make problems while validation (not-blank values errors)
     */
    public function makeAllFieldsWithoutDefaultValueRequired(): self
    {
        $builder = $this->builder;
        /** @var FormBuilderInterface[] $formFields */
        $formFields = $builder->all();
        foreach ($formFields as $fieldName => $field) {
            if (!$this->hasFieldDefaultValue($fieldName)) {
                $this->makeFieldRequired($fieldName);
            }
        }
        return $this;
    }

    /**
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasFieldDefaultValue(string $fieldName): bool
    {
        $builder = $this->builder;
        $field = $builder->get($fieldName);
        if (!$field->hasOption('default')) {
            return false;
        }
        if ($field->getOption('default') === FormFieldDefaultValueExtension::UNDEFINED) {
            return false;
        }
        return true;
    }

    /**
     * @param string $fieldName
     * @param mixed $value
     *
     * @return static
     */
    public function setFieldDefaultValue(string $fieldName, $value): self
    {
        $builder = $this->builder;
        if (!$builder->has($fieldName)) {
            $formTypeClass = \get_class($builder->getType()->getInnerType());
            throw new \LogicException("Failed to remove default value from non-existing field '$fieldName' in form $formTypeClass!");
        }

        $field = $builder->get($fieldName); // get the field
        $fieldOptions = $field->getOptions(); // get the options
        $fieldType = \get_class($field->getType()->getInnerType()); // get the name of the type
        $fieldOptions['default'] = $value;
        $builder->add($fieldName, $fieldType, $fieldOptions); // replace the field
        return $this;
    }

    /**
     * @param string $fieldName
     *
     * @return static
     */
    public function removeFieldDefaultValue(string $fieldName): self
    {
        $this->setFieldDefaultValue($fieldName, FormFieldDefaultValueExtension::UNDEFINED);
        return $this;
    }

    /**
     * @return static
     */
    public function removeDefaultValuesFromAllFields(): self
    {
        $builder = $this->builder;
        $formFieldNames = \array_keys($builder->all());
        foreach ($formFieldNames as $fieldName) {
            $this->removeFieldDefaultValue($fieldName);
        }
        return $this;
    }

}
