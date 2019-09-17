<?php

namespace CodexSoft\JsonApi\Documentation\SwaggerGenerator;

use CodexSoft\JsonApi\Documentation\Collector\ApiDoc;
use CodexSoft\JsonApi\Documentation\Collector\ApiDocCollector;
use PHPUnit\Framework\TestCase;

class SwaggerGeneratorTest extends TestCase
{

    public function testGenerate()
    {
        $apiDocCollector = new ApiDocCollector();
        $apiDoc = $apiDocCollector->collect();
        $generator = new SwaggerGenerator($apiDoc);
        $lines = $generator->generate();
    }
}
