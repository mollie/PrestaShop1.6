<?php

namespace Mollie\Install;

use Mollie\Adapter\ConfigurationAdapter;
use Mollie\Config\Config;
use Mollie\DTO\OrderStateData;
use Mollie\Enum\EmailTemplate;
use Mollie\Exception\CouldNotInstallModule;
use Mollie\Factory\ModuleFactory;
use Mollie\Service\OrderStateImageService;
use OrderState;

class OrderStateInstaller implements InstallerInterface
{
    /** @var ModuleFactory */
    private $moduleFactory;
    /** @var ConfigurationAdapter */
    private $configurationAdapter;
    /** @var OrderStateImageService */
    private $orderStateImageService;

    public function __construct(
        ModuleFactory $moduleFactory,
        ConfigurationAdapter $configurationAdapter,
        OrderStateImageService $orderStateImageService
    ) {
        $this->moduleFactory = $moduleFactory;
        $this->configurationAdapter = $configurationAdapter;
        $this->orderStateImageService = $orderStateImageService;
    }

    /**
     * @returns void
     *
     * @throws CouldNotInstallModule
     */
    public function install()
    {
        $this->installOrderState(
            Config::MOLLIE_STATUS_PARTIAL_REFUND,
            new OrderStateData(
                'Partially refunded by Mollie',
                '#6F8C9F'
            )
        );

        $this->installOrderState(
            Config::MOLLIE_STATUS_AWAITING,
            new OrderStateData(
                'Awaiting Mollie payment',
                '#4169E1'
            )
        );

        $this->installOrderState(
            Config::MOLLIE_STATUS_PARTIALLY_SHIPPED,
            new OrderStateData(
                'Partially shipped',
                '#8A2BE2'
            )
        );

        $this->installOrderState(
            Config::MOLLIE_STATUS_ORDER_COMPLETED,
            new OrderStateData(
                'Completed',
                '#3d7d1c',
                true
            )
        );

        $this->installOrderState(
            Config::MOLLIE_STATUS_KLARNA_AUTHORIZED,
            new OrderStateData(
                'Klarna payment authorized',
                '#8A2BE2',
                true,
                true,
                false,
                true,
                false,
                true,
                EmailTemplate::PAYMENT,
                true
            )
        );

        $this->installOrderState(
            Config::MOLLIE_STATUS_KLARNA_SHIPPED,
            new OrderStateData(
                'Klarna payment shipped',
                '#8A2BE2',
                true,
                true,
                true,
                false,
                true,
                true,
                EmailTemplate::SHIPPED,
                true
            )
        );

        return true;
    }

    /**
     * @param string $orderStatus
     * @param OrderStateData $orderStateInstallerData
     *
     * @return void
     *
     * @throws CouldNotInstallModule
     */
    private function installOrderState($orderStatus, $orderStateInstallerData)
    {
        if ($this->validateIfStatusExists($orderStatus)) {
            $this->enableState($orderStatus);

            return;
        }

        $orderState = $this->createOrderState($orderStateInstallerData);

        $this->updateStateConfiguration($orderStatus, $orderState);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    private function validateIfStatusExists($key)
    {
        $existingStateId = (int) $this->configurationAdapter->get($key);
        $orderState = new OrderState($existingStateId);

        // if state already existed we won't install new one.
        return \Validate::isLoadedObject($orderState);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    private function enableState($key)
    {
        $existingStateId = (int) $this->configurationAdapter->get($key);
        $orderState = new OrderState($existingStateId);

        if ((bool) !$orderState->deleted) {
            return;
        }

        $orderState->deleted = false;
        $orderState->save();
    }

    /**
     * @param OrderStateData $orderStateInstallerData
     *
     * @return OrderState
     *
     * @throws CouldNotInstallModule
     */
    private function createOrderState(OrderStateData $orderStateInstallerData)
    {
        $orderState = new OrderState();

        $orderState->send_email = $orderStateInstallerData->isSendEmail();
        $orderState->color = $orderStateInstallerData->getColor();
        $orderState->delivery = $orderStateInstallerData->isDelivery();
        $orderState->logable = $orderStateInstallerData->isLogable();
        $orderState->invoice = $orderStateInstallerData->isInvoice();
        $orderState->module_name = $this->moduleFactory->getModuleName();
        $orderState->shipped = $orderStateInstallerData->isShipped();
        $orderState->paid = $orderStateInstallerData->isPaid();
        $orderState->template = $orderStateInstallerData->getTemplate();
        $orderState->pdf_invoice = $orderStateInstallerData->isPdfInvoice();
        $orderState->hidden = false;
        $orderState->unremovable = false;

        $languages = \Language::getLanguages();

        foreach ($languages as $language) {
            $orderState->name[$language['id_lang']] = $orderStateInstallerData->getName();
        }

        try {
            $orderState->add();
        } catch (\Exception $exception) {
            throw CouldNotInstallModule::failedToInstallOrderState($orderStateInstallerData->getName(), $exception);
        }

        $this->orderStateImageService->createOrderStateLogo($orderState->id);

        return $orderState;
    }

    /**
     * @param string $key
     * @param OrderState $orderState
     *
     * @return void
     */
    private function updateStateConfiguration($key, $orderState)
    {
        $this->configurationAdapter->updateValue($key, (int) $orderState->id);
    }
}
