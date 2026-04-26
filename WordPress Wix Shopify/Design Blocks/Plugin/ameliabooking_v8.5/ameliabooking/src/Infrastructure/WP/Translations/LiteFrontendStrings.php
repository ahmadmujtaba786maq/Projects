<?php

namespace AmeliaBooking\Infrastructure\WP\Translations;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;

/**
 * Class LiteFrontendStrings
 *
 * @package AmeliaBooking\Infrastructure\WP\Translations
 *
 * @phpcs:disable
 */
class LiteFrontendStrings
{
    /** @var SettingsService $settings */
    private static $settings;

    /**
     * Set Settings
     *
     * @return array|mixed
     */
    public static function getLabelsFromSettings()
    {
        if (!self::$settings) {
            self::$settings = new SettingsService(new SettingsStorage());
        }

        if (self::$settings->getSetting('labels', 'enabled') === true) {
            $labels = self::$settings->getCategorySettings('labels');
            unset($labels['enabled']);

            return $labels;
        }

        return [];
    }

    /**
     * Return all strings for frontend
     *
     * @return array
     */
    public static function getAllStrings()
    {
        return array_merge(
            self::getCommonStrings(),
            self::getBookingStrings(),
            self::getBookableStrings(),
            self::getCatalogStrings(),
            self::getSearchStrings(),
            self::getLabelsFromSettings(),
            self::getEventStrings(),
            self::getCabinetStrings()
        );
    }

    /**
     * Returns the array for the bookable strings
     *
     * @return array
     */
    public static function getBookableStrings()
    {
        return [
        ];
    }

    /**
     * Returns the array of the common frontend strings
     *
     * @return array
     */
    public static function getCommonStrings()
    {
        return [
            'add_to_calendar'              => __('Add to Calendar', 'wpamelia'),
            'amount'                       => __('Amount', 'wpamelia'),
            'all_services'                 => __('All Services', 'wpamelia'),
            'all_locations'                => __('All Locations', 'wpamelia'),
            'no_services_employees'        => __('It seems like there are no employees or services created, or no  employees are assigned to the service, at this moment.'),
            'add_services_employees'       => __('If you are the admin of this page, see how to'),
            'add_services_url'             => __('Add services'),
            'add_employees_url'            => __('employees.'),
            'back'                         => __('Back', 'wpamelia'),
            'base_price_colon'             => __('Base Price:', 'wpamelia'),
            'booking_completed_approved'   => __('Thank you! Your booking is completed.', 'wpamelia'),
            'bookings_limit_reached'       => __('Maximum bookings reached', 'wpamelia'),
            'cancel'                       => __('Cancel', 'wpamelia'),
            'canceled'                     => __('Canceled', 'wpamelia'),
            'capacity_colon'               => __('Capacity:', 'wpamelia'),
            'closed'                       => __('Closed', 'wpamelia'),
            'content_mode_tooltip'         => __('Don\'t use Text mode option if you already have HTML code in the description, since once this option is enabled the existing HTML tags could be lost.', 'wpamelia'),
            'enable_google_meet'           => __('Enable Google Meet', 'wpamelia'),
            'enable_microsoft_teams'       => __('Enable Microsoft Teams', 'wpamelia'),
            'full'                         => __('Full', 'wpamelia'),
            'upcoming'                     => __('Upcoming', 'wpamelia'),
            'confirm'                      => __('Confirm', 'wpamelia'),
            'congratulations'              => __('Congratulations', 'wpamelia'),
            'customer_already_booked_app'  => __('You have already booked this appointment', 'wpamelia'),
            'customer_already_booked_ev'   => __('You have already booked this event', 'wpamelia'),
            'date_colon'                   => __('Date:', 'wpamelia'),
            'duration_colon'               => __('Duration:', 'wpamelia'),
            'email_colon'                  => __('Email:', 'wpamelia'),
            'email_exist_error'            => __('Email already exists with different name. Please check your name.', 'wpamelia'),
            'email_required'               => __('Email field is required', 'wpamelia'),
            'employee_limit_reached'       => __('Employee daily appointment limit has been reached. Please choose another date or employee.', 'wpamelia'),
            'enter_email_warning'          => __('Please enter email', 'wpamelia'),
            'enter_first_name_warning'     => __('Please enter first name', 'wpamelia'),
            'enter_last_name_warning'      => __('Please enter last name', 'wpamelia'),
            'enter_phone_warning'          => __('Please enter phone number', 'wpamelia'),
            'enter_valid_email_warning'    => __('Please enter a valid email address', 'wpamelia'),
            'enter_valid_phone_warning'    => __('Please enter a valid phone number', 'wpamelia'),
            'event_info'                   => __('Event Info', 'wpamelia'),
            'finish_appointment'           => __('Finish', 'wpamelia'),
            'first_name_colon'             => __('First Name:', 'wpamelia'),
            'h'                            => __('h', 'wpamelia'),
            'last_name_colon'              => __('Last Name:', 'wpamelia'),
            'licence_start_description'    => __('Available from Starter license', 'wpamelia'),
            'licence_basic_description'    => __('Available from Standard license', 'wpamelia'),
            'licence_pro_description'      => __('Available from Pro license', 'wpamelia'),
            'licence_dev_description'      => __('Available in Elite licence', 'wpamelia'),
            'licence_button_text'          => __('Upgrade', 'wpamelia'),
            'min'                          => __('min', 'wpamelia'),
            'on_site'                      => __('On-site', 'wpamelia'),
            'payment_btn_on_site'          => __('On-Site', 'wpamelia'),
            'oops'                         => __('Oops...'),
            'payment_btn_square'           => __('Square', 'wpamelia'),
            'open'                         => __('Open', 'wpamelia'),
            'phone_colon'                  => __('Phone:', 'wpamelia'),
            'phone_exist_error'            => __('Phone already exists with different name. Please check your name.', 'wpamelia'),
            'price_colon'                  => __('Price:', 'wpamelia'),
            'service'                      => __('service', 'wpamelia'),
            'select_calendar'              => __('Select Calendar', 'wpamelia'),
            'services_lower'               => __('services', 'wpamelia'),
            'square'                       => __('Square', 'wpamelia'),
            'time_colon'                   => __('Local Time:', 'wpamelia'),
            'time_slot_unavailable'        => __('Time slot is unavailable', 'wpamelia'),
            'total_cost_colon'             => __('Total Cost:', 'wpamelia'),
            'total_number_of_persons'      => __('Total Number of People:', 'wpamelia'),
            'view'                         => __('View', 'wpamelia'),
            'select'                       => __('Select', 'wpamelia'),
        ];
    }

