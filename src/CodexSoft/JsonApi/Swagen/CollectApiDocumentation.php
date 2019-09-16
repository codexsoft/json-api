<?php /** @noinspection DuplicatedCode */

namespace CodexSoft\JsonApi\Swagen;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;

class CollectApiDocumentation extends AbstractCollector
{

    /**
     * @param string $responseClass
     *
     * @return ApiDocumentation
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function collect(): ApiDocumentation
    {
        $docApi = new ApiDocumentation;
        $docApi->actions = $this->collectActions();

        $formsClassMap = $this->formsClassesMap ?: $this->getDefaultFormsClassMap();
        foreach ($formsClassMap as $path => $namespace) {
            \array_push($docApi->forms, ...$this->collectForms($path, $namespace));
        }

        $responsesClassMap = $this->responsesClassesMap ?: $this->getDefaultResponsesClassMap();
        foreach ($responsesClassMap as $path => $namespace) {
            \array_push($docApi->responses, ...$this->collectResponses($this->pathPrefixToRemove, $path, $namespace));
        }

        return $docApi;
    }

    protected function collectResponses(?string $pathPrefixToRemove, string $responsesDir, string $responsesNamespace): array
    {
        $responses = [];
        $responseClasses = $this->lib->findClassesInPath($responsesDir, $responsesNamespace);
        $responseClasses = \array_unique($responseClasses);
        foreach ($responseClasses as $responseClass) {
            $docResponse = (new CollectResponseDocumentation($this->lib))->collect($responseClass);
            if ($docResponse) {
                $responses[$responseClass] = $docResponse;
            }
        }
        return $responses;
    }

    /**
     * @param $formsDir
     * @param $formsNamespace
     *
     * @return FormDocumentation[]
     * @throws \ReflectionException
     */
    protected function collectForms($formsDir, $formsNamespace): array
    {
        $forms = [];
        $formClasses = $this->lib->findClassesInPath($formsDir, $formsNamespace);
        foreach ($formClasses as $formClass) {
            $docForm = (new CollectFormDocumentation($this->lib))->collect($formClass);
            if ($docForm) {
                $forms[$formClass] = $docForm;
            }
        }
        return $forms;
    }

    /**
     * @param null|string $pathPrefixToRemove
     *
     * @return ActionDocumentation[]
     * @throws \Throwable
     */
    protected function collectActions(?string $pathPrefixToRemove = null): array
    {
        $actions = [];

        $router = $this->lib->getRouter();
        /** @var Route[] $routes */
        $routes = $router->getRouteCollection();

        foreach ($routes as $routeName => $route) {
            try {
                $docAction = (new CollectActionDocumentation($this->lib))->setPathPrefixToRemove($pathPrefixToRemove)->collect($route);
                if ($docAction) {
                    $actions[$docAction->actionClass] = $docAction;
                }

            } catch (\Throwable $e) {
                $this->getLogger()->error('FAILED to collect documentation for '.$routeName.': '.$e->getMessage().', SKIPPED');
            }

        }

        return $actions;
    }

    private function getLogger(): LoggerInterface
    {
        return $this->lib->getLogger();
    }

}