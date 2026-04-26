<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Payment;

use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Payment\AbstractPaymentService;
use AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use Exception;
use Square\Environment;
use Square\Exceptions\ApiException;
use Square\Http\ApiResponse;
use Square\Models\Address;
use Square\Models\CheckoutOptions;
use Square\Models\CompletePaymentRequest;
use Square\Models\CreateOrderRequest;
use Square\Models\CreatePaymentLinkRequest;
use Square\Models\CreatePaymentRequest;
use Square\Models\Location;
use Square\Models\Money;
use Square\Models\Order;
use Square\Models\OrderLineItem;
use Square\Models\PaymentLink;
use Square\Models\PrePopulatedData;
use Square\Models\RefundPaymentRequest;
use Square\Models\UpdatePaymentLinkRequest;
use Square\SquareClient;

/**
 * Class SquareService
 */
class SquareService extends AbstractPaymentService implements PaymentServiceInterface
{
    /**
     * @var SquareMiddlewareService $middlewareService
     */
    private $middlewareService;

    /**
     * SquareService constructor.
     *
     * @param SettingsService $settingsService
     * @param CurrencyService $currencyService
     */
    public function __construct(
        SettingsService $settingsService,
        CurrencyService $currencyService
    ) {
        parent::__construct($settingsService, $currencyService);
        $this->middlewareService = new SquareMiddlewareService($settingsService);
    }

    /**
     *
     * @return mixed
     * @throws Exception
     */
    public function getClient()
    {
        $squareSettings = $this->settingsService->getCategorySettings('payments')['square'];
        $accessToken    = $this->middlewareService->getAccessToken($squareSettings['accessToken']);

        return new SquareClient(
            [
                'accessToken' => $accessToken['access_token'],
                'environment' => $squareSettings['testMode'] ? Environment::SANDBOX : Environment::PRODUCTION
            ]
        );
    }

    /**
     * @param string $apiName
     * @param string $functionName
     * @param array $args
     *
     * @return ApiResponse
     * @throws Exception
     */
    private function getApiResponse($apiName, $functionName, $args)
    {
        $client = $this->getClient();
        /** @var ApiResponse $response */
        $response = call_user_func_array([$client->{$apiName}(), $functionName], $args);
        if ($response->getStatusCode() === 401) {
            $this->refreshAccessToken();
            $client   = $this->getClient();
            $response = call_user_func_array([$client->{$apiName}(), $functionName], $args);
        }

        return $response;
    }

    /**
     *
     * @return Location
     * @throws Exception
     */
    private function getLocation()
    {
        $locationId = $this->settingsService->getCategorySettings('payments')['square']['locationId'];

        $apiResponse = $this->getApiResponse('getLocationsApi', 'retrieveLocation', [$locationId]);

        return $apiResponse->isSuccess() ? $apiResponse->getResult()->getLocation() : null;
    }

    public function preparePaymentRequest($data, $amount, $reservation, $additionalInformation)
    {
        $bookingId = $reservation->getBooking() && $reservation->getBooking()->getId() ?
            $reservation->getBooking()->getId()->getValue() : '';

        $customer = $reservation->getCustomer();
        $customerEmail = '';
        if ($customer && $customer->getEmail() && $customer->getEmail()->getValue()) {
            $customerEmail = $customer->getEmail()->getValue();
        }
        $location = $this->getLocation();
        if (!$location) {
            return null;
        }
        $locationId = $location->getId();

        $currency = $location->getCurrency();
        $money = new Money();
        $money->setCurrency($currency);
        $money->setAmount((int)$amount);

        $appointment = new OrderLineItem(1);
        $appointment->setName(
            $additionalInformation['description'] ?: $reservation->getBookable()->getName()->getValue()
        );
        $appointment->setBasePriceMoney($money);

        $order = new Order($locationId);
        $order->setReferenceId(" . $bookingId . ");
        $order->setLineItems([$appointment]);

        if (!empty($data['metaData'])) {
            $order->setMetadata($data['metaData']);
        }

        $ordersApi = $this->getClient()->getOrdersApi();
        $request = new CreateOrderRequest();
        $request->setOrder($order);
        $responseOrder = $ordersApi->createOrder($request);
        $orderResponse = $responseOrder->getResult();

        $address = new Address();
        if ($customer->getFirstName() && $customer->getFirstName()->getValue()) {
            $address->setFirstName($customer->getFirstName()->getValue());
        }

        if ($customer->getLastName() && $customer->getLastName()->getValue()) {
            $address->setLastName($customer->getLastName()->getValue());
        }

        $client = $this->getClient();
        $paymentsApi = $client->getPaymentsApi();
        $paymentData = new CreatePaymentRequest($data['sourceId'], $data['idempotencyKey']);

        $paymentData->setBillingAddress($address);
        $paymentData->setBuyerEmailAddress($customerEmail);
        $paymentData->setOrderId($orderResponse->getOrder()->getId());
        $paymentData->setSourceId($data['sourceId']);
        $paymentData->setIdempotencyKey($data['idempotencyKey']);
        $paymentData->setAmountMoney($money);
        $paymentData->setLocationId($locationId);
        $paymentData->setAutocomplete(true);
        $paymentData->setNote("Amelia - Booking " . $bookingId);

        return $paymentsApi->createPayment($paymentData);
    }

