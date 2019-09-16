<?php /** @noinspection SlowArrayOperationsInLoopInspection */

namespace CodexSoft\JsonApi\Documentation\SwaggerGenerator;

use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SwagenForm;
use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SwagenGenerateResponseDocumentation;
use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SwagenLib;
use CodexSoft\JsonApi\Documentation\SwaggerGenerator\SwagenGenerateActionDocumentation;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Route;

/**
 * Swagger API documentation auto generator
 */
class SwagenGenerateApiDocumentation
{

    /** @var SwagenLib */
    private $lib;

    /** @var string */
    private $rootDir;

    /** @var string */
    private $destinationFile = '/src/Definitions/Definitions.php';

    /** @var array */
    private $formsClassesMap;

    /** @var array */
    private $responsesClassesMap;

    /** @var string */
    private $pathPrefixToRemove;

    private $strictMode = false;

    public function __construct(SwagenLib $lib)
    {
        $this->lib = $lib;
    }

    private function getLogger(): LoggerInterface
    {
        return $this->lib->getLogger();
    }

    /**
     * @param null|string $pathPrefixToRemove
     *
     * @return array
     * @throws \Throwable
     */
    protected function generateRoutes(?string $pathPrefixToRemove): array
    {
        $lines = [];

        $router = $this->lib->getRouter();
        /** @var Route[] $routes */
        $routes = $router->getRouteCollection();

        foreach ($routes as $routeName => $route) {
            try {
                $routeLines = (new SwagenGenerateActionDocumentation($this->lib))->setPathPrefixToRemove($pathPrefixToRemove)
                    ->generate($route);

                if (\is_array($routeLines)) {
                    //$lines = \array_merge($lines, $routeLines);
                    \array_push($lines, ...$routeLines);
                }
            } catch (\Throwable $e) {
                $this->getLogger()
                    ->error('FAILED to generate documentation for '.$routeName.': '.$e->getMessage().', SKIPPED');
                if ($this->strictMode) {
                    throw $e;
                }
            }

        }

        return $lines;
    }

    /**
     * @return array
     * @deprecated can be skipped?
     */
    protected function generateLegacyResponses(): array
    {
        $info = [
            ' * @SWG\Swagger(',
            ' *     schemes={"https"},',
            ' *     host="test.codexsoft.ru",',
            ' *     consumes={"application/json"},',
            ' *     produces={"application/json"},',
            ' *     @SWG\Info(',
            ' *         version="1.0.0",',
            ' *         title="Test Application API",',
            ' *         description="Замечание 1. Булевы значения принимаются и возвращаются в виде INT {0|1}, для некоторых полей допустим набор {0|1|null}",',
            ' *     )',
            ' * )',
        ];

        return [
            ' * @SWG\Response(',
            ' *   response="success_response",',
            ' *   description="Success response",',
            ' *   ref="$/responses/response",',
            ' *   @SWG\Schema(',
            ' *     @SWG\Property(property="status", type="string", enum={"ok"})',
            ' *   )',
            ' * )',
            ' *',
            ' * @SWG\Response(',
            ' *   response="error_response",',
            ' *   description="Error response",',
            ' *   ref="$/responses/response",',
            ' *   @SWG\Schema(',
            ' *     @SWG\Property(property="status", type="string", enum={"error"}),',
            ' *     @SWG\Property(property="error_no", type="integer"),',
            ' *     @SWG\Property(property="error_str", type="string"),',
            ' *     @SWG\Property(property="registered_request_id", type="integer")',
            '    *   )',
            ' * )',
            ' *',
            ' * @SWG\Response(',
            ' *   response="response",',
            ' *   description="Parent response object",',
            ' *   @SWG\Schema(',
            ' *     @SWG\Property(property="status", type="string", enum={"ok", "error", "unknown"})',
            ' *   )',
            ' * )',
        ];
    }

    /**
     * @param string $pathPrefixToRemove
     * @param string $responsesDir
     * @param string $responsesNamespace
     * @param string $examplesDir
     *
     * @return array
     * @throws \Throwable
     */
    protected function generateResponses(?string $pathPrefixToRemove, string $responsesDir, string $responsesNamespace, string $examplesDir): array
    {

        /** generating responses documentation */

        $lines = [];

        $responseClasses = $this->lib->findClassesInPath($responsesDir, $responsesNamespace);
        $responseClasses = \array_unique($responseClasses);

        foreach ($responseClasses as $responseClass) {

            try {
                $responseLines = (new SwagenGenerateResponseDocumentation($this->lib))->setExamplesDir($examplesDir)
                    ->setPathPrefixToRemove($pathPrefixToRemove)
                    ->setResponseClass($responseClass)
                    ->generate();

                if (\is_array($responseLines)) {
                    //$lines = \array_merge($lines, $responseLines);
                    \array_push($lines, ...$responseLines);
                }
            } catch (\Throwable $e) {
                $this->getLogger()
                    ->error('FAILED to generate documentation for response '.$responseClass.': '.$e->getMessage().', SKIPPED');
                if ($this->strictMode) {
                    throw $e;
                }
            }

        }

        return $lines;

    }

