<?php /** @noinspection DuplicatedCode */


namespace CodexSoft\JsonApi\Documentation\SwaggerGenerator;

use CodexSoft\Code\Traits\Loggable;
use CodexSoft\JsonApi\Documentation\Collector\ActionDoc;
use CodexSoft\JsonApi\Documentation\Collector\ApiDoc;
use CodexSoft\JsonApi\Documentation\Collector\ResponseDoc;
use CodexSoft\JsonApi\Form\Type\JsonType\JsonType;
use Symfony\Component\Form\Extension\Core\Type;
use CodexSoft\Code\Helpers\Arrays;
use CodexSoft\JsonApi\Documentation\Collector\FormDoc;
use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use Symfony\Component\HttpFoundation\Request;
use function CodexSoft\Code\str;

class SwaggerGenerator
{

    use Loggable;

    public const CONVERTER = [
        Type\CheckboxType::class => 'boolean',
        Type\ChoiceType::class => 'mixed',
        Type\CollectionType::class => 'array',
        Type\DateType::class => 'string',
        Type\EmailType::class => 'string',
        Type\IntegerType::class => 'integer',
        Type\NumberType::class => 'integer',
        Type\PercentType::class => 'integer',
        Type\TextareaType::class => 'string',
        Type\TextType::class => 'string',
        Type\TimeType::class => 'string',
        Type\UrlType::class => 'string',
        JsonType::class => 'object',
    ];

    /** @var ApiDoc */
    private $apiDoc;

    /**
     * @param FormDoc $formDocumentation
     *
     * @return string[]
     * @throws \ReflectionException
     */
    public function generateFormSchema(FormDoc $formDocumentation): array
    {
        $lines = [];

        foreach ($formDocumentation->items as $name => $item) {

            $elementExtraAttributesString = '';

            // todo: not all attributes must be rendered!
            $elementExtraAttributes = [
                'example' => $item->example,
                'minLength' => $item->minLength,
                'maxLength' => $item->maxLength,
                'exclusiveMinimum' => $item->exclusiveMinimum,
                'minimum' => $item->minimum,
                'exclusiveMaximum' => $item->exclusiveMaximum,
                'maximum' => $item->maximum,
                'enum' => $item->enum,
                'label' => $item->label,
                'description' => $item->description,
                'default' => $item->default,
            ];

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

            if ($item->isCollection()) {
                if ($item->collectionElementsType) {
                    if ($nativeType = $this->detectSwaggerTypeFromNativeType($item->collectionElementsType)) {
                        $lines[] = ' *   @SWG\Property(property="'.$name.'", type="array", @SWG\Items(type="'.$nativeType.'") '.$elementExtraAttributesString.'),';
                    } else {
                        $entryTypedefRef = $this->referenceToDefinition(new \ReflectionClass($item->collectionElementsType));
                        $lines[] = ' *     @SWG\Property(property="'.$name.'", type="array" '.$elementExtraAttributesString.',';
                        $lines[] = ' *       @SWG\Items(ref="'.$entryTypedefRef.'"),';
                        $lines[] = ' *     ),';
                    }
                }
            } elseif($item->isForm()) {
                $propertyReference = $this->referenceToDefinition(new \ReflectionClass($item->swaggerReferencesToClass));
                $lines[] = ' *     @SWG\Property(property="'.$name.'", allOf={@SWG\Schema(ref="'.$propertyReference.'")}'.$elementExtraAttributesString.'),';
            } else {

                $enum = $elementExtraAttributes['enum'] ?? null;
                if (\is_subclass_of($item->fieldTypeClass, BooleanType::class)) {
                    $lines[] = ' *     @SWG\Property(property="'.$name.'", type="boolean"'.$elementExtraAttributesString.'),';
                } else if (\is_array($enum) && \is_subclass_of($item->fieldTypeClass, Type\ChoiceType::class) && (
                        Arrays::areIdenticalByValuesStrict($enum,[true,false,null]) ||
                        Arrays::areIdenticalByValuesStrict($enum,[true,false])
                    )
                ) {
                    $lines[] = ' *     @SWG\Property(property="'.$name.'", type="boolean"'.$elementExtraAttributesString.'),';
                } else {
                    $lines[] = ' *     @SWG\Property(property="'.$name.'", type="'.$this->typeClassToSwaggerType($item->fieldTypeClass).'"'.$elementExtraAttributesString.'),';
                }
            }

        }

        if (\count($formDocumentation->requiredFields)) {
            $lines[] = ' *     required={'.implode(', ',$formDocumentation->requiredFields).'}';
        }

        return $lines;
    }

