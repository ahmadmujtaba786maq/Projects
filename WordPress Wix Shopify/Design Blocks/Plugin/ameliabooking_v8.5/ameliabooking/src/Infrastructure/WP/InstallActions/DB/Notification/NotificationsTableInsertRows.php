<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\Translations\NotificationsStrings;

/**
 * Class NotificationsTableInsertRows
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification
 */
class NotificationsTableInsertRows extends AbstractDatabaseTable
{
    public const TABLE = 'notifications';

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        global $wpdb;

        $table = self::getTableName();
        $rows  = [];

        $addEmail = !(int)$wpdb->get_row("SELECT COUNT(*) AS count FROM {$table} WHERE type = 'email'")->count;

        if ($addEmail) {
            $rows = array_merge($rows, NotificationsStrings::getAppointmentCustomerNonTimeBasedEmailNotifications());
            $rows = array_merge($rows, NotificationsStrings::getAppointmentCustomerTimeBasedEmailNotifications());
            $rows = array_merge($rows, NotificationsStrings::getAppointmentProviderNonTimeBasedEmailNotifications());
            $rows = array_merge($rows, NotificationsStrings::getAppointmentProviderTimeBasedEmailNotifications());
        }

        $addSMS = !(int)$wpdb->get_row("SELECT COUNT(*) AS count FROM {$table} WHERE type = 'sms'")->count;

        if ($addSMS) {
            $rows = array_merge($rows, NotificationsStrings::getAppointmentCustomerNonTimeBasedSMSNotifications());
            $rows = array_merge($rows, NotificationsStrings::getAppointmentCustomerTimeBasedSMSNotifications());
            $rows = array_merge($rows, NotificationsStrings::getAppointmentProviderNonTimeBasedSMSNotifications());
            $rows = array_merge($rows, NotificationsStrings::getAppointmentProviderTimeBasedSMSNotifications());
        }

        $addEvent = !(int)$wpdb->get_row("SELECT COUNT(*) AS count FROM {$table} WHERE entity = 'event'")->count;

        if ($addEvent) {
            $rows = array_merge($rows, NotificationsStrings::getEventCustomerNonTimeBasedEmailNotifications());
            $rows = array_merge($rows, NotificationsStrings::getEventCustomerTimeBasedEmailNotifications());
            $rows = array_merge($rows, NotificationsStrings::getEventProviderNonTimeBasedEmailNotifications());
            $rows = array_merge($rows, NotificationsStrings::getEventProviderTimeBasedEmailNotifications());

            $rows = array_merge($rows, NotificationsStrings::getEventCustomerNonTimeBasedSMSNotifications());
            $rows = array_merge($rows, NotificationsStrings::getEventCustomerTimeBasedSMSNotifications());
            $rows = array_merge($rows, NotificationsStrings::getEventProviderNonTimeBasedSMSNotifications());
            $rows = array_merge($rows, NotificationsStrings::getEventProviderTimeBasedSMSNotifications());
        }

