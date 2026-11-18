<?php

declare(strict_types=1);

namespace SilverShop\Tests\Checkout;

use SilverShop\Checkout\PaymentGatewayRegistry;
use SilverShop\Tests\ShopTestBootstrap;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Omnipay\Model\Payment;

final class PaymentGatewayRegistryTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();
        ShopTestBootstrap::setConfiguration();
        Config::modify()->set(
            Payment::class,
            'allowed_gateways',
            [
                'Dummy',
                'Manual',
            ]
        );
    }

    public function testGetGatewayMetadataForKnownGateways(): void
    {
        $stripeMeta = PaymentGatewayRegistry::getGatewayMetadata('Stripe');
        $this->assertSame('Stripe', $stripeMeta['title']);
        $this->assertSame('icon-stripe', $stripeMeta['icon_class']);
        $this->assertNotEmpty($stripeMeta['description']);
        $this->assertContains('USD', $stripeMeta['currencies']);

        $paypalMeta = PaymentGatewayRegistry::getGatewayMetadata('PayPal_Express');
        $this->assertSame('PayPal Express', $paypalMeta['title']);
        $this->assertSame('icon-paypal', $paypalMeta['icon_class']);

        $manualMeta = PaymentGatewayRegistry::getGatewayMetadata('Manual');
        $this->assertSame('Manual / Bank Transfer', $manualMeta['title']);
        $this->assertSame('icon-manual', $manualMeta['icon_class']);
    }

    public function testGetGatewayMetadataForUnknownGatewayProvidesFallbacks(): void
    {
        $meta = PaymentGatewayRegistry::getGatewayMetadata('CustomGateway');
        $this->assertSame('icon-generic-payment', $meta['icon_class']);
        $this->assertNotEmpty($meta['title']);
    }

    public function testGetSupportedGatewaysWithMeta(): void
    {
        $gateways = PaymentGatewayRegistry::getSupportedGatewaysWithMeta();
        $this->assertArrayHasKey('Dummy', $gateways);
        $this->assertArrayHasKey('Manual', $gateways);
        $this->assertSame('Dummy', $gateways['Dummy']['gateway']);
        $this->assertSame('Manual', $gateways['Manual']['gateway']);
    }

    public function testIsAllowed(): void
    {
        $this->assertTrue(PaymentGatewayRegistry::isAllowed('Dummy'));
        $this->assertTrue(PaymentGatewayRegistry::isAllowed('Manual'));
        $this->assertFalse(PaymentGatewayRegistry::isAllowed('NonExistentGateway'));
    }

    public function testIconAndDescriptionHelpers(): void
    {
        $this->assertSame('icon-stripe', PaymentGatewayRegistry::getGatewayIcon('Stripe'));
        $this->assertStringContainsString('Stripe', PaymentGatewayRegistry::getGatewayDescription('Stripe'));
    }
}
