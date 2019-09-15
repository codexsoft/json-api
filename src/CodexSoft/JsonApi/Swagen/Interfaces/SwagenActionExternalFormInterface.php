<?php

namespace CodexSoft\JsonApi\Swagen\Interfaces;

/**
 * If action's form is not in App\Form\<namespaceAsInAction>\<Action>Form,
 * we must provide form class manually.
 *
 * Interface SwagenActionExternalFormInterface
 */
interface SwagenActionExternalFormInterface
{
    public static function getFormClass(): string;
}