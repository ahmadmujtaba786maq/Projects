<?php

namespace AmeliaBooking\Domain\Services\Booking;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Booking\Event\EventTicket;
use AmeliaBooking\Domain\Entity\Gallery\GalleryImage;
use AmeliaBooking\Domain\Factory\Booking\Event\EventTicketFactory;
use AmeliaBooking\Domain\Factory\Gallery\GalleryImageFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\Recurring;
use AmeliaBooking\Domain\ValueObjects\String\Cycle;
use AmeliaBooking\Infrastructure\Common\Container;
use DateTime as DateTime;

/**
 * Class EventDomainService
 *
 * @package AmeliaBooking\Domain\Services\Booking
 */
class EventDomainService
{
    /**
     * @param DateTime $periodStart
     * @param DateTime $periodEnd
     *
     * @return void
     */
    public function fixPeriod($periodStart, $periodEnd)
    {
        if ($periodStart->format('Y-m-d H:i') === $periodEnd->format('Y-m-d H:i')) {
            $periodEnd->modify('60 minutes');
        }
    }

    /**
     * @param Recurring  $recurring
     * @param Collection $eventPeriods
     *
     * @return array
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getRecurringEventsPeriods($recurring, $eventPeriods)
    {
        $recurringPeriods = [];

        if (!($recurring && $recurring->getCycle() && $recurring->getUntil())) {
            return $recurringPeriods;
        }

        $dateNew         = null;
        $modifyCycle     = 'days';
        $modifyBaseValue = $recurring->getCycleInterval()->getValue();

        switch ($recurring->getCycle()->getValue()) {
            case (Cycle::DAILY):
                $modifyCycle = 'days';
                break;

            case (Cycle::WEEKLY):
                $modifyCycle     = 'days';
                $modifyBaseValue = 7 * $modifyBaseValue;
                break;

            case (Cycle::MONTHLY):
                if ($recurring->getMonthlyRepeat() === 'on') {
                    $repeatPeriod = $recurring->getMonthlyOnRepeat() . ' ' . $recurring->getMonthlyOnDay();
                } else {
                    $dateNew = $recurring->getMonthDate();
                }
                $modifyCycle = 'months';
                break;

            case (Cycle::YEARLY):
                $modifyCycle = 'years';
                break;
        }

        $hasMoreRecurringPeriods = true;

        $recurringOrder = 1;

        $recurringOrderEvent = 2;

        while ($hasMoreRecurringPeriods) {
            $periods = new Collection();

            $modifyValue = $recurringOrder * $modifyBaseValue;

            $periodStartDate0 = DateTimeService::getCustomDateTimeObject($eventPeriods->getItem(0)->getPeriodStart()->getValue()->format('Y-m-d H:i:s'));
            $periodEndDate0   = DateTimeService::getCustomDateTimeObject($eventPeriods->getItem(0)->getPeriodEnd()->getValue()->format('Y-m-d H:i:s'));

            $periodStart0  = null;
            $dayDifference = null;

            if (isset($repeatPeriod)) {
                $periodStart0 = $this->getNextPeriodStartDate($periodStartDate0, $modifyValue, $repeatPeriod);
                if ($periodStart0 === null) {
                    if ($periodStart0 > $recurring->getUntil()->getValue()) {
                        break;
                    }
                    $recurringOrder++;
                    $recurringOrderEvent++;
                    continue;
                }
                $dayDifference = $periodStartDate0->diff($periodStart0);
            } elseif ($dateNew) {
                $periodStartNew = DateTimeService::getCustomDateTimeObject($dateNew->getValue()->format('Y-m-d H:i:s'));
                $dayDifference  = $periodStartDate0->diff($periodStartNew);
            }

            for ($i = 0; $i < count($eventPeriods->getItems()); $i++) {
                $periodStartDate = DateTimeService::getCustomDateTimeObject($eventPeriods->getItem($i)->getPeriodStart()->getValue()->format('Y-m-d H:i:s'));
                $periodEndDate   = DateTimeService::getCustomDateTimeObject($eventPeriods->getItem($i)->getPeriodEnd()->getValue()->format('Y-m-d H:i:s'));

                if (isset($repeatPeriod)) {
                    if ($i === 0) {
                        $periodStart = $periodStart0;
                        $periodEnd   = $periodEndDate0->add($dayDifference);
                    } else {
                        $periodStart = $periodStartDate->add($dayDifference);
                        $periodEnd   = $periodEndDate->add($dayDifference);
                    }
                } else {
                    $periodStart = $periodStartDate->modify("+{$modifyValue} {$modifyCycle}");
                    $periodEnd   = $periodEndDate->modify("+{$modifyValue} {$modifyCycle}");
                    if ($dateNew) {
                        $periodStart = $periodStart->add($dayDifference);
                        $periodEnd   = $periodEnd->add($dayDifference);
                    }
                }

                $newEventPeriod = new EventPeriod();

                $newEventPeriod->setPeriodStart(new DateTimeValue($periodStart));
                $newEventPeriod->setPeriodEnd(new DateTimeValue($periodEnd));

                $periods->addItem($newEventPeriod);

                if ($periodStart > $recurring->getUntil()->getValue()) {
                    $hasMoreRecurringPeriods = false;
                }
            }

            if ($hasMoreRecurringPeriods) {
                $recurringPeriods[] = ['order' => $recurringOrderEvent, 'periods' => $periods];
                $recurringOrderEvent++;
                $recurringOrder++;
            }
        }

        return $recurringPeriods;
    }

    /**
     * @param Collection $eventPeriods
     * @param bool       $setId
     *
     * @return Collection
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getClonedEventPeriods($eventPeriods, $setId = false)
    {
        $clonedPeriods = new Collection();

        /** @var EventPeriod $eventPeriod **/
        foreach ($eventPeriods->getItems() as $eventPeriod) {
            $periodStart = DateTimeService::getCustomDateTimeObject(
                $eventPeriod->getPeriodStart()->getValue()->format('Y-m-d H:i:s')
            );

            $periodEnd = DateTimeService::getCustomDateTimeObject(
                $eventPeriod->getPeriodEnd()->getValue()->format('Y-m-d H:i:s')
            );

            $newEventPeriod = new EventPeriod();

            $newEventPeriod->setPeriodStart(new DateTimeValue($periodStart));
            $newEventPeriod->setPeriodEnd(new DateTimeValue($periodEnd));

            if ($eventPeriod->getZoomMeeting()) {
                $newEventPeriod->setZoomMeeting($eventPeriod->getZoomMeeting());
            }

            if ($setId) {
                $newEventPeriod->setId(new Id($eventPeriod->getId()->getValue()));
            }

            $clonedPeriods->addItem($newEventPeriod);
        }

