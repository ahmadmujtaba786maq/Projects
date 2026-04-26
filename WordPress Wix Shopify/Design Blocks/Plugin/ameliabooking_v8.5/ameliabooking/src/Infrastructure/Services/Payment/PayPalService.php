<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Payment;

use AmeliaBooking\Domain\Services\Payment\AbstractPaymentService;
use AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface;
use Omnipay\Omnipay;
use Omnipay\PayPal\ExpressGateway;

/**
 * Class PayPalService
 */
class PayPalService extends AbstractPaymentService implements PaymentServiceInterface
{
    /**
     *
     * @return mixed
     * @throws \Exception
     */
    private function getGateway()
    {
        /** @var ExpressGateway $gateway */
        $gateway = Omnipay::create('PayPal_Rest');

        $gateway->initialize(
            [
            'clientId' => $this->settingsService->getCategorySettings('payments')['payPal']['sandboxMode'] ?
                $this->settingsService->getCategorySettings('payments')['payPal']['testApiClientId'] :
                $this->settingsService->getCategorySettings('payments')['payPal']['liveApiClientId'],
            'secret'   => $this->settingsService->getCategorySettings('payments')['payPal']['sandboxMode'] ?
                $this->settingsService->getCategorySettings('payments')['payPal']['testApiSecret'] :
                $this->settingsService->getCategorySettings('payments')['payPal']['liveApiSecret'],
            'testMode' => $this->settingsService->getCategorySettings('payments')['payPal']['sandboxMode'],
            ]
        );

        return $gateway;
    }

    /**
     * @param array $data
     * @param array $transfers
     *
     * @return mixed
     * @throws \Exception
     */
    public function execute($data, &$transfers)
    {
        try {
            $payPalData = [
                'cancelUrl'  => $data['cancelUrl'],
                'returnUrl'  => $data['returnUrl'],
                'amount'     => $data['amount'],
                'currency'   => $this->settingsService->getCategorySettings('payments')['currency'],
                'noShipping' => 1,
            ];

            if ($data['description']) {
                $payPalData['description'] = $data['description'];
            }

            return $this->getGateway()->purchase($payPalData)->send();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function complete($data)
    {
        try {
            $response = $this->getGateway()->completePurchase(
                [
                'transactionReference' => $data['transactionReference'],
                'PayerID'              => $data['PayerID'],
                'amount'               => $data['amount'],
                'currency'             => $this->settingsService->getCategorySettings('payments')['currency']
                ]
            )->send();

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    public function getPaymentLink($data)
    {
        $transfers = [];

        $response = $this->execute($data, $transfers);
        if ($response->isSuccessful() && $response->getData() && $response->getData()['links'] && count($response->getData()['links']) > 1) {
            return ['link' => $response->getData()['links'][1]['href'], 'status' => 200];
        }
        return ['message' => $response->getMessage(), 'status' => $response->getCode()];
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    public function refund($data)
    {
        $payment = $this->getTransaction($data['id']);

        if ($payment) {
            $props = [
                'transactionReference' => $payment['transactions'][0]['related_resources'][0]['sale']['id'],
                'currency'             => $this->settingsService->getCategorySettings('payments')['currency']
            ];

            if (!empty($data['amount'])) {
                $props['amount'] = $data['amount'];
            }

            $response = $this->getGateway()->refund($props)->send();

            return ['error' => $response->getCode() !== 201 ? $response->getMessage() : false];
        }

        return ['error' => true];
    }

    /**
     * @param string $id
     * @param array|null $transfers
     *
     * @return mixed
     * @throws \Exception
     */
    public function getTransactionAmount($id, $transfers)
    {
        $transaction = $this->getTransaction($id);
        return $transaction ? $transaction['transactions'][0]['amount']['total'] : null;
    }

    private function getTransaction($id)
    {
        try {
            $response = $this->getGateway()->fetchPurchase(['transactionReference' => $id])->send();

            return $response->getCode() === 200 ? $response->getData() : null;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
