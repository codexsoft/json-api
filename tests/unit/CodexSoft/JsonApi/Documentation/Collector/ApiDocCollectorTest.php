<?php

namespace CodexSoft\JsonApi\Documentation\Collector;


use PHPUnit\Framework\TestCase;

class ApiDocCollectorTest extends TestCase
{

    public function testCollect()
    {
        (new ApiDocCollector)->collect();
    }
}
