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

namespace Mollie\Repository;

use Context;
use Db;
use DbQuery;
use Exception;
use Mollie\Api\Types\PaymentStatus;
use MolPaymentMethod;
use mysqli_result;
use PDOStatement;
use PrestaShopDatabaseException;
use PrestaShopException;

/**
 * @deprecated - outside code must always use interface. Use PaymentMethodRepositoryInterface instead.
 * In Containers use PaymentMethodRepositoryInterface::class
 */
class PaymentMethodRepository extends AbstractRepository implements PaymentMethodRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MolPaymentMethod::class);
    }

    /**
     * @param string $paymentMethodId
     *
     * @return false|string|null
     */
    public function getPaymentMethodIssuersByPaymentMethodId($paymentMethodId)
    {
        $sql = 'Select issuers_json FROM `' . _DB_PREFIX_ . 'mol_payment_method_issuer` WHERE id_payment_method = "' . pSQL($paymentMethodId) . '"';

        return Db::getInstance()->getValue($sql);
    }

    /**
     * @param string $paymentMethodId
     *
     * @return bool
     */
    public function deletePaymentMethodIssuersByPaymentMethodId($paymentMethodId)
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'mol_payment_method_issuer` WHERE id_payment_method = "' . pSQL($paymentMethodId) . '"';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param array $savedPaymentMethods
     * @param int $environment
     *
     * @return bool
     */
    public function deleteOldPaymentMethods(array $savedPaymentMethods, $environment)
    {
        $escapedMethods = array_map(static function ($str) { return pSQL($str); }, $savedPaymentMethods);

        return Db::getInstance()->delete(
            'mol_payment_method',
            'id_method NOT IN ("' . implode('", "', $escapedMethods) . '")
            AND `live_environment` = ' . (int) $environment
        );
    }

    /**
     * @param string $paymentMethodId
     * @param int $environment
     *
     * @return false|string|null
     */
    public function getPaymentMethodIdByMethodId($paymentMethodId, $environment, $shopId = null)
    {
        if (!$shopId) {
            $shopId = Context::getContext()->shop->id;
        }

        $sql = 'SELECT id_payment_method FROM `' . _DB_PREFIX_ . 'mol_payment_method`
        WHERE id_method = "' . pSQL($paymentMethodId) . '" AND live_environment = "' . (int) $environment . '" 
        AND id_shop = ' . (int) $shopId;

        return Db::getInstance()->getValue($sql);
    }

    /**
     * @param string $column
     * @param string $id
     *
     * @return array|bool|object|null
     *
     * @throws PrestaShopDatabaseException
     */
    public function getPaymentBy($column, $id)
    {
        try {
            $paidPayment = Db::getInstance()->getRow(
                sprintf(
                    'SELECT * FROM `%s` WHERE `%s` = \'%s\' AND `bank_status` IN(\'%s\', \'%s\')',
                    _DB_PREFIX_ . 'mollie_payments',
                    bqSQL($column),
                    pSQL($id),
                    PaymentStatus::STATUS_PAID,
                    PaymentStatus::STATUS_AUTHORIZED
                )
            );
        } catch (PrestaShopDatabaseException $e) {
            static::tryAddOrderReferenceColumn();
            throw $e;
        }

        if ($paidPayment) {
            return $paidPayment;
        }

        try {
            $nonPaidPayment = Db::getInstance()->getRow(
                sprintf(
                    'SELECT * FROM `%s` WHERE `%s` = \'%s\' ORDER BY `created_at` DESC',
                    _DB_PREFIX_ . 'mollie_payments',
                    bqSQL($column),
                    pSQL($id)
                )
            );
        } catch (PrestaShopDatabaseException $e) {
            $this->tryAddOrderReferenceColumn();
            throw $e;
        }

        return $nonPaidPayment;
    }

    /**
     * Add the order reference column in case the module upgrade script hasn't run.
     *
     * @return bool
     *
     * @since 3.3.0
     */
    public function tryAddOrderReferenceColumn()
    {
        try {
            if (!Db::getInstance()->getValue('
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = \'' . _DB_NAME_ . '\'
                AND TABLE_NAME = \'' . _DB_PREFIX_ . 'mollie_payments\'
                AND COLUMN_NAME = \'order_reference\'')
            ) {
                return Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'mollie_payments` ADD `order_reference` varchar(191)');
            }
        } catch (PrestaShopException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return array|false|mysqli_result|PDOStatement|resource|null
     *
     * @throws PrestaShopDatabaseException
     */
    public function getMethodsForCheckout($environment, $shopId)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('mol_payment_method');
        $sql->where('live_environment = ' . pSQL($environment));
        $sql->where('id_shop = ' . (int) $shopId);

        return Db::getInstance()->executeS($sql);
    }

    /**
     * @param string $oldTransactionId
     * @param string $newTransactionId
     *
     * @return bool
     */
    public function updateTransactionId($oldTransactionId, $newTransactionId)
    {
        return Db::getInstance()->update(
            'mollie_payments',
            [
                'transaction_id' => pSQL($newTransactionId),
            ],
            '`transaction_id` = \'' . pSQL($oldTransactionId) . '\''
        );
    }

    public function savePaymentStatus($transactionId, $status, $orderId, $paymentMethod)
    {
        try {
            return Db::getInstance()->update(
                'mollie_payments',
                [
                    'updated_at' => ['type' => 'sql', 'value' => 'NOW()'],
                    'bank_status' => pSQL($status),
                    'order_id' => (int) $orderId,
                    'method' => pSQL($paymentMethod),
                ],
                '`transaction_id` = \'' . pSQL($transactionId) . '\''
            );
        } catch (Exception $e) {
            $this->tryAddOrderReferenceColumn();
            throw $e;
        }
    }

    public function addOpenStatusPayment($cartId, $orderPayment, $transactionId, $orderId, $orderReference)
    {
        return Db::getInstance()->insert(
            'mollie_payments',
            [
                'cart_id' => (int) $cartId,
                'method' => pSQL($orderPayment),
                'transaction_id' => pSQL($transactionId),
                'bank_status' => PaymentStatus::STATUS_OPEN,
                'order_id' => (int) $orderId,
                'order_reference' => psql($orderReference),
                'created_at' => ['type' => 'sql', 'value' => 'NOW()'],
            ]
        );
    }

    public function updatePaymentReason($transactionId, $reason)
    {
        try {
            return Db::getInstance()->update(
                'mollie_payments',
                [
                    'updated_at' => ['type' => 'sql', 'value' => 'NOW()'],
                    'reason' => pSQL($reason),
                ],
                '`transaction_id` = \'' . pSQL($transactionId) . '\''
            );
        } catch (Exception $e) {
            throw $e;
        }
    }
}
