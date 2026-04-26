<?php

use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/** @var Container $container */
$container = require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';

/** @var HelperService $helperService */
$helperService = $this->container->get('application.helper.service');

/** @var SettingsService $settingsDS */
$settingsDS = $this->container->get('domain.settings.service');

$data = $invoiceData;

$itemsWithTax = array_filter(
    $data['items'],
    function ($item) {
        return !empty($item['invoice_tax']);
    }
);
$taxEnabled   = !empty($itemsWithTax) && !empty(array_values($itemsWithTax)[0]['invoice_tax']);
$includedTax  = !empty($itemsWithTax) && !array_values($itemsWithTax)[0]['invoice_tax_excluded'];

$invoiceSubtotal = array_sum(array_column($data['items'], 'invoice_subtotal'));
$invoiceDiscount = array_sum(array_column($data['items'], 'invoice_discount'));
$invoiceTax      = $data['invoice_tax'];

$invoiceTotal = $invoiceSubtotal - $invoiceDiscount;
$invoiceTotal += $invoiceTotal ? $invoiceTax : 0;

$invoicePaid = array_sum(array_column($data['items'], 'invoice_paid_amount'));

$invoicePaid = ($invoicePaid < $invoiceTotal) ? $invoicePaid : 0;

$invoiceLeftToPay = $invoiceTotal - $invoicePaid;

