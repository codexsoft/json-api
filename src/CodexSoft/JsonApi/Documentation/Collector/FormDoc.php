<?php

namespace CodexSoft\JsonApi\Documentation\Collector;

class FormDoc
{

    /** @var string */
    public $class;

    /** @var string[] */
    public $requiredFields = [];

    /** @var FormElementDoc[]  */
    public $items = [];

}