<?php

declare(strict_types=1);

namespace SilverShop\Tests\Checkout;

use SilverShop\Model\Order;
use SilverShop\Tests\ShopTestBootstrap;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Omnipay\Model\Payment;
use SilverStripe\Omnipay\Service\ServiceResponse;

final class PaymentExtensionRefundTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        ShopTestBootstrap::setConfiguration();
        Config::modify()->set(Order::class, 'send_receipt', false);
    }

    public function testOnRefundedUpdatesOrderStatusAndTransition(): void
    {
        $order = Order::create();
        $order->Total = 50.00;
        $order->Status = 'Paid';
        $order->write();

        $payment = Payment::create()->init('Manual', 50.00, 'NZD');
        $payment->OrderID = $order->ID;
        $payment->Status = 'Captured';
        $payment->write();

        $this->assertSame(50.0, (float) $order->TotalPaid());

        // Simulate full refund transition
        $payment->Status = 'Refunded';
        $payment->write();

        $serviceResponse = new ServiceResponse($payment);
        $payment->onRefunded($serviceResponse);

        $reloaded = Order::get()->byID($order->ID);
        $this->assertSame('Refunded', $reloaded->Status);

        $transitions = $reloaded->StatusTransitions();
        $this->assertGreaterThanOrEqual(1, $transitions->count());
        $refundTransition = $transitions->filter('TransitionName', 'payment_refunded')->first();
        $this->assertNotNull($refundTransition);
        $this->assertSame('Refunded', $refundTransition->ToStatus);
    }

    public function testTotalRefundedCalculation(): void
    {
        $order = Order::create();
        $order->Total = 100.00;
        $order->Status = 'Paid';
        $order->write();

        $payment1 = Payment::create()->init('Manual', 50.00, 'NZD');
        $payment1->OrderID = $order->ID;
        $payment1->Status = 'Refunded';
        $payment1->write();

        $payment2 = Payment::create()->init('Manual', 50.00, 'NZD');
        $payment2->OrderID = $order->ID;
        $payment2->Status = 'Captured';
        $payment2->write();

        $this->assertSame(50.00, $order->TotalRefunded());
    }

    public function testOnVoidUpdatesOrderStatus(): void
    {
        $order = Order::create();
        $order->Total = 75.00;
        $order->Status = 'Unpaid';
        $order->write();

        $payment = Payment::create()->init('Manual', 75.00, 'NZD');
        $payment->OrderID = $order->ID;
        $payment->Status = 'Void';
        $payment->write();

        $serviceResponse = new ServiceResponse($payment);
        $payment->onVoid($serviceResponse);

        $reloaded = Order::get()->byID($order->ID);
        $this->assertSame('AdminCancelled', $reloaded->Status);
    }
}
