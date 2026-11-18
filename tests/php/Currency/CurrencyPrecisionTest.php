<?php

declare(strict_types=1);

namespace SilverShop\Tests\Currency;

use SilverShop\Cart\OrderTotalCalculator;
use SilverShop\Currency\SimpleCurrencyService;
use SilverShop\Model\Order;
use SilverShop\Tests\ShopTestBootstrap;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

final class CurrencyPrecisionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        ShopTestBootstrap::setConfiguration();
    }

    public function testConvertWithPrecision(): void
    {
        Config::modify()->set(
            SimpleCurrencyService::class,
            'exchange_rates',
            [
                'USD' => 0.654321,
            ]
        );

        $service = SimpleCurrencyService::create();
        $unrounded = $service->convert(100.0, 'NZD', 'USD');
        $this->assertEqualsWithDelta(65.4321, $unrounded, 0.0001);

        $rounded = $service->convert(100.0, 'NZD', 'USD', 2);
        $this->assertSame(65.43, $rounded);
    }

    public function testOrderTotalCalculatorRoundsPrecision(): void
    {
        $order = Order::create();
        $order->write();

        $calculator = OrderTotalCalculator::create($order);
        $total = $calculator->calculate();

        $this->assertSame(0.0, $total);
    }

    public function testCanPayAllowsZeroDollarWhenConfigured(): void
    {
        Config::modify()->set(Order::class, 'allow_zero_order_total', true);

        $order = Order::create();
        $order->Total = 0.0;
        $order->Status = 'Unpaid';
        $order->write();

        $this->assertTrue($order->canPay(), 'Zero dollar orders can be paid when allow_zero_order_total is true');
    }
}
