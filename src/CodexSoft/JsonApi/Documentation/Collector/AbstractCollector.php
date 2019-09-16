<?php


namespace CodexSoft\JsonApi\Documentation\Collector;


use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SwagenLib;

abstract class AbstractCollector
{

    /** @var SwagenLib */
    protected $lib;

    public function __construct(SwagenLib $lib)
    {
        $this->lib = $lib;
    }

}