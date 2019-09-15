<?php

namespace CodexSoft\JsonApi\Form\FormConstraints;

use Symfony\Component\Validator\Constraint;

class FieldIsRequired extends Constraint
{
    public $message = 'Required field missing.';
}