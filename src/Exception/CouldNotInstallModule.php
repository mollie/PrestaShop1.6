<?php

namespace Mollie\Exception;

class CouldNotInstallModule extends MollieException
{
    public static function failedToInstallOrderState($orderStateName, \Exception $exception)
    {
        return new self(
            sprintf('Failed to install order state (%s).', $orderStateName),
            ExceptionCode::INFRASTRUCTURE_FAILED_TO_INSTALL_ORDER_STATE,
            $exception
        );
    }
}
