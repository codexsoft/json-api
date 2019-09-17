<?php


namespace CodexSoft\JsonApi\Documentation\Collector;

use CodexSoft\Code\Traits\Loggable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type;
use CodexSoft\Code\Helpers\Classes;
use CodexSoft\JsonApi\Form\AbstractForm;
use CodexSoft\JsonApi\Form\Extensions\FormFieldDefaultValueExtension;
use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use CodexSoft\JsonApi\Response\AbstractBaseResponse;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenResponseInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class FormDocCollector
{

    use Loggable;

    public const OPTIONS_FIELD_NAME = 'data_collector/passed_options';

    /** @var FormFactory */
    private $formFactory;

    public function __construct(FormFactory $formFactory, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->formFactory = $formFactory;
    }

    /**
     * @param string $formClass
     *
     * @return FormDoc
     * @throws \ReflectionException
     */
    public function collect(string $formClass): FormDoc
    {
        $docForm = new FormDoc;
        $docForm->class = $formClass;

        //$formFactory = $this->lib->getFormFactory();
        $formFactory = $this->formFactory;

        try {
            $formClassReflection = new \ReflectionClass($formClass);
        } catch (\ReflectionException $e) {
            throw new \Exception("SKIPPING $formClass form Failed to create ReflectionClass: ".$e);
            //$this->getLogger()->notice('Failed to create ReflectionClass for '.$formClass.': '.$e);
            //return null;
        }

        if ($formClassReflection->isAbstract()) {
            throw new \Exception("SKIPPING form $formClass: class is abstract");
            //$this->getLogger()->notice("$formClass form SKIPPING: class is abstract");
            //return null;
        }

        if (!$formClassReflection->implementsInterface(FormTypeInterface::class)) {
            throw new \Exception("SKIPPING form $formClass: class does not implement ".FormTypeInterface::class);
            //$this->getLogger()->notice("$formClass form SKIPPING: class is abstract");
            //return null;
        }

        $formBuilder = $formFactory->create($formClass);
        $elements = $formBuilder->all();

        foreach ($elements as $name => $element) {

            $docElement = new FormElementDoc();
            $docForm->items[$name] = $docElement;

            $config = $element->getConfig();
            $elementInnerType = $element->getConfig()->getType()->getInnerType();
            $elementInnerTypeClass = \get_class($elementInnerType);

            $docElement->fieldTypeClass = $elementInnerTypeClass;

            if ($config->hasAttribute(self::OPTIONS_FIELD_NAME)) {
                $passedOptions = $config->getAttribute(self::OPTIONS_FIELD_NAME);
                if ($passedOptions === null) {
                    $passedOptions = [];
                }
            } else {
                $passedOptions = $config->getOptions();
            }

            $docElement->description = $passedOptions['label'] ?? '';

            if (isset($passedOptions['example'])) {
                $docElement->example = htmlspecialchars($passedOptions['example']);
            }

            if (array_key_exists('default', $passedOptions)
                && ($passedOptions['default'] !== FormFieldDefaultValueExtension::UNDEFINED)
            ) {
                $docElement->default = $passedOptions['default'];
                if (($elementInnerTypeClass === BooleanType::class) && \is_bool($docElement->default)) {
                    $docElement->default = $docElement->default ? BooleanType::VALUE_TRUE : BooleanType::VALUE_FALSE;
                }
            }

            // overriding default option with empty_data option value
            // todo: empty_data is not very good option, avoiding using it

            if ($elementInnerTypeClass === Type\ChoiceType::class) {
                $docElement->enum = \array_values($passedOptions['choices'] ?? []);
            }

            $constraints = $passedOptions['constraints'] ?? [];
            foreach($constraints as $constraint) {
                // todo: if field has default value, ignore NotBlank and avoid to set field as required?
                if ($constraint instanceof Constraints\NotBlank) {
                    $docForm->requiredFields[] = $name;
                    break;
                }

                if ($constraint instanceof Constraints\NotNull) {
                    $docForm->requiredFields[] = $name;
                    break;
                }

            }

            if ($elementInnerTypeClass === BooleanType::class) {

                $fieldIsNotNull = false;
                foreach($constraints as $constraint) {
                    if ($constraint instanceof Constraints\NotNull) {
                        $fieldIsNotNull = true;
                        break;
                    }
                }

                if ($fieldIsNotNull) {
                    $docElement->enum = [0,1]; // [true,false]
                } else {
                    $docElement->enum = [0,1,null]; // [true,false,null]
                }

            }

            foreach ($constraints as $constraint) {

                if ($constraint instanceof Constraints\NotBlank) {
                    continue;
                }

                if ($constraint instanceof Constraints\Length) {
                    if ($constraint->min !== null) {
                        $docElement->minLength = $constraint->min;
                    }

                    if ($constraint->max !== null) {
                        $docElement->maxLength = $constraint->max;
                    }

                } elseif ($constraint instanceof Constraints\GreaterThan) {
                    $docElement->exclusiveMinimum = true;
                    $docElement->minimum = $constraint->value;
                } elseif ($constraint instanceof Constraints\GreaterThanOrEqual) {
                    $docElement->minimum = $constraint->value;
                } elseif ($constraint instanceof Constraints\LessThan) {
                    $docElement->exclusiveMaximum = true;
                    $docElement->maximum = $constraint->value;
                } elseif ($constraint instanceof Constraints\LessThanOrEqual) {
                    $docElement->maximum = $constraint->value;
                } elseif ($constraint instanceof Constraints\Choice) {
                    $docElement->enum = \array_values($constraint->choices);
                }
            }

            // changing enum to min/max if enum is big
            if (\is_array($docElement->enum) && \count($docElement->enum)) {
                $enumCollection = new ArrayCollection($docElement->enum);
                $first = $enumCollection->first();
                $last = $enumCollection->last();
                if (\is_int($first) && \is_int($last) && ($first < $last)) {
                    $docElement->enum = null;
                    $docElement->minimum = $first;
                    $docElement->exclusiveMinimum = false;
                    $docElement->maximum = $last;
                    $docElement->exclusiveMaximum = false;
                }
            }

            if ($elementInnerType instanceof CollectionType) {

                $docElement->type = FormElementDoc::TYPE_COLLECTION;
                $elementCollectionEntryType = $element->getConfig()->getOption('entry_type');
                $docElement->collectionElementsType = $elementCollectionEntryType;

                //} elseif (\class_exists($elementInnerTypeClass) && Classes::isImplements($elementInnerTypeClass, FormTypeInterface::class)) {
            } elseif (\class_exists($elementInnerTypeClass) && (
                    \is_subclass_of($elementInnerTypeClass,AbstractForm::class) ||
                    (\is_subclass_of($elementInnerTypeClass,AbstractBaseResponse::class) && Classes::isImplements($elementInnerTypeClass,SwagenResponseInterface::class))
                )
            ) {

                // to provide correct naming of wrapped data
                $docElement->type = FormElementDoc::TYPE_FORM;
                $innerTypeClassReflection = new \ReflectionClass($elementInnerTypeClass);
                $docElement->swaggerReferencesToClass = $innerTypeClassReflection->isAnonymous() ? $formClass : $elementInnerTypeClass;

            } else {
                $docElement->type = FormElementDoc::TYPE_SCALAR;
            }

        }

        return $docForm;
    }

}