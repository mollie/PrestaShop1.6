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

namespace Mollie\Handler\Shipment;

use Mollie\Api\MollieApiClient;
use Mollie\Service\Shipment\ShipmentInformationSenderInterface;
use Mollie\Verification\Shipment\ShipmentVerificationInterface;
use Order;
use OrderState;
use Psr\Log\LoggerInterface;

class ShipmentSenderHandler implements ShipmentSenderHandlerInterface
{
    /**
     * @var ShipmentVerificationInterface
     */
    private $canSendShipment;

    /**
     * @var ShipmentInformationSenderInterface
     */
    private $shipmentInformationSender;

    /**
     * @var LoggerInterface
     */
    private $moduleLogger;

    public function __construct(
        ShipmentVerificationInterface $canSendShipment,
        ShipmentInformationSenderInterface $shipmentInformationSender,
        LoggerInterface $moduleLogger
    ) {
        $this->canSendShipment = $canSendShipment;
        $this->shipmentInformationSender = $shipmentInformationSender;
        $this->moduleLogger = $moduleLogger;
    }

    /**
     * @param MollieApiClient $apiClient
     * @param Order $order
     * @param OrderState $orderState
     *
     * @return bool
     */
    public function handleShipmentSender(MollieApiClient $apiClient, Order $order, OrderState $orderState)
    {
        try {
            if (!$this->canSendShipment->verify($order, $orderState)) {
                return false;
            }
        } catch (\Exception $exception) {
            $this->moduleLogger->error($exception->getMessage());

            return false;
        }

        $this->shipmentInformationSender->sendShipmentInformation($apiClient, $order);

        return true;
    }
}