    /**
     * @param array $data
     * @param array $transfers
     *
     * @return ApiResponse
     * @throws Exception
     */
    public function execute($data, &$transfers)
    {
        // Monetary amounts are specified in the smallest unit of the applicable currency.
        // This amount is in cents
        // Set currency to the currency for the location
        $location = $this->getLocation();
        if (!$location) {
            return null;
        }
        $currency = $location->getCurrency();
        $price    = new Money();
        $price->setCurrency($currency);
        $price->setAmount($data['amount']);

        $appointment = new OrderLineItem(1);
        $appointment->setName($data['description']);
        $appointment->setBasePriceMoney($price);

        // Create a new order and add the line items as necessary.
        $order = new Order($location->getId());
        $order->setLineItems([$appointment]);
        if (!empty($data['metaData'])) {
            $order->setMetadata($data['metaData']);
        }

        $checkoutOptions = new CheckoutOptions();
        $checkoutOptions->setRedirectUrl($data['redirectUrl']);

        $paymentLinkRequest = new CreatePaymentLinkRequest();
        $paymentLinkRequest->setIdempotencyKey(uniqid());
        $paymentLinkRequest->setOrder($order);

        $paymentLinkRequest->setCheckoutOptions($checkoutOptions);
        if (!empty($data['customer'])) {
            $prePopulatedData = new PrePopulatedData();
            if (!empty($data['customer']['phone'])) {
                $prePopulatedData->setBuyerPhoneNumber($data['customer']['phone']);
            }
            if (!empty($data['customer']['email'])) {
                $prePopulatedData->setBuyerEmail($data['customer']['email']);
            }
            $address = new Address();
            if (!empty($data['customer']['firstName'])) {
                $address->setFirstName($data['customer']['firstName']);
            }
            if (!empty($data['customer']['lastName'])) {
                $address->setLastName($data['customer']['lastName']);
            }
            $prePopulatedData->setBuyerAddress($address);
            $paymentLinkRequest->setPrePopulatedData($prePopulatedData);
        }

        return $this->getApiResponse('getCheckoutApi', 'createPaymentLink', [$paymentLinkRequest]);
    }


    /**
     * @param PaymentLink $paymentLink
     * @param string $redirectUrl
     *
     * @return ApiResponse
     * @throws Exception
     */
    public function updatePaymentLink($paymentLink, $redirectUrl, $paymentId)
    {
        if (!$paymentLink) {
            return null;
        }

        if ($paymentId) {
            $paymentLink->setPaymentNote("Amelia - Transaction " . $paymentId);
        }

        if ($redirectUrl) {
            $checkoutOptions = $paymentLink->getCheckoutOptions();
            $checkoutOptions->setRedirectUrl($redirectUrl);
            $paymentLink->setCheckoutOptions($checkoutOptions);
        }

        $updatePaymentResponse = new UpdatePaymentLinkRequest($paymentLink);

        return $this->getApiResponse('getCheckoutApi', 'updatePaymentLink', [$paymentLink->getId(), $updatePaymentResponse]);
    }

    /**
     * @param $data
     *
     * @return array
     * @throws Exception
     */
    public function getPaymentLink($data)
    {
        $transfers = [];

        $apiResponse = $this->execute($data, $transfers);

        if ($apiResponse && $apiResponse->isSuccess() && $apiResponse->getResult() && $apiResponse->getResult()->getPaymentLink()) {
            /**@var PaymentLink $paymentLink */
            $paymentLink = $apiResponse->getResult()->getPaymentLink();

            $orderId = $paymentLink->getOrderId();

            $this->updatePaymentLink($paymentLink, $data['redirectUrl'] . '&squareOrderId=' . $orderId, !empty($data['paymentId']) ? $data['paymentId'] : null);

            return [
                'link' => $paymentLink->getUrl(),
                'status' => 200
            ];
        }

        return [
            'message' => $apiResponse ? $this->getErrorMessage($apiResponse) : null,
            'status' => $apiResponse ? $apiResponse->getStatusCode() : null
        ];
    }

