<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use Interop\Container\Exception\ContainerException;

if (! function_exists('Amelia_admin_enqueue_script')) {
    function Amelia_admin_enqueue_script()
    {
        wp_enqueue_style('buddyboss-addon-admin-css', plugin_dir_url(__FILE__) . 'style.css');
    }

    add_action('admin_enqueue_scripts', 'Amelia_admin_enqueue_script');
}

if (! function_exists('Amelia_get_settings_sections')) {
    function Amelia_get_settings_sections()
    {

        $settings = array(
            'Amelia_settings_section' => array(
                'page'  => 'amelia',
                'title' => BackendStrings::getBuddyBossStrings()['amelia_settings_profile'],
            ),
            'Amelia_settings_section_customers' => array(
                'page'  => 'amelia',
                'title' => BackendStrings::getBuddyBossStrings()['amelia_settings_customers'],
            ),
        );

        return (array) apply_filters('Amelia_get_settings_sections', $settings);
    }
}

if (! function_exists('Amelia_get_settings_fields_for_section')) {
    function Amelia_get_settings_fields_for_section($section_id = '')
    {

        // Bail if section is empty
        if (empty($section_id)) {
            return false;
        }

        $fields = Amelia_get_settings_fields();
        $retval = isset($fields[ $section_id ]) ? $fields[ $section_id ] : false;

        return (array) apply_filters('Amelia_get_settings_fields_for_section', $retval, $section_id);
    }
}

if (! function_exists('Amelia_get_settings_fields')) {
    function Amelia_get_settings_fields()
    {

        $fields = array();

        $fields['Amelia_settings_section'] = array(

            'Amelia_field' => array(
                'title'             => BackendStrings::getBuddyBossStrings()['enable_amelia'],
                'callback'          => 'Amelia_settings_callback_field',
                'sanitize_callback' => 'absint',
                'args'              => array(),
            ),
        );
        if (function_exists('Amelia_is_addon_field_enabled') && Amelia_is_addon_field_enabled()) {
            $fields['Amelia_settings_section'] = array_merge(
                $fields['Amelia_settings_section'],
                array(
                    'Amelia_bookingform_enabled' => array(
                        'title'             => BackendStrings::getBuddyBossStrings()['bookingform_enabled'],
                        'callback'          => 'Amelia_bookingform_enabled',
                        'sanitize_callback' => 'theme_slug_sanitize_select',
                        'args'              => array(),
                    ),

                    'Amelia_booking_form' => array(
                        'title'             => BackendStrings::getBuddyBossStrings()['booking_form_type'],
                        'callback'          => 'Amelia_booking_form_field',
                        'sanitize_callback' => 'theme_slug_sanitize_select',
                        'args'              => array(),
                    ),


                    'Amelia_tab' => array(
                        'title'             => BackendStrings::getBuddyBossStrings()['booking_tab_name'],
                        'callback'          => 'Amelia_settings_callback_tab',
                        'sanitize_callback' => 'wp_filter_nohtml_kses',
                        'args'              => array(),
                    ),

                    'Amelia_subtab_1' => array(
                        'title'             => BackendStrings::getBuddyBossStrings()['book_subtab_name'],
                        'callback'          => 'Amelia_settings_callback_subtab_1',
                        'sanitize_callback' => 'wp_filter_nohtml_kses',
                        'args'              => array(),
                    ),

                    'Amelia_subtab_2' => array(
                        'title'             => BackendStrings::getBuddyBossStrings()['panel_subtab_name'],
                        'callback'          => 'Amelia_settings_callback_subtab_2',
                        'sanitize_callback' => 'wp_filter_nohtml_kses',
                        'args'              => array(),
                    ),

                    'Amelia_bookingform_employee_enabled' => array(
                        'title'             => BackendStrings::getBuddyBossStrings()['bookingform_enabled'],
                        'callback'          => 'Amelia_bookingform_employee_enabled',
                        'sanitize_callback' => 'theme_slug_sanitize_select',
                        'args'              => array(),
                    ),

                    'Amelia_booking_form_employee' => array(
                        'title'             => BackendStrings::getBuddyBossStrings()['booking_form_type_employee'],
                        'callback'          => 'Amelia_booking_form_employee',
                        'sanitize_callback' => 'theme_slug_sanitize_select',
                        'args'              => array(),
                    ),


                    'Amelia_booking_employee_tab' => array(
                        'title'             => BackendStrings::getBuddyBossStrings()['booking_employee_tab_name'],
                        'callback'          => 'Amelia_booking_employee_tab',
                        'sanitize_callback' => 'wp_filter_nohtml_kses',
                        'args'              => array(),
                    ),

                    'Amelia_guest_booking_enabled' => array(
                        'title'             => BackendStrings::getBuddyBossStrings()['guest_booking_enabled'],
                        'callback'          => 'Amelia_guest_booking_enabled',
                        'sanitize_callback' => 'absint',
                        'args'              => array(),
                    ),
                )
            );
        }

        $fields['Amelia_settings_section_customers'] = array(
            'Amelia_subscribers_to_customers' => array(
                'title'             => BackendStrings::getBuddyBossStrings()['subscribers_transform'],
                'callback'          => 'Amelia_settings_callback_transform',
                'sanitize_callback' => 'absint',
                'args'              => array(),
            ),
            'Amelia_create_customers' => array(
                'title'             => BackendStrings::getBuddyBossStrings()['create_customers_text'],
                'callback'          => 'Amelia_settings_callback_create_customers',
                'sanitize_callback' => 'absint',
                'args'              => array(),
            ),
            'Amelia_create_providers' => array(
                'title'             => BackendStrings::getBuddyBossStrings()['create_providers_text'],
                'callback'          => 'Amelia_settings_callback_create_providers',
                'sanitize_callback' => 'absint',
                'args'              => array(),
            ),
        );


        return (array) apply_filters('Amelia_get_settings_fields', $fields);
    }
}