        return $clonedPeriods;
    }


    /**
     * @param Collection $eventTickets
     *
     * @return Collection
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getClonedEventTickets($eventTickets)
    {
        $clonedTickets = new Collection();

        /** @var EventTicket $eventTicket **/
        foreach ($eventTickets->getItems() as $eventTicket) {
            $newEventTicket = EventTicketFactory::create($eventTicket->toArray());
            $clonedTickets->addItem($newEventTicket);
        }

        return $clonedTickets;
    }


    /**
     * @param Event      $followingEvent
     * @param Event      $originEvent
     * @param Collection $clonedOriginEventPeriods
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function buildFollowingEvent($followingEvent, $originEvent, $clonedOriginEventPeriods)
    {
        $followingEvent->setName($originEvent->getName());
        $followingEvent->setPrice($originEvent->getPrice());
        $followingEvent->setMaxCapacity($originEvent->getMaxCapacity());
        $followingEvent->setTags($originEvent->getTags());
        $followingEvent->setProviders($originEvent->getProviders());
        $followingEvent->setBringingAnyone($originEvent->getBringingAnyone());
        $followingEvent->setBookMultipleTimes($originEvent->getBookMultipleTimes());
        $followingEvent->setOrganizerId($originEvent->getOrganizerId());
        $followingEvent->setCustomPricing($originEvent->getCustomPricing());
        $followingEvent->setCloseAfterMin($originEvent->getCloseAfterMin());
        $followingEvent->setMaxCustomCapacity($originEvent->getMaxCustomCapacity());
        $followingEvent->setCloseAfterMinBookings($originEvent->getCloseAfterMinBookings());
        $followingEvent->setAggregatedPrice($originEvent->getAggregatedPrice());
        $followingEvent->setMaxExtraPeople($originEvent->getMaxExtraPeople());
        $followingEvent->setNotifyParticipants($originEvent->isNotifyParticipants());

        if ($originEvent->getCustomPricing() && $originEvent->getCustomPricing()->getValue() && $followingEvent->getBookings()->length() === 0) {
            $newEventTickets = new Collection();
            /** @var EventTicket $eventTicket */
            foreach ($originEvent->getCustomTickets()->getItems() as $ticketIndex => $eventTicket) {
                if (!empty($followingEvent->getCustomTickets()->toArray()[$ticketIndex])) {
                    $editedTicket = EventTicketFactory::create($followingEvent->getCustomTickets()->toArray()[$ticketIndex]);
                    $editedTicket->setName($eventTicket->getName());
                    $editedTicket->setEnabled($eventTicket->getEnabled());
                    $editedTicket->setPrice($eventTicket->getPrice());
                    if ($eventTicket->getTranslations()) {
                        $editedTicket->setTranslations($eventTicket->getTranslations());
                    }
                    $editedTicket->setSpots($eventTicket->getSpots());
                    $newEventTickets->addItem($editedTicket);
                } else {
                    $newTicket = EventTicketFactory::create($eventTicket->toArray());
                    $newTicket->setId(new Id(0));
                    $newTicket->setEventId($followingEvent->getId() ? new Id($followingEvent->getId()->getValue()) : null);
                    $newTicket->setSold(new IntegerValue(0));
                    $newTicket->setDateRanges(new Json('[]'));
                    $newEventTickets->addItem($newTicket);
                }
            }
            $followingEvent->setCustomTickets($newEventTickets);
        }



        if ($originEvent->getTranslations()) {
            $followingEvent->setTranslations($originEvent->getTranslations());
        }

        if ($originEvent->getDeposit()) {
            $followingEvent->setDeposit($originEvent->getDeposit());
        }

        if ($originEvent->getDepositPayment()) {
            $followingEvent->setDepositPayment($originEvent->getDepositPayment());
        }

        if ($originEvent->getDepositPerPerson()) {
            $followingEvent->setDepositPerPerson($originEvent->getDepositPerPerson());
        }

        $followingEventGallery = new Collection();

        /** @var GalleryImage $image **/
        foreach ($originEvent->getGallery()->getItems() as $image) {
            $followingEventGallery->addItem(
                GalleryImageFactory::create(
                    [
                    'id'               => null,
                    'entityId'         => $followingEvent->getId() ? $followingEvent->getId()->getValue() : null,
                    'entityType'       => $image->getEntityType()->getValue(),
                    'pictureFullPath'  => $image->getPicture()->getFullPath(),
                    'pictureThumbPath' => $image->getPicture()->getThumbPath(),
                    'position'         => $image->getPosition()->getValue(),
                    ]
                )
            );
        }

        $followingEvent->setGallery($followingEventGallery);

        if ($originEvent->getSettings()) {
            $followingEvent->setSettings($originEvent->getSettings());
        }

        $followingEvent->setBookingOpens($originEvent->getBookingOpens());

        $followingEvent->setBookingCloses($originEvent->getBookingCloses());

        $followingEvent->setBookingOpensRec($originEvent->getBookingOpensRec());

        $followingEvent->setBookingClosesRec($originEvent->getBookingClosesRec());

        if ($originEvent->getLocationId()) {
            $followingEvent->setLocationId($originEvent->getLocationId());
        }

        if ($originEvent->getCustomLocation()) {
            $followingEvent->setCustomLocation($originEvent->getCustomLocation());
        }

        if ($originEvent->getTags()) {
            $followingEvent->setTags($originEvent->getTags());
        }

        if ($originEvent->getDescription()) {
            $followingEvent->setDescription($originEvent->getDescription());
        }

        if ($originEvent->getColor()) {
            $followingEvent->setColor($originEvent->getColor());
        }

        if ($originEvent->getShow()) {
            $followingEvent->setShow($originEvent->getShow());
        }

        if ($originEvent->getZoomUserId()) {
            $followingEvent->setZoomUserId($originEvent->getZoomUserId());
        }

        $modifyCycle     = 'days';
        $modifyBaseValue = $originEvent->getRecurring()->getCycleInterval()->getValue();
        $dateNew         = null;

        switch ($originEvent->getRecurring()->getCycle()->getValue()) {
            case (Cycle::DAILY):
                $modifyCycle = 'days';
                break;

            case (Cycle::WEEKLY):
                $modifyCycle     = 'days';
                $modifyBaseValue = 7 * $modifyBaseValue;
                break;

            case (Cycle::MONTHLY):
                if ($followingEvent->getRecurring()->getMonthlyRepeat() === 'on') {
                    $repeatPeriod = $followingEvent->getRecurring()->getMonthlyOnRepeat() . ' ' . $followingEvent->getRecurring()->getMonthlyOnDay();
                } else {
                    $dateNew = $followingEvent->getRecurring()->getMonthDate();
                }
                $modifyCycle = 'months';
                break;

            case (Cycle::YEARLY):
                $modifyCycle = 'years';
                break;
        }

        $modifyValue = $modifyBaseValue * ($followingEvent->getRecurring()->getOrder()->getValue() - 1);

        $periodStartDate0 =
            DateTimeService::getCustomDateTimeObject($clonedOriginEventPeriods->getItem(0)->getPeriodStart()->getValue()->format('Y-m-d H:i:s'));
        $periodEndDate0   =
            DateTimeService::getCustomDateTimeObject($clonedOriginEventPeriods->getItem(0)->getPeriodEnd()->getValue()->format('Y-m-d H:i:s'));

        $periodStart0   = null;
        $dayDifference  = null;
        $periodStartNew = null;
        if (isset($repeatPeriod)) {
            $periodStart0 = $this->getNextPeriodStartDate($periodStartDate0, $modifyValue, $repeatPeriod);
            if ($periodStart0 === null) {
                // for added events
                return false;
            }
            $dayDifference = $periodStartDate0->diff($periodStart0);
        } elseif ($dateNew) {
            $thisPeriodStart =
                DateTimeService::getCustomDateTimeObject($originEvent->getPeriods()->getItem(0)->getPeriodStart()->getValue()->format('Y-m-d H:i:s'));
            $periodStartNew  =
                DateTimeService::getCustomDateTimeObject($dateNew->getValue()->format('Y-m-d H:i:s'));
            $dayDifference   = $thisPeriodStart->diff($periodStartNew);
        }


        /** @var EventPeriod $followingEventPeriod */
        foreach ($followingEvent->getPeriods()->getItems() as $key => $followingEventPeriod) {
            if ($clonedOriginEventPeriods->keyExists($key)) {
                /** @var EventPeriod $clonedOriginEventPeriod */
                $clonedOriginEventPeriod = $clonedOriginEventPeriods->getItem($key);

                $periodStartDate = DateTimeService::getCustomDateTimeObject($clonedOriginEventPeriod->getPeriodStart()->getValue()->format('Y-m-d H:i:s'));
                $periodEndDate   = DateTimeService::getCustomDateTimeObject($clonedOriginEventPeriod->getPeriodEnd()->getValue()->format('Y-m-d H:i:s'));

                if (isset($repeatPeriod)) {
                    if ($key === 0) {
                        $periodStart = $periodStart0;
                        $periodEnd   = $periodEndDate0->add($dayDifference);
                    } else {
                        $periodStart = $periodStartDate->add($dayDifference);
                        $periodEnd   = $periodEndDate->add($dayDifference);
                    }
                } else {
                    $periodStart = $periodStartDate->modify("+{$modifyValue} {$modifyCycle}");
                    $periodEnd   = $periodEndDate->modify("+{$modifyValue} {$modifyCycle}");
                    $toEndTime   = $periodStart->diff($periodEnd);
                    if ($dateNew) {
                        $periodStart      = DateTimeService::getCustomDateTimeObject($periodStart->format('Y-m-' . $periodStartNew->format('d') . ' H:i:s'));
                        $periodStartClone = clone $periodStart;
                        $periodEnd        = $periodStartClone->add($toEndTime);
                    }
                }

                $followingEventPeriod->setPeriodStart(new DateTimeValue($periodStart));
                $followingEventPeriod->setPeriodEnd(new DateTimeValue($periodEnd));
            } else {
                $followingEvent->getPeriods()->deleteItem($key);
            }
        }

        /** @var EventPeriod $originEventPeriod */
        foreach ($originEvent->getPeriods()->getItems() as $key => $originEventPeriod) {
            if (!$followingEvent->getPeriods()->keyExists($key)) {

                /** @var EventPeriod $followingEventPeriod */
                $newFollowingEventPeriod = new EventPeriod();

                $newPeriodStart = DateTimeService::getCustomDateTimeObject(
                    $originEventPeriod->getPeriodStart()->getValue()->format('Y-m-d H:i:s')
                );

                $newPeriodEnd = DateTimeService::getCustomDateTimeObject(
                    $originEventPeriod->getPeriodEnd()->getValue()->format('Y-m-d H:i:s')
                );

                if (isset($repeatPeriod)) {
                    $periodStart = $newPeriodStart->add($dayDifference);
                    $periodEnd   = $newPeriodEnd->add($dayDifference);
                } else {
                    $periodStart = $newPeriodStart->modify("+{$modifyValue} {$modifyCycle}");
                    $periodEnd   = $newPeriodEnd->modify("+{$modifyValue} {$modifyCycle}");
                    $toEndTime   = $periodStart->diff($periodEnd);
                    if ($dateNew && $dayDifference && $dayDifference->format('%a') !== '0') {
                        $periodStart      = $periodStart->add($dayDifference);
                        $periodStartClone = clone $periodStart;
                        $periodEnd        = $periodStartClone->add($toEndTime);
                    }
                }

                $newFollowingEventPeriod->setPeriodStart(new DateTimeValue($periodStart));

                $newFollowingEventPeriod->setPeriodEnd(new DateTimeValue($periodEnd));

                $newFollowingEventPeriod->setEventId(new Id($followingEvent->getId()->getValue()));

                $followingEvent->getPeriods()->addItem($newFollowingEventPeriod);
            }
        }

        return true;
    }

    /**
     * @param Container $container
     * @param array $events
     *
     * @return array
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getShortcodeForEventList($container, $events)
    {

        /** @var SettingsService $settingsService */
        $settingsService = $container->get('domain.settings.service');
        $dateFormat      = $settingsService->getSetting('wordpress', 'dateFormat');

        for ($i = 0; $i < count($events); $i++) {
            $dateString = explode(" ", $events[$i]['periods'][0]['periodStart'])[0];
            $newDate    = date_i18n($dateFormat, strtotime($dateString));
            $events[$i]['formattedPeriodStart'] = $newDate;
        }
        return $events;
    }

    /**
     * @param \DateTime $periodStartDate0
     * @param int $modifyValue
     * @param string $repeatPeriod
     *
     * @return \DateTime
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Exception
     */
    public function getNextPeriodStartDate($periodStartDate0, $modifyValue, $repeatPeriod)
    {
        $month = (int)$periodStartDate0->format('m') + $modifyValue;
        $year  = (int)$periodStartDate0->format('Y');

        $year  += floor($month / 12);
        $month -= 12 * floor($month / 12);
        $time   = (int)$periodStartDate0->format('H') * 60 + (int)$periodStartDate0->format('i');

        $periodStart0 = DateTimeService::getCustomDateTimeObject($repeatPeriod . " of $year-$month");
        return explode(' ', $repeatPeriod)[0] === 'fifth'
            && (int)$periodStart0->format('m') !== (int)$month ? null : $periodStart0->add(new \DateInterval('PT' . $time . 'M'));
    }
}
