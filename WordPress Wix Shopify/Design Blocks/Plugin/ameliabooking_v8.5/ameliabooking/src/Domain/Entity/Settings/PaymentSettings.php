<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Settings;

/**
 * Class PaymentSettings
 *
 * @package AmeliaBooking\Domain\Entity\Settings
 */
class PaymentSettings
{
    /** @var bool */
    private $onSite;

    /** @var PaymentPayPalSettings */
    private $payPalSettings;

    /** @var PaymentStripeSettings */
    private $stripeSettings;

    /** @var PaymentWooCommerceSettings */
    private $wooCommerceSettings;

    /** @var PaymentMollieSettings */
    private $mollieSettings;

    /** @var PaymentSquareSettings */
    private $squareSettings;

    /** @var PaymentLinksSettings */
    private $paymentLinksSettings;

    /**
     * @return bool
     */
    public function getOnSite()
    {
        return $this->onSite;
    }

    /**
     * @param bool $onSite
     */
    public function setOnSite($onSite)
    {
        $this->onSite = $onSite;
    }

    /**
     * @return PaymentPayPalSettings
     */
    public function getPayPalSettings()
    {
        return $this->payPalSettings;
    }

    /**
     * @param PaymentPayPalSettings $payPalSettings
     */
    public function setPayPalSettings($payPalSettings)
    {
        $this->payPalSettings = $payPalSettings;
    }

    /**
     * @return PaymentStripeSettings
     */
    public function getStripeSettings()
    {
        return $this->stripeSettings;
    }

    /**
     * @param PaymentStripeSettings $stripeSettings
     */
    public function setStripeSettings($stripeSettings)
    {
        $this->stripeSettings = $stripeSettings;
    }

    /**
     * @return PaymentWooCommerceSettings
     */
    public function getWooCommerceSettings()
    {
        return $this->wooCommerceSettings;
    }

    /**
     * @param PaymentWooCommerceSettings $wooCommerceSettings
     */
    public function setWooCommerceSettings($wooCommerceSettings)
    {
        $this->wooCommerceSettings = $wooCommerceSettings;
    }

    /**
     * @return PaymentMollieSettings
     */
    public function getMollieSettings()
    {
        return $this->mollieSettings;
    }

    /**
     * @param PaymentMollieSettings $mollieSettings
     */
    public function setMollieSettings($mollieSettings)
    {
        $this->mollieSettings = $mollieSettings;
    }

    /**
     * @return PaymentSquareSettings
     */
    public function getSquareSettings()
    {
        return $this->squareSettings;
    }

    /**
     * @param PaymentSquareSettings $squareSettings
     */
    public function setSquareSettings($squareSettings)
    {
        $this->squareSettings = $squareSettings;
    }


    /**
     * @return PaymentLinksSettings
     */
    public function getPaymentLinksSettings()
    {
        return $this->paymentLinksSettings;
    }

    /**
     * @param PaymentLinksSettings $paymentLinksSettings
     */
    public function setPaymentLinksSettings(PaymentLinksSettings $paymentLinksSettings)
    {
        $this->paymentLinksSettings = $paymentLinksSettings;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'onSite' => $this->onSite,
            'payPal' => $this->getPayPalSettings() ? $this->getPayPalSettings()->toArray() : null,
            'stripe' => $this->getStripeSettings() ? $this->getStripeSettings()->toArray() : null,
            'wc'     => $this->getWooCommerceSettings() ? $this->getWooCommerceSettings()->toArray() : null,
            'mollie' => $this->getMollieSettings() ? $this->getMollieSettings()->toArray() : null,
            'square' => $this->getSquareSettings() ? $this->getSquareSettings()->toArray() : null,
            'paymentLinks' => $this->getPaymentLinksSettings() ? $this->getPaymentLinksSettings()->toArray() : null,
        ];
    }
}