if (! function_exists('Amelia_settings_callback_field')) {
    function Amelia_settings_callback_field()
    {
        ?>
        <input name="Amelia_field"
               id="Amelia_field"
               type="checkbox"
               value="1"
            <?php checked(Amelia_is_addon_field_enabled()); ?>
        />
        <label for="Amelia_field">
            <?php echo esc_html__(BackendStrings::getBuddyBossStrings()['enable_booking_tab']) ?>
        </label>
        <?php
    }
}

if (! function_exists('Amelia_settings_callback_tab')) {
    function Amelia_settings_callback_tab()
    {
        ?>
        <input name="Amelia_tab"
               id="Amelia_tab"
               type="text"
               value="<?php echo esc_attr__(Amelia_tab_text()); ?>"
        />
        <?php
    }
}


if (! function_exists('Amelia_booking_employee_tab')) {
    function Amelia_booking_employee_tab()
    {
        ?>
        <input name="Amelia_booking_employee_tab"
               id="Amelia_booking_employee_tab"
               type="text"
               value="<?php echo esc_attr__(Amelia_employee_tab_text()); ?>"
        />
        <?php
    }
}

if (! function_exists('Amelia_guest_booking_enabled')) {
    function Amelia_guest_booking_enabled()
    {
        ?>
        <input name="Amelia_guest_booking_enabled"
               id="Amelia_guest_booking_enabled"
               type="checkbox"
               value="1"
            <?php checked(Amelia_guest_booking_enabled_check()); ?>
        />
        <label for="Amelia_guest_booking_enabled">
            <?php echo esc_html__(BackendStrings::getBuddyBossStrings()['guest_booking_enabled_text']) ?>
        </label>
        <?php
    }
}

if (! function_exists('Amelia_settings_callback_subtab_1')) {
    function Amelia_settings_callback_subtab_1()
    {
        ?>
        <input name="Amelia_subtab_1"
               id="Amelia_subtab_1"
               type="text"
               value="<?php echo esc_attr__(Amelia_subtab1_text()); ?>"
        />
        <?php
    }
}

if (! function_exists('Amelia_settings_callback_subtab_2')) {
    function Amelia_settings_callback_subtab_2()
    {
        ?>
        <input name="Amelia_subtab_2"
               id="Amelia_subtab_2"
               type="text"
               value="<?php echo esc_attr__(Amelia_subtab2_text()); ?>"
        />
        <?php
    }
}

