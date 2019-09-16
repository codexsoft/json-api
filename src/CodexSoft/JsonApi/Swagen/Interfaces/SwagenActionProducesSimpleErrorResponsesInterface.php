<?php

namespace CodexSoft\JsonApi\Swagen\Interfaces;

interface SwagenActionProducesSimpleErrorResponsesInterface
{
    /** @return int[] */
    public static function producesErrorResponses(): array;

}