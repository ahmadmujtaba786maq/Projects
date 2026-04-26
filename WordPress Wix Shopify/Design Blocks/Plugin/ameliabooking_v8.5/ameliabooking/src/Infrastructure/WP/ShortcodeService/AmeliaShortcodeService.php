<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ShortcodeService;

use AmeliaBooking\Application\Services\Cache\CacheApplicationService;
use AmeliaBooking\Application\Services\CustomField\AbstractCustomFieldApplicationService;
use AmeliaBooking\Application\Services\Stash\StashApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class AmeliaShortcodeService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class AmeliaShortcodeService
{
    public static $counter = 0;

    /**
     * Prepare scripts and styles
     * @throws InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public static function prepareScriptsAndStyles()
    {
        $container = null;

        self::$counter++;

        if (self::$counter > 1) {
            return;
        }

        $settingsService = new SettingsService(new SettingsStorage());

        // Enqueue Scripts
        if ($settingsService->getSetting('payments', 'payPal')['enabled'] === true) {
            wp_enqueue_script('amelia_paypal_script', 'https://www.paypalobjects.com/api/checkout.js');
        }

        if ($settingsService->getSetting('payments', 'razorpay')['enabled'] === true) {
            wp_enqueue_script('amelia_razorpay_script', 'https://checkout.razorpay.com/v1/checkout.js');
        }

        $gmapApiKey = $settingsService->getSetting('general', 'gMapApiKey');

        if ($gmapApiKey) {
            wp_enqueue_script(
                'amelia_google_maps_api',
                "https://maps.googleapis.com/maps/api/js?key={$gmapApiKey}&libraries=places&loading=async"
            );
        }

        // Fix for Divi theme.
        // Don't enqueue script if it's activated Divi Visual Page Builder
        if (empty($_GET['et_fb'])) {
            wp_enqueue_script(
                'amelia_booking_scripts',
                AMELIA_URL . 'public/js/frontend/amelia-booking.js',
                [],
                AMELIA_VERSION,
                true
            );
        }

        if ($settingsService->getSetting('payments', 'stripe')['enabled'] === true) {
            wp_enqueue_script('amelia_stripe_js', 'https://js.stripe.com/v3/');
        }

        if ($settingsService->getSetting('payments', 'square')['enabled'] === true) {
            if ($settingsService->getSetting('payments', 'square')['testMode'] === true) {
                wp_enqueue_script('amelia_square_js', 'https://sandbox.web.squarecdn.com/v1/square.js');
            } else {
                wp_enqueue_script('amelia_square_js', 'https://web.squarecdn.com/v1/square.js');
            }
            wp_enqueue_style(
                'amelia_google_button_style',
                'https://developers.google.com/reference/sdks/web/static/styles/code-preview.css',
                [],
                null,
                'all'
            );
        }

        // Enqueue Styles
        wp_enqueue_style(
            'amelia_booking_styles_vendor',
            AMELIA_URL . 'public/css/frontend/vendor.css',
            [],
            AMELIA_VERSION
        );

        $customUrl = $settingsService->getSetting('activation', 'customUrl');

        if (
            $settingsService->getSetting('customization', 'useGenerated') === null ||
            $settingsService->getSetting('customization', 'useGenerated')
        ) {
            wp_enqueue_style(
                'amelia_booking_styles',
                (!empty($customUrl['uploadsPath']) ? $customUrl['uploadsPath'] : AMELIA_UPLOADS_URL)  . '/amelia/css/amelia-booking.' .
                $settingsService->getSetting('customization', 'hash') . '.css',
                [],
                AMELIA_VERSION
            );
        } else {
            wp_enqueue_style(
                'amelia_booking_styles',
                AMELIA_URL . 'public/css/frontend/amelia-booking-' . str_replace('.', '-', AMELIA_VERSION) . '.css',
                [],
                AMELIA_VERSION
            );
        }

        // Underscore
        wp_enqueue_script('undescore', includes_url('js') . '/underscore.min.js');

        // Strings Localization
        wp_localize_script(
            'amelia_booking_scripts',
            'wpAmeliaLabels',
            FrontendStrings::getAllStrings()
        );

        // Settings Localization
        wp_localize_script(
            'amelia_booking_scripts',
            'wpAmeliaSettings',
            $settingsService->getFrontendSettings()
        );

        wp_localize_script(
            'amelia_booking_scripts',
            'wpAmeliaUrls',
            [
                'wpAmeliaUseUploadsAmeliaPath' => AMELIA_UPLOADS_FILES_PATH_USE,
                'wpAmeliaPluginURL'     => empty($customUrl['enabled'])
                    ? AMELIA_URL : AMELIA_HOME_URL . $customUrl['pluginPath'],
                'wpAmeliaPluginAjaxURL' => empty($customUrl['enabled'])
                    ? AMELIA_ACTION_URL : AMELIA_HOME_URL . $customUrl['ajaxPath'] . '?' . AMELIA_ACTION_SLUG . '',
            ]
        );

        wp_localize_script(
            'amelia_booking_scripts',
            'localeLanguage',
            [AMELIA_LOCALE]
        );

        $localeSubstitutes = $settingsService->getSetting('general', 'calendarLocaleSubstitutes');

        if (isset($localeSubstitutes[AMELIA_LOCALE])) {
            wp_localize_script(
                'amelia_booking_scripts',
                'ameliaCalendarLocale',
                [$localeSubstitutes[AMELIA_LOCALE]]
            );
        }

        wp_localize_script(
            'amelia_booking_scripts',
            'fileUploadExtensions',
            array_keys(AbstractCustomFieldApplicationService::$allowedUploadedFileExtensions)
        );

        if ($settingsService->getSetting('activation', 'stash') && self::$counter === 1) {
            $container = $container ?: require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';

            /** @var StashApplicationService $stashAS */
            $stashAS = $container->get('application.stash.service');

            wp_localize_script(
                'amelia_booking_scripts',
                'ameliaEntities',
                $stashAS->getStash()
            );
        }

        if (!empty($_GET['ameliaCache']) || !empty($_GET['ameliaWcCache'])) {
            $container = $container ?: require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';

            /** @var CacheApplicationService $cacheAS */
            $cacheAS = $container->get('application.cache.service');

            try {
                $cacheData = !empty($_GET['ameliaCache']) ?
                    $cacheAS->getCacheByName(sanitize_text_field($_GET['ameliaCache'])) :
                    $cacheAS->getWcCacheByName(sanitize_text_field($_GET['ameliaWcCache']));

                wp_localize_script(
                    'amelia_booking_scripts',
                    'ameliaCache',
                    [$cacheData ? str_replace('&quot;', '\\"', json_encode($cacheData)) : '']
                );
            } catch (QueryExecutionException $e) {
            }
        }

        wp_localize_script(
            'amelia_booking_scripts',
            'wpAmeliaTimeZone',
            [DateTimeService::getTimeZone()->getName()]
        );

        do_action('ameliaScriptsLoaded');
        do_action('amelia_scripts_loaded');
    }
}
