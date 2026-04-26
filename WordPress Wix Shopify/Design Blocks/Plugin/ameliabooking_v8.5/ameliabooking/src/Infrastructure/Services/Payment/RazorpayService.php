<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Payment;

use AmeliaBooking\Domain\Services\Payment\AbstractPaymentService;
use AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface;
use Exception;
use Razorpay\Api\Api;

/**
 * Class RazorpayService
 */
class RazorpayService extends AbstractPaymentService implements PaymentServiceInterface
{
    private $keyId = '';


    /**
     *
     * @return string
     */
    public function getKeyId()
    {
        return $this->keyId;
    }

    /**
     *
     * @return Api
     * @throws Exception
     */
    private function getApi()
    {
        $keyId = $this->settingsService->getCategorySettings('payments')['razorpay']['testMode'] ?
            $this->settingsService->getCategorySettings('payments')['razorpay']['testKeyId'] :
            $this->settingsService->getCategorySettings('payments')['razorpay']['liveKeyId'];

        $this->keyId = $keyId;

        $keySecret = $this->settingsService->getCategorySettings('payments')['razorpay']['testMode'] ?
            $this->settingsService->getCategorySettings('payments')['razorpay']['testKeySecret'] :
            $this->settingsService->getCategorySettings('payments')['razorpay']['liveKeySecret'];


        return new Api($keyId, $keySecret);
    }

    /**
     * @param array $data
     * @param array $transfers
     *
     * @return mixed
     * @throws Exception
     */
    public function execute($data, &$transfers)
    {
        $orderData = [
            'amount'     => $data['amount'],
            'currency'   => $this->settingsService->getCategorySettings('payments')['currency'],
        ];

        return $this->getApi()->order->create($orderData);
    }


    /**
     * @param $paymentId
     * @param $paymentAmount
     *
     * @return mixed
     * @throws Exception
     */
    public function capture($paymentId, $paymentAmount)
    {
        $payment = $this->getApi()->payment->fetch($paymentId);

        if (
            $payment &&
            ($paymentData = $payment->toArray()) &&
            !empty($paymentData['status']) &&
            $paymentData['status'] === 'captured'
        ) {
            return [
                'error_code' => 0,
            ];
        }

        return $payment->capture(
            [
                'amount'   => intval($paymentAmount * 100),
                'currency' => $this->settingsService->getCategorySettings('payments')['currency']
            ]
        );
    }

    /**
     * @param $attributes
     *
     * @return mixed
     * @throws Exception
     */
    public function verify($attributes)
    {
        return $this->getApi()->utility->verifyPaymentSignature($attributes);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function getPaymentLink($data)
    {
        $paymentLink = $this->getApi()->paymentLink->create($data);
        if ($paymentLink['status'] === 'created' && !empty($paymentLink['short_url'])) {
            return ['link' => $paymentLink['short_url'], 'status' => 200];
        }
        return ['message' => $paymentLink['message'], 'status' => $paymentLink['status']];
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    public function refund($data)
    {
        $props = [];

        if (!empty($data['amount'])) {
            $props['amount'] = intval($data['amount'] * 100);
        }

        $refund = $this->getApi()->payment->fetch($data['id'])->refund($props);

        return ['error' => $refund->toArray()['status'] !== 'processed'];
    }

    /**
     * @param string     $id
     * @param array|null $transfers
     *
     * @return mixed
     * @throws \Exception
     */
    public function getTransactionAmount($id, $transfers)
    {
        $payment = $this->getApi()->payment->fetch($id);
        return $payment ? intval($payment->amount / 100) : null;
    }
}
