<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\AbstractCustomerBooking;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;
use AmeliaBooking\Domain\ValueObjects\String\Token;

/**
 * Class PackageCustomer
 *
 * @package AmeliaBooking\Domain\Entity\Bookable\Service
 */
class PackageCustomer extends AbstractCustomerBooking
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $packageId;

    /** @var DateTimeValue */
    private $end;

    /** @var DateTimeValue */
    private $start;

    /** @var DateTimeValue */
    private $purchased;

    /** @var Collection */
    private $payments;

    /** @var WholeNumber */
    private $bookingsCount;

    /** @var Token */
    private $token;


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
    public function setId(Id $id)
    {
        $this->id = $id;
    }

    /**
     * @return Id
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * @param Id $packageId
     */
    public function setPackageId(Id $packageId)
    {
        $this->packageId = $packageId;
    }

    /**
     * @return Collection
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * @param Collection $payments
     */
    public function setPayments(Collection $payments)
    {
        $this->payments = $payments;
    }

    /**
     * @return DateTimeValue
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param DateTimeValue $end
     */
    public function setEnd(DateTimeValue $end)
    {
        $this->end = $end;
    }

    /**
     * @return DateTimeValue
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param DateTimeValue $start
     */
    public function setStart(DateTimeValue $start)
    {
        $this->start = $start;
    }

    /**
     * @return DateTimeValue
     */
    public function getPurchased()
    {
        return $this->purchased;
    }

    /**
     * @param DateTimeValue $purchased
     */
    public function setPurchased(DateTimeValue $purchased)
    {
        $this->purchased = $purchased;
    }

    /**
     * @return WholeNumber
     */
    public function getBookingsCount()
    {
        return $this->bookingsCount;
    }

    /**
     * @param WholeNumber $bookingsCount
     */
    public function setBookingsCount(WholeNumber $bookingsCount)
    {
        $this->bookingsCount = $bookingsCount;
    }

    /**
     * @return Token|null
     */
    public function getToken()
    {
        return $this->token;
    }


    /**
     * @param Token $token
     */
    public function setToken(Token $token)
    {
        $this->token = $token;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $dateTimeFormat = 'Y-m-d H:i:s';

        return array_merge(
            parent::toArray(),
            [
                'packageId'     => $this->getPackageId() ? $this->getPackageId()->getValue() : null,
                'payments'      => $this->getPayments() ? $this->getPayments()->toArray() : null,
                'start'         => $this->getStart() ? $this->getStart()->getValue()->format($dateTimeFormat) : null,
                'end'           => $this->getEnd() ? $this->getEnd()->getValue()->format($dateTimeFormat) : null,
                'purchased'     => $this->getPurchased() ?
                    $this->getPurchased()->getValue()->format($dateTimeFormat) : null,
                'bookingsCount' => $this->getBookingsCount() ? $this->getBookingsCount()->getValue() : null,
                'token'         => $this->getToken() ? $this->getToken()->getValue() : null,
            ]
        );
    }
}