    /**
     * Returns the array of the frontend strings for the search shortcode
     *
     * @return array
     */
    public static function getSearchStrings()
    {
        return [
        ];
    }

    /**
     * Returns the array of the frontend strings for the booking shortcode
     *
     * @return array
     */
    public static function getBookingStrings()
    {
        return [
            'continue'                     => __('Continue', 'wpamelia'),
            'email_address_colon'          => __('Email Address', 'wpamelia'),
            'get_in_touch'                 => __('Get in Touch', 'wpamelia'),
            'collapse_menu'                => __('Collapse menu', 'wpamelia'),
            'payment_onsite_sentence'      => __('The payment will be done on-site.', 'wpamelia'),
            'phone_number_colon'           => __('Phone Number', 'wpamelia'),
            'pick_date_and_time_colon'     => __('Pick date & time:', 'wpamelia'),
            'please_select'                => __('Please select', 'wpamelia'),
            'summary'                      => __('Summary', 'wpamelia'),
            'total_amount_colon'           => __('Total Amount:', 'wpamelia'),
            'your_name_colon'              => __('Your Name', 'wpamelia'),

            'service_selection'            => __('Service Selection', 'wpamelia'),
            'employee_selection'           => __('Employee Selection', 'wpamelia'),
            'location_selection'           => __('Location Selection', 'wpamelia'),
            'service_colon'                => __('Service', 'wpamelia'),
            'please_select_service'        => __('Please select service', 'wpamelia'),
            'dropdown_category_heading'    => __('Category', 'wpamelia'),
            'dropdown_items_heading'       => __('Service', 'wpamelia'),
            'date_time'                    => __('Date & Time', 'wpamelia'),
            'info_step'                    => __('Your Information', 'wpamelia'),
            'enter_first_name'             => __('Enter first name', 'wpamelia'),
            'enter_last_name'              => __('Enter last name', 'wpamelia'),
            'enter_email'                  => __('Enter email', 'wpamelia'),
            'enter_phone'                  => __('Enter phone', 'wpamelia'),
            'payment_step'                 => __('Payments', 'wpamelia'),
            'summary_services'             => __('Services', 'wpamelia'),
            'summary_person'               => __('person', 'wpamelia'),
            'summary_persons'              => __('people', 'wpamelia'),
            'summary_event'                => __('Event', 'wpamelia'),
            'appointment_id'               => __('Appointment ID', 'wpamelia'),
            'event_id'                     => __('Event ID', 'wpamelia'),
            'congrats_payment'             => __('Payment', 'wpamelia'),
            'congrats_date'                => __('Date', 'wpamelia'),
            'congrats_time'                => __('Local Time', 'wpamelia'),
            'congrats_service'             => __('Service', 'wpamelia'),
            'congrats_employee'            => __('Employee', 'wpamelia'),
            'show_more'                    => __('Show more', 'wpamelia'),
            'show_less'                    => __('Show less', 'wpamelia'),
            'learn_more'                   => __('Learn More', 'wpamelia'),
            'view_in_package'              => __('View in Package', 'wpamelia'),
            'service_information'          => __('Service information', 'wpamelia'),
        ];
    }

