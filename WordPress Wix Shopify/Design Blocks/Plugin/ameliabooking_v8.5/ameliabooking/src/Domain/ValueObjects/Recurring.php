<?php

namespace AmeliaBooking\Domain\ValueObjects;

use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;
use AmeliaBooking\Domain\ValueObjects\String\Cycle;

/**
 * Class Recurring
 *
 * @package AmeliaBooking\Domain\ValueObjects
 */
final class Recurring
{
    /**
     * @var Cycle
     */
    private $cycle;

    /**
     * @var WholeNumber
     */
    private $cycleInterval;

    /**
     * @var string
     */
    private $monthlyRepeat;

    /**
     * @var string
     */
    private $monthlyOnRepeat;

    /**
     * @var string
     */
    private $monthlyOnDay;

    /**
     * @var DateTimeValue
     */
    private $monthDate;

    /**
     * @var WholeNumber
     */
    private $order;

    /**
     * @var DateTimeValue
     */
    private $until;

    /**
     * Recurring constructor.
     *
     * @param Cycle $cycle
     */
    public function __construct(Cycle $cycle)
    {
        $this->cycle = $cycle;
    }

    /**
     * @param Cycle $cycle
     */
    public function setCycle(Cycle $cycle)
    {
        $this->cycle = $cycle;
    }

    /**
     * Return the recurring cycle
     *
     * @return Cycle
     */
    public function getCycle()
    {
        return $this->cycle;
    }

    /**
     * @return WholeNumber
     */
    public function getCycleInterval()
    {
        return $this->cycleInterval;
    }

    /**
     * @param WholeNumber $cycleInterval
     */
    public function setCycleInterval($cycleInterval)
    {
        $this->cycleInterval = $cycleInterval;
    }

    /**
     * @return string
     */
    public function getMonthlyRepeat()
    {
        return $this->monthlyRepeat;
    }

    /**
     * @param string $monthlyRepeat
     */
    public function setMonthlyRepeat($monthlyRepeat)
    {
        $this->monthlyRepeat = $monthlyRepeat;
    }

    /**
     * @return string
     */
    public function getMonthlyOnRepeat()
    {
        return $this->monthlyOnRepeat;
    }

    /**
     * @param string $monthlyOnRepeat
     */
    public function setMonthlyOnRepeat($monthlyOnRepeat)
    {
        $this->monthlyOnRepeat = $monthlyOnRepeat;
    }

    /**
     * @return string
     */
    public function getMonthlyOnDay()
    {
        return $this->monthlyOnDay;
    }

    /**
     * @param string $monthlyOnDay
     */
    public function setMonthlyOnDay($monthlyOnDay)
    {
        $this->monthlyOnDay = $monthlyOnDay;
    }

    /**
     * @return DateTimeValue
     */
    public function getMonthDate()
    {
        return $this->monthDate;
    }

    /**
     * @param DateTimeValue $monthDate
     */
    public function setMonthDate($monthDate)
    {
        $this->monthDate = $monthDate;
    }

    /**
     * @param WholeNumber $order
     */
    public function setOrder(WholeNumber $order)
    {
        $this->order = $order;
    }

    /**
     * Return the recurring order
     *
     * @return WholeNumber
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param DateTimeValue $until
     */
    public function setUntil(DateTimeValue $until)
    {
        $this->until = $until;
    }

    /**
     * Return the recurring end
     *
     * @return DateTimeValue
     */
    public function getUntil()
    {
        return $this->until;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'cycle'           => $this->getCycle()->getValue(),
            'order'           => $this->getOrder() ? $this->getOrder()->getValue() : null,
            'until'           => $this->getUntil() ? $this->getUntil()->getValue()->format('Y-m-d H:i:s') : null,
            'cycleInterval'   => $this->getCycleInterval() ? $this->getCycleInterval()->getValue() : null,
            'monthlyRepeat'   => $this->getMonthlyRepeat(),
            'monthDate'       => $this->getMonthDate() ? $this->getMonthDate()->getValue()->format('Y-m-d H:i:s') : null,
            'monthlyOnRepeat' => $this->getMonthlyOnRepeat(),
            'monthlyOnDay'    => $this->getMonthlyOnDay()
        ];
    }
}
