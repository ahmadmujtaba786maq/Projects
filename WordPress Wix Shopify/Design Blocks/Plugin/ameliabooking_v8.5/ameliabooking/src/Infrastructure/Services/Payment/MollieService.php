<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Payment;

use AmeliaBooking\Domain\Services\Payment\AbstractPaymentService;
use AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface;
use Omnipay\Mollie\Gateway;
use Omnipay\Omnipay;

/**
 * Class MollieService
 */
class MollieService extends AbstractPaymentService implements PaymentServiceInterface
{
    /**
     *
     * @return mixed
     * @throws \Exception
     */
    private function getGateway()
    {
        /** @var Gateway $gateway */
        $gateway = Omnipay::create('Mollie');

        $gateway->setApiKey(
            $this->settingsService->getCategorySettings('payments')['mollie']['testMode'] ?
                $this->settingsService->getCategorySettings('payments')['mollie']['testApiKey'] :
                $this->settingsService->getCategorySettings('payments')['mollie']['liveApiKey']
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
            $mollieData = [
                'returnUrl'  => $data['returnUrl'],
                'notifyUrl'  => $data['notifyUrl'],
                'amount'     => $data['amount'],
                'currency'   => $this->settingsService->getCategorySettings('payments')['currency'],
            ];

            if ($data['description']) {
                $mollieData['description'] = $data['description'];
            }

            if ($data['metaData']) {
                $mollieData['metaData'] = $data['metaData'];
            }

            if ($data['method']) {
                $mollieData['method'] = $data['method'];
            }

            return $this->getGateway()->purchase($mollieData)->send();
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
    public function fetchPayment($data)
    {
        try {
            return $this->getGateway()->fetchTransaction(
                [
                    'transactionReference' => $data['id'],
                ]
            )->send();
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
        $apiKey = $this->settingsService->getCategorySettings('payments')['mollie']['testMode'] ?
            $this->settingsService->getCategorySettings('payments')['mollie']['testApiKey'] :
            $this->settingsService->getCategorySettings('payments')['mollie']['liveApiKey'];

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
            CURLOPT_URL => 'https://api.mollie.com/v2/payment-links',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER =>
                array(
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json'
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response, true);
        if (!empty($response) && !empty($response['_links']) && !empty($response['_links']['paymentLink'])) {
            return [
                'link' => $response['_links']['paymentLink']['href'],
                'status' => 200
                ];
        }
        return [
            'message' => $response['detail'],
            'status' => $response['status']
        ];

//        $response = $this->execute($data);
//        if ($response->isRedirect() && $response->getData() && $response->getData()['_links'] && count($response->getData()['_links']) > 1) {
//            return $response->getData()['_links']['checkout']['href'];
//        }
//        return null;
    }


    public function fetchPaymentLink($id)
    {
        $apiKey = $this->settingsService->getCategorySettings('payments')['mollie']['testMode'] ?
            $this->settingsService->getCategorySettings('payments')['mollie']['testApiKey'] :
            $this->settingsService->getCategorySettings('payments')['mollie']['liveApiKey'];

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
            CURLOPT_URL => 'https://api.mollie.com/v2/payment-links/' . $id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $apiKey
            ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response, true);
        if (!empty($response)) {
            return $response;
        }
        return null;
    }


    /**
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    public function refund($data)
    {
        $amount = $this->getTransactionAmount($data['id'], null);

        $response = $this->getGateway()->refund(
            array(
                'transactionReference' => $data['id'],
                'amount'               => !empty($data['amount']) ? $data['amount'] : $amount,
                'currency'             => $this->settingsService->getCategorySettings('payments')['currency']
            )
        )->send();

        return ['error' => $response->getData()['status'] !== 200 ? $response->getData()['detail'] : false];
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
        try {
            $response = $this->getGateway()->fetchTransaction(['transactionReference' => $id])->send();

            return $response->getData()['status'] ? $response->getData()['amount']['value'] : null;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
