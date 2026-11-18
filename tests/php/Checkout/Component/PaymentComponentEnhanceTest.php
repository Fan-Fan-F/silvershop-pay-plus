<?php

declare(strict_types=1);

namespace SilverShop\Tests\Checkout\Component;

use SilverShop\Checkout\Component\Payment as PaymentComponent;
use SilverShop\Model\Order;
use SilverShop\Tests\ShopTestBootstrap;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Omnipay\Model\Payment;

final class PaymentComponentEnhanceTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        ShopTestBootstrap::setConfiguration();
    }

    public function testRequiredFieldsWithMultipleGateways(): void
    {
        Config::modify()->set(
            Payment::class,
            'allowed_gateways',
            [
                'Dummy',
                'Manual',
            ]
        );

        $order = Order::create();
        $order->write();

        $component = PaymentComponent::create();
        $required = $component->getRequiredFields($order);

        $this->assertContains('PaymentMethod', $required, 'PaymentMethod must be required when multiple gateways exist');
    }

    public function testRequiredFieldsWithSingleGateway(): void
    {
        Config::modify()->set(
            Payment::class,
            'allowed_gateways',
            [
                'Manual',
            ]
        );

        $order = Order::create();
        $order->write();

        $component = PaymentComponent::create();
        $required = $component->getRequiredFields($order);

        $this->assertNotContains('PaymentMethod', $required, 'Single gateway is automatically hidden and not required');
    }

    public function testGetFormFieldsPreselectsScalarGateway(): void
    {
        Config::modify()->set(
            Payment::class,
            'allowed_gateways',
            [
                'Dummy',
                'Manual',
            ]
        );

        $order = Order::create();
        $order->write();

        $component = PaymentComponent::create();
        $fields = $component->getFormFields($order);
        $field = $fields->dataFieldByName('PaymentMethod');

        $this->assertNotNull($field);
        $this->assertSame('Dummy', $field->dataValue());
    }

    public function testGetGatewayMetadata(): void
    {
        Config::modify()->set(
            Payment::class,
            'allowed_gateways',
            [
                'Dummy',
                'Manual',
            ]
        );

        $component = PaymentComponent::create();
        $meta = $component->getGatewayMetadata();

        $this->assertArrayHasKey('Dummy', $meta);
        $this->assertArrayHasKey('Manual', $meta);
    }
}
