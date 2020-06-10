<?php /** @noinspection DuplicatedCode */

namespace CodexSoft\JsonApi\Documentation\Collector;

use CodexSoft\JsonApi\Helper\Loggable;
use CodexSoft\JsonApi\Response\ResponseWrappedDataInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenResponseExternalFormInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormTypeInterface;
use CodexSoft\JsonApi\Response\AbstractBaseResponse;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenResponseInterface;
use function Stringy\create as str;

class ResponseDocCollector
{
    use Loggable;

    private FormFactory $formFactory;

    public function __construct(FormFactory $formFactory, LoggerInterface $logger = null)
    {
        $logger && $this->logger = $logger;
        $this->formFactory = $formFactory;
    }

    /**
     * @param string $responseClass
     *
     * @return ResponseDoc|null
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function collect(string $responseClass): ?ResponseDoc
    {
        $responseDoc = new ResponseDoc;
        $responseDoc->class = $responseClass;

        $logger = $this->getLogger();

        if (!\class_exists($responseClass)) {
            //return null;
            //throw new \Exception("SKIPPING response $responseClass: class is not exists");
            $logger->debug("SKIPPING response $responseClass: class is not exists");
            return null;
        }

        try {
            $reflection = new \ReflectionClass($responseClass);
        } catch (\ReflectionException $e) {
            throw new \Exception("SKIPPING response $responseClass: failed to instantiate ReflectionClass");
            //$this->getLogger()->notice("SKIPPING response $responseClass: failed to instantiate ReflectionClass");
            //return null;
        }

        if ($reflection->isAbstract()) {
            //throw new \Exception("SKIPPING response $responseClass: class is abstract");
            $logger->debug("SKIPPING response $responseClass: class is abstract");
            return null;
        }

        if ($reflection->isInterface()) {
            $this->logger->debug("SKIPPING response $responseClass: is interface");
            return null;
        }

        if ($reflection->isTrait()) {
            $this->logger->debug("SKIPPING response $responseClass: is trait");
            return null;
        }

        if (!$reflection->isSubclassOf(AbstractBaseResponse::class)) {
            //throw new \Exception("SKIPPING response $responseClass: class is not ancestor of ".AbstractBaseResponse::class);
            $logger->debug("SKIPPING response $responseClass: class is not ancestor of ".AbstractBaseResponse::class);
            return null;
        }

        /**
         * skip generating definitions if class does not implement auto-generating interface
         */
        if (!$reflection->implementsInterface(SwagenResponseInterface::class)) {
            //throw new \Exception("SKIPPING response $responseClass : class does not implement ".SwagenResponseInterface::class);
            $logger->info("SKIPPING response $responseClass : class does not implement ".SwagenResponseInterface::class);
            return null;
        }

        //$logger->info($responseClass.' response implements '.Classes::short(SwagenResponseInterface::class));

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
            $logger->warning("PROBLEM response $responseClass generate swagger response DEFINITION: it has required parameters in constructor!");
        }

        // add an manual example

        $exampleFileName = str($reflection->getFileName())->removeRight('.php')->append('.json');
        if (\file_exists($exampleFileName)) {
            $responseDoc->example = \file_get_contents($exampleFileName);
        }

        return $responseDoc;
    }

}
