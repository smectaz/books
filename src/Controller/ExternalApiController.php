<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ExternalApiController extends AbstractController
{
    /**
     * Cette méthode fait appel à la route https://api.github.com/repos/symfony/symfony-docs
     * Elle récupère les données et les transmet telles quelles.
     * 
     * doc client http:
     * https://symfony.com/doc/current/http_client.html
     */
    
    #[Route('/api/external/getSfDoc', name: 'external_api', methods: 'GET',)]
    public function getSymfonyDoc(HttpClientInterface $httpClient): JsonResponse
    {
        $response = $httpClient -> request(
            'GET',
            'https://api.github.com/repos/symfony/symfony-docs'
        );
        return new JsonResponse($response -> getContent(), $response -> getStatusCode(), [], true);
    }
}
