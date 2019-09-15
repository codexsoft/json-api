<?php

namespace CodexSoft\JsonApi\Form\Fields;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use function CodexSoft\Code\str;

class TextField extends AbstractField
{

    public function import(FormBuilderInterface $builder, string $name)
    {
        parent::import($builder, $name);
        $this->addExampleForNameFields($name);
        $builder->add($name, Type\TextType::class, $this->options);
        return $this;
    }

    protected function addExampleForNameFields(string $name): void
    {
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

}