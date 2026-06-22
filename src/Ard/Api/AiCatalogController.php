<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Api;

use Swag\AgenticResourceDiscovery\Ard\Catalog\AiCatalogBuilder;
use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfigProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AiCatalogController
{
    public function __construct(
        private readonly AiCatalogBuilder $catalogBuilder,
        private readonly ArdConfigProviderInterface $configProvider,
    ) {
    }

    #[Route(
        path: '/.well-known/ai-catalog.json',
        name: 'swag_agentic_resource_discovery.ai_catalog',
        defaults: [
            '_routeScope' => ['storefront'],
            'auth_required' => false,
            '_httpCache' => true,
            ],
        methods: ['GET'],
    )]
    public function catalog(Request $request, mixed $salesChannelContext = null): Response
    {
        $config = $this->configProvider->getConfig($salesChannelContext);

        if (!$config->enabled) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $manifest = $this->catalogBuilder->build($request->getSchemeAndHttpHost(), $salesChannelContext, $config);

        $response = new JsonResponse($manifest->toArray(), Response::HTTP_OK);

        // headers set in src/Ard/Subscribers/AiCatalogResponseSubscriber.php to prevent overwriting

        return $response;
    }
}
