<?php

namespace CodexSoft\JsonApi\Form\Fields;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use function Stringy\create as str;

class TextField extends AbstractField
{
    /** @var bool */
    private static $generateFakeNames = false;

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        static::$generateFakeNames && $this->addExampleForNameFields($name);
        $builder->add($name, Type\TextType::class, $this->options);
        return $this;
    }

    protected function addExampleForNameFields(string $name): void
    {
        if (isset($this->options['example'])) {
            return;
        }

        if (!\class_exists(\Faker\Factory::class)) {
            return;
        }

        $faker = \Faker\Factory::create('ru_RU');
        //$sex = $faker->boolean ? 'male' : 'female';
        $sex = 'male';

        $example = null;
        if (str($name)->contains('patronymic')) {
            /** @noinspection PhpUndefinedMethodInspection */
            $example = $faker->middleName($sex);
        } elseif (str($name)->contains('surname')) {
            /** @noinspection PhpUndefinedMethodInspection */
            $example = $faker->lastName($sex);
        } elseif (str($name)->contains('name')) /** @noinspection PhpUndefinedMethodInspection */ {
            $example = $faker->firstName($sex);
        } elseif ($this->content !== null) {

            switch ($this->content) {

                case 'surname':
                    /** @noinspection PhpUndefinedMethodInspection */
                    $example = $faker->lastName($sex);
                    break;

                case 'name':
                    /** @noinspection PhpUndefinedMethodInspection */
                    $example = $faker->firstName($sex);
                    break;

                case 'patronymic':
                    /** @noinspection PhpUndefinedMethodInspection */
                    $example = $faker->middleName($sex);
                    break;

            }

        }

        if ($example) {
            $this->example($example);
        }
    }

    /**
     * @param bool $generateFakeNames
     */
    public static function setGenerateFakeNames(bool $generateFakeNames): void
    {
        self::$generateFakeNames = $generateFakeNames;
    }

}
