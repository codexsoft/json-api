<?php

namespace CodexSoft\JsonApi;

use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Routing\RouteCollectionBuilder;

class DevWebServer extends AbstractWebServer
{
    /**
     * @param RouteCollectionBuilder $routes
     *
     * @throws LoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import($this->getProjectDir().'/tests/unit/', '/', 'annotation');
        parent::configureRoutes($routes);
    }

}
