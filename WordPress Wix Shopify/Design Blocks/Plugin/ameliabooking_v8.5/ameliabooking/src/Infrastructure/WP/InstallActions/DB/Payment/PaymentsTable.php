<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Payment;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class PaymentsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Payment
 */
class PaymentsTable extends AbstractDatabaseTable
{
    public const TABLE = 'payments';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table} (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `customerBookingId` int(11) NULL,
                   `amount` DOUBLE NOT NULL default 0,
                   `dateTime` datetime NULL,
                   `status` ENUM('paid', 'pending', 'partiallyPaid', 'refunded') NOT NULL,
                   `gateway` ENUM('onSite', 'payPal', 'stripe', 'wc', 'mollie', 'razorpay', 'square') NOT NULL,
                   `gatewayTitle` varchar(255) NULL,
                   `data` text NULL,
                   `packageCustomerId` int(11) NULL,
                   `parentId` int(11) DEFAULT NULL,
                   `entity` ENUM('appointment', 'event', 'package') NULL,
                   `created` DATETIME NULL,
                   `actionsCompleted` TINYINT(1) NULL,
                   `triggeredActions` TINYINT(1) NULL,
                   `wcOrderId` bigint(20) NULL,
                   `wcOrderItemId` bigint(20) NULL,
                   `transactionId` varchar(255) NULL,
                   `transfers` text NULL,
                   `invoiceNumber` int(11) NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public static function alterTable()
    {
        $table = self::getTableName();

        return ["ALTER TABLE {$table} MODIFY customerBookingId INT(11) NULL"];
    }
}
