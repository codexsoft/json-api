<?php

namespace CodexSoft\JsonApi;

use CodexSoft\OperationsSystem\OperationsProcessor;
use CodexSoft\OperationsSystem\Traits\OperationsProcessorAwareTrait;
use CodexSoft\JsonApi\Operations\CreateActionOperation;

class JsonApiTools
{

    use OperationsProcessorAwareTrait;

    /** @var JsonApiSchema */
    private $config;

    public function __construct()
    {
        $this->operationsProcessor = new OperationsProcessor;
    }

    public function generateAction()
    {
        $operation = (new CreateActionOperation)
            ->setWebServerSchema($this->config)
            ->setOperationsProcessor($this->operationsProcessor);
        return $operation;
    }

    /**
     * @param string $domainConfigFile
     *
     * @return static
     * @throws \Exception
     */
    public static function getFromConfigFile(string $domainConfigFile): self
    {
        ob_start();
        $domainSchema = include $domainConfigFile;
        ob_end_clean();

        if (!$domainSchema instanceof static) {
            throw new \Exception("File $domainConfigFile does not return valid ".static::class."!\n");
        }

        return $domainSchema;
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