<?php

namespace CodexSoft\JsonApi\Form\Extensions;

use CodexSoft\JsonApi\Form\Extensions\FormFieldDefaultValueExtension;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class SetDefaultValuesToFormSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [FormEvents::PRE_SUBMIT => 'preSubmit'];
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if ($data === null) {
            return;
        }

        if (!\is_array($data)) {
            $data = [];
        }

        $form = $event->getForm();
        $children = $form->all();
        $formOptions = $form->getConfig()->getOptions();

        $defaultedData = [];

        foreach ($children as $childName => $child) {
            $childOptions = $child->getConfig()->getOptions();

            if (\array_key_exists($childName,$data)) {
                // если данные действительно были переданы
                $defaultedData[$childName] = $data[$childName];
            } elseif ($childOptions['default'] !== FormFieldDefaultValueExtension::UNDEFINED) {
                // если поле не было передано, но задано его значение по-умолчанию
                if (\is_array($childOptions['default'])) {
                    $default = $childOptions['default'];
                } else {
                    $default = (string) $childOptions['default'];
                }
                $defaultedData[$childName] = $default;
            } else {
                // something strange happen...
            }

        }

        //$event->setData($defaultedData);
        $event->setData(\array_merge($data, $defaultedData));
    }
}
