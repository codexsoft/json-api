<?php

namespace CodexSoft\JsonApi\Swagen;

use CodexSoft\Code\Helpers\Strings;
use CodexSoft\JsonApi\Response\AbstractBaseResponse;
use CodexSoft\JsonApi\Response\DefaultSuccessResponse;
use CodexSoft\Code\Helpers\Classes;
use CodexSoft\JsonApi\Response\ResponseWrappedDataInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenResponseExternalFormInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenResponseInterface;
use CodexSoft\JsonApi\Response\DefaultErrorResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormTypeInterface;
use function CodexSoft\Code\str;

class SwagenGenerateResponseDocumentation
{

    /** @var string */
    private $responseClass;

    /** @var SwagenLib */
    private $lib;

    /** @var string */
    private $pathPrefixToRemove;

    /** @var string путь где лежат примеры ответов в виде JSON-файлов */
    private $examplesDir;

    /** @var FormFactory */
    private $formFactory;

    public function __construct(SwagenLib $lib)
    {
        $this->lib = $lib;
    }

    private function getLogger(): LoggerInterface
    {
        return $this->lib->getLogger();
    }

    public function generate(): ?array
    {

        $lines = [];

        $lib = $this->lib;
        $logger = $this->getLogger();

        $responseClass = $this->responseClass;

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
        $responseDescription = $responseClass::getSwaggerResponseDescription();

        if ($reflection->getConstructor()->getNumberOfRequiredParameters() > 0) {
            $logger->warning("$responseClass response skipping generate swagger response DEFINITION: it has required parameters in constructor!");
        }

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

        $suggestedResponseTitle = (string) str($responseClass)->replace('\\', '_')->trimLeft('_');

        $lines[] = ' * @SWG\Response(';
        $lines[] = ' *   response="'.$suggestedResponseTitle.'",';
        $lines[] = ' *   description="'.$responseDescription.'"';

        if ($reflection->implementsInterface(SwagenResponseExternalFormInterface::class)) {
            /** @var SwagenResponseExternalFormInterface $responseClass */
            $responseFormClass = $responseClass::getFormClass();

            $responseFormLines = (new SymfonyGenerateFormDocumentation($lib))->parseIntoSchema($responseFormClass);

            if ($responseFormLines) {
                $lines[] = ' *   ,@SWG\Schema(';
                \array_push($lines, ...$responseFormLines);
                $lines[] = ' *   )';
            } else {
                $logger->warning($responseClass.' has no schema!');
            }

        } elseif ($reflection->implementsInterface(FormTypeInterface::class)) {

            if ($reflection->getConstructor()->getNumberOfRequiredParameters() === 0) {
                $responseFormLines = (new SymfonyGenerateFormDocumentation($lib))->parseIntoSchema($responseClass);

                if ($responseFormLines) {
                    $lines[] = ' *   ,@SWG\Schema(';
                    \array_push($lines, ...$responseFormLines);
                    $lines[] = ' *   )';
                } else {
                    $logger->warning($responseClass.' has no schema!');
                }

            } else {
                $logger->warning("$responseClass skipping response SCHEMA documenting: has required parameters in constructor and is NOT implementing ".SwagenResponseExternalFormInterface::class);
                //throw new \LogicException($responseClass.' has required parameters in constructor!');
            }

        } elseif ($reflection->isSubclassOf(DefaultErrorResponse::class)) {
            $lines[] = ' *   ref="$/responses/error_response",';
        } elseif ($reflection->isSubclassOf(DefaultSuccessResponse::class)) {
            $lines[] = ' *   ref="$/responses/success_response",';
        }

        // add an manual example

        $examplesDir = $this->examplesDir;
        if ($examplesDir) {

            $suggestedResponseExampleFile = Strings::bs2s($responseClass).'.json';
            $suggestedResponseExamplePath = $examplesDir.$suggestedResponseExampleFile;

            if (file_exists($suggestedResponseExamplePath)) {
                $logger->info('- example found in '.$suggestedResponseExamplePath);
                $lines[] = ' *   ,examples = {';
                $lines[] = ' *     "application/json":';
                $exampleContent = file_get_contents($suggestedResponseExamplePath);
                $filteredExampleContent = (string) str($exampleContent)->replace('[', '{')->replace(']', '}');
                $exampleLines = explode("\n", $filteredExampleContent);

                foreach ($exampleLines as $exampleLine) {
                    $lines[] = ' *     '.$exampleLine;
                }
                $lines[] = ' *   }';
            }

        }

        $lines[] = ' * )';
        $lines[] = ' *';

        return $lines;

    }

    /**
     * @param string $pathPrefixToRemove
     *
     * @return SwagenGenerateResponseDocumentation
     */
    public function setPathPrefixToRemove(?string $pathPrefixToRemove): SwagenGenerateResponseDocumentation
    {
        $this->pathPrefixToRemove = $pathPrefixToRemove;
        return $this;
    }

    /**
     * @param string $responseClass
     *
     * @return SwagenGenerateResponseDocumentation
     */
    public function setResponseClass(string $responseClass): SwagenGenerateResponseDocumentation
    {
        $this->responseClass = $responseClass;
        return $this;
    }

    /**
     * @param string $examplesDir
     *
     * @return SwagenGenerateResponseDocumentation
     */
    public function setExamplesDir(string $examplesDir): SwagenGenerateResponseDocumentation
    {
        $this->examplesDir = $examplesDir;
        return $this;
    }

    /**
     * @param FormFactory $formFactory
     *
     * @return SwagenGenerateResponseDocumentation
     */
    public function setFormFactory(FormFactory $formFactory): SwagenGenerateResponseDocumentation
    {
        $this->formFactory = $formFactory;
        return $this;
    }

}