<?php

/**
 * @author Alexander Gilmanov
 * Defining the user roles and capabilities
 */

namespace AmeliaBooking\Infrastructure\WP\config;

/**
 * Class Roles
 *
 * @package AmeliaBooking\Infrastructure\WP\config
 */
class Roles
{
    /**
     * Array of all Amelia roles capabilities
     *
     * @var array
     */
    public static $rolesList = [
        'amelia_read_menu',
        'amelia_read_dashboard',
        'amelia_read_whats_new',
        'amelia_read_lite_vs_premium',
        'amelia_read_calendar',
        'amelia_read_appointments',
        'amelia_read_events',
        'amelia_read_employees',
        'amelia_read_services',
        'amelia_read_packages',
        'amelia_read_locations',
        'amelia_read_taxes',
        'amelia_read_coupons',
        'amelia_read_customers',
        'amelia_read_finance',
        'amelia_read_notifications',
        'amelia_read_customize',
        'amelia_read_custom_fields',
        'amelia_read_settings',

        'amelia_read_others_settings',
        'amelia_read_others_dashboard',
        'amelia_read_others_calendar',
        'amelia_read_others_appointments',
        'amelia_read_others_services',
        'amelia_read_others_employees',
        'amelia_read_others_customers',

        'amelia_write_dashboard',
        'amelia_write_calendar',
        'amelia_write_appointments',
        'amelia_write_events',
        'amelia_write_employees',
        'amelia_write_services',
        'amelia_write_packages',
        'amelia_write_locations',
        'amelia_write_taxes',
        'amelia_write_coupons',
        'amelia_write_customers',
        'amelia_write_finance',
        'amelia_write_notifications',
        'amelia_write_customize',
        'amelia_write_custom_fields',
        'amelia_write_settings',
        'amelia_write_status',

        'amelia_write_others_settings',
        'amelia_write_others_calendar',
        'amelia_write_others_appointments',
        'amelia_write_others_services',
        'amelia_write_others_employees',
        'amelia_write_others_events',
        'amelia_write_others_finance',
        'amelia_write_others_dashboard',

        'amelia_delete_dashboard',
        'amelia_delete_calendar',
        'amelia_delete_appointments',
        'amelia_delete_events',
        'amelia_delete_employees',
        'amelia_delete_services',
        'amelia_delete_packages',
        'amelia_delete_locations',
        'amelia_delete_taxes',
        'amelia_delete_coupons',
        'amelia_delete_customers',
        'amelia_delete_finance',
        'amelia_delete_notifications',
        'amelia_delete_customize',
        'amelia_delete_custom_fields',
        'amelia_delete_settings',

        'amelia_write_status_appointments',
        'amelia_write_status_events',
        'amelia_write_time_appointments',
    ];

    /**
     * Array of all amelia roles with capabilities
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            // Customer
            [
                'name'         => 'wpamelia-customer',
                'label'        => __('Amelia Customer', 'amelia'),
                'capabilities' => [
                    'read'                             => true,
                    'amelia_read_menu'                 => true,
                    'amelia_read_calendar'             => true,
                    'amelia_read_appointments'         => true,
                    'amelia_read_events'               => true,
                    'amelia_write_time_appointments'   => true,
                ]
            ],

            // Provider
            [
                'name'         => 'wpamelia-provider',
                'label'        => __('Amelia Employee', 'amelia'),
                'capabilities' => [
                    'read'                             => true,
                    'amelia_delete_events'             => true,
                    'amelia_read_menu'                 => true,
                    'amelia_read_calendar'             => true,
                    'amelia_read_appointments'         => true,
                    'amelia_read_events'               => true,
                    'amelia_read_employees'            => true,
                    'amelia_read_others_customers'     => true,
                    'amelia_read_others_services'      => false,
                    'amelia_write_employees'           => true,
                    'amelia_write_status_appointments' => true,
                    'amelia_write_status_events'       => true,
                    'amelia_write_time_appointments'   => true,
                    'amelia_write_others_appointments' => false,
                    'amelia_write_others_services'     => false,
                    'amelia_write_appointments'        => true,
                    'amelia_write_events'              => true,
                    'amelia_write_others_events'       => false,
                ]
            ],

            // Manager
            [
                'name'         => 'wpamelia-manager',
                'label'        => __('Amelia Manager', 'amelia'),
                'capabilities' => [
                    'read' => true,

                    'amelia_delete_events'             => true,
                    'amelia_read_menu'                 => true,
                    'amelia_read_dashboard'            => true,
                    'amelia_read_whats_new'            => true,
                    'amelia_read_lite_vs_premium'      => true,
                    'amelia_read_calendar'             => true,
                    'amelia_read_appointments'         => true,
                    'amelia_read_events'               => true,
                    'amelia_read_employees'            => true,
                    'amelia_read_services'             => true,
                    'amelia_read_resources'            => true,
                    'amelia_read_packages'             => true,
                    'amelia_read_locations'            => true,
                    'amelia_read_taxes'                => true,
                    'amelia_read_coupons'              => true,
                    'amelia_read_customers'            => true,
                    'amelia_read_finance'              => true,
                    'amelia_read_notifications'        => true,
                    'amelia_read_others_dashboard'     => true,
                    'amelia_read_others_calendar'      => true,
                    'amelia_read_others_appointments'  => true,
                    'amelia_read_others_services'      => true,
                    'amelia_read_others_employees'     => true,
                    'amelia_read_others_customers'     => true,
                    'amelia_write_dashboard'           => true,
                    'amelia_write_calendar'            => true,
                    'amelia_write_appointments'        => true,
                    'amelia_write_events'              => true,
                    'amelia_write_employees'           => true,
                    'amelia_write_services'            => true,
                    'amelia_write_resources'           => true,
                    'amelia_write_packages'            => true,
                    'amelia_write_locations'           => true,
                    'amelia_write_taxes'               => true,
                    'amelia_write_coupons'             => true,
                    'amelia_write_customers'           => true,
                    'amelia_write_finance'             => true,
                    'amelia_write_notifications'       => true,
                    'amelia_write_others_calendar'     => true,
                    'amelia_write_others_appointments' => true,
                    'amelia_write_others_services'     => true,
                    'amelia_write_others_employees'    => true,
                    'amelia_write_others_events'       => true,
                    'amelia_write_others_finance'      => true,
                    'amelia_write_others_dashboard'    => true,
                    'amelia_write_status_appointments' => true,
                    'amelia_write_status_events'       => true,
                    'amelia_write_time_appointments'   => true,
                    'upload_files'                     => true,
                ]
            ],
        ];
    }
}
