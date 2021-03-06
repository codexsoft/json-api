<?php

namespace CodexSoft\JsonApi\Documentation\TypeScriptGenerator;

use CodexSoft\JsonApi\Documentation\Collector\ApiDoc;
use CodexSoft\JsonApi\Helper\Loggable;

class TypeScriptGenerator
{
    use Loggable;

    private ApiDoc $apiDoc;

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
        return [];
    }

    private function generateFormAsParameterAndDefinition(\CodexSoft\JsonApi\Documentation\Collector\FormDoc $formDoc)
    {
        // todo
    }

}
