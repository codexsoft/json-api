<?php


namespace CodexSoft\JsonApi\Swagen;

use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type;
use CodexSoft\Code\Helpers\Classes;
use CodexSoft\JsonApi\Form\AbstractForm;
use CodexSoft\JsonApi\Form\Extensions\FormFieldDefaultValueExtension;
use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use CodexSoft\JsonApi\Response\AbstractBaseResponse;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenResponseInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use function CodexSoft\Code\str;

class CollectResponseDocumentation extends AbstractCollector
{

    //public const OPTIONS_FIELD_NAME = 'data_collector/passed_options';

    /**
     * @param $formClass
     *
     * @return FormDocumentation
     * @throws \ReflectionException
     */
    public function collect($formClass): ResponseDocumentation
    {
        $docResponse = new ResponseDocumentation;

        return $docResponse;
    }

}