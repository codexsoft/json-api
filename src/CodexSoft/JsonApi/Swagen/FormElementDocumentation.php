<?php


namespace CodexSoft\JsonApi\Swagen;


class FormElementDocumentation
{
    public const TYPE_COLLECTION = 1;
    public const TYPE_FORM = 2;
    public const TYPE_SCALAR = 3;

    public $example;
    public $minLength;
    public $maxLength;

    /** @var int */
    public $type;

    /** @var bool */
    public $exclusiveMinimum;
    public $minimum;

    /** @var bool */
    public $exclusiveMaximum;
    public $maximum;
    public $enum;
    public $label;
    public $description;
    public $default;

    /** @var string  */
    public $fieldTypeClass;

    /** @var string  */
    public $collectionElementsType;
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

    public $swaggerReferencesToClass;
    // workaround to document nested object (All the siblings of $ref are ignored according to the spec.)
    //$propertyReference = $lib->referenceToDefinition(new \ReflectionClass($docElement->swaggerReferencesToClass));
    //$lines[] = ' *     @SWG\Property(property="'.$name.'", allOf={@SWG\Schema(ref="'.$propertyReference.'")}'.$elementExtraAttributesString.'),';

    /** @var string */
    public $swaggerType;

    /** @var string */
    public $referenceToDefinition; // $propertyReference


    public function isCollection(): bool
    {
        return $this->type === self::TYPE_COLLECTION;
    }

    public function isScalar(): bool
    {
        return $this->type === self::TYPE_SCALAR;
    }

    public function isForm(): bool
    {
        return $this->type === self::TYPE_FORM;
    }

}