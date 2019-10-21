<?php /** @noinspection DuplicatedCode */


namespace CodexSoft\JsonApi\Documentation\SwaggerGenerator;

use CodexSoft\Code\Helpers\Arrays;
use CodexSoft\Code\Traits\Loggable;
use CodexSoft\JsonApi\Documentation\Collector\ActionDoc;
use CodexSoft\JsonApi\Documentation\Collector\ApiDoc;
use CodexSoft\JsonApi\Documentation\Collector\FormDoc;
use CodexSoft\JsonApi\Documentation\Collector\ResponseDoc;
use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use CodexSoft\JsonApi\Form\Type\JsonType\JsonType;
use Symfony\Component\HttpFoundation\Response;
use function CodexSoft\Code\str;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\Request;

class SwaggerGenerator
{

    use Loggable;

    protected const CONVERTER = [
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
     * SwaggerGenerator constructor.
     *
     * @param ApiDoc $apiDoc
     */
    public function __construct(ApiDoc $apiDoc)
    {
        $this->apiDoc = $apiDoc;
    }

    /**
     * @return string[]
     * @throws \ReflectionException
     */
    public function generate(): array
    {
        $lines = [];

        \array_push($lines, ...[
            '@SWG\Swagger(',
            '    schemes={"https"},',
            '    host="'.($_SERVER['HTTP_HOST'] ?? 'localhost').'",',
            '    basePath="",',
            '    consumes={"application/json"},',
            '    produces={"application/json"},',
            '    @SWG\Info(',
            '        version="1.0.0",',
            '        title="Test API",',
            '        description="Булевы значения принимаются в виде INT {0|1}, для некоторых полей допустим набор {0|1|null}",',
            '    )',
            ')',
        ]);

        foreach ($this->apiDoc->forms as $formDoc) {
            if (\is_subclass_of($formDoc->class, Response::class)) {
                continue;
            }
            \array_push($lines, ...$this->generateFormAsParameterAndDefinition($formDoc));
        }

        foreach ($this->apiDoc->responses as $responseDoc) {
            \array_push($lines, ...$this->generateResponse($responseDoc));
        }

        foreach ($this->apiDoc->actions as $actionDoc) {
            \array_push($lines, ...$this->generateAction($actionDoc));
        }

        return $lines;
    }

    /**
     * @param FormDoc $formDocumentation
     *
     * @return string[]
     * @throws \ReflectionException
     */
    protected function generateFormSchema(FormDoc $formDocumentation): array
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
                'enum' => $item->choices,
                //'label' => $item->label,
                'description' => $item->label,
                'default' => $item->defaultValue,
            ];

            if ($elementExtraAttributes) {
                foreach($elementExtraAttributes as $attribute => $value) {

                    if ($value instanceof \Closure) {
                        continue;
                    }

                    if (!$item->isValueDefined($value)) {
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
                if ($item->collectionItemsClass) {
                    if ($nativeType = $this->detectSwaggerTypeFromNativeType($item->collectionItemsClass)) {
                        $lines[] = '  @SWG\Property(property="'.$name.'", type="array", @SWG\Items(type="'.$nativeType.'") '.$elementExtraAttributesString.'),';
                    } else {
                        $entryTypedefRef = $this->referenceToDefinition(new \ReflectionClass($item->collectionItemsClass));
                        $lines[] = '    @SWG\Property(property="'.$name.'", type="array" '.$elementExtraAttributesString.',';
                        $lines[] = '      @SWG\Items(ref="'.$entryTypedefRef.'"),';
                        $lines[] = '    ),';
                    }
                }
            } elseif($item->isForm()) {
                $propertyReference = $this->referenceToDefinition(new \ReflectionClass($item->fieldReferencesToFormClass));
                $lines[] = '    @SWG\Property(property="'.$name.'", allOf={@SWG\Schema(ref="'.$propertyReference.'")}'.$elementExtraAttributesString.'),';
            } else {

                $enum = $elementExtraAttributes['enum'] ?? null;
                if (\is_subclass_of($item->fieldFormTypeClass, BooleanType::class)) {
                    $lines[] = '    @SWG\Property(property="'.$name.'", type="boolean"'.$elementExtraAttributesString.'),';
                } else if (\is_array($enum) && \is_subclass_of($item->fieldFormTypeClass, Type\ChoiceType::class) && (
                        Arrays::areIdenticalByValuesStrict($enum,[true,false,null]) ||
                        Arrays::areIdenticalByValuesStrict($enum,[true,false])
                    )
                ) {
                    $lines[] = '    @SWG\Property(property="'.$name.'", type="boolean"'.$elementExtraAttributesString.'),';
                } else {
                    $lines[] = '    @SWG\Property(property="'.$name.'", type="'.$this->typeClassToSwaggerType($item->fieldFormTypeClass).'"'.$elementExtraAttributesString.'),';
                }
            }

        }

        if (\count($formDocumentation->requiredFields)) {
            $requiredFields = $formDocumentation->requiredFields;
            \array_walk($requiredFields, function (&$requiredField) {
                $requiredField = '"'.$requiredField.'"';
            });
            $lines[] = '    required={'.implode(', ',$requiredFields).'}';
        }

        return $lines;
    }

