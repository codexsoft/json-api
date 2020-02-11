<?php

namespace CodexSoft\JsonApi\Form;

use CodexSoft\DateAndTime\DateAndTime;
use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use CodexSoft\JsonApi\Form\Type\JsonType\JsonType;
use CodexSoft\JsonApi\Form\Type\MixedType\MixedType;
use CodexSoft\JsonApi\Response\ResponseWrappedDataInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type;

class SymfonyFormExampleGenerator
{

    /** @var bool */
    private $preferDefaultValue = false;

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var \Faker\Factory */
    private $faker;

    /** @var LoggerInterface */
    private $logger;

    /** @var bool  */
    private $skipNotReqiredElements = false;

    public function __construct()
    {
        $this->faker = \Faker\Factory::create( 'ru_RU' );
        $this->logger = new NullLogger;
        $this->formFactory = self::generateFormFactory();
    }

    private function getDefaultMaxInteger(): int
    {
        return 100000;
        //return PHP_INT_MAX;
    }

    public static function generateFormFactory()
    {
        return DefaultFormFactoryGenerator::generate();
    }

    /**
     * Сгенерировать валидный пример
     *
     * @param string $formClass
     *
     * @return array
     * @throws \ReflectionException
     */
    public function generateExample(string $formClass) // preferDefaultValue?
    {
        $result = [];

        $formFactory = $this->formFactory;
        $form = $formFactory->create($formClass);
        $elements = $form->all();
        foreach ($elements as $name => $element) {

            $config = $element->getConfig();
            $type = $element->getConfig()->getType();

            $elementType = $type->getInnerType();
            $elementTypeClass = \get_class($elementType);
            $elementTypeReflection = new \ReflectionClass($elementTypeClass);

            //$passedOptions = $config->getAttribute('data_collector/passed_options') ?? [];
            if ($config->hasAttribute('data_collector/passed_options')) {
                $passedOptions = $config->getAttribute('data_collector/passed_options');
                if ($passedOptions === null) {
                    $passedOptions = [];
                }
            } else {
                $passedOptions = $config->getOptions();
            }

            $constraints = $passedOptions['constraints'] ?? [];

            $elementIsRequired = false;
            foreach($constraints as $constraint) {
                // todo: if field has default value, ignore NotBlank and avoid to set field as required?
                if ($constraint instanceof Constraints\NotBlank) {
                    $elementIsRequired = true;
                    break;
                }

                if ($constraint instanceof Constraints\NotNull) {
                    $elementIsRequired = true;
                    break;
                }

            }

            $elementValueIsRestrictedByChoices = null;
            foreach($constraints as $constraint) {
                if ($constraint instanceof Constraints\Choice) {
                    $elementValueIsRestrictedByChoices = $constraint->choices;
                    break;
                }
            }

            if ($this->skipNotReqiredElements) {
                // if element is not required, probably skip it
                if (!$elementIsRequired && \random_int(0, 1)) {
                    continue;
                }
            }

            if ($elementTypeReflection->isAnonymous()) {

                // support for wrapper functionality

                if ($formClass instanceof ResponseWrappedDataInterface) {
                    $formClass::setGeneratingWrappedDataForResponseDefinition(true);
                }

                //ResponseWrappedDataTrait::setGeneratingWrappedDataForResponseDefinition(true);
                $elementData = $this->generateExample($formClass);

                if ($formClass instanceof ResponseWrappedDataInterface) {
                    $formClass::setGeneratingWrappedDataForResponseDefinition(false);
                }

                //ResponseWrappedDataTrait::setGeneratingWrappedDataForResponseDefinition(false);
                //BaseSuccessResponse::setGeneratingWrappedDataForResponseDefinition(false);

            } elseif ($elementTypeClass === Type\ChoiceType::class) {

                $elementChoices = $element->getConfig()->getOption('choices') ?? [];
                $elementData = $this->randomChoice($elementChoices);

            } elseif ($elementType instanceof AbstractType && $elementValueIsRestrictedByChoices) {
                $elementData = $this->randomChoice($elementValueIsRestrictedByChoices);
            } elseif ($elementType instanceof CollectionType) {

                $elementData = [];

                if (!$config->hasOption('entry_type')) {
                    $this->logger->warning($name.' is Collection but entry_type is not specified!');
                    continue;
                }
                $collectionEntryTypeClass = $config->getOption('entry_type');

                $min = 1;
                $max = 3; // default
                foreach($constraints as $constraint) {
                    if ($constraint instanceof Constraints\Count) {
                        $min = $constraint->min ?? $min;
                        $max = $constraint->max ?? $max;
                        break;
                    }
                }

                $innerConstraints = [];
                foreach($constraints as $constraint) {
                    if ($constraint instanceof Constraints\All) {
                        $innerConstraints = $constraint->constraints;
                        break;
                    }
                }

                $iterations = \random_int($min, $max);
                if ($iterations) {
                    for ($i = 1; $i <= $iterations; $i++) {
                        $elementData[] = $this->generateScalarOrFormExample($collectionEntryTypeClass, $innerConstraints, $passedOptions);
                    }
                }
            } elseif ($elementType instanceof AbstractType) {
                $elementConstraints = $element->getConfig()->getOption('constraints') ?? [];
                $elementData = $this->generateScalarOrFormExample(\get_class($elementType), $elementConstraints, $passedOptions);
                //$elementData = $this->generateScalarOrFormExample(\get_class($elementType), $constraints, $passedOptions);
            } else {
                $this->logger->warning('Cannot generate unknown type '.$elementTypeClass);
                continue;
            }

            $result[$name] = $elementData;

        }
        return $result;
    }

