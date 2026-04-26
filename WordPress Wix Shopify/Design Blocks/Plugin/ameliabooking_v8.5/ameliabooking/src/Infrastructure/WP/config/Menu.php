<?php

namespace AmeliaBooking\Infrastructure\WP\config;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Licence\Licence;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class Menu
 */
class Menu
{
    /** @var SettingsService $settingsService */
    private $settingsService;

    /**
     * Menu constructor.
     *
     * @param SettingsService $settingsService
     */
    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * @return array
     */
    public function __invoke()
    {
        $defaultPageOnBackend = $this->settingsService->getSetting(
            'general',
            'defaultPageOnBackend'
        );

        $defaultPages = [
            [
                'parentSlug' => 'amelia',
                'pageTitle'  => 'Dashboard',
                'menuTitle'  => BackendStrings::getDashboardStrings()['dashboard'],
                'capability' => 'amelia_read_dashboard',
                'menuSlug'   => 'wpamelia-dashboard',
            ],
            [
                'parentSlug' => 'amelia',
                'pageTitle'  => 'Calendar',
                'menuTitle'  => BackendStrings::getCalendarStrings()['calendar'],
                'capability' => 'amelia_read_calendar',
                'menuSlug'   => 'wpamelia-calendar',
            ],
            [
                'parentSlug' => 'amelia',
                'pageTitle'  => 'Appointments',
                'menuTitle'  => BackendStrings::getCommonStrings()['appointments'],
                'capability' => 'amelia_read_appointments',
                'menuSlug'   => 'wpamelia-appointments',
            ],
            [
                'parentSlug' => 'amelia',
                'pageTitle'  => 'Events',
                'menuTitle'  => BackendStrings::getCommonStrings()['events'],
                'capability' => 'amelia_read_events',
                'menuSlug'   => 'wpamelia-events',
            ]
        ];

        $defaultPageKey = array_search($defaultPageOnBackend, array_column($defaultPages, 'pageTitle'), true);

        $defaultPageElement = array_splice($defaultPages, $defaultPageKey, 1);

        $menuItems = [
                [
                    'parentSlug' => 'amelia',
                    'pageTitle'  => 'Services',
                    'menuTitle'  => BackendStrings::getCommonStrings()['services'],
                    'capability' => 'amelia_read_services',
                    'menuSlug'   => 'wpamelia-services',
                ],
                [
                    'parentSlug' => 'amelia',
                    'pageTitle'  => 'Locations',
                    'menuTitle'  => BackendStrings::getCommonStrings()['locations'],
                    'capability' => 'amelia_read_locations',
                    'menuSlug'   => 'wpamelia-locations',
                ],
                [
                    'parentSlug' => 'amelia',
                    'pageTitle'  => 'Customers',
                    'menuTitle'  => BackendStrings::getCustomerStrings()['customers'],
                    'capability' => 'amelia_read_customers',
                    'menuSlug'   => 'wpamelia-customers',
                ],
                [
                    'parentSlug' => 'amelia',
                    'pageTitle'  => 'Finance',
                    'menuTitle'  => BackendStrings::getPaymentStrings()['finance'],
                    'capability' => 'amelia_read_finance',
                    'menuSlug'   => 'wpamelia-finance',
                ],
                [
                    'parentSlug' => 'amelia',
                    'pageTitle'  => 'Notifications',
                    'menuTitle'  => BackendStrings::getNotificationsStrings()['notifications'],
                    'capability' => 'amelia_read_notifications',
                    'menuSlug'   => 'wpamelia-notifications',
                ],
                [
                    'parentSlug' => 'amelia',
                    'pageTitle'  => 'Customize',
                    'menuTitle'  => BackendStrings::getCustomizeStrings()['customize'],
                    'capability' => 'amelia_read_customize',
                    'menuSlug'   => 'wpamelia-customize',
                ],
                [
                    'parentSlug' => 'amelia',
                    'pageTitle'  => 'Customize New',
                    'menuTitle'  => BackendStrings::getCustomizeStrings()['customize'] . ' New',
                    'capability' => 'amelia_read_customize',
                    'menuSlug'   => 'wpamelia-customize-new',
                ],
                [
                    'parentSlug' => 'amelia',
                    'pageTitle'  => 'Custom Fields',
                    'menuTitle'  => BackendStrings::getCustomizeStrings()['custom_fields'],
                    'capability' => 'amelia_read_customize',
                    'menuSlug'   => 'wpamelia-cf',
                ],
                [
                    'parentSlug' => 'amelia',
                    'pageTitle'  => 'Settings',
                    'menuTitle'  => BackendStrings::getSettingsStrings()['settings'],
                    'capability' => 'amelia_read_settings',
                    'menuSlug'   => 'wpamelia-settings',
                ],
                [
                    'parentSlug' => 'amelia',
                    'pageTitle'  => 'What\'s new',
                    'menuTitle'  => BackendStrings::getCommonStrings()['whats_new'],
                    'capability' => 'amelia_read_whats_new',
                    'menuSlug'   => 'wpamelia-whats-new',
                ],
            ];

        if ($liteMenuItem = Licence::getLiteMenuItem()) {
            $menuItems[] = $liteMenuItem;
        }

        if ($employeesMenuItem = Licence::getEmployeesMenuItem()) {
            array_unshift($menuItems, $employeesMenuItem);
        }

        return array_merge(
            $defaultPageElement,
            $defaultPages,
            $menuItems
        );
    }
}
