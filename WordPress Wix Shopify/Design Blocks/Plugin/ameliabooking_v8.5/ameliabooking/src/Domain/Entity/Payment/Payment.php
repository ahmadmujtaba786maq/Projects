<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Payment;

use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentData;
use AmeliaBooking\Domain\ValueObjects\String\Url;

/**
 * Class Payment
 *
 * @package AmeliaBooking\Domain\Entity\Payment
 */
class Payment
{
    /** @var Id */
    private $id;

    /** @var  Id */
    private $customerBookingId;

    /** @var  Id */
    private $packageCustomerId;

    /** @var  Id */
    private $parentId;

    /** @var Id */
    private $invoiceNumber;

    /** @var  Price */
    private $amount;

    /** @var  DateTimeValue */
    private $dateTime;

    /** @var  PaymentStatus */
    private $status;

    /** @var  PaymentGateway */
    private $gateway;

    /** @var  Name */
    private $gatewayTitle;

    /** @var PaymentData */
    private $data;

    /** @var DateTimeValue */
    private $created;

    /** @var Name */
    private $entity;

    /** @var BooleanValueObject */
    private $actionsCompleted;

    /** @var BooleanValueObject */
    private $triggeredActions;

    /** @var Id */
    private $wcOrderId;

    /** @var Id */
    private $wcOrderItemId;

    /** @var Url */
    private $wcOrderUrl;

    /** @var Price */
    private $wcItemCouponValue;

    /** @var Price */
    private $wcItemTaxValue;

    /** @var string */
    private $transactionId;

    /** @var Json */
    private $transfers;

    /** @var array */
    private $paymentLinks;

    /**
     * Payment constructor.
     *
     * @param Price          $amount
     * @param DateTimeValue  $dateTime
     * @param PaymentStatus  $status
     * @param PaymentGateway $gateway
     * @param PaymentData    $data
     */
    public function __construct(
        Price $amount,
        DateTimeValue $dateTime,
        PaymentStatus $status,
        PaymentGateway $gateway,
        PaymentData $data
    ) {
        $this->amount = $amount;

        $this->dateTime = $dateTime;

        $this->status = $status;

        $this->gateway = $gateway;

        $this->data = $data;
    }

    /**
     * @return Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Id $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Id
     */
    public function getCustomerBookingId()
    {
        return $this->customerBookingId;
    }

    /**
     * @param Id $customerBookingId
     */
    public function setCustomerBookingId($customerBookingId)
    {
        $this->customerBookingId = $customerBookingId;
    }

    /**
     * @return Id
     */
    public function getPackageCustomerId()
    {
        return $this->packageCustomerId;
    }

    /**
     * @param Id $packageCustomerId
     */
    public function setPackageCustomerId($packageCustomerId)
    {
        $this->packageCustomerId = $packageCustomerId;
    }

