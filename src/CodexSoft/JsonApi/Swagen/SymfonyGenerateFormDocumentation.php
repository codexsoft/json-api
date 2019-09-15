<?php

namespace CodexSoft\JsonApi\Swagen;

use CodexSoft\Code\Helpers\Classes;
use Doctrine\Common\Collections\ArrayCollection;
use CodexSoft\JsonApi\Form\AbstractForm;
use CodexSoft\JsonApi\Form\Extensions\FormFieldDefaultValueExtension;
use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use CodexSoft\JsonApi\Response\AbstractBaseResponse;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenResponseInterface;
use CodexSoft\Code\Helpers\Arrays;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type;

class SymfonyGenerateFormDocumentation
{

    public const OPTIONS_FIELD_NAME = 'data_collector/passed_options';

    /** @var SwagenLib */
    private $lib;

    public function __construct(SwagenLib $lib)
    {
        $this->lib = $lib;
    }

    /**
     * @param $formClass
     *
     * @return FormDocumentation
     * @throws \ReflectionException
     */
    public function parseIntoDoc($formClass): FormDocumentation
    {
        $docForm = new FormDocumentation;

        $formFactory = $this->lib->getFormFactory();
        $lib = $this->lib;

        $formBuilder = $formFactory->create($formClass);
        $elements = $formBuilder->all();

        foreach ($elements as $name => $element) {

            $docElement = new FormElementDocumentation();
            $docForm->items[] = $docElement;

            $config = $element->getConfig();
            $elementInnerType = $element->getConfig()->getType()->getInnerType();
            $elementInnerTypeClass = \get_class($elementInnerType);

            /** @deprecated  */
            $elementExtraAttributes = [];

            $docElement->typeClass = $elementInnerTypeClass;

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
                $defaultValue = $passedOptions['default'];
                $docElement->default = $defaultValue;

                if (($elementInnerTypeClass === BooleanType::class) && \is_bool($defaultValue)) {
                    $docElement->default = $defaultValue ? BooleanType::VALUE_TRUE : BooleanType::VALUE_FALSE;
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
                    //$requiredFields[] = '"'.$name.'"';
                    break;
                }

                if ($constraint instanceof Constraints\NotNull) {
                    $docForm->requiredFields[] = $name;
                    //$requiredFields[] = '"'.$name.'"';
                    break;
                }

            }

            $fieldIsNotNull = false;
            foreach($constraints as $constraint) {
                if ($constraint instanceof Constraints\NotNull) {
                    $fieldIsNotNull = true;
                    break;
                }
            }

            if ($elementInnerTypeClass === BooleanType::class) {
                if ($fieldIsNotNull) {
                    //$elementExtraAttributes['enum'] = [0,1]; // [true,false]
                    $docElement->enum = [0,1]; // [true,false]
                } else {
                    $docElement->enum = [0,1,null]; // [true,false,null]
                    //$elementExtraAttributes['enum'] = [0,1,null]; // [true,false,null]
                }

            }

            foreach ($constraints as $constraint) {

                if ($constraint instanceof Constraints\NotBlank) {
                    continue;
                }

                if ($constraint instanceof Constraints\Length) {
                    if ($constraint->min !== null) {
                        //$elementExtraAttributes['minLength'] = $constraint->min;
                        $docElement->minLength = $constraint->min;
                    }

                    if ($constraint->max !== null) {
                        //$elementExtraAttributes['maxLength'] = $constraint->max;
                        $docElement->maxLength = $constraint->max;
                    }

                } elseif ($constraint instanceof Constraints\GreaterThan) {
                    $docElement->exclusiveMinimum = true;
                    $docElement->minimum = $constraint->value;
                    //$elementExtraAttributes['exclusiveMinimum'] = true;
                    //$elementExtraAttributes['minimum'] = $constraint->value;
                } elseif ($constraint instanceof Constraints\GreaterThanOrEqual) {
                    $docElement->minimum = $constraint->value;
                    //$elementExtraAttributes['minimum'] = $constraint->value;
                } elseif ($constraint instanceof Constraints\LessThan) {
                    $docElement->exclusiveMaximum = true;
                    $docElement->maximum = $constraint->value;
                    //$elementExtraAttributes['exclusiveMaximum'] = true;
                    //$elementExtraAttributes['maximum'] = $constraint->value;
                } elseif ($constraint instanceof Constraints\LessThanOrEqual) {
                    $docElement->maximum = $constraint->value;
                    //$elementExtraAttributes['maximum'] = $constraint->value;
                } elseif ($constraint instanceof Constraints\Choice) {
                    //$elementExtraAttributes['enum'] = \array_values($constraint->choices);
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

            // this is exactly swagger rendering
            //$elementExtraAttributesString = '';
            //if ($elementExtraAttributes) {
            //    foreach($elementExtraAttributes as $attribute => $value) {
            //
            //        if ($value instanceof \Closure) {
            //            continue;
            //        }
            //
            //        $preparedValue = $value;
            //        if ($value === null) {
            //            $preparedValue = 'null';
            //        } elseif (\is_bool($value)) {
            //            $preparedValue = $value ? 'true' : 'false';
            //        } elseif (\is_array($value)) {
            //            $jsonValue = \json_encode($value);
            //            $preparedValue = '{'.trim($jsonValue,'[]').'}';
            //        } elseif(\is_string($value)) {
            //            $preparedValue = '"'.$value.'"';
            //        }
            //        $elementExtraAttributesString .= ', '.$attribute.'='.$preparedValue;
            //
            //    }
            //}

            if ($elementInnerType instanceof CollectionType) {

                $docElement->isCollection = true;
                $elementCollectionEntryType = $element->getConfig()->getOption('entry_type');
                $docElement->collectionElementsType = $elementCollectionEntryType;

                // this is rendering
                //if ($elementCollectionEntryType) {
                //
                //    if ($nativeType = $lib->detectSwaggerTypeFromNativeType($elementCollectionEntryType)) {
                //        $lines[] = ' *   @SWG\Property(property="'.$name.'", type="array", @SWG\Items(type="'.$nativeType.'") '.$elementExtraAttributesString.'),';
                //    } else {
                //        $entryTypedefRef = $lib->referenceToDefinition(new \ReflectionClass($elementCollectionEntryType));
                //        $lines[] = ' *     @SWG\Property(property="'.$name.'", type="array" '.$elementExtraAttributesString.',';
                //        $lines[] = ' *       @SWG\Items(ref="'.$entryTypedefRef.'"),';
                //        $lines[] = ' *     ),';
                //    }
                //
                //}

            } else {

                $docElement->isCollection = false;

                //if (\class_exists($innerTypeClass) && \is_subclass_of($innerTypeClass,BaseForm::class)) {
                if (\class_exists($elementInnerTypeClass) && (
                        \is_subclass_of($elementInnerTypeClass,AbstractForm::class) ||
                        (\is_subclass_of($elementInnerTypeClass,AbstractBaseResponse::class) && Classes::isImplements($elementInnerTypeClass,SwagenResponseInterface::class))
                    )
                ) {
                    $docElement->isForm = true;
                    // to provide correct naming of wrapped data
                    $innerTypeClassReflection = new \ReflectionClass($elementInnerTypeClass);
                    if ($innerTypeClassReflection->isAnonymous()) {
                        $docElement->swaggerReferencesToClass = $formClass;
                    } else {
                        $docElement->swaggerReferencesToClass = $elementInnerTypeClass;
                    }

                    $propertyReference = $lib->referenceToDefinition(new \ReflectionClass($docElement->swaggerReferencesToClass));
                    $docElement->referenceToDefinition = $propertyReference;

                    // workaround to document nested object (All the siblings of $ref are ignored according to the spec.)
                    //$lines[] = ' *     @SWG\Property(property="'.$name.'", allOf={@SWG\Schema(ref="'.$propertyReference.'")}'.$elementExtraAttributesString.'),';
                } else {

                    $enum = $docElement->enum ?? null;
                    if ($elementInnerType instanceof BooleanType) {
                        $docElement->swaggerType = 'boolean';
                    } else if ($elementInnerType instanceof Type\ChoiceType && \is_array($enum) && (
                            Arrays::areIdenticalByValuesStrict($enum,[true,false,null]) ||
                            Arrays::areIdenticalByValuesStrict($enum,[true,false])
                        )
                    ) {
                        $docElement->swaggerType = 'boolean';
                    } else {
                        $docElement->swaggerType = $lib->typeClassToSwaggerType($elementInnerTypeClass);
                    }

                }
            }

        }

        //if (\count($requiredFields)) {
        //    $lines[] = ' *     required={'.implode(', ',$requiredFields).'}';
        //}

        //return $lines;
        return $docForm;
    }

    /**
     * @param $formClass
     *
     * @return array
     * @throws \ReflectionException
     */
    public function parseIntoSchema($formClass): array
    {
        $lines = [];

        $formFactory = $this->lib->getFormFactory();
        $lib = $this->lib;

        $formBuilder = $formFactory->create($formClass);
        $elements = $formBuilder->all();
        $requiredFields = [];

        foreach ($elements as $name => $element) {
            $config = $element->getConfig();
            $type = $element->getConfig()->getType();
            $innerType = $type->getInnerType();
            $innerTypeClass = \get_class($innerType);
            $elementExtraAttributes = [];

            //$passedOptions = $config->getAttribute('data_collector/passed_options');
            //if ($passedOptions === null) {
            //    $passedOptions = [];
            //}

            if ($config->hasAttribute(self::OPTIONS_FIELD_NAME)) {
                $passedOptions = $config->getAttribute(self::OPTIONS_FIELD_NAME);
                if ($passedOptions === null) {
                    $passedOptions = [];
                }
            } else {
                $passedOptions = $config->getOptions();
            }

            $elementExtraAttributes['description'] = $passedOptions['label'] ?? '';

            if (isset($passedOptions['example'])) {
                $elementExtraAttributes['description'] .= '<br/>Пример: '.htmlspecialchars($passedOptions['example']);
                $elementExtraAttributes['example'] = htmlspecialchars($passedOptions['example']);
            }

            if (array_key_exists('default', $passedOptions)
                && ($passedOptions['default'] !== FormFieldDefaultValueExtension::UNDEFINED)
            ) {
                $defaultValue = $passedOptions['default'];
                $elementExtraAttributes['default'] = $defaultValue;

                if (($innerTypeClass === BooleanType::class) && \is_bool($defaultValue)) {
                    $elementExtraAttributes['default'] = $defaultValue ? BooleanType::VALUE_TRUE : BooleanType::VALUE_FALSE;
                }

            }

            // overriding default option with empty_data option value
            // todo: empty_data is not very good option, avoiding using it
            //if (array_key_exists('empty_data', $passedOptions) && $passedOptions['empty_data'] !== null) {
            //    $elementExtraAttributes['default'] = $passedOptions['empty_data'];
            //}

            if ($innerTypeClass === Type\ChoiceType::class) {
                $choiceTypeChoices = $passedOptions['choices'] ?? [];
                $elementExtraAttributes['enum'] = \array_values($choiceTypeChoices);
            }

            $constraints = $passedOptions['constraints'] ?? [];
            foreach($constraints as $constraint) {
                // todo: if field has default value, ignore NotBlank and avoid to set field as required?
                if ($constraint instanceof Constraints\NotBlank) {
                    $requiredFields[] = '"'.$name.'"';
                    break;
                }

                if ($constraint instanceof Constraints\NotNull) {
                    $requiredFields[] = '"'.$name.'"';
                    break;
                }

            }

            $fieldIsNotNull = false;
            foreach($constraints as $constraint) {
                if ($constraint instanceof Constraints\NotNull) {
                    $fieldIsNotNull = true;
                    break;
                }
            }

            if ($innerTypeClass === BooleanType::class) {
                if ($fieldIsNotNull) {
                    $elementExtraAttributes['enum'] = [0,1]; // [true,false]
                } else {
                    $elementExtraAttributes['enum'] = [0,1,null]; // [true,false,null]
                }

            }

            foreach ($constraints as $constraint) {

                if ($constraint instanceof Constraints\NotBlank) {
                    continue;
                }

                if ($constraint instanceof Constraints\Length) {
                    if ($constraint->min !== null) {
                        $elementExtraAttributes['minLength'] = $constraint->min;
                    }

                    if ($constraint->max !== null) {
                        $elementExtraAttributes['maxLength'] = $constraint->max;
                    }

                } elseif ($constraint instanceof Constraints\GreaterThan) {
                    $elementExtraAttributes['exclusiveMinimum'] = true;
                    $elementExtraAttributes['minimum'] = $constraint->value;
                } elseif ($constraint instanceof Constraints\GreaterThanOrEqual) {
                    $elementExtraAttributes['minimum'] = $constraint->value;
                } elseif ($constraint instanceof Constraints\LessThan) {
                    $elementExtraAttributes['exclusiveMaximum'] = true;
                    $elementExtraAttributes['maximum'] = $constraint->value;
                } elseif ($constraint instanceof Constraints\LessThanOrEqual) {
                    $elementExtraAttributes['maximum'] = $constraint->value;
                } elseif ($constraint instanceof Constraints\Choice) {
                    $elementExtraAttributes['enum'] = \array_values($constraint->choices);
                }
            }

            if (isset($elementExtraAttributes['enum']) && \is_array($elementExtraAttributes['enum']) && (\count($elementExtraAttributes['enum']) > 10)) {
                $enumCollection = new ArrayCollection($elementExtraAttributes['enum']);
                $first = $enumCollection->first();
                $last = $enumCollection->last();
                if (\is_int($first) && \is_int($last) && ($first < $last)) {
                    unset($elementExtraAttributes['enum']);
                    $elementExtraAttributes['minimum'] = $first;
                    $elementExtraAttributes['maximum'] = $last;
                }
            }

            $label = \addslashes($passedOptions['label'] ?? '');

            $elementExtraAttributesString = '';
            if ($elementExtraAttributes) {
                foreach($elementExtraAttributes as $attribute => $value) {

                    if ($value instanceof \Closure) {
                        continue;
                    }

                    $preparedValue = $value;
                    if ($value === null) {
                        $preparedValue = 'null';
                    } elseif (\is_bool($value)) {
                        $preparedValue = $value ? 'true' : 'false';
                    } elseif (\is_array($value)) {
                        $jsonValue = \json_encode($value);
                        $preparedValue = '{'.trim($jsonValue,'[]').'}';
                    } elseif(\is_string($value)) {
                        $preparedValue = '"'.$value.'"';
                    }
                    $elementExtraAttributesString .= ', '.$attribute.'='.$preparedValue;

                }
            }

            if ($innerType instanceof CollectionType) {

                $elementCollectionEntryType = $element->getConfig()->getOption('entry_type');

                if ($elementCollectionEntryType) {

                    if ($nativeType = $lib->detectSwaggerTypeFromNativeType($elementCollectionEntryType)) {
                        $lines[] = ' *   @SWG\Property(property="'.$name.'", type="array", @SWG\Items(type="'.$nativeType.'") '.$elementExtraAttributesString.'),';
                    } else {
                        $entryTypedefRef = $lib->referenceToDefinition(new \ReflectionClass($elementCollectionEntryType));
                        $lines[] = ' *     @SWG\Property(property="'.$name.'", type="array" '.$elementExtraAttributesString.',';
                        $lines[] = ' *       @SWG\Items(ref="'.$entryTypedefRef.'"),';
                        $lines[] = ' *     ),';
                    }

                }

            } else {


                //if (\class_exists($innerTypeClass) && \is_subclass_of($innerTypeClass,BaseForm::class)) {
                if (\class_exists($innerTypeClass) && (
                    \is_subclass_of($innerTypeClass,AbstractForm::class) ||
                    (\is_subclass_of($innerTypeClass,AbstractBaseResponse::class) && Classes::isImplements($innerTypeClass,SwagenResponseInterface::class))
                    )
                ) {
                    // to provide correct naming of wrapped data
                    $innerTypeClassReflection = new \ReflectionClass($innerTypeClass);
                    if ($innerTypeClassReflection->isAnonymous()) {
                        $propertyReference = $lib->referenceToDefinition(new \ReflectionClass($formClass));
                    } else {
                        $propertyReference = $lib->referenceToDefinition(new \ReflectionClass($innerTypeClass));
                    }

                    // workaround to document nested object (All the siblings of $ref are ignored according to the spec.)
                    $lines[] = ' *     @SWG\Property(property="'.$name.'", allOf={@SWG\Schema(ref="'.$propertyReference.'")}'.$elementExtraAttributesString.'),';
                } else {

                    $enum = $elementExtraAttributes['enum'] ?? null;
                    if ($innerType instanceof BooleanType) {
                        $lines[] = ' *     @SWG\Property(property="'.$name.'", type="boolean"'.$elementExtraAttributesString.'),';
                    } else if ($innerType instanceof Type\ChoiceType && \is_array($enum) && (
                            Arrays::areIdenticalByValuesStrict($enum,[true,false,null]) ||
                            Arrays::areIdenticalByValuesStrict($enum,[true,false])
                        )
                    ) {
                        $lines[] = ' *     @SWG\Property(property="'.$name.'", type="boolean"'.$elementExtraAttributesString.'),';
                    } else {
                        $lines[] = ' *     @SWG\Property(property="'.$name.'", type="'.$lib->typeClassToSwaggerType($innerTypeClass).'"'.$elementExtraAttributesString.'),';
                    }

                }
            }

        }

        if (\count($requiredFields)) {
            $lines[] = ' *     required={'.implode(', ',$requiredFields).'}';
        }

        return $lines;
    }

    /**
     * @param $formClass
     *
     * @return array
     * @throws \ReflectionException
     * @deprecated use generateFormAsDefinition, generateFormAsParameter or generateFormAsParameterAndDefinition
     */
    public function parseIntoDefinitionAndParameter($formClass): array
    {

        $lines = [];

        //$formFactory = $this->formFactory;
        $lib = $this->lib;

        $reflection = new \ReflectionClass( $formClass );
        $schemaContent = $this->parseIntoSchema($formClass);

        $definitionLines = [
            ' * @SWG\Definition(',
            ' *   definition="'.$lib->formTitle($reflection).'",',
            ' *   type="object",',
        ];

        if ($schemaContent) {
            \array_push($definitionLines,...$schemaContent);
        }

        $definitionLines[] = ' * )';
        $definitionLines[] = ' *';

        $parameterLines = [
            ' * @SWG\Parameter(',
            ' *   parameter="'.$lib->formTitle($reflection).'",',
            ' *   in="body",',
            ' *   name="'.$lib->formTitle($reflection).'",',
            ' *   @SWG\Schema(',
        ];

        if ($schemaContent) {
            \array_push($parameterLines,...$schemaContent);
        }

        $parameterLines[] = ' *   )';
        $parameterLines[] = ' * )';
        $parameterLines[] = ' *';

        \array_push($lines,...$parameterLines);
        \array_push($lines,...$definitionLines);

        return $lines;

    }

    /**
     * @param $formClass
     *
     * @return array
     * @throws \ReflectionException
     */
    public function generateFormAsDefinition($formClass): array
    {
        $reflection = new \ReflectionClass( $formClass );
        $schemaContent = $this->parseIntoSchema($formClass);
        return $this->generateNamedDefinition($this->lib->formTitle($reflection), $schemaContent);
    }

    /**
     * @param $formClass
     *
     * @return array
     * @throws \ReflectionException
     */
    public function generateFormAsParameter($formClass): array
    {
        $reflection = new \ReflectionClass( $formClass );
        $schemaContent = $this->parseIntoSchema($formClass);
        return $this->generateNamedParameter($this->lib->formTitle($reflection), $schemaContent);
    }

    /**
     * @param $formClass
     *
     * @return array
     * @throws \ReflectionException
     */
    public function generateFormAsParameterAndDefinition($formClass): array
    {
        $reflection = new \ReflectionClass( $formClass );
        $schemaContent = $this->parseIntoSchema($formClass);

        $lines = $this->generateNamedParameter($this->lib->formTitle($reflection), $schemaContent);
        $definitionLines = $this->generateNamedDefinition($this->lib->formTitle($reflection), $schemaContent);
        \array_push($lines, ...$definitionLines);

        return $lines;
    }

    /**
     * @param $title
     * @param $schema
     *
     * @return array
     */
    public function generateNamedDefinition(string $title,array $schema): array
    {

        $lines = [
            ' * @SWG\Definition(',
            ' *   definition="'.$title.'",',
            ' *   type="object",',
        ];

        if ($schema) {
            \array_push($lines,...$schema);
        }

        $lines[] = ' * )';
        $lines[] = ' *';

        return $lines;

    }

    /**
     * @param string $title
     * @param array $schema
     *
     * @return array
     */
    public function generateNamedParameter(string $title,array $schema): array
    {
        $parameterLines = [
            ' * @SWG\Parameter(',
            ' *   parameter="'.$title.'",',
            ' *   in="body",',
            ' *   name="'.$title.'",',
            ' *   @SWG\Schema(',
        ];

        if ($schema) {
            \array_push($parameterLines,...$schema);
        }

        $parameterLines[] = ' *   )';
        $parameterLines[] = ' * )';
        $parameterLines[] = ' *';

        return $parameterLines;
    }

}