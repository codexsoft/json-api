<?php /** @noinspection DuplicatedCode */

namespace CodexSoft\JsonApi\Documentation\Collector;

use CodexSoft\Code\Traits\Loggable;
use CodexSoft\JsonApi\Response\ResponseWrappedDataInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenResponseExternalFormInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormTypeInterface;
use CodexSoft\Code\Helpers\Classes;
use CodexSoft\JsonApi\Response\AbstractBaseResponse;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenResponseInterface;
use function CodexSoft\Code\str;

class ResponseDocCollector
{

    use Loggable;

    /** @var FormFactory */
    private $formFactory;

    public function __construct(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param string $responseClass
     *
     * @return ResponseDoc
     * @throws \ReflectionException
     */
    public function collect(string $responseClass): ResponseDoc
    {
        $responseDoc = new ResponseDoc;
        $responseDoc->class = $responseClass;

        $logger = $this->getLogger();

        if (!\class_exists($responseClass)) {
            return null;
        }

        try {
            $reflection = new \ReflectionClass($responseClass);
        } catch (\ReflectionException $e) {
            $this->getLogger()->notice("$responseClass form SKIPPING: failed to instantiate ReflectionClass");
            return null;
        }

        if ($reflection->isAbstract()) {
            throw new \Exception("$responseClass response SKIPPING: class is abstract");
            //$logger->notice("$responseClass response SKIPPING: class is abstract");
            //return null;
        }

        if (!$reflection->isSubclassOf(AbstractBaseResponse::class)) {
            throw new \Exception("$responseClass response SKIPPING: class is not ancestor of ".AbstractBaseResponse::class);
            //$logger->info("$responseClass response SKIPPING: class is not ancestor of ".AbstractBaseResponse::class);
            //return null;
        }

        /**
         * skip generating definitions if class does not implement auto-generating interface
         */
        if (!$reflection->implementsInterface(SwagenResponseInterface::class)) {
            throw new \Exception("$responseClass response SKIPPING: class does not implement ".SwagenResponseInterface::class);
            //$logger->info("$responseClass response SKIPPING: class does not implement ".SwagenResponseInterface::class);
            //return null;
        }

        $logger->info($responseClass.' response implements '.Classes::short(SwagenResponseInterface::class));

        /** @var SwagenResponseInterface $responseClass */
        $responseDoc->description = $responseClass::getSwaggerResponseDescription();

        if ($reflection->implementsInterface(SwagenResponseExternalFormInterface::class)) {
            /** @var SwagenResponseExternalFormInterface $responseClass */
            $responseDoc->formClass = $responseClass::getFormClass();
            $formReflection = new \ReflectionClass($responseDoc->formClass);
        } elseif ($reflection->implementsInterface(FormTypeInterface::class)) {
            $responseDoc->formClass = $responseClass;
            $formReflection = $reflection;
        }

        if (isset($formReflection) && $formReflection->getConstructor()->getNumberOfRequiredParameters() === 0) {

            if ($formReflection->implementsInterface(ResponseWrappedDataInterface::class)) {
                /** @var ResponseWrappedDataInterface $responseClass */
                $responseClass::setGeneratingWrappedDataForResponseDefinition(true);
            }

            $responseFormDoc = (new FormDocCollector($this->formFactory))->collect($responseDoc->formClass);
            $responseDoc->formClassDoc = $responseFormDoc;
            //$responseDefinitionLines = (new SymfonyGenerateFormDocumentation($this->lib))->generateFormAsDefinition($responseClass);
            //if ($responseDefinitionLines) {
            //    \array_push($lines, ...$responseDefinitionLines);
            //}

            if ($formReflection->implementsInterface(ResponseWrappedDataInterface::class)) {
                /** @var ResponseWrappedDataInterface $responseClass */
                $responseClass::setGeneratingWrappedDataForResponseDefinition(false);
            }

        } else {
            $logger->warning("$responseClass response skipping generate swagger response DEFINITION: it has required parameters in constructor!");
        }

        // add an manual example

        $exampleFileName = str($reflection->getFileName())->removeRight('.php')->append('.json');
        if (\file_exists($exampleFileName)) {
            $responseDoc->example = \file_get_contents($exampleFileName);
        }

        return $responseDoc;
    }

}