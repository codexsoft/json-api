<?php /** @noinspection DuplicatedCode */


namespace CodexSoft\JsonApi\Documentation;

use CodexSoft\Code\Traits\Loggable;
use CodexSoft\JsonApi\Documentation\Collector\ApiDoc;
use CodexSoft\JsonApi\Documentation\Collector\FormDoc;
use CodexSoft\JsonApi\Documentation\Collector\FormElementDoc;
use CodexSoft\JsonApi\Response\ResponseWrappedDataInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints;
use CodexSoft\Code\Constants;
use CodexSoft\JsonApi\Form\AbstractForm;
use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use CodexSoft\JsonApi\Form\Type\JsonType\JsonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Extension\Core\Type;

class FormExampleGenerator
{

    use Loggable;

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var \Faker\Factory */
    private $faker;

    /** @var bool  */
    private $skipNotReqiredElements = false;

    /** @var ApiDoc */
    private $apiDoc;

    public function __construct(FormFactoryInterface $formFactory, ApiDoc $apiDoc, \Faker\Factory $faker)
    {
        $this->formFactory = $formFactory;
        $this->faker = $faker;
        $this->apiDoc = $apiDoc;
    }

    /**
     * Сгенерировать валидный пример
     *
     * @param FormDoc $formDoc
     *
     * @return array
     * @throws \ReflectionException
     */
    public function generateExample(FormDoc $formDoc) // preferDefaultValue?
    {
        $result = [];

        foreach ($formDoc->items as $name => $element) {

            // if element is not required, probably skip it
            if ($this->skipNotReqiredElements && $element->isRequired === false) {
                try {
                    if (\random_int(0, 1)) {
                        continue;
                    }
                } catch (\Exception $e) {
                }
            }

            if ($element->isForm()) {
                $elementData = $this->generateExample($this->apiDoc->forms[$element->fieldFormTypeClass]);
            } elseif ($element->isCollection()) {

                $min = $element->collectionCountMin ?? 1;
                $max = $element->collectionCountMax ?? 3;

                try {
                    $iterations = \random_int($min, $max);
                } catch (\Exception $e) {
                    $iterations = $max;
                }

                $elementData = [];
                if ($iterations) {

                    if (\is_subclass_of($element->collectionItemsClass, AbstractType::class)) {
                        for ($i = 1; $i <= $iterations; $i++) {
                            $elementData[] = $this->generateExample($this->apiDoc->forms[$element->collectionItemsClass]);
                        }
                    } else {
                        $collectionElementDoc = new FormElementDoc;
                        $collectionElementDoc->constraints = $element->collectionConstraints;
                        $collectionElementDoc->analyzeConstraints();

                        for ($i = 1; $i <= $iterations; $i++) {
                            $elementData[] = $this->generateScalarExample($collectionElementDoc);
                        }

                    }

                }

            } elseif (\is_subclass_of($element->fieldFormTypeClass, AbstractType::class)) {
                $elementData = $this->generateScalarExample($element);
            } else {
                $this->logger->warning('Cannot generate element '.$name.' of unknown type '.$element->fieldFormTypeClass);
                continue;
            }

            $result[$name] = $elementData;

        }
        return $result;
    }

    private function getDefaultMaxInteger(): int
    {
        //return 100000;
        return PHP_INT_MAX;
    }

    protected function randomChoice(array $choiceTypeChoices)
    {
        return \count($choiceTypeChoices) ? $choiceTypeChoices[\array_rand($choiceTypeChoices)] : null;
    }

    /**
     * @param FormDoc|FormElementDoc $scalarOrForm
     *
     * @return array|int|mixed|string|null
     * @throws \ReflectionException
     */
    protected function generateScalarOrFormExample($scalarOrForm)
    {
        if (is_subclass_of($entryClass, AbstractForm::class)) {
            return $this->generateExample($entryClass);
        }

        return $this->generateScalarExample(new $entryClass, $passedOptions);
    }

    protected function generateScalarExample(FormElementDoc $element)
    {

        if ($element->choices) {
            return $this->randomChoice($element->choices);
        }

        if ($element->example) {
            return $element->example;
        }

        $max = $element->isValueDefined($element->maximum) ? $element->maximum : $this->getDefaultMaxInteger();
        if ($element->exclusiveMaximum) {
            $max--;
        }

        $min = $element->isValueDefined($element->minimum) ? $element->minimum : 0;
        if ($element->exclusiveMinimum) {
            $min++;
        }

        $minLength = $element->minLength ?: 0;
        $maxLength = $element->maxLength ?: 255;

        $elementClassReflection = new \ReflectionClass($element->fieldFormTypeClass);

        if ($element->scalarRestrictedToType && $elementClassReflection->isSubclassOf(Type\TextType::class)) {
            if ($element->scalarRestrictedToType === 'numeric') {
                $lengthThreshold = \random_int($minLength,$maxLength);
                $generatedValue = '';
                for ($i = 1; $i <= $lengthThreshold; $i++) {
                    $generatedValue .= (string) \random_int(0,9);
                }
                return $generatedValue;
            }
        }

        if ($elementClassReflection->isSubclassOf(Type\IntegerType::class)) {
            return $this->faker->numberBetween($min,$max);
        }

        if ($elementClassReflection->isSubclassOf(Type\NumberType::class)) {
            return $this->faker->randomFloat(6,$min,$max);
        }

        if ($elementClassReflection->isSubclassOf(Type\PercentType::class)) {
            return $this->faker->randomFloat(null,0,1);
        }

        if ($elementClassReflection->isSubclassOf(Type\EmailType::class)) {
            return $this->faker->email;
        }

        if ($elementClassReflection->isSubclassOf(Type\UrlType::class)) {
            return $this->faker->url;
        }

        if ($elementClassReflection->isSubclassOf(Type\CheckboxType::class)) {
            return $this->faker->boolean;
        }

        if ($elementClassReflection->isSubclassOf(Type\DateType::class)) {
            return $this->faker->date(Constants::FORMAT_YMD);
        }

        if ($elementClassReflection->isSubclassOf(Type\TimeType::class)) {
            return $this->faker->time(Constants::FORMAT_HOURMIN);
        }

        // todo: timestamp?
        if ($elementClassReflection->isSubclassOf(Type\DateTimeType::class)) {
            return $this->faker->dateTime->format(Constants::FORMAT_YMD_HIS);
        }

        if ($elementClassReflection->isSubclassOf(JsonType::class)) {
            return [];
        }

        if ($elementClassReflection->isSubclassOf(BooleanType::class)) {
            if ($element->isNotNull) {
                return \random_int(0, 1);
            }

            $rnd = \random_int(0, 2);
            return (($rnd === 2) ? null : $rnd);
        }

        if ($elementClassReflection->isSubclassOf(Type\TextType::class) || $elementClassReflection->isSubclassOf(Type\TextareaType::class)) {
            if ($minLength === 0) {
                return $this->faker->realText($maxLength);
            }
            // todo: as faker has not minLength parameter from text out of the box, currently using password...
            return $this->faker->password($minLength, $maxLength);
        }

        return 'UNKNOWN DATA TYPE';

    }

}