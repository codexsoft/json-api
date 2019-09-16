<?php

namespace CodexSoft\JsonApi\Documentation\Collector\Interfaces;

interface SwagenResponseInterface extends SwagenInterface
{

    public static function getSwaggerResponseDescription(): string;

}