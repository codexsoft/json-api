<?php


namespace CodexSoft\JsonApi\Documentation\TypeScriptGenerator;


use CodexSoft\Code\Traits\Loggable;
use CodexSoft\JsonApi\Documentation\Collector\ApiDoc;

class TypeScriptGenerator
{

    use Loggable;

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

    public function generate(): array
    {
        foreach ($this->apiDoc->forms as $formDoc) {
            \array_push($lines, ...$this->generateFormAsParameterAndDefinition($formDoc));
        }
    }

}