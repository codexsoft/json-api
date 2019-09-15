<?php

namespace CodexSoft\JsonApi\Swagen;

use CodexSoft\JsonApi\Form\AbstractForm;
use CodexSoft\Code\Helpers\Classes;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenInterface;
use Psr\Log\LoggerInterface;

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

        if (!\is_subclass_of($formClass, AbstractForm::class)) {
            $this->getLogger()->info("$formClass form SKIPPING: class does not extend BaseForm");
            return null;
        }

        /**
         * skip generating definitions if class does not implement auto-generating interface
         */
        if (!Classes::isImplements($formClass, SwagenInterface::class)) {
            $this->getLogger()
                ->info("$formClass form SKIPPING: class does not implement SwagenInterface");
            return null;
        }

        try {
            $reflection = new \ReflectionClass($formClass);
        } catch (\ReflectionException $e) {
            $this->getLogger()
                ->notice("$formClass form SKIPPING: failed to instantiate ReflectionClass");
            return null;
        }

        if ($reflection->isAbstract()) {
            $this->getLogger()->info("$formClass form SKIPPING: class is abstract");
            return null;
        }

        return (new SymfonyGenerateFormDocumentation($this->lib))->generateFormAsParameterAndDefinition($formClass);

    }

}