    public function referenceToDefinition(\ReflectionClass $reflectionClass): string
    {
        return '#/definitions/'.$this->formTitle($reflectionClass);
    }

    public function typeClassToSwaggerType($class): ?string
    {
        $detected = $this->detectSwaggerTypeFromNativeType($class);
        return $detected ?? 'string';
    }

    public function detectSwaggerTypeFromNativeType($class): ?string
    {
        if (\array_key_exists($class, self::CONVERTER)) {
            return self::CONVERTER[$class];
        }
        return null;
    }

    /**
     * @param FormDoc $formDoc
     *
     * @return string[]
     * @throws \ReflectionException
     */
    public function generateFormAsDefinition(FormDoc $formDoc): array
    {
        //$reflection = new \ReflectionClass( $formClass );
        //$schemaContent = $this->parseIntoSchema($formClass);
        //return $this->generateNamedDefinition($this->lib->formTitle($reflection), $schemaContent);

        $schemaContent = $this->generateFormSchema($formDoc);
        $formTitle = $this->formTitle($formDoc->class);
        return $this->generateNamedDefinition($this->formTitle($formTitle), $schemaContent);
    }

    /**
     * @param FormDoc $formDoc
     *
     * @return string[]
     * @throws \ReflectionException
     */
    public function generateFormAsParameter(FormDoc $formDoc): array
    {
        //$reflection = new \ReflectionClass( $formClass );
        //$schemaContent = $this->parseIntoSchema($formClass);

        $schemaContent = $this->generateFormSchema($formDoc);
        $formTitle = $this->formTitle($formDoc->class);
        return $this->generateNamedParameter($this->formTitle($formTitle), $schemaContent);
    }

    /**
     * @param FormDoc $formDoc
     *
     * @return string[]
     * @throws \ReflectionException
     */
    public function generateFormAsParameterAndDefinition(FormDoc $formDoc): array
    {
        $lines = [];
        $schemaContent = $this->generateFormSchema($formDoc);
        $formTitle = $this->formTitle($formDoc->class);
        \array_push($lines, ...$this->generateNamedParameter($formTitle, $schemaContent));
        \array_push($lines, ...$this->generateNamedDefinition($formTitle, $schemaContent));

        //$lines = $this->generateNamedParameter($this->formTitle($formDoc->class), $schemaContent);
        //$definitionLines = $this->generateNamedDefinition($this->formTitle($formDoc->class), $schemaContent);
        //\array_push($lines, ...$definitionLines);

        return $lines;
    }

