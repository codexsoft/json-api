<?php

namespace CodexSoft\JsonApi\Documentation\Collector\Interfaces;

interface SwagenActionProducesSimpleErrorResponsesInterface
{
    /** @return int[] */
    public static function producesErrorResponses(): array;

}