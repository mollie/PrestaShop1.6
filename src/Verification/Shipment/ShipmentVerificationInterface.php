<?php
/**
 * Mollie       https://www.mollie.nl
 *
 * @author      Mollie B.V. <info@mollie.nl>
 * @copyright   Mollie B.V.
 * @license     https://github.com/mollie/PrestaShop/blob/master/LICENSE.md
 *
 * @see        https://github.com/mollie/PrestaShop
 * @codingStandardsIgnoreStart
 */

namespace Mollie\Verification\Shipment;

use Order;
use OrderState;

interface ShipmentVerificationInterface
{
    /**
     * @param Order $order
     * @param OrderState $orderState
     *
     * @returns bool
     */
    public function verify(Order $order, OrderState $orderState);
}
