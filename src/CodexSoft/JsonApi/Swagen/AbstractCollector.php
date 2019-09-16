<?php


namespace CodexSoft\JsonApi\Swagen;


abstract class AbstractCollector
{

    /** @var SwagenLib */
    protected $lib;

    public function __construct(SwagenLib $lib)
    {
        $this->lib = $lib;
    }

}