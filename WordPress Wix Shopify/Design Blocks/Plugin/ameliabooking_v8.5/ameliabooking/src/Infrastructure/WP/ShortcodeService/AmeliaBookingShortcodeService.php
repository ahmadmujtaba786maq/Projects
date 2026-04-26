<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ShortcodeService;

use AmeliaBooking\Application\Services\Cache\CacheApplicationService;
use AmeliaBooking\Application\Services\Stash\StashApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Interop\Container\Exception\ContainerException;

/**
 * Class AmeliaBookingShortcodeService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class AmeliaBookingShortcodeService
{
    public static $counter = 1000;

    /**
     * Prepare scripts and styles
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public static function prepareScriptsAndStyles()
    {
        $container = null;

        self::$counter++;

        if (self::$counter > 1001) {
            return;
        }

        $settingsService = new SettingsService(new SettingsStorage());

        if ($settingsService->getSetting('payments', 'payPal')['enabled'] === true) {
            wp_enqueue_script('amelia_paypal_script', 'https://www.paypalobjects.com/api/checkout.js');
        }

        if ($settingsService->getSetting('payments', 'stripe')['enabled'] === true) {
            wp_enqueue_script('amelia_stripe_script', 'https://js.stripe.com/v3/');
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

        if ($settingsService->getSetting('payments', 'razorpay')['enabled'] === true) {
            wp_enqueue_script('amelia_razorpay_script', 'https://checkout.razorpay.com/v1/checkout.js');
        }

        $gmapApiKey = $settingsService->getSetting('general', 'gMapApiKey');

        if ($gmapApiKey) {
            $container = $container ?: require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';

            /** @var CustomFieldRepository $customFieldRepository */
            $customFieldRepository = $container->get('domain.customField.repository');

            $addressCustomFields = $customFieldRepository->getByFieldValue('type', 'address');

            if (count($addressCustomFields->getItems())) {
                wp_enqueue_script(
                    'amelia_google_maps_api',
                    "https://maps.googleapis.com/maps/api/js?key={$gmapApiKey}&libraries=places&loading=async"
                );
            }
        }

        $scriptId = AMELIA_DEV ? 'amelia_booking_scripts_dev_vite' : 'amelia_booking_script_index';

        if (AMELIA_DEV) {
            wp_enqueue_script(
                $scriptId,
                'http://localhost:3000/@vite/client',
                [],
                null,
                false
            );

            wp_enqueue_script(
                'amelia_booking_scripts_dev_main',
                'http://localhost:3000/src/assets/js/public/public.js',
                [],
                null,
                true
            );
        } else {
            wp_enqueue_script(
                $scriptId,
                AMELIA_URL . 'v3/public/assets/public.d17a4091.js',
                [],
                AMELIA_VERSION,
                true
            );
        }

        $ameliaLocale = apply_filters('amelia_modify_locale_filter', AMELIA_LOCALE) ?: AMELIA_LOCALE;

        wp_localize_script(
            $scriptId,
            'localeLanguage',
            [$ameliaLocale]
        );

        wp_localize_script(
            $scriptId,
            'wpAmeliaSettings',
            $settingsService->getFrontendSettings()
        );

        // Strings Localization
        wp_localize_script(
            $scriptId,
            'wpAmeliaLabels',
            FrontendStrings::getAllStrings()
        );

        wp_localize_script(
            $scriptId,
            'wpAmeliaTimeZone',
            [DateTimeService::getTimeZone()->getName()]
        );

        $ameliaUrl = AMELIA_URL;

        $ameliaActionUrl = AMELIA_ACTION_URL;

        if (strpos($ameliaUrl, 'http://') === 0) {
            $ameliaUrl = substr($ameliaUrl, strpos(substr($ameliaUrl, 7), '/') + 7);

            $ameliaActionUrl = substr($ameliaActionUrl, strpos(substr($ameliaActionUrl, 7), '/') + 7);
        } elseif (strpos($ameliaUrl, 'https://') === 0) {
            $ameliaUrl = substr($ameliaUrl, strpos(substr($ameliaUrl, 8), '/') + 8);

            $ameliaActionUrl = substr($ameliaActionUrl, strpos(substr($ameliaActionUrl, 8), '/') + 8);
        }

        wp_localize_script(
            $scriptId,
            'wpAmeliaUrls',
            [
                'wpAmeliaUseUploadsAmeliaPath' => AMELIA_UPLOADS_FILES_PATH_USE,
                'wpAmeliaPluginURL'            => $ameliaUrl,
                'wpAmeliaPluginAjaxURL'        => $ameliaActionUrl,
            ]
        );

        if (!empty($_GET['ameliaCache']) || !empty($_GET['ameliaWcCache'])) {
            $container = $container ?: require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';

            /** @var CacheApplicationService $cacheAS */
            $cacheAS = $container->get('application.cache.service');

            try {
                $cacheData = !empty($_GET['ameliaCache']) ?
                    $cacheAS->getCacheByName($_GET['ameliaCache']) : $cacheAS->getWcCacheByName($_GET['ameliaWcCache']);

                wp_localize_script(
                    $scriptId,
                    'ameliaCache',
                    [$cacheData ? str_replace('&quot;', '\\"', json_encode($cacheData)) : '']
                );
            } catch (QueryExecutionException $e) {
            }
        }

        if ($settingsService->getSetting('activation', 'stash')) {
            $container = $container ?: require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';

            /** @var StashApplicationService $stashAS */
            $stashAS = $container->get('application.stash.service');

            wp_localize_script(
                $scriptId,
                'ameliaEntities',
                $stashAS->getStash()
            );
        }
    }

    /**
     * @param string $tag
     * @param string $handle
     * @param string $src
     *
     * @return string
     */
    public static function prepareScripts($tag, $handle, $src)
    {
        switch ($handle) {
            case ('amelia_booking_scripts_dev_vite'):
            case ('amelia_booking_scripts_dev_main'):
                return "<script type='module' src='{$src}'></script>";

            case ('amelia_booking_script_index'):
                $settingsService = new SettingsService(new SettingsStorage());

                if ($settingsService->getSetting('activation', 'v3RelativePath')) {
                    $customUrl = $settingsService->getSetting('activation', 'customUrl');

                    $position = strpos($src, $customUrl['pluginPath'] . 'v3/public/assets/public.');

                    if ($position !== false) {
                        $src = substr($src, $position);
                    }
                } elseif (strpos($src, 'http://') === 0) {
                    $src = substr($src, strpos(substr($src, 7), '/') + 7);
                } elseif (strpos($src, 'https://') === 0) {
                    $src = substr($src, strpos(substr($src, 8), '/') + 8);
                }

                $asyncLoading = $settingsService->getSetting('activation', 'v3AsyncLoading') ?
                    'async' : '';

                return "<script type='module' {$asyncLoading} crossorigin src='{$src}'></script>";

            case ('amelia_booking_script_vendor'):
                return "<link rel='modulepreload' href='{$src}'>";

            default:
                return $tag;
        }
    }

    /**
     * @param string $tag
     * @param string $handle
     * @param string $href
     *
     * @return string
     */
    public static function prepareStyles($tag, $handle, $href)
    {
        switch ($handle) {
            case ('amelia_booking_style_index'):
            case ('amelia_booking_style_vendor'):
                return "<link rel='stylesheet' href='{$href}'>";

            default:
                return $tag;
        }
    }
}
