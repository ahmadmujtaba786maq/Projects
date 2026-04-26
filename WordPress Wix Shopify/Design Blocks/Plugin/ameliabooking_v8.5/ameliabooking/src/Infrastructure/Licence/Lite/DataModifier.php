<?php

namespace AmeliaBooking\Infrastructure\Licence\Lite;

use AmeliaBooking\Infrastructure\WP\InstallActions\ActivationSettingsHook;

/**
 * Class DataModifier
 *
 * @package AmeliaBooking\Infrastructure\Licence\Lite
 */
class DataModifier
{
    /**
     * @param array $settings
     * @param array $savedSettings
     */
    public static function restoreSettings(&$settings, $savedSettings)
    {
        self::commonRestoreSettings($settings, $savedSettings);

        $settings['payments']['coupons'] = $savedSettings['payments']['coupons'];

        $settings['roles']['customerCabinet']['enabled'] = $savedSettings['roles']['customerCabinet']['enabled'];

        $settings['roles']['providerCabinet']['enabled'] = $savedSettings['roles']['providerCabinet']['enabled'];

        $settings['general']['serviceDurationAsSlot'] = $savedSettings['general']['serviceDurationAsSlot'];

        $settings['general']['bufferTimeInSlot'] = $savedSettings['general']['bufferTimeInSlot'];

        $settings['general']['minimumTimeRequirementPriorToBooking'] = $savedSettings['general']['minimumTimeRequirementPriorToBooking'];

        $settings['general']['minimumTimeRequirementPriorToCanceling'] = $savedSettings['general']['minimumTimeRequirementPriorToCanceling'];

        $settings['general']['minimumTimeRequirementPriorToRescheduling'] = $savedSettings['general']['minimumTimeRequirementPriorToRescheduling'];
    }

    /**
     * @param array $settings
     * @param array $savedSettings
     */
    public static function commonRestoreSettings(&$settings, $savedSettings)
    {
        $settings['payments']['cart'] = $savedSettings['payments']['cart'];

        $settings['payments']['stripe']['enabled'] = $savedSettings['payments']['stripe']['enabled'];

        $settings['payments']['payPal']['enabled'] = $savedSettings['payments']['payPal']['enabled'];

        $settings['payments']['razorpay']['enabled'] = $savedSettings['payments']['razorpay']['enabled'];

        $settings['payments']['mollie']['enabled'] = $savedSettings['payments']['mollie']['enabled'];

        $settings['payments']['wc']['enabled'] = $savedSettings['payments']['wc']['enabled'];

        $settings['payments']['paymentLinks']['enabled'] = $savedSettings['payments']['paymentLinks']['enabled'];

        $settings['roles']['limitPerCustomerService']['enabled'] = $savedSettings['roles']['limitPerCustomerService']['enabled'];

        $settings['roles']['limitPerCustomerPackage']['enabled'] = $savedSettings['roles']['limitPerCustomerPackage']['enabled'];

        $settings['roles']['limitPerCustomerEvent']['enabled'] = $savedSettings['roles']['limitPerCustomerEvent']['enabled'];

        $settings['roles']['limitPerEmployee']['enabled'] = $savedSettings['roles']['limitPerEmployee']['enabled'];

        $settings['roles']['allowCustomerCancelPackages'] = $savedSettings['roles']['allowCustomerCancelPackages'];

        $settings['roles']['enableNoShowTag'] = $savedSettings['roles']['enableNoShowTag'];

        $settings['notifications']['whatsAppEnabled'] = $savedSettings['notifications']['whatsAppEnabled'];

        $settings['general']['usedLanguages'] = $savedSettings['general']['usedLanguages'];

        $settings['appointments']['employeeSelection'] = $savedSettings['appointments']['employeeSelection'];
    }

    /**
     * @param array $settings
     */
    public static function modifySettings(&$settings)
    {
        $generalSettings = ActivationSettingsHook::getDefaultGeneralSettings($settings);


        self::commonModifySettings($settings);

        if ($settings && isset($settings['payments'])) {
            $settings['payments']['coupons'] = false;
        }

        if ($settings && isset($settings['activation'])) {
            $settings['activation']['hideUnavailableFeatures'] = false;
        }

        if ($settings && isset($settings['roles'])) {
            $settings['roles']['customerCabinet']['enabled'] = false;

            $settings['roles']['providerCabinet']['enabled'] = false;
        }

        if ($settings && isset($settings['general'])) {
            $settings['general']['serviceDurationAsSlot'] = $generalSettings['serviceDurationAsSlot'];

            $settings['general']['bufferTimeInSlot'] = $generalSettings['bufferTimeInSlot'];

            $settings['general']['minimumTimeRequirementPriorToBooking'] = $generalSettings['minimumTimeRequirementPriorToBooking'];

            $settings['general']['minimumTimeRequirementPriorToCanceling'] = $generalSettings['minimumTimeRequirementPriorToCanceling'];

            $settings['general']['minimumTimeRequirementPriorToRescheduling'] = $generalSettings['minimumTimeRequirementPriorToRescheduling'];
        }
    }

