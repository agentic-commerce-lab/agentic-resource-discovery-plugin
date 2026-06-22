<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Api;

use Swag\AgenticResourceDiscovery\Ard\Catalog\AiCatalogBuilder;
use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfigProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AiCatalogController
{
    public function __construct(
        private readonly AiCatalogBuilder $catalogBuilder,
        private readonly ArdConfigProviderInterface $configProvider,
    ) {
    }

    #[Route(path: '/.well-known/ai-catalog.json', name: 'swag_agentic_resource_discovery.ai_catalog', methods: ['GET'])]
    public function catalog(string $baseUrl, mixed $salesChannelContext): Response
    {
        $config = $this->configProvider->getConfig($salesChannelContext);

        if (!$config->enabled) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $manifest = $this->catalogBuilder->build($baseUrl, $salesChannelContext, $config);

        $response = new JsonResponse($manifest->toArray(), Response::HTTP_OK);
        $response->headers->set('cache-control', 'public, max-age=300');

        return $response;
    }
}
