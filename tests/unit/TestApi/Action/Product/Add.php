<?php

namespace TestApi\Action\Product;

use CodexSoft\JsonApi\DocumentedFormAction;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TestApi\Action\Product\AddResponse;

class Add extends DocumentedFormAction
{
    
    /**
     * @Route("/product/add", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(): Response
    {
        $data = $this->getJsonData();
        if ($data instanceof Response) { return $data; }
        if ($this->isResponseExampleRequested()) { return $this->generateResponseExample(); }
        
        return new AddResponse([]);
    }
    
}