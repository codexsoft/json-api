<?php

namespace CodexSoft\JsonApi\Form\Fields;

use CodexSoft\Code\Classes\Classes;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Form\FormBuilderInterface;
use function Stringy\create as str;

abstract class AbstractField
{

    /** @var mixed */
    protected $content;

    protected array $choicesSourceArray = [];
    protected array $options = [];

    /**
     * Чуть более читабельная конвертация массива (например, используется в Swagen). Пример:
     * BODY_TYPE_BOARD:1, BODY_TYPE_REFRIGERATOR:2, BODY_TYPE_TENT:3, BODY_TYPE_ISOTERM:4
     *
     * @param array $array
     *
     * @param string $delimiter
     *
     * @return string
     */
    public static function explainArray(array $array, string $delimiter = ' '): string
    {
        return str_replace( ['"', ','], ['', ','.$delimiter], \json_encode($array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE));
    }

    protected static function explainArrayBr(array $array): string
    {
        $result = self::explainArray($array,'<br />');
        return (string) str($result)->removeLeft('{')->removeRight('}')->surround('<br>');
    }

    protected function getDefaultOptions(): array
    {
        return [];
    }

    public function __construct(string $label = '', array $constraints = [], array $options = [])
    {
        $options['constraints'] = $constraints;
        $options['label'] = $label;
        $this->options = $options;
    }

    protected function addLabelForChoices(): void
    {
        if ($this->choicesSourceArray && \is_string($this->options['label']) && $this->options['label'] !== '') {
            if (\count($this->choicesSourceArray) <= 10) {
                $this->options['label'] .= self::explainArrayBr($this->choicesSourceArray);
            } else {
                $coll = new ArrayCollection($this->choicesSourceArray);
                $keys = new ArrayCollection($coll->getKeys());
                $firstKey = $keys->first();
                $keys->next();
                $secondKey = $keys->current();
                $lastKey = $keys->last();
                $this->options['label'] .= self::explainArrayBr([
                    $firstKey  => $this->choicesSourceArray[$firstKey],
                    $secondKey => $this->choicesSourceArray[$secondKey],
                    '...'      => '',
                    $lastKey   => $this->choicesSourceArray[$lastKey],
                ]);
            }
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string $name
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function import(FormBuilderInterface $builder, string $name)
    {
        $defaultOptions = $this->getDefaultOptions();
        if (\count($defaultOptions)) {
            $this->options = array_merge($defaultOptions, $this->options);
        }

        $this->addLabelForChoices();
    }

    public function removeConstraintIfExists(string $constraintClass)
    {
        $oldConstraints = $this->options['constraints'];
        $newConstraints = [];
        foreach ($oldConstraints as $constraint) {
            if (Classes::isSameOrExtends($constraint, $constraintClass)) {
                continue;
            }
            $newConstraints[] = $constraint;
        }

        $this->options['constraints'] = $newConstraints;
    }

    /**
     * @param Constraint[] $constraints
     *
     * @return static
     */
    public function setConstraints(array $constraints)
    {
        $this->options['constraints'] = $constraints;
        return $this;
    }

    /**
     * @param Constraint[] $constraints
     *
     * @return static
     */
    public function addConstraints(array $constraints)
    {
        $this->options['constraints'] = array_merge($this->options['constraints'], $constraints);
        return $this;
    }

    /**
     * @param Constraint $constraint
     *
     * @return static
     */
    public function replaceConstraintIfExists(Constraint $constraint)
    {
        $this->removeConstraintIfExists(\get_class($constraint));
        $this->addConstraint($constraint);
        return $this;
    }

    /**
     * @param Constraint $constraint
     *
     * @return static
     */
    public function addConstraint(Constraint $constraint)
    {
        //$this->constraints[] = $constraint;
        $this->options['constraints'][] = $constraint;
        return $this;
    }

    /**
     * @return static
     */
    public function notNull()
    {
        $this->addConstraint(new Constraints\NotNull);
        return $this;
    }

    /**
     * @return static
     */
    public function nullable()
    {
        $this->removeConstraintIfExists(Constraints\NotNull::class);
        return $this;
    }

    /**
     * @return static
     */
    public function notBlank()
    {
        $this->addConstraint(new Constraints\NotBlank());
        return $this;
    }

    /**
     * @return static
     */
    public function example($exampleValue)
    {
        $this->options['example'] = $exampleValue;
        return $this;
    }

    /**
     * @return static
     */
    public function defaultValue($value)
    {
        $this->options['default'] = $value;
        return $this;
    }

    /**
     * @return static
     */
    public function length($options)
    {
        $this->addConstraint(new Constraints\Length($options));
        return $this;
    }

    /**
     * @param null $min
     * @param null $max
     *
     * @return static
     */
    public function assertLength($min = null, $max = null)
    {
        $this->addConstraint(new Constraints\Length(['min' => $min, 'max' => $max]));
        return $this;
    }

    /**
     * @return static
     */
    public function greaterThan($value)
    {
        $this->addConstraint(new Constraints\GreaterThan($value));
        return $this;
    }

    /**
     * @return static
     */
    public function greaterThanOrEqual($value)
    {
        $this->addConstraint(new Constraints\GreaterThanOrEqual($value));
        return $this;
    }

    /**
     * @return static
     */
    public function url($options = null)
    {
        $this->addConstraint(new Constraints\Url($options));
        return $this;
    }

    /**
     * @return static
     */
    public function email($options = null)
    {
        $this->addConstraint(new Constraints\Email($options));
        return $this;
    }

    /**
     * @param array $validChoices
     *
     * @return static
     */
    public function choices(array $validChoices)
    {
        // todo: перед извлечением значений следует запомнить переданный массив с ключами для документирования
        $this->choicesSourceArray = $validChoices;
        $this->addConstraint(new Constraints\Choice(['choices' => \array_values($validChoices)]));
        return $this;
    }

    /**
     * @return static
     */
    public function lessThan($value)
    {
        $this->addConstraint(new Constraints\LessThan($value));
        return $this;
    }

    /**
     * @return static
     */
    public function lessThanOrEqual($value)
    {
        $this->addConstraint(new Constraints\LessThanOrEqual($value));
        return $this;
    }

    /**
     * @return static
     */
    public function type($value)
    {
        $this->addConstraint(new Constraints\Type($value));
        return $this;
    }

    /**
     * @return static
     */
    public function label($label)
    {
        $this->options['label'] = $label;
        return $this;
    }

    /**
     * @return static
     */
    public function content($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return static
     */
    public function file($options = null)
    {
        $this->addConstraint(new Constraints\File($options));
        return $this;
    }

}
