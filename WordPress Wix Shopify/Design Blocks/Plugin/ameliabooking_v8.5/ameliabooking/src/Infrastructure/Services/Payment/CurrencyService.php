<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Payment;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use Money\Currencies\ISOCurrencies;
use Money\Exception\ParserException;
use Money\Parser\DecimalMoneyParser;

/**
 * Class CurrencyService
 */
class CurrencyService
{
    /**
     * @var SettingsService $settingsService
     */
    protected $settingsService;

    /**
     * CurrencyService constructor.
     *
     * @param SettingsService $settingsService
     */
    public function __construct(
        SettingsService $settingsService
    ) {
        $this->settingsService = $settingsService;
    }

    /**
     * @param Price  $amount
     *
     * @return string
     * @throws ParserException
     */
    public function getAmountInFractionalUnit($amount)
    {
        $currencies = new ISOCurrencies();

        $moneyParser = new DecimalMoneyParser($currencies);

        return $moneyParser->parse(
            (string)$amount->getValue(),
            $this->settingsService->getCategorySettings('payments')['currency']
        )->getAmount();
    }
}
