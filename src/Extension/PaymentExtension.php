<?php

declare(strict_types=1);

namespace SilverShop\Extension;

use SilverShop\Checkout\OrderProcessor;
use SilverShop\Model\Order;
use SilverStripe\Core\Extension;
use SilverStripe\Omnipay\Model\Payment;
use SilverStripe\Omnipay\Service\ServiceResponse;

/**
 * Customisations to {@link Payment} specifically for the shop module.
 * @property int $OrderID
 * @method Order Order()
 * @extends Extension<(Payment & static)>
 */
class PaymentExtension extends Extension
{
    private static array $has_one = [
        'Order' => Order::class,
    ];

    public function onAwaitingAuthorized(ServiceResponse $response): void
    {
        $this->placeOrder();
    }

    public function onAwaitingCaptured(ServiceResponse $response): void
    {
        $this->placeOrder();
    }

    public function onAuthorized(ServiceResponse $response): void
    {
        $this->placeOrder();
    }

    public function onCaptured(ServiceResponse $response): void
    {
        // ensure order is being reloaded from DB, to prevent dealing with stale data!
        /**
         * @var Order $order
         */
        $order = Order::get()->byID($this->getOwner()->OrderID);
        if ($order && $order->exists()) {
            OrderProcessor::create($order)->completePayment();
        }
    }

    public function onRefunded(ServiceResponse $response): void
    {
        /** @var Order $order */
        $owner = $this->getOwner();
        $order = Order::get()->byID($owner->OrderID);
        if ($order && $order->exists()) {
            if ($order->TotalPaid() <= 0.0001) {
                $order->setStatus('Refunded', 'payment_refunded');
            } else {
                $order->recordTransition('payment_partial_refund');
            }
            $order->extend('onPaymentRefunded', $owner, $response);
            $order->write();
        }
    }

    public function onVoid(ServiceResponse $response): void
    {
        /** @var Order $order */
        $owner = $this->getOwner();
        $order = Order::get()->byID($owner->OrderID);
        if ($order && $order->exists()) {
            if ($order->TotalPaidOrAuthorized() <= 0.0001 && !$order->IsCart()) {
                $order->setStatus('AdminCancelled', 'payment_voided');
            } else {
                $order->recordTransition('payment_voided');
            }
            $order->extend('onPaymentVoided', $owner, $response);
            $order->write();
        }
    }

    public function onCancelled(): void
    {
        /** @var Order $order */
        $owner = $this->getOwner();
        $order = Order::get()->byID($owner->OrderID);
        if ($order && $order->exists()) {
            $order->recordTransition('payment_cancelled');
            $order->extend('onPaymentCancelled', $owner);
            $order->write();
        }
    }

    protected function placeOrder(): void
    {
        // ensure order is being reloaded from DB, to prevent dealing with stale data!
        /**
         * @var Order $order
         */
        $order = Order::get()->byID($this->getOwner()->OrderID);
        if ($order && $order->exists()) {
            OrderProcessor::create($order)->placeOrder();
        }
    }
}
