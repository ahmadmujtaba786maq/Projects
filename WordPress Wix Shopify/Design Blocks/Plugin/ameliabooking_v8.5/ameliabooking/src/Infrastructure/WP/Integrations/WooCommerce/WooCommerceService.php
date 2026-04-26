<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\Placeholder\PlaceholderService;
use AmeliaBooking\Application\Services\Tax\TaxApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBookingExtra;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventTicket;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageCustomerFactory;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageFactory;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\Factory\Coupon\CouponFactory;
use AmeliaBooking\Domain\Factory\Cache\CacheFactory;
use AmeliaBooking\Domain\Factory\Payment\PaymentFactory;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\DepositType;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Cache\CacheRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\PackageCustomerUpdatedEventHandler;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Interop\Container\Exception\ContainerException;

/**
 * Class WooCommerceService
 *
 * @package AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce
 */
class WooCommerceService
{
    /** @var Container $container */
    public static $container;

    /** @var SettingsService $settingsService */
    public static $settingsService;

    /** @var array $checkout_info */
    protected static $checkout_info = [];

    /** @var array $processedAmeliaItems */
    protected static $processedAmeliaItems = [];

    public const AMELIA = 'ameliabooking';

    /**
     * Init
     *
     * @param $settingsService
     */
    public static function init($settingsService)
    {
        self::setContainer(require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php');
        self::$settingsService = $settingsService;

        add_action('woocommerce_before_cart_contents', [self::class, 'beforeCartContents'], 10, 0);
        add_filter('woocommerce_get_item_data', [self::class, 'getItemData'], 10, 2);
        add_filter('woocommerce_cart_item_price', [self::class, 'cartItemPrice'], 10, 3);
        add_filter('woocommerce_checkout_get_value', [self::class, 'checkoutGetValue'], 10, 2);

        add_action('woocommerce_checkout_create_order_line_item', [self::class, 'checkoutCreateOrderLineItem'], 10, 4);

        add_filter('woocommerce_order_item_meta_end', [self::class, 'orderItemMeta'], 10, 3);
        add_filter('woocommerce_after_order_itemmeta', [self::class, 'orderItemMeta'], 10, 3);

        $wcSettings = self::$settingsService->getCategorySettings('payments')['wc'];

        if (
            empty($wcSettings['rules']['appointment']) &&
            empty($wcSettings['rules']['package']) &&
            empty($wcSettings['rules']['event'])
        ) {
            add_action('woocommerce_order_status_completed', [self::class, 'orderStatusChanged'], 10, 1);
            add_action('woocommerce_order_status_on-hold', [self::class, 'orderStatusChanged'], 10, 1);
            add_action('woocommerce_order_status_processing', [self::class, 'orderStatusChanged'], 10, 1);
        } else {
            add_action("woocommerce_order_status_pending", [self::class, 'orderStatusChanged'], 10, 1);
            add_action("woocommerce_order_status_on-hold", [self::class, 'orderStatusChanged'], 10, 1);
            add_action("woocommerce_order_status_processing", [self::class, 'orderStatusChanged'], 10, 1);
            add_action("woocommerce_order_status_completed", [self::class, 'orderStatusChanged'], 10, 1);
            add_action("woocommerce_order_status_cancelled", [self::class, 'orderStatusChanged'], 10, 1);
            add_action("woocommerce_order_status_refunded", [self::class, 'orderStatusChanged'], 10, 1);
            add_action("woocommerce_order_status_failed", [self::class, 'orderStatusChanged'], 10, 1);
        }

        add_filter('woocommerce_thankyou', [self::class, 'redirectAfterOrderReceived'], 10, 2);

        add_action('woocommerce_before_checkout_process', [self::class, 'beforeCheckoutProcess'], 10, 1);
        add_action('woocommerce_checkout_create_order', [self::class, 'beforeCheckoutProcess'], 10, 2);
        add_filter('woocommerce_before_calculate_totals', [self::class, 'beforeCalculateTotals'], 10);

        add_action('woocommerce_store_api_checkout_order_processed', [self::class, 'orderCreated'], 10, 1);
        add_action('woocommerce_checkout_order_created', [self::class, 'orderCreated'], 10, 1);

        add_action('template_redirect', [self::class, 'beforeCheckoutForm']);

        add_action('woocommerce_new_order', function ($order_id) {
            $order = wc_get_order($order_id);
            self::orderCreated($order);
        }, 10, 1);
    }

    /**
     * Check if cache is valid
     *
     * @param array $wc_item
     *
     * @return bool
     */
    private static function isCacheValid($wc_item)
    {
        if (isset($wc_item[self::AMELIA]) && is_array($wc_item[self::AMELIA])) {
            return self::getEntity($wc_item[self::AMELIA]) !== null;
        }

        return true;
    }

    /**
     * @param $cart_obj
     *
     */
    public static function beforeCalculateTotals($cart_obj)
    {
        $wooCommerceCart = self::getWooCommerceCart();

        if (!$wooCommerceCart) {
            return;
        }

        foreach ($wooCommerceCart->get_cart() as $wc_item) {
            if (!self::isCacheValid($wc_item)) {
                return;
            }
        }

        $groupData = [];

        foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
            if (isset($wc_item[self::AMELIA]) && is_array($wc_item[self::AMELIA])) {
                $key = isset($wc_item[self::AMELIA]['wcItemHash']) ? $wc_item[self::AMELIA]['wcItemHash'] : 0;

                $groupData[$key] = 0;
            }
        }

        foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
            if (isset($wc_item[self::AMELIA]) && is_array($wc_item[self::AMELIA])) {
                $key = isset($wc_item[self::AMELIA]['wcItemHash']) ? $wc_item[self::AMELIA]['wcItemHash'] : 0;

                $product_price = self::getPaymentAmount($wc_item[self::AMELIA]);

                $bookableData = self::getEntity($wc_item[self::AMELIA]);

                $isCart = isset($wc_item[self::AMELIA]['isCart']) && is_string($wc_item[self::AMELIA]['isCart'])
                    ? filter_var($wc_item[self::AMELIA]['isCart'], FILTER_VALIDATE_BOOLEAN)
                    : !empty($wc_item[self::AMELIA]['isCart']);

                if (
                    !$isCart &&
                    $wc_item[self::AMELIA]['type'] !== 'event' &&
                    $wc_item[self::AMELIA]['type'] !== 'package' &&
                    isset($bookableData['bookable']['recurringPayment']) &&
                    $bookableData['bookable']['recurringPayment'] !== null &&
                    $groupData[$key] > $bookableData['bookable']['recurringPayment']
                ) {
                    $wc_item['data']->set_price(0);

                    continue;
                }

                $groupData[$key]++;

                /** @var \WC_Product $wc_item ['data'] */
                $wc_item['data']->set_price($product_price >= 0 ? $product_price : 0);
            }
        }
    }

    /**
     * Set Amelia Container
     *
     * @param $container
     */
    public static function setContainer($container)
    {
        self::$container = $container;
    }

    /**
     * Get cart page
     *
     * @param array $appointmentData
     * @return string
     */
    public static function getPageUrl($appointmentData)
    {
        $locale = !empty($appointmentData['locale']) ? $appointmentData['locale'] : '';
        $locale = $locale ? explode('_', $locale) : null;

        switch (self::$settingsService->getCategorySettings('payments')['wc']['page']) {
            case 'checkout':
                if (
                    !empty($locale[0]) &&
                    function_exists('icl_object_id') &&
                    ($url = apply_filters('wpml_permalink', get_permalink(get_option('woocommerce_checkout_page_id')), $locale[0], true))
                ) {
                    $redirectUrl = $url;
                } else {
                    $redirectUrl = wc_get_checkout_url();
                }

                break;
            case 'cart':
                if (
                    !empty($locale[0]) &&
                    function_exists('icl_object_id') &&
                    ($url = apply_filters('wpml_permalink', get_permalink(get_option('woocommerce_cart_page_id')), $locale[0], true))
                ) {
                    $redirectUrl = $url;
                } else {
                    $redirectUrl = wc_get_cart_url();
                }

                break;
            default:
                $locale = defined(AMELIA_LOCALE) ? explode('_', AMELIA_LOCALE) : null;

                if (
                    !empty($locale[0]) &&
                    function_exists('icl_object_id') &&
                    ($url = apply_filters('wpml_permalink', get_permalink(get_option('woocommerce_cart_page_id')), $locale[0], true))
                ) {
                    $redirectUrl = $url;
                } else {
                    $redirectUrl = wc_get_cart_url();
                }

                break;
        }

        return apply_filters('amelia_wc_redirect_page', $redirectUrl, $appointmentData);
    }

    /**
     * Get WooCommerce Cart
     */
    private static function getWooCommerceCart()
    {
        return wc()->cart;
    }

    /**
     * Is WooCommerce enabled
     *
     * @return bool
     */
    public static function isEnabled()
    {
        return class_exists('WooCommerce');
    }

    /**
     * Get product id from settings
     *
     * @return int
     */
    private static function getProductIdFromSettings()
    {
        return self::$settingsService->getCategorySettings('payments')['wc']['productId'];
    }

    /**
     * Validate appointment booking
     *
     * @param array $data
     *
     * @return string
     */
    private static function validateBooking($data)
    {
        try {
            if ($data) {
                /** @var CommandResult $result */
                $result = new CommandResult();

                /** @var ReservationServiceInterface $reservationService */
                $reservationService = self::$container->get('application.reservation.service')->get($data['type']);

                $data['bookings'][0]['customFields'] =
                    $data['bookings'][0]['customFields'] && is_array($data['bookings'][0]['customFields'])
                        ? json_encode($data['bookings'][0]['customFields']) : '';

                $reservation = $reservationService->getNew(true, false, true);

                $reservationService->processBooking($result, $data, $reservation, false);

                if ($result->getResult() === CommandResult::RESULT_ERROR) {
                    return self::getBookingErrorMessage($result, $data['type']);
                }

                return '';
            }

            return '';
        } catch (ContainerException $e) {
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get existing, or new created product id
     *
     * @param array $params
     * @return array
     */
    public static function getAllProducts($params)
    {
        $params = array_merge(['post_type' => 'product', 'posts_per_page' => -1], $params);

        $products = [];

        foreach (get_posts($params) as $product) {
            $products[] = [
                'id'   => $product->ID,
                'name' => $product->post_title,
            ];
        }

        return $products;
    }

    /**
     * Get initial products
     *
     * @return array
     */
    public static function getInitialProducts()
    {
        $products = self::getAllProducts(
            [
                'posts_per_page' => 50,
            ]
        );

        $product = self::getAllProducts(
            [
                'include' => self::getProductIdFromSettings()
            ]
        );

        if ($product && !in_array($product[0]['id'], array_column($products, 'id'))) {
            $products[] = $product[0];
        }

        return $products;
    }

    /**
     * Get existing, or new created product id
     *
     * @param $postId
     *
     * @return int|\WP_Error
     */
    public static function getIdForExistingOrNewProduct($postId)
    {
        if (!$postId || !in_array($postId, array_column(self::getAllProducts(['include' => [$postId]]), 'id'))) {
            $params = [
                'post_title'   => FrontendStrings::getCommonStrings()['wc_product_name'],
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'product',
            ];

            if (function_exists('get_current_user')) {
                $params['post_author'] = get_current_user();
            }

            $postId = wp_insert_post($params);


            wp_set_object_terms($postId, 'simple', 'product_type');
            wp_set_object_terms($postId, ['exclude-from-catalog', 'exclude-from-search'], 'product_visibility');
            update_post_meta($postId, '_visibility', 'hidden');
            update_post_meta($postId, '_stock_status', 'instock');
            update_post_meta($postId, 'total_sales', '0');
            update_post_meta($postId, '_downloadable', 'no');
            update_post_meta($postId, '_virtual', 'yes');
            update_post_meta($postId, '_regular_price', 0);
            update_post_meta($postId, '_sale_price', '');
            update_post_meta($postId, '_purchase_note', '');
            update_post_meta($postId, '_featured', 'no');
            update_post_meta($postId, '_weight', '');
            update_post_meta($postId, '_length', '');
            update_post_meta($postId, '_width', '');
            update_post_meta($postId, '_height', '');
            update_post_meta($postId, '_sku', '');
            update_post_meta($postId, '_product_attributes', array());
            update_post_meta($postId, '_sale_price_dates_from', '');
            update_post_meta($postId, '_sale_price_dates_to', '');
            update_post_meta($postId, '_price', 0);
            update_post_meta($postId, '_sold_individually', 'yes');
            update_post_meta($postId, '_manage_stock', 'no');
            update_post_meta($postId, '_backorders', 'no');
            update_post_meta($postId, '_stock', '');
        }

        return $postId;
    }

    /**
     * Fetch Taxes entities if not in cache
     *
     * @return Collection
     */
    private static function getTaxes()
    {
        if (Cache::getTaxes() === null) {
            self::fetchTaxesEntities();
        }

        return Cache::getTaxes();
    }

    /**
     * Fetch entity if not in cache
     *
     * @param $data
     *
     * @return array
     */
    private static function getEntity($data)
    {
        if (!Cache::get($data)) {
            self::populateCache([$data]);
        }

        return Cache::get($data);
    }

    /**
     * Fetch entities from DB and set them into cache
     *
     * @param array  $ameliaEntitiesIds
     */
    private static function populateCache($ameliaEntitiesIds)
    {
        $appointmentEntityIds = [];

        $eventEntityIds = [];

        $packageEntityIds = [];

        foreach ($ameliaEntitiesIds as $ids) {
            switch ($ids['type']) {
                case (Entities::APPOINTMENT):
                    $appointmentEntityIds[] = [
                        'serviceId'  => $ids['serviceId'],
                        'providerId' => $ids['providerId'],
                        'duration'   => !empty($ids['bookings'][0]['duration']) ? $ids['bookings'][0]['duration'] : null,
                        'couponId'   => !empty($ids['couponId']) ? $ids['couponId'] : null,
                        'persons'    => !empty($ids['bookings']) ? $ids['bookings'][0]['persons'] : 1,
                    ];

                    break;

                case (Entities::EVENT):
                    $eventEntityIds[] = [
                        'eventId'    => $ids['eventId'],
                        'couponId'   => $ids['couponId'],
                    ];
                    break;

                case (Entities::PACKAGE):
                    $packageEntityIds[] = [
                        'packageId'    => $ids['packageId'],
                        'couponId'     => $ids['couponId'],
                    ];
                    break;
            }
        }

        if ($appointmentEntityIds) {
            self::fetchAppointmentEntities($appointmentEntityIds);
        }

        if ($eventEntityIds) {
            self::fetchEventEntities($eventEntityIds);
        }

        if ($packageEntityIds) {
            self::fetchPackageEntities($packageEntityIds);
        }
    }

    /**
     * Fetch entities from DB and set them into cache
     */
    private static function fetchTaxesEntities()
    {
        /** @var TaxApplicationService $taxAS */
        $taxAS = self::$container->get('application.tax.service');

        /** @var SettingsService $settingsService */
        $settingsService = self::$container->get('domain.settings.service');

        $taxesSettings = $settingsService->getSetting(
            'payments',
            'taxes'
        );

        try {
            Cache::setTaxes($taxesSettings['enabled'] ? $taxAS->getAll() : new Collection());
        } catch (\Exception $e) {
        }
    }

    /**
     * Fetch entities from DB and set them into cache
     *
     * @param $ameliaEntitiesIds
     */
    private static function fetchEventEntities($ameliaEntitiesIds)
    {
        try {
            $bookings = [];

            /** @var EventApplicationService $eventApplicationService */
            $eventApplicationService = self::$container->get('application.booking.event.service');

            /** @var Event $event */
            $event = $eventApplicationService->getEventById(
                $ameliaEntitiesIds[0]['eventId'],
                [
                    'fetchEventsTickets'    => true,
                    'fetchEventsProviders'  => true,
                ]
            );

            $bookings[$event->getId()->getValue()] = [
                'bookable'   => [
                    'type'             => Entities::EVENT,
                    'name'             => $event->getName()->getValue(),
                    'translations'     => $event->getTranslations() ? $event->getTranslations()->getValue() : null,
                    'description'      => $event->getDescription() ? $event->getDescription()->getValue() : null,
                    'price'            => $event->getPrice()->getValue(),
                    'aggregatedPrice'  => $event->getAggregatedPrice() ? $event->getAggregatedPrice()->getValue() : true,
                    'recurringPayment' => 0,
                    'locationId'       => $event->getLocationId() ? $event->getLocationId()->getValue() : null,
                    'customLocation'   => $event->getCustomLocation() ? $event->getCustomLocation()->getValue() : null,
                    'providers'        => $event->getProviders()->length() ? $event->getProviders()->toArray() : [],
                    'depositPayment'   => $event->getDepositPayment()->getValue(),
                    'deposit'          => $event->getDeposit()->getValue(),
                    'depositPerPerson' => $event->getDepositPerPerson()->getValue(),
                    'customTickets'    => [],
                    'customPricing'    => $event->getCustomPricing() ? $event->getCustomPricing()->getValue() : false,
                ],
                'coupons'   => []
            ];

            /** @var EventApplicationService $eventApplicationService */
            $eventAS = self::$container->get('application.booking.event.service');

            if ($event->getCustomPricing()->getValue()) {
                $event->setCustomTickets($eventAS->getTicketsPriceByDateRange($event->getCustomTickets()));
            }

            /** @var Collection $customTickets */
            $customTickets = $event->getCustomTickets();

            /** @var EventTicket $customTicket */
            foreach ($customTickets->getItems() as $customTicket) {
                $bookings[$event->getId()->getValue()]['bookable']['customTickets'][$customTicket->getId()->getValue()] = [
                    'id'             => $customTicket->getId()->getValue(),
                    'price'          => $customTicket->getPrice()->getValue(),
                    'dateRangePrice' => $customTicket->getDateRangePrice() ?
                        $customTicket->getDateRangePrice()->getValue() : null,
                ];
            }

            if (!!$ameliaEntitiesIds[0]['couponId']) {
                /** @var CouponRepository $couponRepository */
                $couponRepository = self::$container->get('domain.coupon.repository');

                /** @var Coupon $coupon */
                $coupon = $couponRepository->getById($ameliaEntitiesIds[0]['couponId']);

                $bookings[$event->getId()->getValue()]['coupons'][$coupon->getId()->getValue()] = [
                    'deduction' => $coupon->getDeduction()->getValue(),
                    'discount'  => $coupon->getDiscount()->getValue(),
                ];
            }

            Cache::add(Entities::EVENT, $bookings);
        } catch (\Exception $e) {
        }
    }

    /**
     * Fetch entities from DB and set them into cache
     *
     * @param $ameliaEntitiesIds
     */
    private static function fetchPackageEntities($ameliaEntitiesIds)
    {
        try {
            /** @var PackageRepository $packageRepository */
            $packageRepository = self::$container->get('domain.bookable.package.repository');

            /** @var Coupon|null $coupon */
            $coupon = null;

            if (!!$ameliaEntitiesIds[0]['couponId']) {
                /** @var CouponRepository $couponRepository */
                $couponRepository = self::$container->get('domain.coupon.repository');

                $coupon = $couponRepository->getById($ameliaEntitiesIds[0]['couponId']);
            }

            /** @var Package $package */
            $package = $packageRepository->getById($ameliaEntitiesIds[0]['packageId']);

            $bookings = [];

            $bookings[$package->getId()->getValue()] = [
                'bookable'   => [
                    'type'             => Entities::PACKAGE,
                    'name'             => $package->getName()->getValue(),
                    'translations'     => $package->getTranslations() ? $package->getTranslations()->getValue() : null,
                    'description'      => $package->getDescription() ? $package->getDescription()->getValue() : null,
                    'price'            => $package->getPrice()->getValue(),
                    'discount'         => $package->getDiscount()->getValue(),
                    'calculatedPrice'  => $package->getCalculatedPrice()->getValue(),
                    'depositPayment'   => $package->getDepositPayment()->getValue(),
                    'deposit'          => $package->getDeposit()->getValue(),
                    'depositPerPerson' => null,
                ],
                'coupons'   => []
            ];

            if ($coupon) {
                $bookings[$package->getId()->getValue()]['coupons'][$coupon->getId()->getValue()] = [
                    'deduction' => $coupon->getDeduction()->getValue(),
                    'discount'  => $coupon->getDiscount()->getValue(),
                ];
            }

            Cache::add(Entities::PACKAGE, $bookings);
        } catch (\Exception $e) {
        }
    }


    /**
     * Fetch entities from DB and set them into cache
     *
     * @param $ameliaEntitiesIds
     */
    private static function fetchAppointmentEntities($ameliaEntitiesIds)
    {
        try {
            /** @var ProviderRepository $providerRepository */
            $providerRepository = self::$container->get('domain.users.providers.repository');

            /** @var Collection $providers */
            $providers = $providerRepository->getWithServicesAndExtras($ameliaEntitiesIds);

            /** @var Coupon|null $coupon */
            $coupon = null;

            if (!!$ameliaEntitiesIds[0]['couponId']) {
                /** @var CouponRepository $couponRepository */
                $couponRepository = self::$container->get('domain.coupon.repository');

                $coupon = $couponRepository->getById($ameliaEntitiesIds[0]['couponId']);
            }

            $bookings = [];

            foreach ((array)$providers->keys() as $providerKey) {
                /** @var Provider $provider */
                $provider = $providers->getItem($providerKey);

                /** @var Collection $services */
                $services = $provider->getServiceList();

                foreach ((array)$services->keys() as $serviceKey) {
                    /** @var Service $service */
                    $service = $services->getItem($serviceKey);

                    /** @var Collection $extras */
                    $extras = $service->getExtras();

                    $bookings[$providerKey][$serviceKey] = [
                        'firstName' => $provider->getFirstName()->getValue(),
                        'lastName'  => $provider->getLastName()->getValue(),
                        'email'     => $provider->getEmail()->getValue(),
                        'timeZone'  => $provider->getTimeZone() ? $provider->getTimeZone()->getValue() : null,
                        'bookable'   => [
                            'type'             => Entities::APPOINTMENT,
                            'id'               => $service->getId()->getValue(),
                            'name'             => $service->getName()->getValue(),
                            'price'            => $service->getPrice()->getValue(),
                            'aggregatedPrice'  => $service->getAggregatedPrice()->getValue(),
                            'recurringPayment' => $service->getRecurringPayment() ?
                                $service->getRecurringPayment()->getValue() : null,
                            'duration'         => !empty($ameliaEntitiesIds[0]['duration'])
                                ? $ameliaEntitiesIds[0]['duration']
                                : $service->getDuration()->getValue(),
                            'depositPayment'   => $service->getDepositPayment()->getValue(),
                            'deposit'          => $service->getDeposit()->getValue(),
                            'depositPerPerson' => $service->getDepositPerPerson()->getValue(),
                            'customPricing'    => $service->getCustomPricing()
                                ? $service->getCustomPricing()->getValue()
                                : null,
                        ],
                        'coupons'   => [],
                        'extras'    => []
                    ];

                    foreach ((array)$extras->keys() as $extraKey) {
                        /** @var Extra $extra */
                        $extra = $extras->getItem($extraKey);

                        $bookings[$providerKey][$serviceKey]['extras'][$extra->getId()->getValue()] = [
                            'price'           => $extra->getPrice()->getValue(),
                            'name'            => $extra->getName()->getValue(),
                            'aggregatedPrice' => $extra->getAggregatedPrice() ? $extra->getAggregatedPrice()->getValue() : null,
                        ];
                    }

                    if ($coupon) {
                        $bookings[$providerKey][$serviceKey]['coupons'][$coupon->getId()->getValue()] = [
                            'deduction' => $coupon->getDeduction()->getValue(),
                            'discount'  => $coupon->getDiscount()->getValue(),
                        ];
                    }
                }
            }

            Cache::add(Entities::APPOINTMENT, $bookings);
        } catch (\Exception $e) {
        }
    }

    /**
     * Process data for amelia cart items
     *
     * @param bool $inspectData
     */
    private static function processCart($inspectData)
    {
        $wooCommerceCart = self::getWooCommerceCart();

        if (!$wooCommerceCart) {
            return;
        }

        foreach ($wooCommerceCart->get_cart() as $wc_item) {
            if (!self::isCacheValid($wc_item)) {
                return;
            }
        }

        $ameliaEntitiesIds = [];

        if (!Cache::getAll()) {
            foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
                if (isset($wc_item[self::AMELIA]) && is_array($wc_item[self::AMELIA])) {
                    if (
                        $inspectData &&
                        empty($wc_item[self::AMELIA]['payment']['fromLink']) &&
                        ($errorMessage = self::validateBooking($wc_item[self::AMELIA]))
                    ) {
                        wc_add_notice(
                            $errorMessage . FrontendStrings::getCommonStrings()['wc_appointment_is_removed'],
                            'error'
                        );
                        $wooCommerceCart->remove_cart_item($wc_key);
                    }

                    $ameliaEntitiesIds[] = $wc_item[self::AMELIA];
                }
            }

            if ($ameliaEntitiesIds) {
                self::populateCache($ameliaEntitiesIds);
            }
        }

        if (!WC()->is_rest_api_request()) {
            $groupData = [];

            foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
                if (isset($wc_item[self::AMELIA]) && is_array($wc_item[self::AMELIA])) {
                    $key = isset($wc_item[self::AMELIA]['wcItemHash']) ? $wc_item[self::AMELIA]['wcItemHash'] : 0;

                    $groupData[$key] = 0;
                }
            }

            foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
                if (isset($wc_item[self::AMELIA]) && is_array($wc_item[self::AMELIA])) {
                    $key = isset($wc_item[self::AMELIA]['wcItemHash']) ? $wc_item[self::AMELIA]['wcItemHash'] : 0;

                    $product_price = self::getPaymentAmount($wc_item[self::AMELIA]);

                    $bookableData = self::getEntity($wc_item[self::AMELIA]);

                    $isCart = isset($wc_item[self::AMELIA]['isCart']) && is_string($wc_item[self::AMELIA]['isCart'])
                        ? filter_var($wc_item[self::AMELIA]['isCart'], FILTER_VALIDATE_BOOLEAN)
                        : !empty($wc_item[self::AMELIA]['isCart']);

                    if (
                        !$isCart &&
                        $wc_item[self::AMELIA]['type'] !== 'event' &&
                        $wc_item[self::AMELIA]['type'] !== 'package' &&
                        isset($bookableData['bookable']['recurringPayment']) &&
                        $bookableData['bookable']['recurringPayment'] !== null &&
                        $groupData[$key] > $bookableData['bookable']['recurringPayment']
                    ) {
                        $wc_item['data']->set_price(0);

                        break;
                    }

                    $groupData[$key]++;

                    /** @var \WC_Product $wc_item ['data'] */
                    $wc_item['data']->set_price($product_price >= 0 ? $product_price : 0);
                }
            }

            $wooCommerceCart->calculate_totals();
        }

        if (isset($wc_item[self::AMELIA]) && is_array($wc_item[self::AMELIA])) {
            wc_print_notices();
        }
    }

    /**
     * Add appointment booking to cart
     *
     * @param array $data
     *
     * @return boolean
     * @throws \Exception
     */
    public static function addToCart($data)
    {
        if (!$data) {
            return false;
        }

        $wooCommerceCart = self::getWooCommerceCart();

        if (!$wooCommerceCart) {
            return false;
        }

        do_action('AmeliaAddBookingToWcCart', $data);
        do_action('amelia_add_booking_to_wc_cart', $data);

        $wcSettings = self::$settingsService->getSetting('payments', 'wc');

        foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
            if (isset($wc_item[self::AMELIA])) {
                if (empty($wcSettings['bookMultiple'])) {
                    $wooCommerceCart->remove_cart_item($wc_key);
                }
            }
        }

        $defaultProductId = self::getProductIdFromSettings();

        $token = null;

        if (!empty($wcSettings['bookMultiple'])) {
            $token = new Token();

            $data['wcItemHash'] = $token->getValue();
        }

        $variationId = apply_filters('amelia_add_to_wc_cart_variation_id', '', $data) ?: '';
        $variationAttributes = apply_filters('amelia_add_to_wc_cart_variation_attributes', [], $data);

        $wooCommerceCart->add_to_cart(
            !empty($data['wcProductId']) ? $data['wcProductId'] : $defaultProductId,
            1,
            $variationId,
            $variationAttributes,
            [self::AMELIA => array_merge($data, ['recurring' => []])]
        );

        foreach ($data['recurring'] as $item) {
            $productId = !empty($item['wcProductId']) ? $item['wcProductId'] : $defaultProductId;

            $recurringData = array_merge($data, $item, ['recurring' => []]);

            $recurringData['bookings'][0]['utcOffset'] = $item['utcOffset'];

            $recurringData['bookings'][0]['extras'] = $item['extras'];

            $recurringData['dateTimeValues'] = $item['dateTimeValues'];

            $recurringData['bookings'][0]['persons'] = $item['persons'];

            $recurringData['bookings'][0]['duration'] = $item['duration'];

            $recurringData['bookings'][0]['customFields'] = $item['customFields'];

            $recurringData['couponId'] = $item['couponId'];

            $recurringData['couponCode'] = $item['couponCode'];

            $recurringData['bookings'][0]['deposit'] = $item['deposit'];

            if ($token && !empty($wcSettings['bookMultiple'])) {
                $recurringData['wcItemHash'] = $token->getValue();
            }

            $itemVariationId = apply_filters('amelia_add_to_wc_cart_variation_id', '', $item) ?: '';
            $itemVariationAttributes = apply_filters('amelia_add_to_wc_cart_variation_attributes', [], $item);

            $wooCommerceCart->add_to_cart(
                $productId ?: self::getProductIdFromSettings(),
                1,
                $itemVariationId,
                $itemVariationAttributes,
                [self::AMELIA => $recurringData]
            );
        }

        return true;
    }

    /**
     * Verifies the availability of all appointments that are in the cart
     */
    public static function beforeCartContents()
    {
        self::processCart(true);
    }

    /**
     * Get Booking Start in site locale
     *
     * @param $timeStamp
     *
     * @return string
     */
    private static function getBookingStartString($timeStamp)
    {
        $wooCommerceSettings = self::$settingsService->getCategorySettings('wordpress');

        return date_i18n($wooCommerceSettings['dateFormat'] . ' ' . $wooCommerceSettings['timeFormat'], $timeStamp);
    }

    /**
     * Get Booking Start in site locale
     *
     * @param array $dateStrings
     * @param int   $utcOffset
     * @param string   $type
     *
     * @return array
     */
    private static function getDateInfo($dateStrings, $utcOffset, $type)
    {
        $clientZoneBookingStart = null;

        $timeInfo = [];

        foreach ($dateStrings as $dateString) {
            $start = self::getBookingStartString(
                \DateTime::createFromFormat('Y-m-d H:i', substr($dateString['start'], 0, 16))->getTimestamp()
            );

            $end = $dateString['end'] && $type === Entities::EVENT ? $end = self::getBookingStartString(
                \DateTime::createFromFormat('Y-m-d H:i', substr($dateString['end'], 0, 16))->getTimestamp()
            ) : '';

            $timeInfo[] = '<strong>' . FrontendStrings::getCommonStrings()['time_colon'] . '</strong> '
                . $start . ($end ? ' - ' . $end : '');
        }

        foreach ($dateStrings as $dateString) {
            if ($utcOffset !== null) {
                $clientZoneStart = self::getBookingStartString(
                    DateTimeService::getClientUtcCustomDateTimeObject(
                        DateTimeService::getCustomDateTimeInUtc(substr($dateString['start'], 0, 16)),
                        $utcOffset
                    )->getTimestamp()
                );

                $clientZoneEnd = $dateString['end'] && $type === Entities::EVENT ? self::getBookingStartString(
                    DateTimeService::getClientUtcCustomDateTimeObject(
                        DateTimeService::getCustomDateTimeInUtc(substr($dateString['end'], 0, 16)),
                        $utcOffset
                    )->getTimestamp()
                ) : '';

                $utcString = '(UTC' . ($utcOffset < 0 ? '-' : '+') .
                    sprintf('%02d:%02d', floor(abs($utcOffset) / 60), abs($utcOffset) % 60) . ')';

                $timeInfo[] = '<strong>' . FrontendStrings::getCommonStrings()['client_time_colon'] . '</strong> '
                    . $utcString . $clientZoneStart . ($clientZoneEnd ? ' - ' . $clientZoneEnd : '');
            }
        }

        return $timeInfo;
    }

    /**
     * Get package labels.
     *
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    private static function getCartLabels($data)
    {
        $cartInfo = [];

        foreach (array_merge([$data], $data['recurring']) as $bookingData) {
            $booking = self::getEntity(
                array_merge(['type' => Entities::APPOINTMENT], $bookingData)
            );

            $serviceId = $booking['bookable']['id'];

            $cartInfo[$serviceId]['name'] =
                '<strong>' .
                self::$settingsService->getCategorySettings('labels')['service'] .
                ':</strong> ' .
                $booking['bookable']['name'];

            $cartInfo[$serviceId]['data'][] =
                '<strong>' .
                self::$settingsService->getCategorySettings('labels')['employee'] .
                ':</strong> ' .
                $booking['firstName'] . ' ' . $booking['lastName'];

            $timeInfo = self::getDateInfo(
                [
                    [
                        'start' => $bookingData['bookingStart'],
                        'end'   => $bookingData['bookingEnd'],
                    ]
                ],
                $data['bookings'][0]['utcOffset'],
                $data['type']
            );

            $cartInfo[$serviceId]['data'][] = $timeInfo[0];
        }

        $result = [
            '<br>',
            "<hr style='margin-top: 16px; margin-bottom: 10px'>",
            '<p>' . '<strong>' . FrontendStrings::getAllStrings()['appointments'] . ':</strong> ' . '</p>'
        ];

        foreach ($cartInfo as $serviceData) {
            $result[] = $serviceData['name'];

            foreach ($serviceData['data'] as $value) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Get package labels.
     *
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    private static function getPackageLabels($data)
    {
        $packageInfo = [];

        foreach ($data['package'] as $bookingData) {
            $booking = self::getEntity(
                array_merge(['type' => Entities::APPOINTMENT], $bookingData)
            );

            $serviceId = $booking['bookable']['id'];

            $packageInfo[$serviceId]['name'] =
                '<strong>' .
                self::$settingsService->getCategorySettings('labels')['service'] .
                ':</strong> ' .
                $booking['bookable']['name'];

            $packageInfo[$serviceId]['data'][] =
                '<strong>' .
                self::$settingsService->getCategorySettings('labels')['employee'] .
                ':</strong> ' .
                $booking['firstName'] . ' ' . $booking['lastName'];

            $timeInfo = self::getDateInfo(
                [
                    [
                        'start' => $bookingData['bookingStart'],
                        'end'   => $bookingData['bookingEnd'],
                    ]
                ],
                $data['bookings'][0]['utcOffset'],
                $data['type']
            );

            $packageInfo[$serviceId]['data'][] = $timeInfo[0];
        }

        $result = [
            '<br>',
            "<hr style='margin-top: 16px; margin-bottom: 10px'>",
            '<p>' . '<strong>' . FrontendStrings::getAllStrings()['package'] . ':</strong> ' . $data['name'] . '</p>'
        ];

        foreach ($packageInfo as $serviceId => $serviceData) {
            $result[] = $serviceData['name'];

            foreach ($serviceData['data'] as $value) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Get appointments labels.
     *
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    private static function getAppointmentLabels($data)
    {
        /** @var array $booking */
        $booking = self::getEntity($data);

        $bookableName = !empty($booking['bookable']['name']) ? $booking['bookable']['name'] : $data['name'];

        $providerFullName = !empty($booking['firstName']) && !empty($booking['lastName']) ?
            $booking['firstName'] . ' ' . $booking['lastName'] : '';

        return array_merge(
            ['<br>', "<hr style='margin-top: 16px; margin-bottom: 10px'>"],
            self::getDateInfo(
                $data['dateTimeValues'],
                $data['bookings'][0]['utcOffset'],
                $data['type']
            ),
            [
                '<strong>' . self::$settingsService->getCategorySettings('labels')['service']
                . ':</strong> ' . $bookableName,
                '<strong>' . self::$settingsService->getCategorySettings('labels')['employee']
                . ':</strong> ' . $providerFullName,
                '<strong>' . FrontendStrings::getCommonStrings()['total_number_of_persons'] . '</strong> '
                . $data['bookings'][0]['persons'],
            ]
        );
    }

    /**
     * Get event labels.
     *
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    private static function getEventLabels($data)
    {
        /** @var array $booking */
        $booking = self::getEntity($data);

        $bookableName = !empty($booking['bookable']['name']) ? $booking['bookable']['name'] : $data['name'];

        $ticketsData = [];

        if (!empty($data['bookings'][0]['ticketsData'])) {
            foreach ($data['bookings'][0]['ticketsData'] as $item) {
                if ($item['persons']) {
                    $ticketsData[] = $item['persons'] . ' x ' . $item['name'];
                }
            }
        }

        return array_merge(
            ['<br>', "<hr style='margin-top: 16px; margin-bottom: 10px'>"],
            self::getDateInfo(
                $data['dateTimeValues'],
                $data['bookings'][0]['utcOffset'],
                $data['type']
            ),
            [
                '<strong>' . FrontendStrings::getAllStrings()['event']
                . ':</strong> ' . $bookableName,
                !$ticketsData ? '<strong>' . FrontendStrings::getCommonStrings()['total_number_of_persons'] . '</strong> '
                . $data['bookings'][0]['persons'] :
                '<strong>' . BackendStrings::getCommonStrings()['event_tickets'] . ': ' . '</strong> '
                    . implode(', ', $ticketsData),
            ]
        );
    }

    /**
     * Set checkout block
     *
     * @param array $wc_item
     * @param array $metaData
     *
     * @return bool
     */
    public static function addCheckoutBlockValues(&$wc_item, $metaData)
    {
        if ((is_cart() || is_checkout()) && !is_wc_endpoint_url()) {
            $content = get_post(wc_get_page_id(is_checkout() ? 'checkout' : 'cart'))->post_content;

            if ($content && strpos($content, 'block') !== false) {
                $checkoutData = apply_filters(
                    'amelia_checkout_block_data',
                    [
                        'email'              => $wc_item[self::AMELIA]['bookings'][0]['customer']['email'],
                        'billing-first_name' => $wc_item[self::AMELIA]['bookings'][0]['customer']['firstName'],
                        'billing-last_name'  => $wc_item[self::AMELIA]['bookings'][0]['customer']['lastName'],
                        'billing-phone'      => $wc_item[self::AMELIA]['bookings'][0]['customer']['phone'] ?: '',
                    ],
                    self::$container,
                    $wc_item[self::AMELIA]
                );

                wp_enqueue_script(
                    'amelia_wc_checkout_block',
                    AMELIA_URL . 'public/js/wc/checkout.js',
                    [],
                    AMELIA_VERSION,
                    true
                );

                wp_localize_script(
                    'amelia_wc_checkout_block',
                    'ameliaCustomer',
                    $checkoutData
                );

                $wooCommerceCart = self::getWooCommerceCart();

                $index = null;

                $i = 0;

                foreach ($wooCommerceCart->get_cart() as $item_key => $item) {
                    if (isset($wc_item['key']) ? $item_key === $wc_item['key'] : $item === $wc_item) {
                        $index = $i;

                        break;
                    }

                    $i++;
                }

                wp_localize_script(
                    'amelia_wc_checkout_block',
                    "ameliaNote$index",
                    [$metaData]
                );

                return true;
            }
        }

        return false;
    }

    /**
     * Get item data for cart.
     *
     * @param $other_data
     * @param $wc_item
     *
     * @return array
     * @throws \Exception
     * @throws ContainerException
     */
    public static function getItemData($other_data, $wc_item)
    {
        return self::getItemDataLabels($other_data, $wc_item, false);
    }

    /**
     * Get item data for cart.
     *
     * @param $other_data
     * @param $wc_item
     * @param $forMetaData
     *
     * @return array
     * @throws \Exception
     * @throws ContainerException
     */
    public static function getItemDataLabels($other_data, $wc_item, $forMetaData)
    {
        if (!self::isCacheValid($wc_item)) {
            return $other_data;
        }

        if (isset($wc_item[self::AMELIA]) && is_array($wc_item[self::AMELIA])) {
            /** @var SettingsService $settingsService */
            $settingsService = self::$container->get('domain.settings.service');

            if (self::getWooCommerceCart() && !$settingsService->getSetting('payments', 'wc')['skipGetItemDataProcessing']) {
                self::processCart(false);
            }

            /** @var array $booking */
            $booking = self::getEntity($wc_item[self::AMELIA]);

            $customFieldsInfo = [];

            if (!is_array($wc_item[self::AMELIA]['bookings'][0]['customFields'])) {
                $wc_item[self::AMELIA]['bookings'][0]['customFields'] =
                    !empty($wc_item[self::AMELIA]['bookings'][0]['customFields']) ?
                    json_decode($wc_item[self::AMELIA]['bookings'][0]['customFields'], true) :
                    null;
            }

            foreach ((array)$wc_item[self::AMELIA]['bookings'][0]['customFields'] as $customField) {
                if (
                    !array_key_exists('type', $customField) ||
                    (array_key_exists('type', $customField) && $customField['type'] !== 'file')
                ) {
                    if (isset($customField['value']) && is_array($customField['value'])) {
                        $customFieldsInfo[] = '<b>' . $customField['label'] . ':</b> ' . implode(', ', $customField['value']);
                    } elseif (isset($customField['value'])) {
                        $customFieldsInfo[] = '<b>' . $customField['label'] . ':</b> ' . $customField['value'];
                    }
                }
            }


            $extrasInfo = [];

            foreach ((array)$wc_item[self::AMELIA]['bookings'][0]['extras'] as $index => $extra) {
                if (empty($booking['extras'][$extra['extraId']]['name'])) {
                    $extrasInfo[] = 'Extra' . $index . ' (x' . $extra['quantity'] . ')';
                } else {
                    $extrasInfo[] = $booking['extras'][$extra['extraId']]['name'] . ' (x' . $extra['quantity'] . ')';
                }
            }

            $couponUsed = [];

            if (!empty($wc_item[self::AMELIA]['couponId']) && !empty($wc_item[self::AMELIA]['couponCode'])) {
                $couponUsed = [
                    '<strong>' . FrontendStrings::getCommonStrings()['coupon_used'] . ':</strong> ' . $wc_item[self::AMELIA]['couponCode']
                ];
            }

            $bookableInfo = [];

            $bookableLabel = '';

            switch ($wc_item[self::AMELIA]['type']) {
                case Entities::APPOINTMENT:
                    $bookableInfo = self::getAppointmentLabels($wc_item[self::AMELIA]);

                    $bookableLabel = FrontendStrings::getCommonStrings()['appointment_info'];

                    break;

                case Entities::PACKAGE:
                    $bookableInfo = self::getPackageLabels($wc_item[self::AMELIA]);

                    $bookableLabel = FrontendStrings::getCommonStrings()['package_info'];

                    break;

                case Entities::EVENT:
                    $bookableInfo = self::getEventLabels($wc_item[self::AMELIA]);

                    $bookableLabel = FrontendStrings::getCommonStrings()['event_info'];

                    break;

                case Entities::CART:
                    $bookableInfo = self::getCartLabels($wc_item[self::AMELIA]);

                    $bookableLabel = FrontendStrings::getCommonStrings()['cart_info'];

                    break;
            }

            $recurringInfo = [];

            $isCart = isset($wc_item[self::AMELIA]['isCart']) && is_string($wc_item[self::AMELIA]['isCart'])
                ? filter_var($wc_item[self::AMELIA]['isCart'], FILTER_VALIDATE_BOOLEAN)
                : !empty($wc_item[self::AMELIA]['isCart']);

            $recurringItems = !empty($wc_item[self::AMELIA]['recurring']) && !$isCart
                ? $wc_item[self::AMELIA]['recurring'] : [];

            foreach ($recurringItems as $index => $recurringReservation) {
                $recurringInfo[] = self::getDateInfo(
                    [
                        [
                            'start' => $recurringReservation['bookingStart'],
                            'end'   => null
                        ]
                    ],
                    $wc_item[self::AMELIA]['bookings'][0]['utcOffset'],
                    $wc_item[self::AMELIA]['type']
                );
            }

            $recurringInfo = $recurringInfo ? array_column($recurringInfo, 1) : null;

            /** @var SettingsService $settingsService */
            $settingsService = self::$container->get('domain.settings.service');

            $wcSettings = $settingsService->getSetting('payments', 'wc');

            $metaData = '';

            /** @var HelperService $helperService */
            $helperService = self::$container->get('application.helper.service');

            $description = !empty($wcSettings['checkoutData'][$wc_item[self::AMELIA]['type']]) ?
                trim($wcSettings['checkoutData'][$wc_item[self::AMELIA]['type']]) : '';

            $description = str_replace('<!-- Content -->', '', $description);

            $description = str_replace("\n", "", $description);

            if (!empty($wcSettings['checkoutData']['translations'][$wc_item[self::AMELIA]['type']])) {
                $description = $helperService->getBookingTranslation(
                    $wc_item[self::AMELIA]['locale'],
                    json_encode($wcSettings['checkoutData']['translations']),
                    $wc_item[self::AMELIA]['type']
                ) ?: $description;
            }

            if ($booking && $description) {
                /** @var PlaceholderService|null $placeholderService */
                $placeholderService = null;

                $reservationData = [];

                $wc_item[self::AMELIA]['bookings'][0]['couponId'] = !empty($wc_item[self::AMELIA]['couponId'])
                    ? $wc_item[self::AMELIA]['couponId']
                    : null;

                if (
                    $wc_item[self::AMELIA]['type'] === Entities::APPOINTMENT ||
                    $wc_item[self::AMELIA]['type'] === Entities::EVENT
                ) {
                    $bookableData = self::getEntity($wc_item[self::AMELIA]);

                    $wc_item[self::AMELIA]['bookings'][0]['aggregatedPrice'] = $bookableData['bookable']['aggregatedPrice'];

                    if (!empty($wc_item[self::AMELIA]['bookings'][0]['extras'])) {
                        foreach ($wc_item[self::AMELIA]['bookings'][0]['extras'] as $extraItemKey => $extraItem) {
                            if (isset($bookableData['extras'][$extraItem['extraId']])) {
                                $wc_item[self::AMELIA]['bookings'][0]['extras'][$extraItemKey]['aggregatedPrice'] =
                                    $bookableData['extras'][$extraItem['extraId']]['aggregatedPrice'];
                            }
                        }
                    }
                }

                switch ($wc_item[self::AMELIA]['type']) {
                    case Entities::APPOINTMENT:
                        $placeholderService = self::$container->get('application.placeholder.appointment.service');

                        $reservation = AppointmentFactory::create($wc_item[self::AMELIA]);

                        $reservationData = $reservation->toArray();

                        $reservationData['recurring'] = [];

                        foreach ($wc_item[self::AMELIA]['recurring'] as $index => $recurringReservation) {
                            $reservationData['recurring'][] = [
                                'type'                => Entities::APPOINTMENT,
                                Entities::APPOINTMENT => array_merge(
                                    $reservationData,
                                    $recurringReservation,
                                    [
                                        'type' => Entities::APPOINTMENT,
                                        'bookings' => [
                                            array_merge(
                                                $wc_item[self::AMELIA]['bookings'][0],
                                                ['price' => 0]
                                            )
                                        ],
                                    ]
                                ),
                            ];
                        }

                        break;
                    case Entities::CART:
                        $placeholderService = self::$container->get("application.placeholder.appointments.service");

                        $reservation = AppointmentFactory::create($wc_item[self::AMELIA]);

                        $reservationData = $reservation->toArray();

                        $reservationData['customer'] = $wc_item[self::AMELIA]['bookings'][0]['customer'];

                        $reservationData['recurring'][] = [
                            'type'                => Entities::APPOINTMENT,
                            Entities::APPOINTMENT => array_merge(
                                $reservationData,
                                [
                                    'type' => Entities::APPOINTMENT,
                                    'bookings' => [
                                        array_merge(
                                            $wc_item[self::AMELIA]['bookings'][0],
                                            ['price' => 0]
                                        )
                                    ],
                                ]
                            ),
                        ];

                        foreach ($wc_item[self::AMELIA]['recurring'] as $recurringReservation) {
                            $reservationData['recurring'][] = [
                                'type'                => Entities::APPOINTMENT,
                                Entities::APPOINTMENT => array_merge(
                                    $reservationData,
                                    $recurringReservation,
                                    [
                                        'type' => Entities::APPOINTMENT,
                                        'bookings' => [
                                            array_merge(
                                                $wc_item[self::AMELIA]['bookings'][0],
                                                ['price' => 0]
                                            )
                                        ],
                                    ]
                                ),
                            ];
                        }

                        break;
                    case Entities::PACKAGE:
                        $placeholderService = self::$container->get('application.placeholder.package.service');

                        $reservation = PackageFactory::create(
                            array_merge(
                                $wc_item[self::AMELIA],
                                $booking['bookable']
                            )
                        );

                        $reservationData = $reservation->toArray();

                        $reservationData['customer'] = $wc_item[self::AMELIA]['customer'];

                        $reservationData['bookings'] = $wc_item[self::AMELIA]['bookings'];

                        $reservationData['recurring'] = [];

                        $info = json_encode(
                            [
                                'firstName' => $wc_item[self::AMELIA]['customer']['firstName'],
                                'lastName'  => $wc_item[self::AMELIA]['customer']['lastName'],
                                'phone'     => $wc_item[self::AMELIA]['customer']['phone'],
                                'locale'    => $wc_item[self::AMELIA]['locale'],
                                'timeZone'  => $wc_item[self::AMELIA]['timeZone'],
                            ]
                        );

                        foreach ($wc_item[self::AMELIA]['package'] as $index => $packageReservation) {
                            $reservationData['recurring'][] = [
                                'type'                => Entities::APPOINTMENT,
                                Entities::APPOINTMENT => array_merge(
                                    $packageReservation,
                                    [
                                        'type' => Entities::APPOINTMENT,
                                        'bookings' => [
                                            array_merge(
                                                $wc_item[self::AMELIA]['bookings'][0],
                                                [
                                                    'info'         => $info,
                                                    'utcOffset'    => $packageReservation['utcOffset'],
                                                    'price'        => 0,
                                                    'customFields' => $reservationData['bookings'][0]['customFields'] ?
                                                        json_encode($reservationData['bookings'][0]['customFields']) : ''
                                                ]
                                            )
                                        ],
                                    ]
                                ),
                            ];
                        }

                        $reservationData['bookings'][0]['info'] = $info;

                        break;
                    case Entities::EVENT:
                        $placeholderService = self::$container->get('application.placeholder.event.service');

                        $periods = [];

                        foreach ($wc_item[self::AMELIA]['dateTimeValues'] as $period) {
                            $periods[] = [
                                'periodStart' => $period['start'],
                                'periodEnd'   => $period['end'],
                            ];
                        }

                        $reservation = EventFactory::create(
                            array_merge(
                                self::getEntity($wc_item[self::AMELIA])['bookable'],
                                [
                                    'bookings' => [
                                        array_merge(
                                            $wc_item[self::AMELIA]['bookings'][0],
                                            ['status' => 'approved']
                                        )
                                    ],
                                    'periods'  => $periods
                                ]
                            )
                        );

                        $reservationData = $reservation->toArray();

                        break;
                }

                $reservationData['bookings'][0]['customFields'] =
                    $reservationData['bookings'][0]['customFields'] ?
                        (is_string($reservationData['bookings'][0]['customFields']) ?
                            $reservationData['bookings'][0]['customFields'] : json_encode($reservationData['bookings'][0]['customFields'])) : '';

                $reservationData['bookings'][0]['isChangedStatus'] = true;

                $reservationData['isForCustomer'] = true;

                if ($wc_item[self::AMELIA]['type'] === Entities::APPOINTMENT) {
                    /** @var Reservation $reservation */
                    $reservation = self::getReservation($wc_item[self::AMELIA]);

                    $reservationData['bookings'][0]['tax'] = $reservation->getBooking()->getTax()
                        ? $reservation->getBooking()->getTax()->getValue()
                        : null;

                    $reservationData['bookings'][0]['price'] = $reservation->getBookable()->getPrice()->getValue();

                    foreach ($reservationData['bookings'][0]['extras'] as &$bookingExtra) {
                        foreach ($reservation->getBooking()->getExtras()->getItems() as $extra) {
                            if ($bookingExtra['extraId'] === $extra->getExtraId()->getValue()) {
                                $bookingExtra['price'] = $extra->getPrice()->getValue();

                                $bookingExtra['tax'] = $extra->getTax() ? $extra->getTax()->getValue() : null;
                            }
                        }
                    }
                }

                $placeholderData = $placeholderService->getPlaceholdersData(
                    $reservationData,
                    0,
                    'email',
                    UserFactory::create($wc_item[self::AMELIA]['bookings'][0]['customer'])
                );

                $placeholderData['customer_firstName'] = $wc_item[self::AMELIA]['bookings'][0]['customer']['firstName'];

                $placeholderData['customer_lastName'] = $wc_item[self::AMELIA]['bookings'][0]['customer']['lastName'];

                $placeholderData['customer_fullName'] = $placeholderData['customer_firstName'] . ' ' . $placeholderData['customer_lastName'];

                $placeholderData['customer_email'] = $wc_item[self::AMELIA]['bookings'][0]['customer']['email'];

                $placeholderData['customer_phone'] = $wc_item[self::AMELIA]['bookings'][0]['customer']['phone'];

                $descriptionParts = strpos($description, '<p>') !== false ? explode('<p', $description) : [];

                foreach ($descriptionParts as $index => $part) {
                    if (($position = strpos($part, '%custom_field_')) !== false) {
                        $value = substr(
                            substr($part, $position + 14),
                            0
                        );

                        $id = substr($value, 0, strpos($value, '%'));

                        if (
                            isset($placeholderData['custom_field_' . $id]) &&
                            !$placeholderData['custom_field_' . $id]
                        ) {
                            $descriptionParts[$index] = ' class="am-cf-empty"' . $descriptionParts[$index];
                        }
                    }
                }

                $description = $descriptionParts ? implode('<p', $descriptionParts) : $description;

                $paymentDueAmount = self::getPaymentAmount($wc_item[self::AMELIA], false);

                if (self::hasDeposit($wc_item[self::AMELIA])) {
                    $paymentDueAmount = $paymentDueAmount - self::getPaymentAmount($wc_item[self::AMELIA]);
                }

                $placeholderData["payment_due_amount"] = $paymentDueAmount < 0
                    ? $helperService->getFormattedPrice(0)
                    : $helperService->getFormattedPrice($paymentDueAmount);

                $placeholderData["{$wc_item[self::AMELIA]['type']}_deposit_payment"] = self::hasDeposit($wc_item[self::AMELIA])
                    ? $helperService->getFormattedPrice(self::getPaymentAmount($wc_item[self::AMELIA]))
                    : $helperService->getFormattedPrice(0);

                $metaData = $placeholderService->applyPlaceholders(
                    $description,
                    $placeholderData
                );
            }

            if (!$forMetaData && $metaData && self::addCheckoutBlockValues($wc_item, [$metaData])) {
                $other_data[] = [
                    'name'  => '',
                    'value' => '',
                ];

                return $other_data;
            }

            $other_data[] = [
                'name'  => $bookableLabel,
                'value' => $metaData ? $metaData : implode(
                    PHP_EOL . PHP_EOL,
                    array_merge(
                        $bookableInfo,
                        $extrasInfo ? array_merge(
                            [
                                '<strong>' . FrontendStrings::getCatalogStrings()['extras'] . ':</strong>'
                            ],
                            $extrasInfo
                        ) : [],
                        $customFieldsInfo ? array_merge(
                            [
                                '<strong>' . FrontendStrings::getCommonStrings()['custom_fields'] . ':</strong>'
                            ],
                            $customFieldsInfo
                        ) : [],
                        $couponUsed,
                        $recurringInfo ? array_merge(
                            [
                                '<strong>' . FrontendStrings::getBookingStrings()['recurring_appointments'] . ':</strong>'
                            ],
                            $recurringInfo
                        ) : []
                    )
                )
            ];
        }

        return $other_data;
    }

    /**
     * Get payment amount for reservation
     *
     * @param array $wcItemAmeliaCache
     * @param bool  $applyDeposit
     *
     * @return Reservation
     * @throws InvalidArgumentException
     */
    private static function getReservation($wcItemAmeliaCache, $applyDeposit = true)
    {
        $bookableData = self::getEntity($wcItemAmeliaCache);

        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = self::$container->get('application.booking.appointment.service');

        /** @var TaxApplicationService $taxAS */
        $taxAS = self::$container->get('application.tax.service');

        /** @var Collection $taxes */
        $taxes = self::getTaxes();

        /** @var Coupon $coupon */
        $coupon = !empty($bookableData['coupons'][$wcItemAmeliaCache['couponId']])
            ? CouponFactory::create($bookableData['coupons'][$wcItemAmeliaCache['couponId']])
            : null;

        /** @var Reservation $reservation */
        $reservation = new Reservation();

        /** @var AbstractBookable|null $bookable */
        $bookable = null;

        switch ($wcItemAmeliaCache['type']) {
            case (Entities::APPOINTMENT):
                /** @var Service $bookable */
                $bookable = ServiceFactory::create(
                    [
                        'price'            => $bookableData['bookable']['price'],
                        'aggregatedPrice'  => isset($bookableData['bookable']['aggregatedPrice'])
                            ? $bookableData['bookable']['aggregatedPrice'] : 1,
                        'deposit'          => $bookableData['bookable']['deposit'],
                        'depositPayment'   => $bookableData['bookable']['depositPayment'],
                        'depositPerPerson' => $bookableData['bookable']['depositPerPerson'],
                        'extras'           => $bookableData['extras'],
                        'customPricing'    => $bookableData['bookable']['customPricing'],
                    ]
                );

                $bookable->setPrice(
                    new Price(
                        $appointmentAS->getBookingPriceForService(
                            $bookable,
                            CustomerBookingFactory::create(
                                [
                                    'duration' => $wcItemAmeliaCache['bookings'][0]['duration'],
                                    'persons'  => $wcItemAmeliaCache['bookings'][0]['persons'],
                                ]
                            ),
                            $appointmentAS->isPeriodCustomPricing($bookable)
                                ? UserFactory::create([
                                    'type'      => AbstractUser::USER_ROLE_PROVIDER,
                                    'firstName' => $bookableData['firstName'],
                                    'lastName'  => $bookableData['lastName'],
                                    'email'     => $bookableData['email'],
                                    'timeZone'  => $bookableData['timeZone'],
                                ])
                                : null,
                            $wcItemAmeliaCache['bookingStart']
                        )
                    )
                );

                /** @var CustomerBookingExtra $extra */
                foreach ($wcItemAmeliaCache['bookings'][0]['extras'] as $key => $extra) {
                    $wcItemAmeliaCache['bookings'][0]['extras'][$key]['price'] = $bookableData['extras'][$extra['extraId']]['price'];

                    $wcItemAmeliaCache['bookings'][0]['extras'][$key]['tax'] = $taxAS->getTaxData(
                        $extra['extraId'],
                        Entities::EXTRA,
                        $taxes
                    );
                }

                /** @var CustomerBooking $booking */
                $booking = CustomerBookingFactory::create(
                    [
                        'persons'         => $wcItemAmeliaCache['bookings'][0]['persons'],
                        'coupon'          => $coupon ? $coupon->toArray() : null,
                        'extras'          => $wcItemAmeliaCache['bookings'][0]['extras'],
                        'aggregatedPrice' => isset($bookableData['bookable']['aggregatedPrice'])
                            ? $bookableData['bookable']['aggregatedPrice'] : 1,
                        'duration'        => !empty($wcItemAmeliaCache['bookings'][0]['duration']) ?
                            $wcItemAmeliaCache['bookings'][0]['duration'] : null,
                        'tax'             => $taxAS->getTaxData(
                            $wcItemAmeliaCache['serviceId'],
                            Entities::SERVICE,
                            $taxes
                        ),
                    ]
                );

                $reservation->setBooking($booking);

                $reservation->setRecurring(new Collection());

                break;

            case (Entities::EVENT):
                $customTickets = !empty($bookableData['bookable']['customTickets']) && !empty($bookableData['bookable']['customPricing'])
                    ? $bookableData['bookable']['customTickets']
                    : [];

                $eventCustomPricing = [];

                foreach ($customTickets as $key => $customTicket) {
                    $eventCustomPricing[$key] = [
                        'dateRanges' => '[]',
                        'price'      => !empty($customTicket['dateRangePrice'])
                            ? $customTicket['dateRangePrice'] : $customTicket['price'],
                    ];
                }

                /** @var Event $bookable */
                $bookable = EventFactory::create(
                    [
                        'price'            => $bookableData['bookable']['price'],
                        'aggregatedPrice'  => $bookableData['bookable']['aggregatedPrice'],
                        'deposit'          => $bookableData['bookable']['deposit'],
                        'depositPayment'   => $bookableData['bookable']['depositPayment'],
                        'depositPerPerson' => $bookableData['bookable']['depositPerPerson'],
                        'customPricing'    => !empty($eventCustomPricing),
                        'customTickets'    => !empty($eventCustomPricing) ? $eventCustomPricing : null,
                    ]
                );

                /** @var CustomerBooking $booking */
                $booking = CustomerBookingFactory::create(
                    [
                        'persons'      => $wcItemAmeliaCache['bookings'][0]['persons'],
                        'aggregatedPrice' => isset($bookableData['bookable']['aggregatedPrice'])
                            ? $bookableData['bookable']['aggregatedPrice'] : 1,
                        'ticketsData'  => $wcItemAmeliaCache['bookings'][0]['ticketsData'],
                        'coupon'       => $coupon ? $coupon->toArray() : null,
                        'tax'          => $taxAS->getTaxData(
                            $wcItemAmeliaCache['eventId'],
                            Entities::EVENT,
                            $taxes
                        ),
                    ]
                );

                $reservation->setBooking($booking);

                break;

            case (Entities::PACKAGE):
                /** @var Package $bookable */
                $bookable = PackageFactory::create(
                    [
                        'price'            => $bookableData['bookable']['price'],
                        'deposit'          => $bookableData['bookable']['deposit'],
                        'depositPayment'   => $bookableData['bookable']['depositPayment'],
                        'depositPerPerson' => $bookableData['bookable']['depositPerPerson'],
                        'calculatedPrice'  => $bookableData['bookable']['calculatedPrice'],
                        'discount'         => $bookableData['bookable']['discount'],
                    ]
                );

                /** @var PackageCustomer $packageCustomer */
                $packageCustomer = PackageCustomerFactory::create(
                    [
                        'packageId' => $wcItemAmeliaCache['packageId'],
                        'coupon'    => $coupon ? $coupon->toArray() : null,
                        'tax'      => $taxAS->getTaxData(
                            $wcItemAmeliaCache['packageId'],
                            Entities::PACKAGE,
                            $taxes
                        ),
                    ]
                );

                $reservation->setPackageCustomer($packageCustomer);

                break;
        }

        $reservation->setApplyDeposit(
            new BooleanValueObject(
                $applyDeposit &&
                $bookableData['bookable']['depositPayment'] !== DepositType::DISABLED &&
                self::hasDeposit($wcItemAmeliaCache)
            )
        );

        $reservation->setBookable($bookable);

        return $reservation;
    }

    /**
     * Get payment amount for reservation
     *
     * @param array $wcItemAmeliaCache
     * @param bool  $applyDeposit
     *
     * @return float
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    private static function getPaymentAmount($wcItemAmeliaCache, $applyDeposit = true)
    {
        $bookableData = self::getEntity($wcItemAmeliaCache);

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = self::$container->get('application.reservation.service')->get($wcItemAmeliaCache['type']);

        /** @var Reservation $reservation */
        $reservation = self::getReservation($wcItemAmeliaCache, $applyDeposit);

        $paymentAmount = $reservationService->getReservationPaymentAmount($reservation);

        return apply_filters('amelia_get_modified_price', $paymentAmount, $wcItemAmeliaCache, $bookableData);
    }

    /**
     * @param $wcItemAmeliaCache
     *
     * @return bool
     */
    private static function hasDeposit($wcItemAmeliaCache)
    {
        switch ($wcItemAmeliaCache['type']) {
            case (Entities::APPOINTMENT):
            case (Entities::EVENT):
                return !empty($wcItemAmeliaCache['bookings'][0]['deposit']);

            case (Entities::PACKAGE):
                return !empty($wcItemAmeliaCache['deposit']);
        }

        return false;
    }

    /**
     * Get cart item price.
     *
     * @param $product_price
     * @param $wc_item
     * @param $cart_item_key
     *
     * @return mixed
     */
    public static function cartItemPrice($product_price, $wc_item, $cart_item_key)
    {
        if (!self::isCacheValid($wc_item)) {
            return $product_price;
        }

        if (isset($wc_item[self::AMELIA]) && is_array($wc_item[self::AMELIA])) {
            $product_price = wc_price(self::getPaymentAmount($wc_item[self::AMELIA]));
        }

        return $product_price >= 0 ? $product_price : 0;
    }

    /**
     * Assign checkout value from appointment.
     *
     * @param $null
     * @param $field_name
     *
     * @return string|null
     */
    public static function checkoutGetValue($null, $field_name)
    {
        $wooCommerceCart = self::getWooCommerceCart();

        if (!$wooCommerceCart) {
            return null;
        }

        /** @var SettingsService $settingsService */
        $settingsService = self::$container->get('domain.settings.service');

        if (!$settingsService->getSetting('payments', 'wc')['skipCheckoutGetValueProcessing']) {
            self::processCart(false);
        }

        if (empty(self::$checkout_info)) {
            if (!WC()->is_rest_api_request()) {
                foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
                    if (array_key_exists(self::AMELIA, $wc_item) && is_array($wc_item[self::AMELIA])) {
                        self::$checkout_info = apply_filters(
                            'amelia_checkout_data',
                            [
                                'billing_first_name' => $wc_item[self::AMELIA]['bookings'][0]['customer']['firstName'],
                                'billing_last_name'  => $wc_item[self::AMELIA]['bookings'][0]['customer']['lastName'],
                                'billing_email'      => $wc_item[self::AMELIA]['bookings'][0]['customer']['email'],
                                'billing_phone'      => $wc_item[self::AMELIA]['bookings'][0]['customer']['phone'],
                            ],
                            self::$container,
                            $wc_key
                        );

                        break;
                    }
                }
            }
        }

        if (array_key_exists($field_name, self::$checkout_info)) {
            return self::$checkout_info[$field_name];
        }

        return null;
    }

    /**
     * Checkout Create Order Line Item.
     *
     * @param $item
     * @param $cart_item_key
     * @param $values
     * @param $order
     * @throws ContainerException
     */
    public static function checkoutCreateOrderLineItem($item, $cart_item_key, $values, $order)
    {
        if (isset($values[self::AMELIA]) && is_array($values[self::AMELIA])) {
            $item->update_meta_data(
                self::AMELIA,
                array_merge(
                    $values[self::AMELIA],
                    [
                        'labels' => self::getLabels($values[self::AMELIA])
                    ]
                )
            );
        }
    }

    /**
     * Update Order Item Meta data.
     *
     * @param int   $orderId
     * @param int   $orderItemId
     * @param array $reservation
     * @throws ContainerException
     */
    public static function updateItemMetaData($orderId, $orderItemId, $reservation)
    {
        $order = wc_get_order($orderId);

        if ($order) {
            foreach ($order->get_items() as $itemId => $orderItem) {
                $data = wc_get_order_item_meta($itemId, 'ameliabooking');

                if ($data && is_array($data) && (!$orderItemId || $orderItemId === $itemId)) {
                    wc_update_order_item_meta(
                        $itemId,
                        self::AMELIA,
                        array_merge(
                            $data,
                            [
                                'labels' => WooCommerceService::getLabels($reservation)
                            ]
                        )
                    );
                }
            }
        }
    }

    /**
     * Print appointment details inside order items in the backend.
     *
     * @param int $item_id
     * @throws ContainerException
     */
    public static function orderItemMeta($item_id, $item, $order)
    {
        $data = wc_get_order_item_meta($item_id, self::AMELIA);

        if (!empty($data['labels'])) {
            echo $data['labels'];
        } else {
            echo self::getLabels($data);
        }
    }

    /**
     * Get labels to print
     *
     * @param array $data
     * @return string
     * @throws ContainerException
     */
    public static function getLabels($data)
    {
        if ($data && is_array($data)) {
            $other_data = self::getItemDataLabels([], [self::AMELIA => array_merge($data, ['recurring'  => []])], true);

            if (empty($data['payment']['fromLink'])) {
                $labels = strpos($other_data[0]['value'], '<br>') !== false ?
                    preg_replace("/\r|\n/", '<br>', $other_data[0]['value']) :
                    $other_data[0]['value'];
            } else {
                $labels = str_replace("<br>", '', $other_data[0]['value']);
                $labels = str_replace("\n\n<hr", '<hr', $labels);
                $labels = str_replace("10px'>\n\n", "10px'>", $labels);
            }

            $labels = str_replace("\n", '', $labels);

            $labels = str_replace('<p><br></p>', '<br>', $labels);

            return '<br/>' . $other_data[0]['name'] . '<br/>' . nl2br($labels);
        }

        return '';
    }

    /**
     * Before checkout process
     *
     * @param $array
     * @param $data
     *
     * @throws \Exception
     */
    public static function beforeCheckoutProcess($array, $data = null)
    {
        $wooCommerceCart = self::getWooCommerceCart();

        if (!$wooCommerceCart) {
            return;
        }

        foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
            if (isset($wc_item[self::AMELIA]) && is_array($wc_item[self::AMELIA]) && empty($wc_item[self::AMELIA]['payment']['fromLink'])) {
                if ($errorMessage = self::validateBooking($wc_item[self::AMELIA])) {
                    $cartUrl = self::getPageUrl($wc_item[self::AMELIA]);
                    $removeAppointmentMessage = FrontendStrings::getCommonStrings()['wc_appointment_is_removed'];

                    throw new \Exception($errorMessage . "<a href='{$cartUrl}'>{$removeAppointmentMessage}</a>");
                }
            }
        }
    }

    /**
     * @param Payment $payment
     * @param string $type
     * @param $order
     *
     * @throws BookingCancellationException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    private static function updateBookingStatus($payment, $type, $order)
    {
        /** @var ReservationServiceInterface $reservationService */
        $reservationService = self::$container->get('application.reservation.service')->get($type);

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = self::$container->get('domain.payment.repository');

        $paymentStatus =  $reservationService->getWcStatus(
            $type,
            $order->get_status(),
            'payment',
            true
        ) ?: PaymentStatus::PAID;

        if ($paymentStatus !== false) {
            if (
                $payment->getStatus()->getValue() === PaymentStatus::PARTIALLY_PAID &&
                $paymentStatus === 'paid'
            ) {
                $paymentStatus = PaymentStatus::PARTIALLY_PAID;
            }

            $paymentRepository->updateFieldById(
                $payment->getId()->getValue(),
                $paymentStatus,
                'status'
            );
        }

        $requestedStatus = $reservationService->getWcStatus(
            $type,
            $order->get_status(),
            'booking',
            true
        );

        if ($requestedStatus !== false) {
            switch ($type) {
                case (Entities::APPOINTMENT):
                    self::bookingAppointmentUpdated($payment, $requestedStatus, true);
                    break;

                case (Entities::EVENT):
                    self::bookingEventUpdated($payment, $requestedStatus, true);
                    break;

                case (Entities::PACKAGE):
                    self::bookingPackageUpdated($payment, $requestedStatus, true);
                    break;
            }
        }
    }


    /**
     * @param Payment $payment
     * @param string  $requestedStatus
     * @param bool    $runActions
     *
     * @throws BookingCancellationException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    private static function bookingAppointmentUpdated($payment, $requestedStatus, $runActions)
    {
        /** @var ReservationServiceInterface $reservationService */
        $reservationService = self::$container->get('application.reservation.service')->get(Entities::APPOINTMENT);

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = self::$container->get('domain.booking.customerBooking.repository');

        /** @var CustomerBooking $booking */
        $booking = $bookingRepository->getById($payment->getCustomerBookingId()->getValue());

        $bookingData = $reservationService->updateStatus($booking, $requestedStatus);

        if ($runActions) {
            $result = new CommandResult();

            $result->setData(
                [
                    Entities::APPOINTMENT          => $bookingData[Entities::APPOINTMENT],
                    'appointmentStatusChanged'     => $bookingData['appointmentStatusChanged'],
                    'appointmentRescheduled'       => false,
                    'bookingsWithChangedStatus'    => [$bookingData[Entities::BOOKING]],
                    'appointmentEmployeeChanged'   => null,
                    'appointmentZoomUserChanged'   => false,
                    'appointmentZoomUsersLicenced' => false,
                    'lessonSpaceChanged'           => false,
                ]
            );

            AppointmentEditedEventHandler::handle($result, self::$container);
        }
    }

    /**
     * @param Payment $payment
     * @param string  $requestedStatus
     * @param bool    $runActions
     *
     * @throws BookingCancellationException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    private static function bookingEventUpdated($payment, $requestedStatus, $runActions)
    {
        /** @var ReservationServiceInterface $reservationService */
        $reservationService = self::$container->get('application.reservation.service')->get(Entities::EVENT);

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = self::$container->get('domain.booking.customerBooking.repository');

        /** @var CustomerBooking $booking */
        $booking = $bookingRepository->getById($payment->getCustomerBookingId()->getValue());

        $bookingData = $reservationService->updateStatus($booking, $requestedStatus);

        if ($runActions) {
            $result = new CommandResult();

            $result->setData(
                [
                    'type'                 => Entities::EVENT,
                    Entities::EVENT        => $bookingData[Entities::EVENT],
                    Entities::BOOKING      => $bookingData[Entities::BOOKING],
                    'bookingStatusChanged' => true,
                ]
            );

            BookingEditedEventHandler::handle($result, self::$container);
        }
    }

    /**
     * @param Payment $payment
     * @param string  $requestedStatus
     * @param bool    $runActions
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    private static function bookingPackageUpdated($payment, $requestedStatus, $runActions)
    {
        /** @var PackageCustomerRepository $packageCustomerRepository */
        $packageCustomerRepository = self::$container->get('domain.bookable.packageCustomer.repository');

        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = self::$container->get('domain.bookable.packageCustomerService.repository');

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = self::$container->get('domain.booking.appointment.repository');

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = self::$container->get('domain.booking.customerBooking.repository');

        $packageCustomerRepository->updateFieldById(
            $payment->getPackageCustomerId()->getValue(),
            $requestedStatus,
            'status'
        );

        if ($requestedStatus === 'canceled') {
            /** @var Collection $packageCustomerServices */
            $packageCustomerServices = $packageCustomerServiceRepository->getByCriteria(
                [
                    'packageCustomerIds' => [$payment->getPackageCustomerId()->getValue()],
                ]
            );

            /** @var Collection $packageAppointments */
            $packageAppointments = $packageCustomerServices->length() ?
                $appointmentRepository->getFiltered(['packageCustomerServices' => $packageCustomerServices->keys()]) : new Collection();

            if ($packageAppointments) {
                /** @var Collection $appointments */
                $appointments = $appointmentRepository->getFiltered(['ids' => $packageAppointments->keys()]);
                /** @var Appointment $appointment */
                foreach ($appointments->getItems() as $appointment) {
                    /** @var CustomerBooking $booking */
                    foreach ($appointment->getBookings()->getItems() as $booking) {
                        if (
                            $booking->getPackageCustomerService() &&
                            in_array(
                                $booking->getPackageCustomerService()->getId()->getValue(),
                                $packageCustomerServices->keys()
                            )
                        ) {
                            $approvedOrPendingBookings = array_filter($appointment->getBookings()->toArray(), function ($booking) {
                                return $booking['status'] === 'approved' || $booking['status'] === 'pending';
                            });

                            $bookingRepository->updateFieldById($booking->getId()->getValue(), 'canceled', 'status');
                            if (count($approvedOrPendingBookings) === 1) {
                                $appointmentRepository->updateFieldById($appointment->getId()->getValue(), 'canceled', 'status');
                            }

                            break;
                        }
                    }
                }
            }
        }

        if ($runActions) {
            $result = new CommandResult();

            $result->setData(
                [
                    'packageCustomerId' => $payment->getPackageCustomerId()->getValue(),
                    'status'            => $requestedStatus,
                ]
            );

            PackageCustomerUpdatedEventHandler::handle($result, self::$container);
        }
    }

    /**
     * @param $orderId
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    public static function redirectAfterOrderReceived($orderId, $order = null)
    {
        $order = new \WC_Order($orderId);

        /** @var SettingsService $settingsService */
        $settingsService = self::$container->get('domain.settings.service');

        $updatedOrder = false;

        if (!$order->has_status('failed')) {
            if (!self::isAmeliaOrderProcessed($order)) {
                self::completeBookings($order);
            }

            foreach ($order->get_items() as $itemId => $orderItem) {
                $data = wc_get_order_item_meta($itemId, self::AMELIA);

                // add created user to WooCommerce order if WooCommerce didn't create user but Amelia Customer has WordPress user
                if (
                    !$updatedOrder &&
                    $data &&
                    is_array($data) &&
                    !empty($data['externalId']) &&
                    $settingsService->getSetting('roles', 'automaticallyCreateCustomer')
                ) {
                    update_post_meta(
                        $order->get_id(),
                        '_customer_user',
                        $data['externalId']
                    );

                    $updatedOrder = true;
                }

                $wcSettings = self::$settingsService->getSetting('payments', 'wc');

                if (
                    $data && is_array($data) &&
                    isset($data['processed'], $wcSettings['redirectPage']) &&
                    $wcSettings['redirectPage'] === 2
                ) {
                    if (isset($data['payment']['fromLink']) && $data['payment']['fromLink']) {
                        $redirectUrl = $data['redirectUrl'] ?: self::$settingsService->getSetting('payments', 'paymentLinks')['redirectUrl'];
                        $redirectUrl = empty($redirectUrl) ? AMELIA_SITE_URL : $redirectUrl;

                        wp_redirect($redirectUrl);

                        exit;
                    } else {
                        $token = new Token();

                        $identifier = $orderId . '_' . $token->getValue() . '_' . $data['type'];

                        $urlParts = explode('#', $data['returnUrl']);

                        wp_safe_redirect($urlParts[0] . (strpos($urlParts[0], '?') ? '&' : '?')
                            . 'ameliaWcCache=' . $identifier
                            . (!empty($urlParts[1]) ? ('#' . $urlParts[1]) : ''));

                        exit;
                    }
                }
            }
        }
    }

    /**
     * @param $orderId
     *
     * @return array|null
     * @throws ContainerException
     */
    public static function getCacheData($orderId)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = self::$container->get('domain.payment.repository');

        $order = null;

        try {
            $order = new \WC_Order($orderId);
        } catch (\Exception $e) {
        }

        if ($order && !$order->has_status('failed')) {
            foreach ($order->get_items() as $itemId => $orderItem) {
                $data = wc_get_order_item_meta($itemId, self::AMELIA);

                if ($data && is_array($data) && isset($data['processed'])) {
                    try {
                        /** @var Collection $payments */
                        $payments = $paymentRepository->getByEntityId($orderId, 'wcOrderId');

                        /** @var Payment $payment */
                        $payment = $payments->length() ? $payments->getItem(0) : null;

                        if ($payment) {
                            /** @var ReservationServiceInterface $reservationService */
                            $reservationService = self::$container->get('application.reservation.service')->get(
                                $payment->getEntity()->getValue()
                            );

                            $reservationData = $reservationService->getReservationByPayment($payment)->getData();

                            $reservationData['appointmentStatusChanged'] = $data['appointmentStatusChanged'];

                            if ($payment->getEntity()->getValue() === Entities::PACKAGE) {
                                $reservationData = array_merge(
                                    $reservationData,
                                    [
                                        'type'    => Entities::PACKAGE,
                                        'package' => array_merge(
                                            $reservationData[Entities::APPOINTMENT] && $reservationData[Entities::BOOKING] ? [
                                                [
                                                    'type'                     => Entities::APPOINTMENT,
                                                    Entities::APPOINTMENT      => $reservationData[Entities::APPOINTMENT],
                                                    Entities::BOOKING          => $reservationData[Entities::BOOKING],
                                                    'appointmentStatusChanged' => $reservationData['appointmentStatusChanged'],
                                                    'utcTime'                  => $reservationData['utcTime']
                                                ]
                                            ] : [],
                                            $reservationData['recurring']
                                        )
                                    ]
                                );

                                $reservationData['appointmentStatusChanged'] = false;

                                $reservationData['recurring'] = [];

                                unset($reservationData[Entities::APPOINTMENT]);
                                unset($reservationData[Entities::BOOKING]);
                            }

                            $cacheData = json_decode($data['cacheData'], true);


                            if (
                                !empty($cacheData['request']['state']['appointment']['bookings'][0]['customer']) &&
                                !empty($data['bookings'][0]['customer'])
                            ) {
                                $cacheData['request']['state']['appointment']['bookings'][0]['customer'] =
                                    $data['bookings'][0]['customer'];
                            }

                            return array_merge(
                                $cacheData ?: [],
                                [
                                    'response' => $reservationData,
                                    'status'   => 'paid'
                                ]
                            );
                        }
                    } catch (InvalidArgumentException $e) {
                    } catch (QueryExecutionException $e) {
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param $orderId
     *
     * @return array|null
     * @throws ContainerException
     */
    public static function getPaymentLink($orderId)
    {
        $order = wc_get_order($orderId);
        if ($order) {
            return ['link' => $order->get_checkout_payment_url(), 'status' => 200];
        }
        return null;
    }

    public static function createWcOrder($appointmentData, $price, $oldOrderId)
    {
        if ($oldOrderId) {
            $oldOrder = new \WC_Order($oldOrderId);
            $order    = wc_create_order(['customer_id' => $oldOrder->get_customer_id()]);
            $order->set_address($oldOrder->get_address(), 'billing');

            $order->add_product(
                wc_get_product(
                    !empty($appointmentData['wcProductId']) ?
                        $appointmentData['wcProductId'] :
                        self::getProductIdFromSettings()
                ),
                1,
                ['subtotal' => $price, 'total' => $price ]
            );

            foreach ($order->get_items() as $itemId => $orderItem) {
                wc_add_order_item_meta($itemId, self::AMELIA, $appointmentData);
            }

            $order->calculate_totals();

            return self::getPaymentLink($order->get_id());
        } else {
            $link =  wc_get_checkout_url();
            if (
                !empty($appointmentData['locale'][0]) &&
                function_exists('icl_object_id') &&
                ($plink = apply_filters('wpml_permalink', get_permalink(get_option('woocommerce_checkout_page_id')), $appointmentData['locale'][0], true))
            ) {
                $link = $plink;
            }

            if (!empty($appointmentData['payment']['fromPanel'])) {
                self::addToCart($appointmentData);
            } else {
                $cache = CacheFactory::create(
                    [
                        'name' => (new Token())->getValue(),
                        'data' => json_encode($appointmentData),
                    ]
                );

                /** @var CacheRepository $cacheRepository */
                $cacheRepository = self::$container->get('domain.cache.repository');

                $cacheId = $cacheRepository->add($cache);

                $cache->setId(new Id($cacheId));

                $link .= (strpos($link, '?') ? '&' : '?') . 'amelia_cache_id=' . $cacheId . '_' . $cache->getName()->getValue();
            }

            return ['link' =>  $link, 'status' => 200];
        }
    }

    public static function beforeCheckoutForm()
    {
        if (!is_checkout() || is_wc_endpoint_url() || empty($_GET['amelia_cache_id'])) {
            return;
        }

        $cacheId = explode('_', $_GET['amelia_cache_id']);

        if (empty($cacheId[0]) || empty($cacheId[1])) {
            return;
        }

        /** @var CacheRepository $cacheRepository */
        $cacheRepository = self::$container->get('domain.cache.repository');

        $appointmentData = $cacheRepository->getByIdAndName($cacheId[0], $cacheId[1]);

        if (
            $appointmentData &&
            $appointmentData->getData() &&
            $appointmentData->getData()->getValue() &&
            json_decode($appointmentData->getData()->getValue(), true)
        ) {
            self::addToCart(json_decode($appointmentData->getData()->getValue(), true));
        }
    }


    /**
     * Refund order
     *
     * @param $order_id
     * @param $order_item_id
     * @param $amount
     * @param $refund_reason
     *
     * @return array
     * @throws ContainerException
     */
    public static function refund($order_id, $order_item_id, $amount, $refund_reason = '')
    {
        $order = wc_get_order($order_id);

        $amount = !empty($amount) ? $amount : $order->get_total();

        // If it's something else such as a WC_Order_Refund, we don't want that.
        if (! is_a($order, 'WC_Order') || $order->get_status() === 'refunded') {
            return false;
        }

        // Get Items
        $order_items = $order->get_items();

        // Prepare line items which we are refunding
        $line_items = array();

        if ($order_items) {
            foreach ($order_items as $item_id => $item) {
                $item_meta = $order->get_item_meta($item_id);

                if (in_array(self::AMELIA, array_keys($item_meta)) && !$order_item_id || $item_id === $order_item_id) {
                    $refund_tax = $item->get_taxes()['total'] ?: 0;
                    $line_items[ $item_id ] = array(
                        'qty' => $item_meta['_qty'][0],
                        'refund_total' => wc_format_decimal($item_meta['_line_total'][0]),
                        'refund_tax' =>  $refund_tax );
                }
            }
        }

        $refund = wc_create_refund(
            array(
            'amount'         => $amount,
            'reason'         => $refund_reason,
            'order_id'       => $order_id,
            'line_items'     => $line_items,
            'refund_payment' => true
            )
        );

        return ['error' => (get_class($refund) === 'WP_Error' ? $refund->get_error_message() : false)];
    }


    /**
     * Get order
     *
     * @param $order_id
     *
     * @return float
     * @throws ContainerException
     */
    public static function getOrderAmount($order_id)
    {
        $order = wc_get_order($order_id);
        return $order ? $order->get_total() : null;
    }

    /**
     * @param CommandResult $result
     *
     * @return string
     */
    private static function getBookingErrorMessage($result, $type)
    {
        $errorMessage = '';

        if (isset($result->getData()['emailError'])) {
            $errorMessage = FrontendStrings::getCommonStrings()['email_exist_error'];
        }

        if (isset($result->getData()['phoneError'])) {
            $errorMessage = FrontendStrings::getCommonStrings()['phone_exist_error'];
        }

        if (isset($result->getData()['couponUnknown'])) {
            $errorMessage = FrontendStrings::getCommonStrings()['coupon_unknown'];
        }

        if (isset($result->getData()['couponInvalid'])) {
            $errorMessage = FrontendStrings::getCommonStrings()['coupon_invalid'];
        }

        if (isset($result->getData()['customerAlreadyBooked'])) {
            switch ($type) {
                case (Entities::APPOINTMENT):
                case (Entities::PACKAGE):
                    $errorMessage = FrontendStrings::getCommonStrings()['customer_already_booked_app'];

                    break;

                case (Entities::EVENT):
                    $errorMessage = FrontendStrings::getCommonStrings()['customer_already_booked_ev'];

                    break;
            }
        }

        if (isset($result->getData()['timeSlotUnavailable'])) {
            $errorMessage = FrontendStrings::getCommonStrings()['time_slot_unavailable'];
        }

        return $errorMessage ? "$errorMessage " : '';
    }

    /**
     * @param $order
     * @throws ContainerException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public static function orderCreated($order)
    {
        if (
            self::isAmeliaOrder($order) &&
            self::isAmeliaOrderValidForBooking($order) &&
            !self::isAmeliaOrderPrePaid() &&
            !self::isAmeliaOrderFromPaymentLink($order, false)
        ) {
            self::createBookings($order, false, false);
        }
    }

    /**
     * @param $order_id
     * @throws BookingCancellationException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public static function orderStatusChanged($order_id)
    {
        $order = new \WC_Order($order_id);

        if (self::isAmeliaOrder($order)) {
            if (self::isAmeliaOrderProcessed($order)) {
                self::manageOrderUpdateStatus($order);
            } elseif (self::isAmeliaOrderValidForBooking($order)) {
                if (self::isAmeliaOrderFromPaymentLink($order, true)) {
                    self::managePaymentCreatedFromPaymentLink($order);
                } elseif (self::isAmeliaOrderPrePaid()) {
                    self::createBookings($order, true, true);
                } else {
                    self::completeBookings($order);
                }
            } else {
                self::manageOrderCreationFailed($order);
            }
        }
    }

    /**
     * inspect if order has amelia booking items
     *
     * @param $order
     * @return bool
     */
    private static function isAmeliaOrder($order)
    {
        foreach ($order->get_items() as $item_id => $order_item) {
            $data = wc_get_order_item_meta($item_id, self::AMELIA);

            if ($data && is_array($data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * inspect if bookings or payment are created
     *
     * @param $order
     * @return bool
     */
    private static function isAmeliaOrderProcessed($order)
    {
        foreach ($order->get_items() as $item_id => $order_item) {
            $data = wc_get_order_item_meta($item_id, self::AMELIA);

            if ($data && is_array($data)) {
                if (isset($data['processed'], $data['payment']['wcOrderId'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * inspect if amelia order is created from payment link
     *
     * @param $order
     * @param $inspectRules
     * @return bool
     */
    private static function isAmeliaOrderFromPaymentLink($order, $inspectRules)
    {
        foreach ($order->get_items() as $item_id => $order_item) {
            $data = wc_get_order_item_meta($item_id, self::AMELIA);

            if (
                (!$inspectRules || self::isValid($order->get_status(), $data) !== false) &&
                !isset($data['processed']) &&
                isset($data['payment']['fromLink']) &&
                $data['payment']['fromLink']
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * inspect if actions should be performed
     *
     * @param string $cacheData
     *
     * @return bool
     */
    private static function shouldAmeliaActionsRun($cacheData)
    {
        $cacheDataArray = json_decode($cacheData, true);

        $trigger = $cacheDataArray && isset($cacheDataArray['request']['trigger'])
            ? $cacheDataArray['request']['trigger']
            : (
                $cacheDataArray && isset($cacheDataArray['request']['form']['shortcode']['trigger'])
                    ? $cacheDataArray['request']['form']['shortcode']['trigger']
                    : ''
            );

        /** @var SettingsService $settingsService */
        $settingsService = self::$container->get('domain.settings.service');

        $wcSettings = self::$settingsService->getSetting('payments', 'wc');

        if (
            $settingsService->getSetting('general', 'runInstantPostBookingActions') ||
            (isset($wcSettings['redirectPage']) && $wcSettings['redirectPage'] === 1) ||
            $trigger
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private static function isAmeliaOrderPrePaid()
    {
        $wcSettings = self::$settingsService->getSetting('payments', 'wc');

        return empty($wcSettings['bookUnpaid']);
    }

    /**
     * inspect if amelia order is valid by order status
     *
     * @param $order
     * @return bool
     */
    private static function isAmeliaOrderValidForBooking($order)
    {
        return $order->get_status() !== 'cancelled' && $order->get_status() !== 'failed';
    }

    /**
     * Manage bookings after order status is changed.
     *
     * @param string $orderStatus
     * @param array  $data
     * @return bool|null
     */
    private static function isValid($orderStatus, $data)
    {
        $isValid = $data && is_array($data);

        $wcSettings = self::$settingsService->getSetting('payments', 'wc');

        if ($isValid && isset($data['type'], $wcSettings['rules'][$data['type']])) {
            /** @var ReservationServiceInterface $reservationService */
            $reservationService = self::$container->get('application.reservation.service')->get($data['type']);

            $isValid = $reservationService->getWcStatus(
                $data['type'],
                $orderStatus,
                'booking',
                isset($data['processed'])
            );
        }

        return $isValid;
    }

    /**
     * Set Amelia Item Data
     *
     * @param $data
     * @param $order
     * @param $couponCode
     * @param $bookingData
     * @param $paid
     * @param $item_id
     * @return void
     */
    private static function setAmeliaItemData(&$data, $order, $couponCode, $bookingData, $paid, $item_id)
    {
        if ($couponCode && empty($data['couponCode'])) {
            $data['couponCode'] = $couponCode;
        }

        $data['recurring'] = $bookingData;

        if ($paid) {
            $data['processed'] = true;
        }

        $data['taxIncluded'] = wc_prices_include_tax();

        wc_update_order_item_meta($item_id, self::AMELIA, $data);

        $data['payment']['wcOrderId'] = $order->get_id();

        $data['payment']['wcOrderItemId'] = $item_id;

        $data['payment']['orderStatus'] = $order->get_status();

        $data['payment']['gatewayTitle'] = $order->get_payment_method_title();

        $data['payment']['amount'] = 0;

        $data['payment']['status'] = $order->get_payment_method() === 'cod' ?
            PaymentStatus::PENDING : PaymentStatus::PAID;

        /** @var SettingsService $settingsService */
        $settingsService = self::$container->get('domain.settings.service');

        $orderUserId = $order->get_user_id();

        if ($orderUserId && $settingsService->getSetting('roles', 'automaticallyCreateCustomer')) {
            $data['bookings'][0]['customer']['externalId'] = $order->get_user_id();
        }

        $customFields = !empty($data['allCustomFields']) ?
            $data['allCustomFields'] : $data['bookings'][0]['customFields'];

        $data['bookings'][0]['customFields'] = $customFields ? json_encode($customFields) : null;
    }

    /**
     * Create bookings
     *
     * @param $order
     * @param $paid
     * @param $inspectRules
     * @throws \Exception
     */
    private static function createBookings($order, $paid, $inspectRules)
    {
        $groupData = [];

        $couponCode = null;

        foreach ($order->get_items() as $item_id => $order_item) {
            $data = wc_get_order_item_meta($item_id, self::AMELIA);

            if (
                $data &&
                is_array($data) &&
                !isset($data['processed']) &&
                !empty($data['type']) &&
                $data['type'] !== 'package' &&
                $data['type'] !== 'event'
            ) {
                if ($data['couponCode']) {
                    $couponCode = $data['couponCode'];
                }

                $serviceBookingData = [
                    'providerId'         => $data['providerId'],
                    'locationId'         => $data['locationId'],
                    'bookingStart'       => $data['bookingStart'],
                    'bookingEnd'         => $data['bookingEnd'],
                    'notifyParticipants' => $data['notifyParticipants'],
                    'status'             => $data['status'],
                    'utcOffset'          => $data['bookings'][0]['utcOffset'],
                    'extras'             => $data['bookings'][0]['extras'],
                    'persons'            => $data['bookings'][0]['persons'],
                    'duration'           => $data['bookings'][0]['duration'],
                    'couponId'           => $data['couponId'],
                    'couponCode'         => $data['couponCode'],
                    'wcOrderItemId'      => $item_id,
                ];

                if (!empty($data['serviceId'])) {
                    $serviceBookingData['serviceId'] = $data['serviceId'];
                }

                $key = isset($data['wcItemHash']) ? $data['wcItemHash'] : 0;

                $groupData[$key][] = $serviceBookingData;
            }
        }

        foreach ($groupData as $key => $value) {
            array_shift($groupData[$key]);
        }

        foreach ($order->get_items() as $item_id => $order_item) {
            $data = wc_get_order_item_meta($item_id, self::AMELIA);

            $key = $data && is_array($data) && isset($data['wcItemHash']) ? $data['wcItemHash'] : 0;

            try {
                if (
                    (!$inspectRules || self::isValid($order->get_status(), $data) !== false) &&
                    !array_key_exists($key, self::$processedAmeliaItems) &&
                    !empty($data) &&
                    !isset($data['processed'])
                ) {
                    self::$processedAmeliaItems[$key] = true;

                    $customFields = !empty($data['allCustomFields']) ?
                        $data['allCustomFields'] : $data['bookings'][0]['customFields'];

                    self::setAmeliaItemData($data, $order, $couponCode, $groupData ? $groupData[$key] : [], $paid, $item_id);

                    $data = apply_filters('amelia_before_booking_added_filter', $data);

                    do_action('amelia_before_booking_added', $data);

                    /** @var ReservationServiceInterface $reservationService */
                    $reservationService = self::$container->get('application.reservation.service')->get($data['type']);

                    $reservation = $reservationService->getNew(false, false, !$paid);

                    $result = $reservationService->processRequest($data, $reservation, true);

                    if (!$paid && $result->getResult() === CommandResult::RESULT_ERROR) {
                        $cartUrl = self::getPageUrl($data);

                        $removeAppointmentMessage = FrontendStrings::getCommonStrings()['wc_appointment_is_removed'];

                        $errorMessage = self::getBookingErrorMessage($result, $data['type']);

                        if ($errorMessage) {
                            throw new \Exception($errorMessage . "<a href='{$cartUrl}'>{$removeAppointmentMessage}</a>");
                        }
                    }

                    /** @var PaymentRepository $paymentRepository */
                    $paymentRepository = self::$container->get('domain.payment.repository');

                    /** @var Collection $payments */
                    $payments = $paymentRepository->getByEntityId($data['payment']['wcOrderId'], 'wcOrderId');

                    /** @var Payment $payment */
                    foreach ($payments->getItems() as $payment) {
                        foreach ($order->get_items() as $itemId => $orderItem) {
                            if ($payment->getWcOrderItemId() && $payment->getWcOrderItemId()->getValue() === (int)$itemId) {
                                $paymentRepository->updateFieldById(
                                    $payment->getId()->getValue(),
                                    ($orderItem->get_total() > 0 ? $orderItem->get_total() : 0) + $orderItem->get_total_tax(),
                                    'amount'
                                );
                            }
                        }
                    }

                    $data['bookings'][0]['customFields'] = $customFields;

                    if ($result && !$order->get_user_id()) {
                        /** @var UserRepository $userRepository */
                        $userRepository = self::$container->get('domain.users.repository');

                        $user = $userRepository->getByEmail($result->getData()['customer']['email']);

                        $data['externalId'] = $user && $user->getExternalId() ? $user->getExternalId()->getValue() : null;
                    }

                    do_action('amelia_after_booking_added', $result ? $result->getData() : null);

                    if (self::shouldAmeliaActionsRun($data['cacheData'])) {
                        if ($paid) {
                            $reservationService->runPostBookingActions($result);
                        } else {
                            $data['result'] = $result->getData();
                        }
                    }

                    $data['appointmentStatusChanged'] = $result->getData()['appointmentStatusChanged'];

                    $data['recurring'] = [];

                    wc_update_order_item_meta($item_id, self::AMELIA, $data);
                }
            } catch (ContainerException $e) {
            } catch (\Exception $e) {
                if (!$paid) {
                    throw new \Exception($e->getMessage());
                }
            }
        }
    }

    /**
     * Manage payment created from payment link
     *
     * @param $order
     */
    private static function managePaymentCreatedFromPaymentLink($order)
    {
        foreach ($order->get_items() as $item_id => $order_item) {
            $data = wc_get_order_item_meta($item_id, self::AMELIA);

            $key = $data && is_array($data) && isset($data['wcItemHash']) ? $data['wcItemHash'] : 0;

            try {
                if (
                    self::isValid($order->get_status(), $data) !== false &&
                    !array_key_exists($key, self::$processedAmeliaItems) &&
                    !isset($data['processed']) &&
                    isset($data['payment']['fromLink']) &&
                    $data['payment']['fromLink']
                ) {
                    self::$processedAmeliaItems[$key] = true;

                    self::setAmeliaItemData($data, $order, $data['couponCode'], [], true, $item_id);

                    if (isset($data['payment']['newPayment']) && $data['payment']['newPayment']) {
                        /** @var PaymentApplicationService $paymentAS */
                        $paymentAS = self::$container->get('application.payment.service');

                        $data['payment']['gateway'] = 'wc';
                        $linkPayment     = $paymentAS->insertPaymentFromLink(
                            $data['payment'],
                            $order_item->get_total() + ($order_item->get_total_tax() ?: 0),
                            $data['type']
                        );
                        $data['payment'] = $linkPayment->toArray();
                    } else {
                        /** @var PaymentRepository $paymentRepository */
                        $paymentRepository = self::$container->get('domain.payment.repository');

                        $paymentRepository->updateFieldById($data['payment']['id'], $data['payment']['status'], 'status');
                        $paymentRepository->updateFieldById($data['payment']['id'], $data['payment']['gatewayTitle'], 'gatewayTitle');
                        $paymentRepository->updateFieldById(
                            $data['payment']['id'],
                            DateTimeService::getNowDateTimeObjectInUtc()->format('Y-m-d H:i:s'),
                            'dateTime'
                        );
                        $paymentRepository->updateFieldById($data['payment']['id'], 'wc', 'gateway');
                        $paymentRepository->updateFieldById($data['payment']['id'], $order_item->get_total() + ($order_item->get_total_tax() ?: 0), 'amount');
                        $paymentRepository->updateFieldById($data['payment']['id'], $data['payment']['wcOrderId'], 'wcOrderId');
                    }

                    if (!empty($_GET['amelia_cache_id'])) {
                        /** @var CacheRepository $cacheRepository */
                        $cacheRepository = self::$container->get('domain.cache.repository');

                        $cacheRepository->delete($_GET['amelia_cache_id']);
                    }

                    $payment = PaymentFactory::create($data['payment']);
                    if (!($payment instanceof Payment)) {
                        return;
                    }

                    /** @var ReservationServiceInterface $reservationService */
                    $reservationService = self::$container->get('application.reservation.service')->get(
                        $payment->getEntity()->getValue()
                    );

                    $requestedStatus = $reservationService->getWcStatus(
                        $data['payment']['entity'],
                        $order->get_status(),
                        'booking',
                        true
                    );
                    if ($requestedStatus !== false) {
                        self::updateBookingStatus($payment, $data['payment']['entity'], $order);
                    } elseif ($data['payment']['entity'] === Entities::APPOINTMENT) {
                        /** @var SettingsService $settingsDS */
                        $settingsDS = self::$container->get('domain.settings.service');
                        /** @var AppointmentApplicationService $appointmentAS */
                        $appointmentAS = self::$container->get('application.booking.appointment.service');

                        $reservation = $reservationService->getReservationByPayment($payment, true);

                        $data = $reservation->getData();

                        $bookableSettings     = $data['bookable']['settings'];
                        $entitySettings       = !empty($bookableSettings) && json_decode($bookableSettings, true) ? json_decode($bookableSettings, true) : null;
                        $paymentLinksSettings =
                            !empty($entitySettings) && !empty($entitySettings['payments']['paymentLinks']) ?
                                $entitySettings['payments']['paymentLinks'] :
                                null;
                        $changeBookingStatus  =
                            $paymentLinksSettings &&
                            $paymentLinksSettings['changeBookingStatus'] !== null ?
                                $paymentLinksSettings['changeBookingStatus'] :
                                $settingsDS->getSetting('payments', 'paymentLinks')['changeBookingStatus'];

                        //call woo (update or create?) rules here
                        if ($changeBookingStatus && $data['booking']['status'] !== BookingStatus::APPROVED) {
                            $appointmentAS->approveBooking($data['booking']['id']);
                        }
                    }
                }
            } catch (ContainerException $e) {
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Manage bookings after order status is changed.
     *
     * @param $order
     * @throws BookingCancellationException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    private static function manageOrderUpdateStatus($order)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = self::$container->get('domain.payment.repository');

        foreach ($order->get_items() as $item_id => $order_item) {
            $data = wc_get_order_item_meta($item_id, self::AMELIA);

            if (
                self::isValid($order->get_status(), $data) !== false &&
                isset($data['processed'], $data['payment']['wcOrderId'])
            ) {
                /** @var Collection $payments */
                $payments = $paymentRepository->getByEntityId($order->get_id(), 'wcOrderId');

                /** @var Payment $payment */
                foreach ($payments->getItems() as $payment) {
                    try {
                        self::updateBookingStatus(
                            $payment,
                            $payment->getEntity() ? $payment->getEntity()->getValue() : $data['type'],
                            $order
                        );
                    } catch (ContainerException $e) {
                    } catch (\Exception $e) {
                    }
                }
            }
        }
    }

    /**
     * @param $order
     * @return void
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    private static function completeBookings($order)
    {
        foreach ($order->get_items() as $item_id => $order_item) {
            $data = wc_get_order_item_meta($item_id, self::AMELIA);

            if ($data && is_array($data)) {
                $data['processed'] = true;

                wc_update_order_item_meta($item_id, self::AMELIA, $data);

                if (!self::isAmeliaOrderPrePaid()) {
                    self::manageOrderUpdateStatus($order);
                }

                if (isset($data['result']) && self::shouldAmeliaActionsRun($data['cacheData'])) {
                    /** @var ReservationServiceInterface $reservationService */
                    $reservationService = self::$container->get('application.reservation.service')->get($data['type']);

                    /** @var CommandResult $result */
                    $result = new CommandResult();

                    $result->setResult(CommandResult::RESULT_SUCCESS);
                    $result->setMessage('Successfully get booking');
                    $result->setDataInResponse(false);
                    $result->setData($data['result']);

                    try {
                        $reservationService->runPostBookingActions($result);
                    } catch (ContainerException $e) {
                    } catch (\Exception $e) {
                    }
                }
            }
        }
    }

    /**
     * Manage bookings after order is failed on creation.
     *
     * @param $order
     * @throws BookingCancellationException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    private static function manageOrderCreationFailed($order)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = self::$container->get('domain.payment.repository');

        foreach ($order->get_items() as $item_id => $order_item) {
            $data = wc_get_order_item_meta($item_id, self::AMELIA);

            if ($data && is_array($data)) {
                /** @var Collection $payments */
                $payments = $paymentRepository->getByEntityId($item_id, 'wcOrderItemId');

                if ($payments->length() === 1) {
                    /** @var Payment $firstPayment */
                    $firstPayment = $payments->getItem($payments->keys()[0]);

                    /** @var Collection $followingPayments */
                    $followingPayments = $paymentRepository->getByEntityId(
                        $firstPayment->getId()->getValue(),
                        'parentId'
                    );

                    /** @var Payment $payment */
                    foreach ($followingPayments->getItems() as $payment) {
                        $payments->addItem($payment);
                    }
                }

                /** @var Payment $payment */
                foreach ($payments->getItems() as $payment) {
                    $paymentRepository->updateFieldById(
                        $payment->getId()->getValue(),
                        'pending',
                        'status'
                    );

                    $paymentRepository->updateFieldById(
                        $payment->getId()->getValue(),
                        0,
                        'amount'
                    );

                    try {
                        switch ($data['type']) {
                            case (Entities::APPOINTMENT):
                                self::bookingAppointmentUpdated($payment, 'canceled', false);
                                break;

                            case (Entities::EVENT):
                                self::bookingEventUpdated($payment, 'canceled', false);
                                break;

                            case (Entities::PACKAGE):
                                self::bookingPackageUpdated($payment, 'canceled', false);
                                break;
                        }
                    } catch (ContainerException $e) {
                    } catch (\Exception $e) {
                    }
                }
            }
        }
    }
}
