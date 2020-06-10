<?php

namespace CodexSoft\JsonApi\Documentation\Collector;

class ApiDoc
{
    /** @var ResponseDoc[] */
    public array $responses = [];

    /** @var ActionDoc[] */
    public array $actions = [];

    /** @var FormDoc[] */
    public array $forms = [];
}
