<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\Catalog\CoreShopwareResourceProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $services->load('Swag\\AgenticResourceDiscovery\\', __DIR__.'/../../*')
        ->exclude([__DIR__.'/../../Resources']);

    $services->set(CoreShopwareResourceProvider::class)
        ->tag('swag_agentic_resource_discovery.catalog_entry_provider');
};