    public function formTitle(string $formClass): string
    {
        return (string) str($formClass)
            ->replace('\\', '_')
            ->removeLeft('_')
            ->removeRight('_');
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

    public function generateResponse(ResponseDoc $responseDocumentation): ?array
    {
        $lines = [];
        $logger = $this->getLogger();

        // generating response as definition
        //$responseDefinitionLines = (new SymfonyGenerateFormDocumentation($this->lib))->generateFormAsDefinition($responseDocumentation->class);
        $responseFormDoc = $this->apiDoc->forms[$responseDocumentation->formClass];
        $responseDefinitionLines = $this->generateFormAsDefinition($responseFormDoc); // need to get
        if ($responseDefinitionLines) {
            \array_push($lines, ...$responseDefinitionLines);
        }

        // generating response
        \array_push($lines, ...[
            ' * @SWG\Response(',
            ' *   response="'.ResponseDoc::generateTitleStatic($responseDocumentation->class).'",',
            ' *   description="'.$responseDocumentation->description.'"',
        ]);

        //$responseFormDoc = $this->apiDoc->forms[$responseDocumentation->formClass];
        $responseFormLines = $this->generateFormSchema($responseFormDoc);

        //$responseFormLines = (new SymfonyGenerateFormDocumentation($this->lib))
        //    ->parseIntoSchema($responseDocumentation->formClass);

        if ($responseFormLines) {
            $lines[] = ' *   ,@SWG\Schema(';
            \array_push($lines, ...$responseFormLines);
            $lines[] = ' *   )';
        } else {
            $logger->warning($responseDocumentation->formClass.' has no schema!');
        }

        if ($responseDocumentation->example) {
            $lines[] = ' *   ,examples = {';
            $lines[] = ' *     "application/json":';
            $exampleContent = $responseDocumentation->example;
            $filteredExampleContent = (string) str($exampleContent)->replace('[', '{')->replace(']', '}');
            $exampleLines = explode("\n", $filteredExampleContent);

            foreach ($exampleLines as $exampleLine) {
                $lines[] = ' *     '.$exampleLine;
            }
            $lines[] = ' *   }';
        }

        $lines[] = ' * )';
        $lines[] = ' *';

        return $lines;
    }

    public function generateAction(ActionDoc $actionDocumentation): ?array
    {
        $methods = $actionDocumentation->route->getMethods();
        $method = Request::METHOD_POST;

        /* Допущение, что экшн заточен под один конкретный метод */
        if (\count($methods)) {
            $method = Arrays::tool()->getFirst($methods);
        }

        $requestTags = $actionDocumentation->tags;
        \array_walk($requestTags, function (&$tag) {
            $tag = '"'.$tag.'"';
        });
        $requestTagsString = \implode(',', $requestTags);

        $lines = [
            ' * @SWG\\'.str($method)->toTitleCase().'(',
            ' *     path="'.$actionDocumentation->path.'",',
            ' *     tags={'.$requestTagsString.'},',
            ' *     summary="'.$actionDocumentation->path.'",',
            ' *     description="'.$actionDocumentation->description.'",',
        ];

        $pathVars = $$actionDocumentation->compiledRoute->getPathVariables();

        if ($pathVars) {
            foreach ($pathVars as $pathVar) {
                \array_push($lines, ...[
                    ' *     @SWG\Parameter(',
                    ' *         type="integer",', // assuming is integer
                    ' *         description="'.str($pathVar)->toTitleCase().'",',
                    ' *         in="path",',
                    ' *         name="'.$pathVar.'",',
                    ' *         required=true,', // assuming is required
                    ' *     ),',
                ]);
            }
        } else {
            $formTitleUnderscored = $this->formTitle($actionDocumentation->inputFormClass);
            $lines[] = ' *     @SWG\Parameter(ref="#/parameters/'.$formTitleUnderscored.'"),';
        }

        foreach ($actionDocumentation->responses as $responseHttpCode => $responseClass) {
            $suggestedResponseTitle = ResponseDoc::generateTitleStatic($responseClass);
            $lines[] = ' *     @SWG\Response(response="'.$responseHttpCode.'", ref="#/responses/'.$suggestedResponseTitle.'"),';
        }

        //$parser = new SymfonyGenerateFormDocumentation($this->lib);
        //$responseSchemaContent = $parser->parseIntoSchema($errorCodesClass);

        //$lines[] = ' *       @SWG\Response(response="'.$httpErrorCode.'", description="'.$errorCodeDescription.' ('.$errorCode.')",';
        //$lines[] = ' *       @SWG\Schema(';
        //\array_push($lines, ...$responseSchemaLinesWithSpecifiedErrorCode);
        //$lines[] = ' *       )';
        //$lines[] = ' *     ),';

        $lines[] = ' * )';
        $lines[] = ' *';

        return $lines;
    }

}