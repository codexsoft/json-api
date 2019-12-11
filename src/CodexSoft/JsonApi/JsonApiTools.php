<?php

namespace CodexSoft\JsonApi;

use CodexSoft\OperationsSystem\Traits\OperationsProcessorAwareTrait;
use CodexSoft\JsonApi\Operations\CreateActionOperation;

class JsonApiTools
{

    use OperationsProcessorAwareTrait;

    /** @var JsonApiSchema */
    private $config;

    public function generateAction(): CreateActionOperation
    {
        $operation = (new CreateActionOperation)
            ->setJsonApiSchema($this->config);
        return $operation;
    }

    /**
     * @return JsonApiSchema
     */
    public function getConfig(): JsonApiSchema
    {
        return $this->config;
    }

    /**
     * @param JsonApiSchema $config
     *
     * @return static
     */
    public function setConfig(JsonApiSchema $config): self
    {
        $this->config = $config;
        return $this;
    }

}
