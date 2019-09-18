<?php


namespace CodexSoft\JsonApi\Documentation\Collector;

use CodexSoft\Code\Traits\Loggable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Response;
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
     * @return FormDoc|null
     * @throws \ReflectionException
     */
    public function collect(string $formClass): ?FormDoc
    {
        $docForm = new FormDoc;
        $docForm->class = $formClass;

        $formFactory = $this->formFactory;

        try {
            $formClassReflection = new \ReflectionClass($formClass);
        } catch (\ReflectionException $e) {
            throw new \Exception("SKIPPING $formClass form Failed to create ReflectionClass: ".$e->getMessage());
        }

        if ($formClassReflection->isAbstract()) {
            //throw new \Exception("SKIPPING form $formClass: class is abstract");
            $this->logger->debug("SKIPPING form $formClass: class is abstract");
            return null;
        }

        if ($formClassReflection->isInterface()) {
            $this->logger->debug("SKIPPING form $formClass: is interface");
            return null;
        }

        if (!$formClassReflection->isSubclassOf(AbstractForm::class) && !$formClassReflection->isSubclassOf(Response::class)) {
            //throw new \Exception("SKIPPING form $formClass: class does not implement ".AbstractForm::class);
            $this->logger->debug("SKIPPING form $formClass: class does not implement ".AbstractForm::class);
            return null;
        }

        $formBuilder = $formFactory->create($formClass);
        $elements = $formBuilder->all();

        foreach ($elements as $name => $element) {

            $docElement = new FormElementDoc();
            $docForm->items[$name] = $docElement;

            $config = $element->getConfig();
            $elementInnerType = $element->getConfig()->getType()->getInnerType();
            $elementInnerTypeClass = \get_class($elementInnerType);

            $docElement->fieldFormTypeClass = $elementInnerTypeClass;

            if ($config->hasAttribute(self::OPTIONS_FIELD_NAME)) {
                $passedOptions = $config->getAttribute(self::OPTIONS_FIELD_NAME);
                if ($passedOptions === null) {
                    $passedOptions = [];
                }
            } else {
                $passedOptions = $config->getOptions();
            }

            $docElement->label = $passedOptions['label'] ?? '';

            if (isset($passedOptions['example'])) {
                $docElement->example = htmlspecialchars($passedOptions['example']);
            }

            if (array_key_exists('default', $passedOptions)
                && ($passedOptions['default'] !== FormFieldDefaultValueExtension::UNDEFINED)
            ) {
                $docElement->defaultValue = $passedOptions['default'];
                if (($elementInnerTypeClass === BooleanType::class) && \is_bool($docElement->defaultValue)) {
                    $docElement->defaultValue = $docElement->defaultValue ? BooleanType::VALUE_TRUE : BooleanType::VALUE_FALSE;
                }
            }

            // overriding default option with empty_data option value
            // todo: empty_data is not very good option, avoiding using it

            if ($elementInnerTypeClass === Type\ChoiceType::class) {
                $docElement->choices = \array_values($passedOptions['choices'] ?? []);
            }

            $docElement->isRequired = false;
            $docElement->isNotBlank = false;
            $docElement->isNotNull = false;

            $constraints = $passedOptions['constraints'] ?? [];
            $docElement->constraints = $constraints;
            $docElement->analyzeConstraints();

            // todo: if field has default value, ignore NotBlank and avoid to set field as required?
            if ($docElement->isRequired) {
                $docForm->requiredFields[] = $name;
            }

            // changing element choices to min/max if enum is big and values are integers
            if (\is_array($docElement->choices) && \count($docElement->choices)) {
                $enumCollection = new ArrayCollection($docElement->choices);
                $first = $enumCollection->first();
                $last = $enumCollection->last();
                if (\is_int($first) && \is_int($last) && ($first < $last)) {
                    $docElement->choices = null;
                    $docElement->minimum = $first;
                    $docElement->exclusiveMinimum = false;
                    $docElement->maximum = $last;
                    $docElement->exclusiveMaximum = false;
                }
            }

            if ($elementInnerType instanceof CollectionType) {

                $docElement->type = FormElementDoc::TYPE_COLLECTION;
                $elementCollectionEntryType = $element->getConfig()->getOption('entry_type');
                $docElement->collectionItemsClass = $elementCollectionEntryType;

            } elseif (\class_exists($elementInnerTypeClass) && (
                    \is_subclass_of($elementInnerTypeClass,AbstractForm::class) ||
                    (\is_subclass_of($elementInnerTypeClass,AbstractBaseResponse::class) && Classes::isImplements($elementInnerTypeClass,SwagenResponseInterface::class))
                )
            ) {

                // to provide correct naming of wrapped data
                $docElement->type = FormElementDoc::TYPE_FORM;
                $innerTypeClassReflection = new \ReflectionClass($elementInnerTypeClass);
                $docElement->fieldReferencesToFormClass = $innerTypeClassReflection->isAnonymous() ? $formClass : $elementInnerTypeClass;

            } else {
                $docElement->type = FormElementDoc::TYPE_SCALAR;
            }

        }

        return $docForm;
    }

}