    /**
     * @param $formsDir
     * @param $formsNamespace
     *
     * @return array
     * @throws \ReflectionException
     */
    protected function generateForms($formsDir, $formsNamespace): array
    {
        $lines = [];

        $formClasses = $this->lib->findClassesInPath($formsDir, $formsNamespace);

        foreach ($formClasses as $formClass) {

            try {
                $formLines = (new SwagenForm($this->lib))->generate($formClass);

                if (\is_array($formLines)) {
                    //$lines = \array_merge($lines, $formLines);
                    \array_push($lines, ...$formLines);
                }

            } catch (\ReflectionException $e) {
                $this->getLogger()
                    ->error('FAILED to generate documentation for form '.$formClass.': '.$e->getMessage().', SKIPPED');
                if ($this->strictMode) {
                    throw $e;
                }
            }

        }

        return $lines;
    }

    private function getDefaultResponsesClassMap(): array
    {
        return [
            $this->rootDir.'/Response' => $this->lib->getResponsesDefaultNamespace(),
        ];
    }

    private function getDefaultFormsClassMap(): array
    {
        return [
            $this->rootDir.'/Form' => $this->lib->getFormsDefaultNamespace(),
        ];
    }

    /**
     * @throws \Throwable
     */
    public function execute(): void
    {

        $destinationClassNamespace = 'App\\Definitions';

        $lines = [
            '<?php',
            'namespace '.$destinationClassNamespace.';',
            '',
            '/**',
        ];

        \array_push($lines, ...$this->generateRoutes($this->pathPrefixToRemove));

        $formsClassMap = $this->formsClassesMap ?: $this->getDefaultFormsClassMap();
        foreach ($formsClassMap as $path => $namespace) {
            \array_push($lines, ...$this->generateForms($path, $namespace));
        }

        $responsesClassMap = $this->responsesClassesMap ?: $this->getDefaultResponsesClassMap();
        foreach ($responsesClassMap as $path => $namespace) {
            \array_push($lines, ...$this->generateResponses($this->pathPrefixToRemove, $path, $namespace, $this->rootDir.'/docs/response/'));
        }

        // todo: can be skipped?
        //\array_push($lines, ...$this->generateLegacyResponses());

        $lines[] = ' */';
        $lines[] = 'class Definitions {}';

        $code = implode("\n", $lines);

        $destinationPathFromRoot = $this->destinationFile;
        $destinationFilePath = $this->rootDir.$destinationPathFromRoot;

        $fs = new Filesystem;
        $fs->dumpFile($destinationFilePath, $code);

    }

    /**
     * @param string $rootDir
     *
     * @return SwagenGenerateApiDocumentation
     */
    public function setRootDir(string $rootDir): SwagenGenerateApiDocumentation
    {
        $this->rootDir = $rootDir;
        return $this;
    }

    /**
     * @param string $destinationFile
     *
     * @return SwagenGenerateApiDocumentation
     */
    public function setDestinationFile(string $destinationFile): SwagenGenerateApiDocumentation
    {
        $this->destinationFile = $destinationFile;
        return $this;
    }

    /**
     * @param array $responsesClassesMap
     *
     * @return SwagenGenerateApiDocumentation
     */
    public function setResponsesClassesMap(array $responsesClassesMap): SwagenGenerateApiDocumentation
    {
        $this->responsesClassesMap = $responsesClassesMap;
        return $this;
    }

    /**
     * @param array $formsClassesMap
     *
     * @return SwagenGenerateApiDocumentation
     */
    public function setFormsClassesMap(array $formsClassesMap): SwagenGenerateApiDocumentation
    {
        $this->formsClassesMap = $formsClassesMap;
        return $this;
    }

    /**
     * @param string $pathPrefixToRemove
     *
     * @return SwagenGenerateApiDocumentation
     */
    public function setPathPrefixToRemove(?string $pathPrefixToRemove): SwagenGenerateApiDocumentation
    {
        $this->pathPrefixToRemove = $pathPrefixToRemove;
        return $this;
    }

    /**
     * @param bool $strictMode
     *
     * @return SwagenGenerateApiDocumentation
     */
    public function setStrictMode(bool $strictMode): SwagenGenerateApiDocumentation
    {
        $this->strictMode = $strictMode;
        return $this;
    }

}