    protected function generateScalarOrFormExample(string $entryClass, $constraints = [], $passedOptions = [])
    {
        if (is_subclass_of($entryClass, AbstractForm::class)) {
            return $this->generateExample($entryClass);
        }

        return $this->generateScalarExample(new $entryClass, $constraints, $passedOptions);
    }

    protected function generateScalarExample(AbstractType $innerType, array $constraints, array $passedOptions = [])
    {

        if (isset($passedOptions['example'])) {
            return $passedOptions['example'];
        }

        foreach ($constraints as $constraint) {
            if ($constraint instanceof Constraints\Choice) {
                return $this->randomChoice($constraint->choices);
            }
        }

        $min = 0;
        $max = $this->getDefaultMaxInteger();

        foreach($constraints as $constraint) {
            if ($constraint instanceof Constraints\GreaterThanOrEqual) {
                $min = $constraint->value;
                continue;
            }

            if ($constraint instanceof Constraints\GreaterThan) {
                $min = $constraint->value+1;
                continue;
            }

            if ($constraint instanceof Constraints\LessThanOrEqual) {
                $max = $constraint->value;
                continue;
            }

            if ($constraint instanceof Constraints\LessThan) {
                $max = $constraint->value-1;
                continue;
            }

        }

        $minLength = 0;
        $maxLength = 255;

        foreach($constraints as $constraint) {
            if ($constraint instanceof Constraints\Length) {
                $minLength = $constraint->min ?? $minLength;
                $maxLength = $constraint->max ?? $maxLength;
                continue;
            }
        }

        $valueMustNotBeNull = false;
        foreach($constraints as $constraint) {
            if ($constraint instanceof Constraints\NotNull) {
                $valueMustNotBeNull = true;
                continue;
            }
        }

        $typeRestricted = null;
        foreach($constraints as $constraint) {
            if ($constraint instanceof Constraints\Type) {
                $typeRestricted = $constraint->type;
            }
        }

        if ($innerType instanceof Type\TextType && $typeRestricted) {
            if ($typeRestricted === 'numeric') {
                $lengthThreshold = \random_int($minLength,$maxLength);
                $generatedValue = '';
                for ($i = 1; $i <= $lengthThreshold; $i++) {
                    $generatedValue .= (string) \random_int(0,9);
                }
                return $generatedValue;
            }
        }

        if ($innerType instanceof Type\IntegerType) {
            return $this->faker->numberBetween($min,$max);
        }

        if ($innerType instanceof Type\NumberType) {
            return $this->faker->randomFloat(6,$min,$max);
        }

        if ($innerType instanceof Type\PercentType) {
            return $this->faker->randomFloat(null,0,1);
        }

        if ($innerType instanceof Type\TextType || $innerType instanceof Type\TextareaType) {
            if ($minLength === 0) {
                return $this->faker->realText($maxLength);
            }
            // todo: as faker has not minLength parameter from text out of the box, currently using password...
            return $this->faker->password($minLength, $maxLength);
        }

        if ($innerType instanceof Type\EmailType) {
            return $this->faker->email;
        }

        if ($innerType instanceof Type\UrlType) {
            return $this->faker->url;
        }

        if ($innerType instanceof BooleanType) {
            if ($valueMustNotBeNull) {
                return \random_int(0, 1);
            }

            $rnd = \random_int(0, 2);
            return (($rnd === 2) ? null : $rnd);
        }

        if ($innerType instanceof Type\CheckboxType) {
            return $this->faker->boolean;
        }

        if ($innerType instanceof Type\DateType) {
            return $this->faker->date(DateAndTime::FORMAT_YMD);
        }

        if ($innerType instanceof Type\TimeType) {
            return $this->faker->time(DateAndTime::FORMAT_HOURMIN);
        }

        if ($innerType instanceof Type\DateTimeType) {
            // todo: timestamp?
            return $this->faker->dateTime->format(DateAndTime::FORMAT_YMD_HIS);
            //return 'DateTimeType not implemented';
        }

        if ($innerType instanceof JsonType) {
            return [];
        }

        if ($innerType instanceof MixedType) {
            return 42;
        }

        return 'UNKNOWN DATA TYPE';

    }

    protected function randomChoice(array $choiceTypeChoices)
    {
        return \count($choiceTypeChoices) ? $choiceTypeChoices[\array_rand($choiceTypeChoices)] : null;
    }

    /**
     * @param bool $preferDefaultValue
     *
     * @return SymfonyFormExampleGenerator
     */
    public function setPreferDefaultValue(bool $preferDefaultValue): SymfonyFormExampleGenerator
    {
        $this->preferDefaultValue = $preferDefaultValue;
        return $this;
    }

    /**
     * @param FormFactoryInterface $formFactory
     *
     * @return SymfonyFormExampleGenerator
     */
    public function setFormFactory(FormFactoryInterface $formFactory): SymfonyFormExampleGenerator
    {
        $this->formFactory = $formFactory;
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return SymfonyFormExampleGenerator
     */
    public function setLogger(LoggerInterface $logger): SymfonyFormExampleGenerator
    {
        $this->logger = $logger;
        return $this;
    }



}
