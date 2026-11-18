<?php

declare(strict_types=1);

namespace SilverShop\Checkout;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Omnipay\GatewayInfo;
use SilverStripe\Omnipay\Model\Payment;

/**
 * Registry and metadata manager for mainstream payment gateways (Stripe, PayPal, Mollie, Manual, etc.).
 *
 * Provides gateway metadata (icons, descriptions, display titles, supported currencies)
 * and helper methods to query and register payment channels for checkout rendering.
 */
class PaymentGatewayRegistry
{
    use Injectable;
    use Configurable;

    /**
     * Pre-defined metadata definitions for mainstream payment gateways.
     * Can be customized or extended via YAML config.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $gateways = [
        'Stripe' => [
            'title' => 'Stripe',
            'description' => 'Pay securely with Credit Card, Apple Pay or Google Pay via Stripe.',
            'icon_class' => 'icon-stripe',
            'badge' => 'Credit / Debit Card',
            'type' => 'offsite',
            'currencies' => ['USD', 'EUR', 'GBP', 'AUD', 'NZD', 'CAD', 'JPY', 'CNY', 'HKD', 'SGD'],
        ],
        'PayPal_Express' => [
            'title' => 'PayPal Express',
            'description' => 'Fast and secure checkout with your PayPal account or balance.',
            'icon_class' => 'icon-paypal',
            'badge' => 'PayPal',
            'type' => 'offsite',
            'currencies' => ['USD', 'EUR', 'GBP', 'AUD', 'NZD', 'CAD', 'JPY'],
        ],
        'PayPal_Rest' => [
            'title' => 'PayPal REST',
            'description' => 'Direct PayPal gateway using modern REST API.',
            'icon_class' => 'icon-paypal',
            'badge' => 'PayPal',
            'type' => 'offsite',
            'currencies' => ['USD', 'EUR', 'GBP', 'AUD', 'NZD', 'CAD'],
        ],
        'Mollie' => [
            'title' => 'Mollie Payments',
            'description' => 'Pay with iDEAL, Bancontact, SOFORT, Cartes Bancaires or Credit Card.',
            'icon_class' => 'icon-mollie',
            'badge' => 'Mollie',
            'type' => 'offsite',
            'currencies' => ['EUR', 'USD', 'GBP'],
        ],
        'Manual' => [
            'title' => 'Manual / Bank Transfer',
            'description' => 'Direct bank transfer or invoice payment upon order confirmation.',
            'icon_class' => 'icon-manual',
            'badge' => 'Bank Transfer',
            'type' => 'manual',
            'currencies' => [],
        ],
        'Dummy' => [
            'title' => 'Dummy Payment (Test Mode)',
            'description' => 'Test payment gateway for local simulation and integration testing.',
            'icon_class' => 'icon-test',
            'badge' => 'Test Mode',
            'type' => 'onsite',
            'currencies' => [],
        ],
    ];

    /**
     * Get metadata for a specific gateway.
     *
     * @param string $gateway Gateway identifier
     * @return array<string, mixed>
     */
    public static function getGatewayMetadata(string $gateway): array
    {
        $registry = self::config()->get('gateways') ?: [];
        $meta = $registry[$gateway] ?? [];

        // Fill defaults if missing
        if (!isset($meta['title'])) {
            $meta['title'] = GatewayInfo::niceTitle($gateway);
        }
        if (!isset($meta['icon_class'])) {
            $meta['icon_class'] = 'icon-generic-payment';
        }
        if (!isset($meta['badge'])) {
            $meta['badge'] = $meta['title'];
        }
        if (!isset($meta['description'])) {
            $meta['description'] = '';
        }

        return $meta;
    }

    /**
     * Retrieve all currently allowed gateways combined with their metadata.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getSupportedGatewaysWithMeta(): array
    {
        $supported = GatewayInfo::getSupportedGateways();
        $result = [];

        foreach ($supported as $gateway => $title) {
            $meta = self::getGatewayMetadata($gateway);
            $meta['gateway'] = $gateway;
            $meta['display_title'] = $title;
            $result[$gateway] = $meta;
        }

        return $result;
    }

    /**
     * Get the icon CSS class for a given gateway.
     */
    public static function getGatewayIcon(string $gateway): string
    {
        $meta = self::getGatewayMetadata($gateway);
        return (string) ($meta['icon_class'] ?? 'icon-generic-payment');
    }

    /**
     * Get the human-readable description for a given gateway.
     */
    public static function getGatewayDescription(string $gateway): string
    {
        $meta = self::getGatewayMetadata($gateway);
        return (string) ($meta['description'] ?? '');
    }

    /**
     * Check if a gateway is configured in Payment allowed_gateways.
     */
    public static function isAllowed(string $gateway): bool
    {
        $allowed = Payment::config()->get('allowed_gateways');
        return is_array($allowed) && in_array($gateway, $allowed, true);
    }
}
