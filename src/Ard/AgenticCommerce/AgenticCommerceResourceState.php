<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\AgenticCommerce;

final class AgenticCommerceResourceState
{
    public const UCP_VERSION = '2026-04-08';

    public const UCP_CAPABILITY_CATALOG = 'dev.ucp.shopping.catalog';
    public const UCP_CAPABILITY_CART = 'dev.ucp.shopping.cart';
    public const UCP_CAPABILITY_DISCOUNT = 'dev.ucp.shopping.discount';
    public const UCP_CAPABILITY_CHECKOUT = 'dev.ucp.shopping.checkout';
    public const UCP_CAPABILITY_ORDER = 'dev.ucp.shopping.order';
    public const UCP_CAPABILITY_IDENTITY_LINKING = 'dev.ucp.common.identity_linking';
    public const UCP_CAPABILITY_PAYMENT_TOKENIZATION = 'dev.ucp.shopping.payment_tokenization';

    /**
     * @param list<string> $enabledCapabilities
     */
    public function __construct(
        public readonly bool $ucpActive,
        public readonly bool $mcpAvailable = false,
        public readonly bool $nativeDiscoveryAvailable = false,
        public readonly array $enabledCapabilities = [
            self::UCP_CAPABILITY_CATALOG,
            self::UCP_CAPABILITY_CART,
            self::UCP_CAPABILITY_DISCOUNT,
            self::UCP_CAPABILITY_CHECKOUT,
            self::UCP_CAPABILITY_ORDER,
        ],
        public readonly string $protocolVersion = self::UCP_VERSION,
    ) {
    }
}