$filename = !empty($data['company_logo']) ? $data['company_logo'] : null;
if ($filename) {
    $mime = pathinfo($filename, PATHINFO_EXTENSION);
    $img  = base64_encode(file_get_contents($filename));
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <style>
      body { margin: 0; font-family: 'DejaVu Sans', sans-serif; line-height: 15px; }
      html { margin: 0; font-family: 'DejaVu Sans', sans-serif; line-height: 15px;}
      @page { margin: 0; font-family: 'DejaVu Sans', sans-serif; line-height: 15px;}

      .invoice-header {
          background-color: #F5F5F5;
          padding: 44px;
      }

      .invoice-header-first-line {
          margin-bottom: 10px;
          display: block
      }

      .invoice-header-first-line table {
          float: right;
      }

      .invoice-header-first-line table tr td:first-child {
          vertical-align: middle;
          padding-right: 16px
      }

      .invoice-header img {
          width: 50px;
          height: 50px;
      }

      .invoice-header-first-line-company-name {
          vertical-align: middle;
          font-weight: 700;
          font-size: 16px;
          color: #1A2C37
      }

      .invoice-header h2 {
          float:left;
          color: #0E1920;
          font-size: 22px
      }

      .invoice-header-second-line {
          padding-bottom: 8px
      }

      .invoice-header-table {
          width: 100%;
          font-size: 14px;
          font-weight: 400;
          color: #1A2C37;
          border-spacing: 0 2px;
      }

      .invoice-header-table-company {
          text-align: right;
      }

      .invoice-header-table-company-name {
          font-size: 16px;
      }

      .invoice-header-table-invoice-number-value, .invoice-header-table-invoice-issued-date {
          text-align: right;
          padding-left: 8px
      }

      .invoice-body {
          padding: 0 44px;
          margin-top: 25px
      }

      .invoice-body-customer {
          color: #1A2C37;
          margin-bottom: 35px;
      }

      .invoice-body-customer > div {
          font-size: 14px;
          font-weight: 400;
          padding-bottom: 5px;
      }

      .invoice-body-customer > div:first-child {
          font-weight: bold;
          padding-bottom: 10px
      }

      .invoice-body-items {
          width: 100%;
          margin-bottom: 20px;
      }

      .invoice-body-items td {
          padding: 10px;
          font-weight: 400;
          font-size: 12px;
      }

      .invoice-body-items tr td {
          text-align: right;
      }

      .invoice-body-items tr td:first-child {
          text-align: left;
      }

      .invoice-body-items-name {
          background-color: #F5F5F5;
          color: #808A90;
      }

      .invoice-body-items-data {
          color: #1A2C37;
      }

      .invoice-body-items-data td {
          border-bottom: 1px dashed lightgray;
      }

      .invoice-body-sum {
          float: right;
          width: 50%;
          font-size: 13px;
          font-weight: 400;
          color: #1A2C37
      }

      .invoice-body-sum > div {
          margin-bottom: 10px;
      }

      .invoice-body-sum > div > span:nth-child(2) {
          float: right;
      }

      .invoice-body-total {
          font-size: 16px;
          margin-bottom: 20px;
      }

      .invoice-body-sum hr {
          color:lightgray;
      }

      .invoice-footer {
          padding: 44px;
          background-color: #F5F5F5;
          position: absolute;
          bottom: 0;
          width: 100%;
      }

      .invoice-footer-info {
          font-weight: bolder;
          font-size: 12px;
          color: #808A90;
          margin-bottom: 10px
      }

      .invoice-footer-method > div:first-child {
          font-weight: bolder;
          font-size: 11px;
          color: #1A2C37;
          margin-bottom: 5px
      }

      .invoice-footer-method > div:nth-child(2) {
          font-weight: bold;
          font-size: 12px;
          color: #808A90;
      }

  </style>
  <title><?php echo BackendStrings::getFinanceStrings()['invoice'] ?></title>
</head>
<body>
<div class="invoice">

    <div class="invoice-header">

        <div class="invoice-header-first-line">
            <table class="invoice-header-first-line-company">
                <tr>
                    <?php if (!empty($data['company_name']) && $filename) : ?>
                        <td class="invoice-header-first-line-company-name">
                            <span><b><?php echo $data['company_name'] ?></b></span>
                        </td>
                    <?php endif; ?>
                    <?php if ($filename) : ?>
                        <td>
                            <img src='data:<?php echo $mime; ?>;base64, . <?php echo $img; ?>' alt="logo">
                        </td>
                    <?php endif; ?>
                </tr>
            </table>

            <h2>
                <?php echo BackendStrings::getFinanceStrings()['invoice'] ?>
            </h2>

        </div>

        <br><br><br><br>

        <div class="invoice-header-second-line">

            <table class="invoice-header-table">
                <tr>
                    <td class="invoice-header-table-invoice-number">
                        <span class="invoice-header-table-invoice-number-label"><?php echo BackendStrings::getFinanceStrings()['invoice'] ?></span>
                        <span class="invoice-header-table-invoice-number-value">#<?php echo str_pad($data['invoice_number'], 4, '0', STR_PAD_LEFT) ?></span>
                    </td>
                    <td class="invoice-header-table-company invoice-header-table-company-name">
                        <span><?php echo $data['company_name'] ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="invoice-header-table-invoice-issued">
                        <span class="invoice-header-table-invoice-issued-label"><?php echo BackendStrings::getFinanceStrings()['issued'] ?></span>
                        <span class="invoice-header-table-invoice-issued-date"><?php echo $data['invoice_issued'] ?></span>
                    </td>
                    <td class="invoice-header-table-company">
                        <?php if (!empty($data['company_address'])) : ?>
                            <span><?php echo $data['company_address']?></span>
                        <?php elseif (!empty($data['company_email']) || !empty($data['company_phone'])) : ?>
                            <?php echo !empty($data['company_email']) ? $data['company_email'] : ''?>
                            <?php if (!empty($data['company_email']) && !empty($data['company_phone'])) : ?>
                                |
                            <?php endif; ?>
                            <?php echo !empty($data['company_phone']) ? $data['company_phone'] : ''?>
                        <?php elseif (!empty($data['company_vat_number'])) : ?>
                            <?php echo $data['company_vat_number'] ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ((!empty($data['company_email']) || !empty($data['company_phone'])) && !empty($data['company_address'])) : ?>
                    <tr>
                        <td></td>
                        <td class="invoice-header-table-company">
                            <?php echo !empty($data['company_email']) ? $data['company_email'] : ''?>
                            <?php if (!empty($data['company_email']) && !empty($data['company_phone'])) : ?>
                                 |
                            <?php endif; ?>
                            <?php echo !empty($data['company_phone']) ? $data['company_phone'] : ''?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if (!empty($data['company_vat_number']) && (!empty($data['company_address']) || !empty($data['company_email']) || !empty($data['company_phone']))) : ?>
                    <tr>
                        <td></td>
                        <td class="invoice-header-table-company">
                            <?php echo $data['company_vat_number'] ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

    </div>

    <div class="invoice-body">
        <div class="invoice-body-customer">
            <div class="invoice-body-customer-to"><?php echo BackendStrings::getCommonStrings()['to_upper'] ?></div>
            <div class="invoice-body-customer-name"><?php echo $data['customer_full_name'] ?></div>
            <div class="invoice-body-customer-email"><?php echo !empty($data['customer_email']) ? $data['customer_email'] : '' ?></div>
            <div class="invoice-body-customer-phone"><?php echo !empty($data['customer_phone']) ? $data['customer_phone'] : '' ?></div>
        </div>

        <table class='invoice-body-items'>
            <thead class="invoice-body-items-name">
            <tr>
                <td><?php echo BackendStrings::getFinanceStrings()['invoice_item']; ?></td>
                <td><?php echo BackendStrings::getFinanceStrings()['invoice_unit_price'] .
                        ($includedTax ? BackendStrings::getFinanceStrings()['invoice_incl_tax'] : ''); ?></td>
                <td><?php echo BackendStrings::getFinanceStrings()['invoice_qty']; ?></td>
                <td><?php echo BackendStrings::getFinanceStrings()['invoice_total_item_price'] .
                        ($includedTax ? BackendStrings::getFinanceStrings()['invoice_incl_tax'] : ''); ?></td>
                <?php if ($taxEnabled) : ?>
                    <td><?php echo BackendStrings::getFinanceStrings()['invoice_tax_rate']; ?></td>
                    <td><?php echo BackendStrings::getFinanceStrings()['invoice_tax_amount']; ?></td>
                <?php endif; ?>
            </tr>
            </thead>
            <?php
            foreach ($data['items'] as $key => $appData) : ?>
                <tr class="invoice-body-items-data">
                    <td><?php echo $appData['item_name'] ?></td>
                    <td><?php echo $helperService->getFormattedPrice($appData['invoice_unit_price']) ?></td>
                    <td><?php echo $appData['invoice_qty'] ?></td>
                    <td><?php echo $helperService->getFormattedPrice($appData['invoice_unit_price'] * $appData['invoice_qty']) ?></td>
                    <?php if ($taxEnabled) : ?>
                        <td><?php echo !empty($appData['invoice_tax_rate']) ? $appData['invoice_tax_rate'] : 0 ?></td>
                        <td><?php echo $helperService->getFormattedPrice(!empty($appData['invoice_tax']) ? $appData['invoice_tax'] : 0) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="invoice-body-sum">
            <?php if ($invoiceSubtotal !== $invoiceTotal) : ?>
                <div>
                    <span><?php echo BackendStrings::getFinanceStrings()['invoice_subtotal'];?></span>
                    <span><?php echo $helperService->getFormattedPrice($invoiceSubtotal) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($invoiceDiscount > 0) : ?>
                <div>
                    <span><?php echo BackendStrings::getFinanceStrings()['invoice_discount']; ?></span>
                    <span><?php echo $helperService->getFormattedPrice($invoiceDiscount) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($invoiceTax > 0) : ?>
                <div>
                    <span><?php echo BackendStrings::getFinanceStrings()['invoice_tax']; ?></span>
                    <span><?php echo $helperService->getFormattedPrice($invoiceTax) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($invoiceSubtotal !== $invoiceTotal || $invoiceTax > 0) : ?>
                <hr>
            <?php endif; ?>
            <div class="invoice-body-total">
                <span><?php echo BackendStrings::getFinanceStrings()['invoice_total'] .
                        ($includedTax && $invoiceTotal > 0 ? BackendStrings::getFinanceStrings()['invoice_incl_tax'] : ''); ?></span>
                <span><?php echo $helperService->getFormattedPrice($invoiceTotal) ?></span>
            </div>
            <?php if ($invoicePaid) : ?>
                <hr>
                <div>
                    <span><?php echo BackendStrings::getFinanceStrings()['invoice_paid']; ?></span>
                    <span><?php echo $helperService->getFormattedPrice($invoicePaid) ?></span>
                </div>
                <div>
                    <span><?php echo BackendStrings::getFinanceStrings()['invoice_left_to_pay']; ?></span>
                    <span><?php echo $helperService->getFormattedPrice($invoiceLeftToPay) ?></span>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="invoice-footer">
<!--        <div class="invoice-footer-info">--><?php //echo BackendStrings::getFinanceStrings()['invoice_payment_info']; ?><!--</div>-->
        <div class="invoice-footer-method">
            <div><?php echo BackendStrings::getFinanceStrings()['invoice_payment_method']; ?></div>
            <div><?php echo $data['invoice_method'] ?></div>
        </div>
    </div>

</div>

</body>
</html>