    /**
     * @return Id
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param Id $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }

    /**
     * @return Id
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    /**
     * @param Id $invoiceNumber
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;
    }


    /**
     * @return Price
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param Price $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return DateTimeValue
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param DateTimeValue $dateTime
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @return PaymentStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param PaymentStatus $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return PaymentGateway
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * @param PaymentGateway $gateway
     */
    public function setGateway($gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * @return Name
     */
    public function getGatewayTitle()
    {
        return $this->gatewayTitle;
    }

    /**
     * @param Name $gatewayTitle
     */
    public function setGatewayTitle($gatewayTitle)
    {
        $this->gatewayTitle = $gatewayTitle;
    }

    /**
     * @return PaymentData
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param PaymentData $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return DateTimeValue
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param DateTimeValue $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return Name
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param Name $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return BooleanValueObject
     */
    public function getActionsCompleted()
    {
        return $this->actionsCompleted;
    }

    /**
     * @param BooleanValueObject $actionsCompleted
     */
    public function setActionsCompleted($actionsCompleted)
    {
        $this->actionsCompleted = $actionsCompleted;
    }

    /**
     * @return BooleanValueObject
     */
    public function getTriggeredActions()
    {
        return $this->triggeredActions;
    }

    /**
     * @param BooleanValueObject $triggeredActions
     */
    public function setTriggeredActions($triggeredActions)
    {
        $this->triggeredActions = $triggeredActions;
    }

    /**
     * @return Id
     */
    public function getWcOrderId()
    {
        return $this->wcOrderId;
    }

    /**
     * @param Id $wcOrderId
     */
    public function setWcOrderId($wcOrderId)
    {
        $this->wcOrderId = $wcOrderId;
    }

    /**
     * @return Id
     */
    public function getWcOrderItemId()
    {
        return $this->wcOrderItemId;
    }

    /**
     * @param Id $wcOrderItemId
     */
    public function setWcOrderItemId($wcOrderItemId)
    {
        $this->wcOrderItemId = $wcOrderItemId;
    }

    /**
     * @return Url
     */
    public function getWcOrderUrl()
    {
        return $this->wcOrderUrl;
    }

    /**
     * @param Url $wcOrderUrl
     */
    public function setWcOrderUrl($wcOrderUrl)
    {
        $this->wcOrderUrl = $wcOrderUrl;
    }

    /**
     * @return Price
     */
    public function getWcItemCouponValue()
    {
        return $this->wcItemCouponValue;
    }

    /**
     * @param Price $wcItemCouponValue
     */
    public function setWcItemCouponValue($wcItemCouponValue)
    {
        $this->wcItemCouponValue = $wcItemCouponValue;
    }

    /**
     * @return Price
     */
    public function getWcItemTaxValue()
    {
        return $this->wcItemTaxValue;
    }

    /**
     * @param Price $wcItemTaxValue
     */
    public function setWcItemTaxValue($wcItemTaxValue)
    {
        $this->wcItemTaxValue = $wcItemTaxValue;
    }


    /**
     * @return string|null
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string|null $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return Json
     */
    public function getTransfers()
    {
        return $this->transfers;
    }

    /**
     * @param Json $transfers
     */
    public function setTransfers($transfers)
    {
        $this->transfers = $transfers;
    }

    /**
     * @return array
     */
    public function getPaymentLinks()
    {
        return $this->paymentLinks;
    }

    /**
     * @param array $paymentLinks
     */
    public function setPaymentLinks($paymentLinks)
    {
        $this->paymentLinks = $paymentLinks;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'                => null !== $this->getId() ? $this->getId()->getValue() : null,
            'customerBookingId' => $this->customerBookingId ? $this->customerBookingId->getValue() : null,
            'packageCustomerId' => $this->packageCustomerId ? $this->packageCustomerId->getValue() : null,
            'parentId'          => $this->getParentId() ? $this->getParentId()->getValue() : null,
            'amount'            => $this->amount->getValue(),
            'gateway'           => $this->gateway->getName()->getValue(),
            'gatewayTitle'      => null !== $this->getGatewayTitle() ? $this->getGatewayTitle()->getValue() : '',
            'dateTime'          => null !== $this->dateTime ? $this->dateTime->getValue()->format('Y-m-d H:i:s') : null,
            'status'            => $this->status->getValue(),
            'data'              => $this->data->getValue(),
            'entity'            => $this->getEntity() ? $this->getEntity()->getValue() : null,
            'created'           => $this->getCreated() ? $this->getCreated()->getValue()->format('Y-m-d H:i:s') : null,
            'actionsCompleted'  => $this->getActionsCompleted() ? $this->getActionsCompleted()->getValue() : null,
            'triggeredActions'  => $this->getTriggeredActions() ? $this->getTriggeredActions()->getValue() : null,
            'wcOrderId'         => $this->getWcOrderId() ? $this->getWcOrderId()->getValue() : null,
            'wcOrderItemId'     => $this->getWcOrderItemId() ? $this->getWcOrderItemId()->getValue() : null,
            'wcOrderUrl'        => $this->getWcOrderUrl() ? $this->getWcOrderUrl()->getValue() : null,
            'wcItemCouponValue' => $this->getWcItemCouponValue() ? $this->getWcItemCouponValue()->getValue() : null,
            'wcItemTaxValue'    => $this->getWcItemTaxValue() ? $this->getWcItemTaxValue()->getValue() : null,
            'transactionId'     => $this->getTransactionId(),
            'transfers'         => $this->getTransfers() ? $this->getTransfers()->getValue() : null,
            'invoiceNumber'     => $this->getInvoiceNumber() ? $this->getInvoiceNumber()->getValue() : null,
            'paymentLinks'      => $this->getPaymentLinks() ?: null,
        ];
    }
}