    protected function referenceToDefinition(\ReflectionClass $reflectionClass): string
    {
        //return '#/definitions/'.$this->formTitle($reflectionClass);
        return '#/definitions/'.$this->formTitle($reflectionClass->getName());
    }

    protected function typeClassToSwaggerType($class): ?string
    {
        $detected = $this->detectSwaggerTypeFromNativeType($class);
        return $detected ?? 'string';
    }

    protected function detectSwaggerTypeFromNativeType($class): ?string
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
    protected function generateFormAsDefinition(FormDoc $formDoc): array
    {
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
    protected function generateFormAsParameter(FormDoc $formDoc): array
    {
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
    protected function generateFormAsParameterAndDefinition(FormDoc $formDoc): array
    {
        $lines = [];
        $schemaContent = $this->generateFormSchema($formDoc);
        $formTitle = $this->formTitle($formDoc->class);
        \array_push($lines, ...$this->generateNamedParameter($formTitle, $schemaContent));
        \array_push($lines, ...$this->generateNamedDefinition($formTitle, $schemaContent));

        return $lines;
    }

    protected function formTitle(string $formClass): string
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
    protected function generateNamedDefinition(string $title,array $schema): array
    {

        $lines = [
            '@SWG\Definition(',
            '  definition="'.$title.'",',
            '  type="object",',
        ];

        if ($schema) {
            \array_push($lines,...$schema);
        }

        $lines[] = ')';
        $lines[] = '';

        return $lines;
    }

    /**
     * @param string $title
     * @param array $schema
     *
     * @return array
     */
    protected function generateNamedParameter(string $title,array $schema): array
    {
        $parameterLines = [
            '@SWG\Parameter(',
            '  parameter="'.$title.'",',
            '  in="body",',
            '  name="'.$title.'",',
            '  @SWG\Schema(',
        ];

        if ($schema) {
            \array_push($parameterLines,...$schema);
        }

        $parameterLines[] = '  )';
        $parameterLines[] = ')';
        $parameterLines[] = '';

        return $parameterLines;
    }

    /**
     * @param ResponseDoc $responseDocumentation
     *
     * @return array|null
     * @throws \ReflectionException
     */
    protected function generateResponse(ResponseDoc $responseDocumentation): ?array
    {
        $lines = [];
        $logger = $this->getLogger();

        // generating response as definition
        $responseFormDoc = $this->apiDoc->forms[$responseDocumentation->formClass];
        $responseDefinitionLines = $this->generateFormAsDefinition($responseFormDoc); // need to get
        if ($responseDefinitionLines) {
            \array_push($lines, ...$responseDefinitionLines);
        }

        // generating response
        \array_push($lines, ...[
            '@SWG\Response(',
            '  response="'.ResponseDoc::generateTitleStatic($responseDocumentation->class).'",',
            '  description="'.$responseDocumentation->description.'"',
        ]);

        //$responseFormDoc = $this->apiDoc->forms[$responseDocumentation->formClass];
        $responseFormLines = $this->generateFormSchema($responseFormDoc);

        if ($responseFormLines) {
            $lines[] = '  ,@SWG\Schema(';
            \array_push($lines, ...$responseFormLines);
            $lines[] = '  )';
        } else {
            $logger->warning($responseDocumentation->formClass.' has no schema!');
        }

        if ($responseDocumentation->example) {
            $lines[] = '  ,examples = {';
            $lines[] = '    "application/json":';
            $exampleContent = $responseDocumentation->example;
            $filteredExampleContent = (string) str($exampleContent)->replace('[', '{')->replace(']', '}');
            $exampleLines = explode("\n", $filteredExampleContent);

            foreach ($exampleLines as $exampleLine) {
                $lines[] = '    '.$exampleLine;
            }
            $lines[] = '  }';
        }

        $lines[] = ')';
        $lines[] = '';

        return $lines;
    }

    /**
     * @param ActionDoc $actionDocumentation
     *
     * @return array|null
     */
    protected function generateAction(ActionDoc $actionDocumentation): ?array
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
            '@SWG\\'.str($method)->toTitleCase().'(',
            '    path="'.$actionDocumentation->path.'",',
            '    tags={'.$requestTagsString.'},',
            '    summary="'.$actionDocumentation->path.'",',
            '    description="'.$actionDocumentation->description.'",',
        ];

        $pathVars = $actionDocumentation->compiledRoute->getPathVariables();

        if ($pathVars) {
            foreach ($pathVars as $pathVar) {
                \array_push($lines, ...[
                    '    @SWG\Parameter(',
                    '        type="integer",', // assuming is integer
                    '        description="'.str($pathVar)->toTitleCase().'",',
                    '        in="path",',
                    '        name="'.$pathVar.'",',
                    '        required=true,', // assuming is required
                    '    ),',
                ]);
            }
        } else {
            $formTitleUnderscored = $this->formTitle($actionDocumentation->inputFormClass);
            $lines[] = '    @SWG\Parameter(ref="#/parameters/'.$formTitleUnderscored.'"),';
        }

        foreach ($actionDocumentation->responses as $responseHttpCode => $responseClass) {
            $suggestedResponseTitle = ResponseDoc::generateTitleStatic($responseClass);
            $lines[] = '    @SWG\Response(response="'.$responseHttpCode.'", ref="#/responses/'.$suggestedResponseTitle.'"),';
        }

        $lines[] = ')';
        $lines[] = '';

        return $lines;
    }

}