    /**
     * Returns the array of the frontend strings for the event shortcode
     *
     * @return array
     */
    public static function getEventStrings()
    {
        return [
            'event_book_event'          => __('Book event', 'wpamelia'),
            'event_book'                => __('Book this event', 'wpamelia'),
            'event_capacity'            => __('Capacity:', 'wpamelia'),
            'event_filters'             => __('Filters', 'wpamelia'),
            'event_start'               => __('Event Starts', 'wpamelia'),
            'event_end'                 => __('Event Ends', 'wpamelia'),
            'event_at'                  => __('at', 'wpamelia'),
            'event_close'               => __('Close', 'wpamelia'),
            'event_congrats'            => __('Congratulations', 'wpamelia'),
            'event_payment'             => __('Payment', 'wpamelia'),
            'event_customer_info'       => __('Your Information', 'wpamelia'),
            'event_about_list'          => __('About Event', 'wpamelia'),
            'events_available'          => __('Events Available', 'wpamelia'),
            'event_available'           => __('Event Available', 'wpamelia'),
            'event_search'              => __('Search for Events', 'wpamelia'),
            'event_slot_left'           => __('slot left', 'wpamelia'),
            'event_slots_left'          => __('slots left', 'wpamelia'),
            'event_learn_more'          => __('Learn more', 'wpamelia'),
            'event_read_more'           => __('Read more', 'wpamelia'),
            'event_timetable'           => __('Timetable', 'wpamelia'),
            'event_bringing'            => __('How many attendees do you want to book event for?', 'wpamelia'),
            'event_show_less'           => __('Show less', 'wpamelia'),
            'event_show_more'           => __('Show more', 'wpamelia'),
            'event_location'            => __('Event Location', 'wpamelia'),
        ];
    }

    /**
     * Returns the array of the frontend strings for the catalog shortcode
     *
     * @return array
     */
    public static function getCatalogStrings()
    {
        return [
            'categories'                         => __('Categories', 'wpamelia'),
            'category_colon'                     => __('Category:', 'wpamelia'),
            'description'                        => __('Description', 'wpamelia'),
            'info'                               => __('Info', 'wpamelia'),
            'view_more'                          => __('View More', 'wpamelia'),
            'view_all'                           => __('View All', 'wpamelia'),
            'filter_input'                       => __('Search', 'wpamelia'),
            'book_now'                           => __('Book Now', 'wpamelia'),
            'about_service'                      => __('About Service', 'wpamelia'),
            'view_all_photos'                    => __('View all photos', 'wpamelia'),
            'back_btn'                           => __('Go Back', 'wpamelia'),
            'heading_service'                    => __('Service', 'wpamelia'),
            'heading_services'                   => __('Services', 'wpamelia'),
        ];
    }

    /**
     * Returns the array of the frontend strings for the event shortcode
     *
     * @return array
     */
    public static function getCabinetStrings()
    {
        return [
            'available'                              => __('Available', 'wpamelia'),
            'booking_cancel_exception'               => __('Booking can\'t be canceled', 'wpamelia'),
            'generate_payment_links'                 => __('Generate payment links', 'wpamelia'),
            'generate_payment_links_tooltip'         => __('Check this box to generate a payment link.<br> To include it in the notification, add the payment link placeholder.', 'wpamelia'),
            'no_results'                             => __('There are no results...', 'wpamelia'),
            'select_customer'                        => __('Select Customer', 'wpamelia'),
            'select_service'                         => __('Select Service', 'wpamelia'),
            'subtotal'                               => __('Subtotal', 'wpamelia'),
        ];
    }
}
