<?php


namespace CodexSoft\JsonApi\Swagen;


class FormDocumentation
{

    /** @var string */
    public $class;

    /** @var string[] */
    public $requiredFields = [];

    /** @var FormElementDocumentation[]  */
    public $items = [];

}