if (! function_exists('Amelia_bookingform_enabled')) {
    function Amelia_bookingform_enabled()
    {
        ?>
        <input name="Amelia_bookingform_enabled"
               id="Amelia_bookingform_enabled"
               type="checkbox"
               value="1"
            <?php checked(Amelia_is_bookingform_enabled()); ?>
        />
        <label for="Amelia_bookingform_enabled">
            <?php echo esc_html__(BackendStrings::getBuddyBossStrings()['booking_form_enabled']) ?>
        </label>
        <?php
    }
}

if (! function_exists('Amelia_bookingform_employee_enabled')) {
    function Amelia_bookingform_employee_enabled()
    {
        ?>
        <input name="Amelia_bookingform_employee_enabled"
               id="Amelia_bookingform_employee_enabled"
               type="checkbox"
               value="1"
            <?php checked(Amelia_is_bookingform_employee_enabled()); ?>
        />
        <label for="Amelia_bookingform_employee_enabled">
            <?php echo esc_html__(BackendStrings::getBuddyBossStrings()['booking_form_employee_enabled']) ?>
        </label>
        <?php
    }
}


if (! function_exists('Amelia_booking_form_field')) {
    function Amelia_booking_form_field()
    {
        ?>
        <select name="Amelia_booking_form"
                id="Amelia_booking_form"
        >
            <option <?php selected(Amelia_booking_form_field_selected(), 'step_booking')?> value="step_booking">
                <?php echo esc_html__(BackendStrings::getWordPressStrings()['step_booking'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_field_selected(), 'catalog_booking')?> value="catalog_booking">
                <?php echo esc_html__(BackendStrings::getWordPressStrings()['catalog_booking'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_field_selected(), 'booking')?> value="booking">
                <?php echo esc_html__(BackendStrings::getCommonStrings()['booking'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_field_selected(), 'catalog')?> value="catalog">
                <?php echo esc_html__(BackendStrings::getWordPressStrings()['catalog'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_field_selected(), 'search')?> value="search">
                <?php echo esc_html__(BackendStrings::getWordPressStrings()['search'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_field_selected(), 'events_list_booking')?> value="events_list_booking">
                <?php echo esc_html__(BackendStrings::getWordPressStrings()['events_list_booking'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_field_selected(), 'events_list')?> value="events_list">
                <?php echo esc_html__(BackendStrings::getBuddyBossStrings()['booking_form_events_list'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_field_selected(), 'events_calendar')?> value="events_calendar">
                <?php echo esc_html__(BackendStrings::getBuddyBossStrings()['booking_form_events_calendar'])  ?>
            </option>
        </select>
        <?php
    }
}


if (! function_exists('Amelia_booking_form_employee')) {
    function Amelia_booking_form_employee()
    {
        ?>
        <select name="Amelia_booking_form_employee"
                id="Amelia_booking_form_employee"
        >
            <option <?php selected(Amelia_booking_form_field_selected(), 'step_booking')?> value="step_booking">
                <?php echo esc_html__(BackendStrings::getWordPressStrings()['step_booking'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_field_selected(), 'catalog_booking')?> value="catalog_booking">
                <?php echo esc_html__(BackendStrings::getWordPressStrings()['catalog_booking'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_employee_selected(), 'booking')?> value="booking">
                <?php echo esc_html__(BackendStrings::getCommonStrings()['booking'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_employee_selected(), 'catalog')?> value="catalog">
                <?php echo esc_html__(BackendStrings::getWordPressStrings()['catalog'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_employee_selected(), 'events_list_booking')?> value="events_list_booking">
                <?php echo esc_html__(BackendStrings::getWordPressStrings()['events_list_booking'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_employee_selected(), 'events_list')?> value="events_list">
                <?php echo esc_html__(BackendStrings::getBuddyBossStrings()['booking_form_events_list'])  ?>
            </option>
            <option <?php selected(Amelia_booking_form_employee_selected(), 'events_calendar')?> value="events_calendar">
                <?php echo esc_html__(BackendStrings::getBuddyBossStrings()['booking_form_events_calendar'])  ?>
            </option>
        </select>
        <?php
    }
}

if (! function_exists('Amelia_settings_callback_transform')) {
    function Amelia_settings_callback_transform()
    {
        ?>
        <input name="Amelia_subscribers_to_customers"
               id="Amelia_subscribers_to_customers"
               type="checkbox"
               value="1"
            <?php checked(Amelia_transform_users_enabled()); ?>
        />
        <label for="Amelia_subscribers_to_customers">
            <?php echo esc_html__(BackendStrings::getBuddyBossStrings()['subscribers_transform_text']) ?>
        </label>
        <?php
    }
}

if (! function_exists('Amelia_settings_callback_create_customers')) {
    function Amelia_settings_callback_create_customers()
    {
        ?>
        <form method="post">
            <input type="submit" value="<?php echo esc_attr__(BackendStrings::getBuddyBossStrings()['create_customers']) ?>" name="create_customers">
        </form>
        <?php
    }
}

if (! function_exists('Amelia_settings_callback_create_providers')) {
    function Amelia_settings_callback_create_providers()
    {
        ?>
        <form method="post">
            <input type="submit" value="<?php echo esc_attr__(BackendStrings::getBuddyBossStrings()['create_providers']) ?>" name="create_providers">
        </form>
        <?php
    }
}



if (! function_exists('Amelia_is_addon_field_enabled')) {
    function Amelia_is_addon_field_enabled($default = 0)
    {
        return (bool) apply_filters('Amelia_is_addon_field_enabled', (bool) get_option('Amelia_field', $default));
    }
}

if (! function_exists('Amelia_is_bookingform_enabled')) {
    function Amelia_is_bookingform_enabled($default = 0)
    {
        return (bool) apply_filters('Amelia_is_bookingform_enabled', (bool) get_option('Amelia_bookingform_enabled', $default));
    }
}

if (! function_exists('Amelia_is_bookingform_employee_enabled')) {
    function Amelia_is_bookingform_employee_enabled($default = 0)
    {
        return (bool) apply_filters('Amelia_is_bookingform_employee_enabled', (bool) get_option('Amelia_bookingform_employee_enabled', $default));
    }
}


if (! function_exists('Amelia_booking_form_field_selected')) {
    function Amelia_booking_form_field_selected($default = 'booking')
    {
        return (string) apply_filters('Amelia_booking_form_field_selected', (string) get_option('Amelia_booking_form', $default));
    }
}


if (! function_exists('Amelia_booking_form_employee_selected')) {
    function Amelia_booking_form_employee_selected($default = 'booking')
    {
        return (string) apply_filters('Amelia_booking_form_employee_selected', (string) get_option('Amelia_booking_form_employee', $default));
    }
}

if (! function_exists('Amelia_subtab1_text')) {
    function Amelia_subtab1_text($default = 'Book')
    {
        return (string) apply_filters('Amelia_subtab1_text', (string) get_option('Amelia_subtab_1', $default));
    }
}

if (! function_exists('Amelia_subtab2_text')) {
    function Amelia_subtab2_text($default = 'Panel')
    {
        return (string) apply_filters('Amelia_subtab2_text', (string) get_option('Amelia_subtab_2', $default));
    }
}

if (! function_exists('Amelia_tab_text')) {
    function Amelia_tab_text($default = 'Booking')
    {
        return (string) apply_filters('Amelia_tab_text', (string) get_option('Amelia_tab', $default));
    }
}

if (! function_exists('Amelia_employee_tab_text')) {
    function Amelia_employee_tab_text($default = 'Book')
    {
        return (string) apply_filters('Amelia_employee_tab_text', (string) get_option('Amelia_booking_employee_tab', $default));
    }
}

if (! function_exists('Amelia_guest_booking_enabled_check')) {
    function Amelia_guest_booking_enabled_check($default = 0)
    {
        return (string) apply_filters('Amelia_guest_booking_enabled_check', (string) get_option('Amelia_guest_booking_enabled', $default));
    }
}

if (! function_exists('Amelia_transform_users_enabled')) {
    function Amelia_transform_users_enabled($default = 1)
    {
        return (bool) apply_filters('Amelia_transform_users_enabled', (bool) get_option('Amelia_subscribers_to_customers', $default));
    }
}



if (! function_exists('getWorkHours')) {
    function getWorkHours($schedule)
    {
        $weekDayList = [];

        foreach ((array)$schedule as $index => $weekDay) {
            $timeoutList = [];
            foreach ($weekDay['breaks'] as $break) {
                array_push($timeoutList, ['id'=>null, 'startTime'=>$break['time'][0].':00', 'endTime'=>$break['time'][1].':00']);
            }

            $periodList = [];
            if (!empty($weekDay['time'][0]) && !empty($weekDay['time'][1])) {
                if (!(array_key_exists('periods', $weekDay))) {
                    $period = [
                        'id' => null,
                        'startTime' => $weekDay['time'][0] . ':00',
                        'endTime'  => $weekDay['time'][1] . ':00',
                        'serviceIds' => [],
                        'locationId' => null,
                        'periodServiceList' => [],
                        'savedPeriodServiceList' => [],
                        'periodLocationList' => [],
                    ];
                    array_push($periodList, $period);
                } else {
                    foreach ($weekDay['periods'] as $period) {
                        $periodItem = [
                            'id' => null,
                            'startTime' => $period['time'][0] . ':00',
                            'endTime'  => $period['time'][1] . ':00',
                            'serviceIds' => [],
                            'locationId' => null,
                            'periodServiceList' => [],
                            'savedPeriodServiceList' => [],
                            'periodLocationList' => [],
                        ];
                        array_push($periodList, $periodItem);
                    }
                }
                $weekDayItem =[
                    'dayIndex' => $index + 1,
                    'id' => null,
                    'startTime' => $weekDay['time'][0] . ':00',
                    'endTime'  => $weekDay['time'][1] . ':00',
                    'periodList' => $periodList,
                    'timeoutList' => $timeoutList
                ];
                array_push($weekDayList, $weekDayItem);
            }
        }
        return $weekDayList;
    }
}

if (! function_exists('afterActivationAsAdmin')) {
    /**
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    function afterActivationAsAdmin($signup_ids, $result)
    {
        if (Amelia_transform_users_enabled()) {
            convertUserRoles($result['activated']);
        }
    }

}
add_action('bp_core_signup_after_activate', 'afterActivationAsAdmin', 10, 2);


if (! function_exists('afterActivationFromEmail')) {
    /**
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    function afterActivationFromEmail($user_id, $key, $user)
    {
        if (Amelia_transform_users_enabled()) {
            convertUserRoles([$user_id]);
        }
    }

}

add_action('bp_core_activated_user', 'afterActivationFromEmail', 10, 3);


if (! function_exists('convertUserRoles')) {
    /**
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    function convertUserRoles($userIds)
    {
        $args  = array(
            'role'    => 'subscriber',
            'include' => $userIds
        );
        $users = get_users($args);
        foreach ($users as $user) {
            $user->add_role('wpamelia-customer');
            createCustomers($user);
        }
    }
}

if (! function_exists('createCustomers')) {
    /**
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws QueryExecutionException
     */
    function createCustomers($user)
    {
        $container = require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';
        /** @var CustomerApplicationService $customerAS */
        $customerAS = $container->get('application.user.customer.service');
        /** @var UserRepository $userRepo */
        $userRepo = $container->get('domain.users.repository');

        $ameliaUser = $userRepo->getByEntityId($user->ID, 'externalId');
        if ($ameliaUser && $ameliaUser->length() > 0) {
            return;
        }

        $userMetaData = get_user_meta($user->ID);
        $userArr      =
            [
                'status'     => 'visible',
                'type'       => 'customer',
                'firstName'  => !empty($userMetaData['first_name'][0]) ? $userMetaData['first_name'][0] : $user->data->user_login,
                'lastName'   => !empty($userMetaData['last_name'][0]) ? $userMetaData['last_name'][0] : $user->data->user_login,
                'email'      => $user->data->user_email,
                'externalId' => $user->ID,
            ];

        $customerAS->createCustomer($userArr);
    }
}

if (! function_exists('createProviders')) {
    /**
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    function createProviders()
    {
        $args  = array(
            'role'    => 'wpamelia-provider',
        );
        $users = get_users($args);

        $container = require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';
        /** @var SettingsService $settingsService */
        $settingsService = $container->get('domain.settings.service');
        /** @var LocationRepository $locationRepository */
        $locationRepository = $container->get('domain.locations.repository');
        /** @var ProviderApplicationService $providerAS */
        $providerAS = $container->get('application.user.provider.service');
        /** @var UserRepository $userRepo */
        $userRepo = $container->get('domain.users.repository');

        $schedule    = $settingsService->getCategorySettings('weekSchedule');
        $weekDayList = getWorkHours($schedule);

        foreach ($users as $user) {
            $ameliaUser = $userRepo->getByEntityId($user->ID, 'externalId');
            if ($ameliaUser && $ameliaUser->length() > 0) {
                if ($ameliaUser->toArray()[0]['type'] === 'provider') {
                    continue;
                }
                if ($ameliaUser->toArray()[0]['type'] === 'customer') {
                    $userRepo->delete($ameliaUser->toArray()[0]['id']);
                }
            }
            $userMetaData = get_user_meta($user->ID);

            $locations = $locationRepository->getFiltered([], 1);

            $userArr =
                [
                    'status'      => 'visible',
                    'type'        => 'provider',
                    'password'    => $user->data->user_pass,
                    'firstName'  => !empty($userMetaData['first_name'][0]) ? $userMetaData['first_name'][0] : $user->data->user_login,
                    'lastName'   => !empty($userMetaData['last_name'][0]) ? $userMetaData['last_name'][0] : $user->data->user_login,
                    'email'       => $user->data->user_email,
                    'externalId'  => $user->ID,
                    'weekDayList' => $weekDayList,
                    'sendEmployeePanelAccessEmail' => true,
                    'locationId'  => $locations && $locations->length() && $locations->getItem(0) ? $locations->getItem(0)->getId()->getValue() : ''
                ];

            $providerAS->createProvider($userArr, true);
        }
    }
}

// added to fix conflict with "WP Login and Logout redirect"
if (!function_exists('get_current_screen')) {
    require_once ABSPATH . '/wp-admin/includes/screen.php';
}

if (isset($_POST['create_customers'])) {
    try {
        $args  = array(
            'role'    => 'wpamelia-customer'
        );
        $users = get_users($args);
        foreach ($users as $user) {
            createCustomers($user);
        }
    } catch (\AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException $e) {
    } catch (QueryExecutionException $e) {
    } catch (ContainerException $e) {
    }
}

if (isset($_POST['create_providers'])) {
    try {
        createProviders();
    } catch (\AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException $e) {
    } catch (QueryExecutionException $e) {
    } catch (ContainerException $e) {
    }
}

/**************************************** AMELIA INTEGRATION ************************************/

/**
 * Set up the my plugin integration.
 */
function Amelia_register_integration()
{
    require_once dirname(__FILE__) . '/integration/buddyboss-integration.php';
    buddypress()->integrations['amelia'] = new Amelia_BuddyBoss_Integration();
}
add_action('bp_setup_integrations', 'Amelia_register_integration');

$displayedUserId       = '';
$displayedUserAmeliaId = '';


function buddyboss_amelia_user_tab()
{

    // Avoid fatal errors when plugin is not available.
    if (! function_exists('bp_core_new_nav_item') ||
        ! function_exists('bp_loggedin_user_domain') ||
        ! function_exists('get_current_user_id') ||
        ! function_exists('bp_loggedin_user_id') ||
        ! function_exists('bp_core_new_subnav_item') ||
        !Amelia_is_addon_field_enabled() ||
        ! function_exists('bp_displayed_user_id')
    ) {
        return;
    }

    global $displayedUserId;
    $displayedUserId = bp_displayed_user_id();


    // Employee profile displayed
    if ($displayedUserId && $displayedUserId !== bp_loggedin_user_id() && Amelia_is_bookingform_employee_enabled()) {
        if (! function_exists('bp_displayed_user_domain')) {
            return;
        }

        if (empty(get_current_user_id())) {
            if (!Amelia_guest_booking_enabled_check()) {
                return;
            }
        } else {
            $user_meta  = get_userdata(get_current_user_id());
            $user_roles = $user_meta->roles;

            $user_meta_employee  = get_userdata($displayedUserId);
            $user_roles_employee = $user_meta_employee->roles;
            if (!in_array('wpamelia-provider', $user_roles_employee) ||in_array('wpamelia-provider', $user_roles)) {
                return;
            }
        }


        $bookingTabEmployee = array(
            'name'                    => esc_html__(Amelia_employee_tab_text()),
            'slug'                    => 'bookEmployee',
            'screen_function'         => 'bookEmployee',
            'position'                => 100,
            'parent_url'              => bp_displayed_user_domain() . 'bookEmployee/',
        );

        bp_core_new_nav_item($bookingTabEmployee);
    } else if (!empty(get_current_user_id())) {
        //customer profile displayed
        $user_meta  = get_userdata(get_current_user_id());
        $user_roles = $user_meta->roles;
        if (!in_array('wpamelia-provider', $user_roles) && !in_array('administrator', $user_roles) && !in_array('wpamelia-customer', $user_roles)) {
            return;
        }


        $bookingTab = array(
            'name'                    => esc_html__(Amelia_tab_text()),
            'slug'                    => 'booking',
            'screen_function'         => 'booking',
            'position'                => 100,
            'parent_url'              => bp_loggedin_user_domain() . '/booking/',
            'show_for_displayed_user' => false,
            'default_subnav_slug'     => in_array('wpamelia-customer', $user_roles) && Amelia_is_bookingform_enabled() ? 'book' : 'panel'
        );

        bp_core_new_nav_item($bookingTab);

        $args = array();

        if (in_array('wpamelia-customer', $user_roles) && Amelia_is_bookingform_enabled()) {
            $args[] = array(
                'name'                => esc_html__(Amelia_subtab1_text()),
                'slug'                => 'book',
                'screen_function'     => 'book',
                'position'            => 100,
                'parent_url'          => bp_loggedin_user_domain() . '/booking/',
                'parent_slug'         => 'booking',
            );
        }

        $args[] = array(
            'name'                => esc_html__(Amelia_subtab2_text()),
            'slug'                => 'panel',
            'screen_function'     => 'panel',
            'position'            => 101,
            'parent_url'          => bp_loggedin_user_domain() . '/booking/',
            'parent_slug'         => 'booking',
        );


        foreach ($args as $arg) {
            bp_core_new_subnav_item($arg);
        }
    }
}
add_action('bp_setup_nav', 'buddyboss_amelia_user_tab', 50);


function custom_bar_menu()
{
    global $wp_admin_bar, $bp;

    if (!function_exists('bp_use_wp_admin_bar') ||
        !bp_use_wp_admin_bar() ||
        defined('DOING_AJAX') ||
        !Amelia_is_addon_field_enabled() ||
        empty(get_current_user_id()) ||
        !function_exists('bp_loggedin_user_domain')) {
        return;
    }

    $user_meta  = get_userdata(get_current_user_id());
    $user_roles = $user_meta->roles;
    if (!in_array('wpamelia-provider', $user_roles) && !in_array('administrator', $user_roles) && !in_array('wpamelia-customer', $user_roles)) {
        return;
    }

    $user_domain = bp_loggedin_user_domain();
    $item_link   = trailingslashit($user_domain . 'booking');

    $wp_admin_bar->add_menu(
        array(
        'parent'  => $bp->my_account_menu_id,
        'id'      => 'bookings',
        'title'   => esc_html__(Amelia_tab_text()),
        'href'    => trailingslashit($item_link)
        )
    );

    if (in_array('wpamelia-customer', $user_roles) && Amelia_is_bookingform_enabled()) {
        $wp_admin_bar->add_menu(
            array(
                'parent' => 'bookings',
                'id'     => 'bookings-book',
                'title'  => esc_html__(Amelia_subtab1_text()),
                'href'   => trailingslashit($item_link) . 'book'
            )
        );
    }

    $wp_admin_bar->add_menu(
        array(
            'parent' => 'bookings',
            'id'     => 'bookings-panel',
            'title'  => esc_html__(Amelia_subtab2_text()),
            'href'   => trailingslashit($item_link) . 'panel'
        )
    );
}
add_action('bp_setup_admin_bar', 'custom_bar_menu', 300);

/**
 * Set template for new tab.
 */
function book()
{
    // Add title and content here - last is to call the members plugin.php template.
    //add_action('bp_template_title', 'booking_title');
    add_action('bp_template_content', 'booking_content', 100);
    bp_core_load_template('buddypress/members/single/plugins');
}

/**
 * Set template for new tab.
 */
function panel()
{
    // Add title and content here - last is to call the members plugin.php template.
    //add_action('bp_template_title', 'panel_title');
    add_action('bp_template_content', 'panel_content', 100);
    bp_core_load_template('buddypress/members/single/plugins');
}

/**
 * Set title for custom tab.
 */
function booking_title()
{
    echo esc_html__('Book an appointment');
}

/**
 * Set title for custom tab.
 */
function panel_title()
{
    echo esc_html__('Manage bookings');
}

/**
 * Set template for new tab.
 */
function getBookingFormText($bookingFormType, $employee = '')
{
    $bookingForm = '';
    if ($bookingFormType === 'booking') {
        $bookingForm = '[ameliabooking ' . $employee  . ']';
    } else if ($bookingFormType === 'catalog') {
        $bookingForm = '[ameliacatalog ' . $employee  . ']';
    } else if ($bookingFormType === 'search') {
        $bookingForm = '[ameliasearch  ' . $employee  . ']';
    } else if ($bookingFormType === 'events_list') {
        $bookingForm = "[ameliaevents type='list'  " . $employee  . ']';
    } else if ($bookingFormType === 'events_calendar') {
        $bookingForm = "[ameliaevents type='calendar'  " . $employee  . ']';
    } else if ($bookingFormType === 'step_booking') {
        $bookingForm = "[ameliastepbooking '  " . $employee  . ']';
    } else if ($bookingFormType === 'catalog_booking') {
        $bookingForm = "[ameliacatalogbooking '  " . $employee  . ']';
    } else if ($bookingFormType === 'events_list_booking') {
        $bookingForm = "[ameliaeventslistbooking " . $employee  . ']';
    }

    return $bookingForm;
}

/**
 * Display content of custom tab.
 */
function booking_content()
{
    echo do_shortcode(getBookingFormText(Amelia_booking_form_field_selected()));
}

/**
 * Display content of custom tab.
 */
function panel_content()
{
    $user_meta = get_userdata(bp_loggedin_user_id());

    $user_roles = $user_meta->roles;
    if (in_array('wpamelia-provider', $user_roles) || in_array('administrator', $user_roles)) {
        echo do_shortcode('[ameliaemployeepanel]');
    } else if (in_array('wpamelia-customer', $user_roles)) {
        echo do_shortcode('[ameliacustomerpanel version=2]');
    }
}

/**
 * Set template for new tab.
 */
function bookEmployeeContent()
{
    echo do_shortcode(getBookingFormText(Amelia_booking_form_employee_selected(), 'employee=' . $GLOBALS['displayedUserAmeliaId']));
}

/**
 * Set template for new tab.
 */
function bookEmployee()
{
    $container = require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';
    /** @var UserRepository $userRepo */
    $userRepo   = $container->get('domain.users.repository');
    $externalId = $GLOBALS['displayedUserId'];
    if ($externalId) {
        $provider = $userRepo->findByExternalId($externalId);
        if ($provider) {
            global $displayedUserAmeliaId;
            $displayedUserAmeliaId = $provider->getId()->getValue();
            add_action('bp_template_content', 'bookEmployeeContent');
            bp_core_load_template('buddypress/members/single/plugins');
        }
    }
}

?>
