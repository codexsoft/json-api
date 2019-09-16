<?php /** @noinspection DuplicatedCode */

namespace CodexSoft\JsonApi\Swagen;

use CodexSoft\JsonApi\Response\ResponseWrappedDataInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenResponseExternalFormInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormTypeInterface;
use CodexSoft\Code\Helpers\Classes;
use CodexSoft\JsonApi\Response\AbstractBaseResponse;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenResponseInterface;
use function CodexSoft\Code\str;

class CollectResponseDocumentation extends AbstractCollector
{

    /**
     * @param string $responseClass
     *
     * @return ResponseDocumentation
     * @throws \ReflectionException
     */
    public function collect(string $responseClass): ResponseDocumentation
    {
        $docResponse = new ResponseDocumentation;
        $docResponse->class = $responseClass;

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
            $logger->notice("$responseClass response SKIPPING: class is abstract");
            return null;
        }

        if (!$reflection->isSubclassOf(AbstractBaseResponse::class)) {
            $logger->info("$responseClass response SKIPPING: class is not ancestor of ".AbstractBaseResponse::class);
            return null;
        }

        /**
         * skip generating definitions if class does not implement auto-generating interface
         */
        if (!$reflection->implementsInterface(SwagenResponseInterface::class)) {
            $logger->info("$responseClass response SKIPPING: class does not implement ".SwagenResponseInterface::class);
            return null;
        }

        $logger->info($responseClass.' response implements '.Classes::short(SwagenResponseInterface::class));

        /** @var SwagenResponseInterface $responseClass */
        $docResponse->description = $responseClass::getSwaggerResponseDescription();

        if ($reflection->getConstructor()->getNumberOfRequiredParameters() === 0) {

            if ($reflection->implementsInterface(ResponseWrappedDataInterface::class)) {
                /** @var ResponseWrappedDataInterface $responseClass */
                $responseClass::setGeneratingWrappedDataForResponseDefinition(true);
            }

            $responseDefinitionLines = (new SymfonyGenerateFormDocumentation($this->lib))->generateFormAsDefinition($responseClass);
            if ($responseDefinitionLines) {
                \array_push($lines, ...$responseDefinitionLines);
            }

            if ($reflection->implementsInterface(ResponseWrappedDataInterface::class)) {
                /** @var ResponseWrappedDataInterface $responseClass */
                $responseClass::setGeneratingWrappedDataForResponseDefinition(false);
            }

        } else {
            $logger->warning("$responseClass response skipping generate swagger response DEFINITION: it has required parameters in constructor!");
        }

        if ($reflection->implementsInterface(SwagenResponseExternalFormInterface::class)) {
            /** @var SwagenResponseExternalFormInterface $responseClass */
            $docResponse->formClass = $responseClass::getFormClass();
        } elseif ($reflection->implementsInterface(FormTypeInterface::class)) {
            $docResponse->formClass = $responseClass;
        }

        // add an manual example

        $exampleFileName = str($reflection->getFileName())->removeRight('.php')->append('.json');
        if (\file_exists($exampleFileName)) {
            $docResponse->example = \file_get_contents($exampleFileName);
        }

        return $docResponse;
    }

    private function getLogger(): LoggerInterface
    {
        return $this->lib->getLogger();
    }

}