<?php

namespace CodexSoft\JsonApi\Documentation\SwaggerGenerator;

use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SwagenLib;
use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SymfonyGenerateFormDocumentation;
use CodexSoft\JsonApi\Form\AbstractForm;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenInterface;
use Psr\Log\LoggerInterface;

/**
 * @deprecated SwaggerGenerator is used now
 */
class SwagenForm
{

    /** @var SwagenLib */
    private $lib;

    private function getLogger(): LoggerInterface
    {
        return $this->lib->getLogger();
    }

    public function __construct(SwagenLib $lib)
    {
        $this->lib = $lib;
    }

    /**
     * Сгенерировать заданную симфони-форму в виде swagger definition и swagger parameter.
     *
     * @param string $formClass
     *
     * @return string[]|null
     * @throws \ReflectionException
     */
    public function generate(string $formClass): ?array
    {

        if (!\class_exists($formClass)) {
            $this->getLogger()->notice("$formClass form SKIPPING: class does not exists");
            return null;
        }

        try {
            $formClassReflection = new \ReflectionClass($formClass);
        } catch (\ReflectionException $e) {
            $this->getLogger()->notice("$formClass form SKIPPING: failed to instantiate ReflectionClass");
            return null;
        }

        if ($formClassReflection->isAbstract()) {
            $this->getLogger()->info("$formClass form SKIPPING: class is abstract");
            return null;
        }

        /**
         * skip generating definitions if class does not implement auto-generating interface
         */
        if ($formClassReflection->implementsInterface(SwagenInterface::class) === false) {
            $this->getLogger()->info("$formClass form SKIPPING: class does not implement SwagenInterface");
            return null;
        }

        if ($formClassReflection->isSubclassOf(AbstractForm::class) === false) {
            $this->getLogger()->info("$formClass form SKIPPING: class does not extend BaseForm");
            return null;
        }

        return (new SymfonyGenerateFormDocumentation($this->lib))->generateFormAsParameterAndDefinition($formClass);
    }

}