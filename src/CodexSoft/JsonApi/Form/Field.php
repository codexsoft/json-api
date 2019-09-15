<?php

namespace CodexSoft\JsonApi\Form;

use CodexSoft\JsonApi\Form\Fields;
use Symfony\Component\Form\FormBuilderInterface;

class Field
{

    public static function text(string $label = '', array $constraints = [], array $options = []): Fields\TextField
    {
        return new Fields\TextField($label, $constraints, $options);
    }

    public static function integer(string $label = '', array $constraints = [], array $options = []): Fields\IntegerField
    {
        return new Fields\IntegerField($label, $constraints, $options);
    }

    public static function boolean(string $label = '', array $constraints = [], array $options = []): Fields\BooleanField
    {
        return new Fields\BooleanField($label, $constraints, $options);
    }

    public static function date(string $label = '', array $constraints = [], array $options = []): Fields\DateField
    {
        return new Fields\DateField($label, $constraints, $options);
    }

    public static function time(string $label = '', array $constraints = [], array $options = []): Fields\TimeField
    {
        return new Fields\TimeField($label, $constraints, $options);
    }

    public static function form(string $formClass, string $label = '', array $constraints = [], array $options = []): Fields\FormField
    {
        return new Fields\FormField($formClass, $label, $constraints, $options);
    }

    public static function collection(string $itemClass, string $label = '', array $constraints = [], array $options = []): Fields\CollectionField
    {
        return new Fields\CollectionField($itemClass, $label, $constraints, $options);
    }

    public static function custom(string $fieldClass, string $label = '', array $constraints = [], array $options = []): Fields\CustomField
    {
        return new Fields\CustomField($fieldClass, $label, $constraints, $options);
    }

    public static function json(string $label = '', array $constraints = [], array $options = []): Fields\JsonField
    {
        return new Fields\JsonField($label, $constraints, $options);
    }

    public static function float(string $label = '', array $constraints = [], array $options = []): Fields\NumberField
    {
        return new Fields\NumberField($label, $constraints, $options);
    }

    public static function email(string $label = '', array $constraints = [], array $options = []): Fields\EmailField
    {
        return new Fields\EmailField($label, $constraints, $options);
    }

    public static function url(string $label = '', array $constraints = [], array $options = []): Fields\UrlField
    {
        return new Fields\UrlField($label, $constraints, $options);
    }

    public static function file(string $label = '', array $constraints = [], array $options = []): Fields\FileField
    {
        return new Fields\FileField($label, $constraints, $options);
    }

    public static function password(string $label = '', array $constraints = [], array $options = []): Fields\PasswordField
    {
        return new Fields\PasswordField($label, $constraints, $options);
    }

    public static function timestamp(string $label = '', array $constraints = [], array $options = []): Fields\IntegerField
    {
        return self::integer($label, $constraints, $options)->example(1541508448)->greaterThan(0);
    }

    public static function latitude(string $label = '', array $constraints = [], array $options = []): Fields\NumberField
    {
        return self::float($label, $constraints, $options)->example(55.573231)->greaterThanOrEqual(-180)->lessThanOrEqual(180);
    }

    public static function longitude(string $label = '', array $constraints = [], array $options = []): Fields\NumberField
    {
        return self::float($label, $constraints, $options)->example(37.363968)->greaterThanOrEqual(-90)->lessThanOrEqual(90);
    }

    public static function id(string $label = '', array $constraints = [], array $options = []): Fields\IntegerField
    {
        return self::integer($label, $constraints, $options)->greaterThan(0);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param Fields\AbstractField[] $fields
     */
    public static function import(FormBuilderInterface $builder, array $fields): void
    {
        foreach ($fields as $fieldName => $field) {
            $field->import($builder, $fieldName);
        }
    }

}