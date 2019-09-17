<?php

return (new \CodexSoft\JsonApi\JsonApiSchema)
    ->setNamespaceBase('TestApi')
    ->setPathToPsrRoot(dirname(__DIR__).'/tests/unit');
    //->setPathToPsrRoot(dirname(__DIR__).'/src');
