<?php

use Mollie\Api\MollieApiClient;
use Mollie\Handler\Shipment\ShipmentSenderHandler;
use Mollie\Logger\PrestaLogger;
use Mollie\Service\Shipment\ShipmentInformationSender;
use Mollie\Verification\Shipment\CanSendShipment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShipmentSenderHandlerTest extends TestCase
{
    /**
     * @var MollieApiClient|MockObject
     */
    private $apiClient;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var OrderState|MockObject
     */
    private $orderState;

    /**
     * @var CanSendShipment|MockObject
     */
    private $canSendShipment;

    /**
     * @var ShipmentInformationSender|MockObject
     */
    private $shipmentInformationSender;

    /**
     * @var PrestaLogger|MockObject
     */
    private $moduleLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = $this
            ->getMockBuilder(MollieApiClient::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->order = $this
            ->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->orderState = $this
            ->getMockBuilder(OrderState::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->canSendShipment = $this
            ->getMockBuilder(CanSendShipment::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->shipmentInformationSender = $this
            ->getMockBuilder(ShipmentInformationSender::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->moduleLogger = $this
            ->getMockBuilder(PrestaLogger::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    public function testCanSendShipment()
    {
        $this->canSendShipment
            ->expects($this->once())
            ->method('verify')
            ->willReturn(true)
        ;

        $this->moduleLogger
            ->expects($this->never())
            ->method('error')
        ;

        $shipmentSenderHandler = new ShipmentSenderHandler(
            $this->canSendShipment,
            $this->shipmentInformationSender,
            $this->moduleLogger
        );

        $result = $shipmentSenderHandler->handleShipmentSender($this->apiClient, $this->order, $this->orderState);

        $this->assertEquals(true, $result);
    }

    public function testOnVerificationExceptionLogExceptionAndNotSendInformation()
    {
        $this->order->reference = 'test';

        $this->canSendShipment
            ->expects($this->once())
            ->method('verify')
            ->willReturn(false)
        ;

        $this->moduleLogger
            ->expects($this->once())
            ->method('error')
        ;

        $shipmentSenderHandler = new ShipmentSenderHandler(
            $this->canSendShipment,
            $this->shipmentInformationSender,
            $this->moduleLogger
        );

        $result = $shipmentSenderHandler->handleShipmentSender($this->apiClient, $this->order, $this->orderState);

        $this->assertEquals(false, $result);
    }
}
