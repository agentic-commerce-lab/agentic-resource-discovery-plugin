<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\AgenticCommerce\AgenticCommerceBridgeInterface;
use Swag\AgenticResourceDiscovery\Ard\AgenticCommerce\NullAgenticCommerceBridge;
use Swag\AgenticResourceDiscovery\Ard\Catalog\CoreShopwareResourceProvider;
use Swag\AgenticResourceDiscovery\Ard\Catalog\AgenticCommerceResourceProvider;
use Swag\AgenticResourceDiscovery\Ard\Catalog\AiCatalogBuilder;
use Swag\AgenticResourceDiscovery\Ard\Api\AiCatalogController;
use Swag\AgenticResourceDiscovery\Ard\Api\RegistryController;
use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfigProviderInterface;
use Swag\AgenticResourceDiscovery\Ard\Config\SystemConfigArdConfigProvider;
use Swag\AgenticResourceDiscovery\Ard\Catalog\StaticConfigResourceProvider;
use Swag\AgenticResourceDiscovery\Ard\Log\NullStaticEntryWarningLogger;
use Swag\AgenticResourceDiscovery\Ard\Log\StaticEntryWarningLoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

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

    $services->set(StaticConfigResourceProvider::class)
        ->tag('swag_agentic_resource_discovery.catalog_entry_provider');

    $services->set(AgenticCommerceResourceProvider::class)
        ->tag('swag_agentic_resource_discovery.catalog_entry_provider');

    $services->alias(AgenticCommerceBridgeInterface::class, NullAgenticCommerceBridge::class);

    $services->alias(StaticEntryWarningLoggerInterface::class, NullStaticEntryWarningLogger::class);

    $services->alias(ArdConfigProviderInterface::class, SystemConfigArdConfigProvider::class);

    $services->set(SystemConfigArdConfigProvider::class)
        ->arg('$systemConfigService', service(SystemConfigService::class));

    $services->set(AiCatalogBuilder::class)
        ->arg('$entryProviders', tagged_iterator('swag_agentic_resource_discovery.catalog_entry_provider'));

    $services->set(AiCatalogController::class)
        ->tag('controller.service_arguments');

    $services->set(RegistryController::class)
        ->tag('controller.service_arguments');
};
