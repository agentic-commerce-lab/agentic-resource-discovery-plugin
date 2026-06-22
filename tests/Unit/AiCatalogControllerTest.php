<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\Api\AiCatalogController;
use Swag\AgenticResourceDiscovery\Ard\Catalog\AiCatalogBuilder;
use Swag\AgenticResourceDiscovery\Ard\Catalog\CoreShopwareResourceProvider;
use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfig;
use Swag\AgenticResourceDiscovery\Ard\Config\InMemoryArdConfigProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

return [
    'enabled controller returns ai catalog json with cache headers' => static function (): void {
        $controller = new AiCatalogController(
            new AiCatalogBuilder([new CoreShopwareResourceProvider()]),
            new InMemoryArdConfigProvider(new ArdConfig(
                enabled: true,
                hostDisplayName: 'Demo Shop',
            )),
        );

        $response = $controller->catalog('https://example.com', null);
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        assert_true($response instanceof JsonResponse, 'Expected a Symfony JsonResponse.');
        assert_same(Response::HTTP_OK, $response->getStatusCode());
        assert_same('application/json', $response->headers->get('content-type'));
        assert_true($response->headers->hasCacheControlDirective('public'), 'Expected public cache directive.');
        assert_same('300', $response->headers->getCacheControlDirective('max-age'));
        assert_same('1.0', $payload['specVersion']);
        assert_same('Demo Shop', $payload['host']['displayName']);
        assert_same('urn:air:example.com:shopware:ard-registry', $payload['entries'][0]['identifier']);
    },

    'disabled controller returns not found' => static function (): void {
        $controller = new AiCatalogController(
            new AiCatalogBuilder([new CoreShopwareResourceProvider()]),
            new InMemoryArdConfigProvider(new ArdConfig(
                enabled: false,
                hostDisplayName: 'Demo Shop',
            )),
        );

        $response = $controller->catalog('https://example.com', null);

        assert_true($response instanceof Response, 'Expected a Symfony Response.');
        assert_same(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        assert_same('', (string) $response->getContent());
    },
];