        $addAccountRecovery = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'customer_account_recovery'"
        )->count;

        if ($addAccountRecovery) {
            $rows = array_merge($rows, [NotificationsStrings::getAccountRecoveryNotification()]);
        }

        $addEmployeePanelAccess = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'provider_panel_access'"
        )->count;

        if ($addEmployeePanelAccess) {
            $rows = array_merge($rows, [NotificationsStrings::getEmployeePanelAccessNotification()]);
        }

        $addEmployeePanelRecovery = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'provider_panel_recovery'"
        )->count;

        if ($addEmployeePanelRecovery) {
            $rows = array_merge($rows, [NotificationsStrings::getEmployeeAccountRecoveryNotification()]);
        }

        $customerPackagePurchased = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'customer_package_purchased'"
        )->count;

        if ($customerPackagePurchased) {
            $rows = array_merge($rows, [NotificationsStrings::getCustomerPackagePurchasedEmailNotification()]);
            $rows = array_merge($rows, [NotificationsStrings::getCustomerPackagePurchasedSmsNotification()]);
        }

        $providerPackagePurchased = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'provider_package_purchased'"
        )->count;

        if ($providerPackagePurchased) {
            $rows = array_merge($rows, [NotificationsStrings::getProviderPackagePurchasedEmailNotification()]);
            $rows = array_merge($rows, [NotificationsStrings::getProviderPackagePurchasedSmsNotification()]);
        }

        $customerPackageCanceled = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'customer_package_canceled'"
        )->count;

        if ($customerPackageCanceled) {
            $rows = array_merge($rows, [NotificationsStrings::getCustomerPackageCanceledEmailNotification()]);
            $rows = array_merge($rows, [NotificationsStrings::getCustomerPackageCanceledSmsNotification()]);
        }

        $providerPackageCanceled = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'provider_package_canceled'"
        )->count;

        if ($providerPackageCanceled) {
            $rows = array_merge($rows, [NotificationsStrings::getProviderPackageCanceledEmailNotification()]);
            $rows = array_merge($rows, [NotificationsStrings::getProviderPackageCanceledSmsNotification()]);
        }

        $customerCart = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'customer_cart'"
        )->count;

        if ($customerCart) {
            $rows = array_merge($rows, [NotificationsStrings::getCustomerCartEmailNotification()]);
            $rows = array_merge($rows, [NotificationsStrings::getCustomerCartSmsNotification()]);
        }

        $providerCart = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'provider_cart'"
        )->count;

        if ($providerCart) {
            $rows = array_merge($rows, [NotificationsStrings::getProviderCartEmailNotification()]);
            $rows = array_merge($rows, [NotificationsStrings::getProviderCartSmsNotification()]);
        }

        $customerWaitingList = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'customer_event_waiting'"
        )->count;

        if ($customerWaitingList) {
            $rows = array_merge($rows, [NotificationsStrings::getCustomerWaitingListEmailNotification()]);
            $rows = array_merge($rows, [NotificationsStrings::getCustomerWaitingListSmsNotification()]);
        }

        $providerWaitingList = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'provider_event_waiting'"
        )->count;

        if ($providerWaitingList) {
            $rows = array_merge($rows, [NotificationsStrings::getProviderWaitingListEmailNotification()]);
            $rows = array_merge($rows, [NotificationsStrings::getProviderWaitingListSmsNotification()]);
        }

        $addWhatsApp = !(int)$wpdb->get_row("SELECT COUNT(*) AS count FROM {$table} WHERE type = 'whatsapp'")->count;

        if ($addWhatsApp) {
            $rows = array_merge($rows, NotificationsStrings::getWhatsAppNotifications());
        }

        $whatsAppCart = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'provider_cart' AND type = 'whatsapp'"
        )->count;

        if ($whatsAppCart) {
            $rows = array_merge($rows, NotificationsStrings::getWhatsAppCartNotifications());
        }

        $whatsAppWaitingList = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'provider_event_waiting' AND type = 'whatsapp'"
        )->count;

        if ($whatsAppWaitingList) {
            $rows = array_merge($rows, NotificationsStrings::getWhatsAppWaitingListNotifications());
        }

        $appointmentUpdated = !(int)$wpdb->get_row("SELECT COUNT(*) AS count FROM {$table} WHERE name LIKE '%appointment_updated'")->count;
        if ($appointmentUpdated) {
            $rows = array_merge($rows, NotificationsStrings::getAppointmentUpdatedNotifications($addEmail));
        }

        $eventUpdated = !(int)$wpdb->get_row("SELECT COUNT(*) AS count FROM {$table} WHERE name LIKE '%event_updated'")->count;
        if ($eventUpdated) {
            $rows = array_merge($rows, NotificationsStrings::getEventUpdatedNotifications($addEmail));
        }

        $invoiceNotifications = !(int)$wpdb->get_row(
            "SELECT COUNT(*) AS count FROM {$table} WHERE name = 'customer_invoice'"
        )->count;

        if ($invoiceNotifications) {
            $rows = array_merge($rows, NotificationsStrings::getInvoiceNotification());
        }

        $result = [];

        foreach ($rows as $row) {
            $status   = !empty($row['status']) ? $row['status'] : 'enabled';
            $result[] = "INSERT INTO {$table} 
                        (
                            `name`,
                            `type`,
                            `time`,
                            `timeBefore`,
                            `timeAfter`,
                            `sendTo`,
                            `subject`,
                            `content`,
                            `entity`,
                            `status`
                        ) 
                        VALUES
                        (
                            '{$row['name']}',
                            '{$row['type']}',
                             {$row['time']},
                             {$row['timeBefore']},
                             {$row['timeAfter']},
                            '{$row['sendTo']}',
                            '{$row['subject']}',
                            '{$row['content']}',
                            '{$row['entity']}',
                            '{$status}'
                        )";
        }

        return $result;
    }
}