    /**
     * @param array $settings
     */
    public static function commonModifySettings(&$settings)
    {
        $rolesSettings = ActivationSettingsHook::getDefaultRolesSettings();

        $generalSettings = ActivationSettingsHook::getDefaultGeneralSettings($settings);

        $notificationSettings = ActivationSettingsHook::getDefaultNotificationsSettings($settings);

        $appointmentsSettings = ActivationSettingsHook::getDefaultAppointmentsSettings();

        $paymentSettings = ActivationSettingsHook::getDefaultPaymentsSettings($settings);


        if ($settings && isset($settings['payments'])) {
            $settings['payments']['cart'] = $paymentSettings['cart'];

            $settings['payments']['stripe']['enabled'] = $paymentSettings['stripe']['enabled'];

            $settings['payments']['payPal']['enabled'] = $paymentSettings['payPal']['enabled'];

            $settings['payments']['razorpay']['enabled'] = $paymentSettings['razorpay']['enabled'];

            $settings['payments']['mollie']['enabled'] = $paymentSettings['mollie']['enabled'];

            $settings['payments']['wc']['enabled'] = $paymentSettings['wc']['enabled'];

            $settings['payments']['paymentLinks']['enabled'] = $paymentSettings['paymentLinks']['enabled'];

            $settings['payments']['taxes']['enabled'] = $paymentSettings['taxes']['enabled'];
        }

        if ($settings && isset($settings['roles'])) {
            $settings['roles']['limitPerCustomerService']['enabled'] = $rolesSettings['limitPerCustomerService']['enabled'];

            $settings['roles']['limitPerCustomerPackage']['enabled'] = $rolesSettings['limitPerCustomerPackage']['enabled'];

            $settings['roles']['limitPerCustomerEvent']['enabled'] = $rolesSettings['limitPerCustomerEvent']['enabled'];

            $settings['roles']['limitPerEmployee']['enabled'] = $rolesSettings['limitPerEmployee']['enabled'];

            $settings['roles']['allowCustomerCancelPackages'] = false;

            $settings['roles']['enableNoShowTag'] = false;
        }

        if ($settings && isset($settings['general'])) {
            $settings['general']['usedLanguages'] = $generalSettings['usedLanguages'];
        }

        if ($settings && isset($settings['notifications'])) {
            $settings['notifications']['whatsAppEnabled'] = $notificationSettings['whatsAppEnabled'];
        }

        if ($settings && isset($settings['appointments'])) {
            $settings['appointments']['employeeSelection'] = $appointmentsSettings['employeeSelection'];
        }
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getUserRepositoryData($data)
    {
        return [
            'values'              =>
                [
                ],
            'columns'             =>
                '',
            'placeholders'        =>
                '',
            'columnsPlaceholders' =>
                '',
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function userFactory(&$data)
    {
        $data['locationId'] = null;

        $data['googleCalendar'] = null;

        $data['outlookCalendar'] = null;

        $data['badgeId'] = null;

        $data['zoomUserId'] = null;

        $data['appleCalendarId'] = null;

        $data['translations'] = null;

        $data['timeZone'] = null;

        $data['employeeAppleCalendar'] = null;

        if (!empty($data['serviceList'])) {
            foreach ($data['serviceList'] as $key => $value) {
                $data['serviceList'][$key]['customPricing'] = null;
            }
        }
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getProviderServiceRepositoryData($data)
    {
        return [
            'values'              =>
                [
                ],
            'columns'             =>
                '',
            'placeholders'        =>
                '',
            'columnsPlaceholders' =>
                '',
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function providerServiceFactory(&$data)
    {
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getPeriodRepositoryData($data)
    {
        return [
            'values'              =>
                [
                ],
            'columns'             =>
                '',
            'placeholders'        =>
                '',
            'columnsPlaceholders' =>
                '',
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function periodFactory(&$data)
    {
        $data['locationId'] = null;

        $data['periodLocationList'] = [];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getServiceRepositoryData($data)
    {
        return [
            'values'              =>
                [
                ],
            'columns'             =>
                '',
            'placeholders'        =>
                '',
            'columnsPlaceholders' =>
                '',
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function serviceFactory(&$data)
    {
        self::commonServiceFactory($data);

        $data['extras'] = [];

        $data['show'] = 1;

        $data['timeAfter'] = 0;

        $data['timeBefore'] = 0;

        $data['minCapacity'] = 1;

        $data['maxCapacity'] = 1;
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function commonServiceFactory(&$data)
    {
        $data['recurringCycle'] = 'disabled';

        $data['recurringSub'] = 'future';

        $data['recurringPayment'] = 0;

        $data['customPricing'] = null;

        $data['limitPerCustomer'] = null;

        $data['deposit'] = 0;

        $data['depositPayment'] = 'disabled';

        $data['depositPerPerson'] = 1;

        $data['fullPayment'] = 0;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getEventRepositoryData($data)
    {
        return [
            'values'              =>
                [
                ],
            'addValues'           =>
                [
                ],
            'columns'             =>
                '',
            'placeholders'        =>
                '',
            'columnsPlaceholders' =>
                '',
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function eventFactory(&$data)
    {
        self::commonEventFactory($data);

        $data['tags'] = [];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function commonEventFactory(&$data)
    {
        $data['ticketRangeRec'] = 'calculate';

        $data['deposit'] = 0;

        $data['depositPayment'] = 'disabled';

        $data['fullPayment'] = 0;

        $data['customPricing'] = 0;

        $data['depositPerPerson'] = 1;

        $data['locationId'] = null;
    }
}
