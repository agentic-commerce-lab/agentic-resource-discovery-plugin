<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Api;

use Swag\AgenticResourceDiscovery\Ard\Catalog\AiCatalogBuilder;
use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfig;
use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfigProviderInterface;
use Swag\AgenticResourceDiscovery\Ard\Http\ArdRequestValidator;
use Swag\AgenticResourceDiscovery\Ard\Http\ValidationResult;
use Swag\AgenticResourceDiscovery\Ard\Registry\RegistrySearchService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RegistryController
{
    public function __construct(
        private readonly AiCatalogBuilder $catalogBuilder,
        private readonly ArdConfigProviderInterface $configProvider,
        private readonly RegistrySearchService $searchService,
        private readonly ArdRequestValidator $requestValidator,
    ) {
    }

    #[Route(
        path: '/ard/search',
        name: 'swag_agentic_resource_discovery.search',
        defaults: ['_routeScope' => ['storefront'], 'auth_required' => false],
        methods: ['POST'],
    )]
    public function search(Request $request, mixed $salesChannelContext = null): Response
    {
        $config = $this->configProvider->getConfig($salesChannelContext);
        if (!$config->enabled) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $validation = $this->requestValidator->validateSearch($request);
        if (!$validation->valid) {
            return $this->error($validation);
        }

        return new JsonResponse($this->searchService->search(
            $this->entries($request, $salesChannelContext, $config),
            $this->registrySource($request),
            $validation->payload['query'],
            $validation->payload['pageSize'],
            $validation->payload['pageToken'],
            $validation->payload['federation'],
            $config->referrals,
        ));
    }

    #[Route(
        path: '/ard/explore',
        name: 'swag_agentic_resource_discovery.explore',
        defaults: ['_routeScope' => ['storefront'], 'auth_required' => false],
        methods: ['POST'],
    )]
    public function explore(Request $request, mixed $salesChannelContext = null): Response
    {
        $config = $this->configProvider->getConfig($salesChannelContext);
        if (!$config->enabled) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $validation = $this->requestValidator->validateExplore($request);
        if (!$validation->valid) {
            return $this->error($validation);
        }

        return new JsonResponse($this->searchService->explore(
            $this->entries($request, $salesChannelContext, $config),
            $validation->payload['query'],
            $validation->payload['facets'],
        ));
    }

    #[Route(
        path: '/ard/agents',
        name: 'swag_agentic_resource_discovery.agents',
        defaults: ['_routeScope' => ['storefront'], 'auth_required' => false],
        methods: ['GET'],
    )]
    public function agents(Request $request, mixed $salesChannelContext = null): Response
    {
        $config = $this->configProvider->getConfig($salesChannelContext);
        if (!$config->enabled) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $validation = $this->requestValidator->validateList($request);

        return new JsonResponse($this->searchService->list(
            $this->entries($request, $salesChannelContext, $config),
            $validation->payload['filter'],
            $validation->payload['pageSize'],
            $validation->payload['pageToken'],
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function entries(Request $request, mixed $salesChannelContext, ArdConfig $config): array
    {
        return $this->catalogBuilder
            ->build($request->getSchemeAndHttpHost(), $salesChannelContext, $config)
            ->toArray()['entries'];
    }

    private function registrySource(Request $request): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/').'/ard';
    }

    private function error(ValidationResult $validation): JsonResponse
    {
        return new JsonResponse([
            'errorCode' => $validation->errorCode ?? 'INVALID_ARGUMENT',
            'message' => $validation->message ?? 'Invalid request.',
        ], Response::HTTP_BAD_REQUEST);
    }
}
