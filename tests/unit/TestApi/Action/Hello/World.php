<?php

namespace TestApi\Action\Hello;

use CodexSoft\JsonApi\DocumentedFormAction;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TestApi\Action\Hello\WorldResponse;

class World extends DocumentedFormAction
{
    
    /**
     * @Route("/hello/world", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(): Response
    {
        $data = $this->getJsonData();
        if ($data instanceof Response) { return $data; }
        if ($this->isResponseExampleRequested()) { return $this->generateResponseExample(); }
        
        return new WorldResponse([]);
    }
    
}