    /**
     *
     * @param string $orderId
     * @return ApiResponse
     *
     * @throws ApiException
     * @throws Exception
     */
    public function getOrderResponse($orderId)
    {
        return $this->getApiResponse('getOrdersApi', 'retrieveOrder', [$orderId]);
    }

    /**
     *
     * @param string $paymentId
     * @return ApiResponse
     *
     * @throws ApiException
     * @throws Exception
     */
    public function completePayment($paymentId)
    {
        return $this->getApiResponse('getPaymentsApi', 'completePayment', [$paymentId, new CompletePaymentRequest()]);
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    public function refund($data)
    {
        $location = $this->getLocation();
        $currency = $location->getCurrency();

        $money = new Money();
        $money->setAmount(intval($this->currencyService->getAmountInFractionalUnit(new Price($data['amount']))));
        $money->setCurrency($currency);

        $body = new RefundPaymentRequest(uniqid(), $money);
        $body->setPaymentId($data['id']);

        $apiResponse =  $this->getApiResponse('getRefundsApi', 'refundPayment', [$body]);

        return ['error' => $apiResponse->isSuccess() ? false : $this->getErrorMessage($apiResponse)];
    }

    /**
     *
     * @param ApiResponse $response
     * @return string
     *
     * @throws Exception
     */
    public function getErrorMessage($response)
    {
        $errors = $response->getErrors();
        $errors =  array_map(
            function ($error) {
                return $error->getDetail();
            },
            $errors
        );
        return implode('; ', $errors);
    }

    /**
     *
     *
     * @return array
     *
     * @throws Exception
     */
    public function getLocations()
    {
        $apiResponse = $this->getApiResponse('getLocationsApi', 'listLocations', []);

        $result = $apiResponse->isSuccess() ? $apiResponse->getResult() : null;
        return $result ? $result->getLocations() : [];
    }


    /**
     * @param string $id
     * @param array|null $transfers
     *
     * @return mixed
     * @throws Exception
     */
    public function getTransactionAmount($id, $transfers)
    {
        $apiResponse = $this->getApiResponse('getPaymentsApi', 'getPayment', [$id]);

        if ($apiResponse->isSuccess() && $apiResponse->getResult()) {
            return $apiResponse->getResult()->getPayment()->getAmountMoney()->getAmount() / 100;
        }

        return null;
    }

    /**
     *
     * @return boolean
     *
     * @throws Exception
     */
    public function disconnectAccount($fromSquare = false)
    {
        $squareSettings = $this->settingsService->getCategorySettings('payments')['square'];

        if (!$fromSquare) {
            $this->middlewareService->disconnectAccount($squareSettings['accessToken'], $squareSettings['testMode']);
        }

        $squareSettings['accessToken'] = null;
        $squareSettings['locationId']  = null;
        $this->settingsService->setSetting('payments', 'square', $squareSettings);
        delete_transient('amelia_square_access_token');

        return true;
    }

    public function isAccessTokenExpired($accessToken)
    {
        return DateTimeService::getNowDateTimeObject() >= DateTimeService::getCustomDateTimeObject($accessToken['expires_at']);
    }

    /**
     *
     *
     * @return boolean
     *
     * @throws Exception
     */
    public function refreshAccessToken()
    {
        $squareSettings = $this->settingsService->getCategorySettings('payments')['square'];

        if (empty($squareSettings['accessToken']['refresh_token'])) {
            return true;
        }

        $response = $this->middlewareService->refreshAccessToken($squareSettings['accessToken'], $squareSettings['testMode']);

        if ($response) {
            $accessToken = $response['result'];

            set_transient(
                'amelia_square_access_token',
                [
                    'access_token' => $accessToken['decrypted_access_token'],
                    'refresh_token' => $accessToken['decrypted_refresh_token']
                ],
                604800
            );

            unset($accessToken['decrypted_access_token']);
            unset($accessToken['decrypted_refresh_token']);

            $squareSettings['accessToken'] = $accessToken;
            $this->settingsService->setSetting('payments', 'square', $squareSettings);
        }

        return true;
    }

    public function getAuthUrl()
    {
        $squareSettings = $this->settingsService->getCategorySettings('payments')['square'];

        return $this->middlewareService->getAuthUrl($squareSettings['testMode']);
    }

    //create method to get country from location by locationId

    /**
     * @throws Exception
     */
    public function getCountryCodeByLocationId($locationId)
    {
        $apiResponse = $this->getApiResponse('getLocationsApi', 'retrieveLocation', [$locationId]);

        if ($apiResponse->isSuccess() && $apiResponse->getResult()) {
            $location = $apiResponse->getResult()->getLocation();
            return $location ? $location->getCountry() : null;
        }

        return null;
    }
}
