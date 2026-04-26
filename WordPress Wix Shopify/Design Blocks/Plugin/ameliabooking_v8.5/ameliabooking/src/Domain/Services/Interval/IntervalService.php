<?php

namespace AmeliaBooking\Domain\Services\Interval;

/**
 * Class IntervalService
 *
 * @package AmeliaBooking\Domain\Services\Interval
 */
class IntervalService
{
    /**
     * @param string $time
     *
     * @return int
     */
    public function getSeconds($time)
    {
        $timeParts = explode(':', $time);

        return (int)$timeParts[0] * 60 * 60 + (int)$timeParts[1] * 60 + (int)$timeParts[2];
    }

    /**
     * @param string $endTime
     *
     * @return string
     */
    public function getEndTimeString($endTime)
    {
        return $endTime === '00:00:00' ? '24:00:00' : $endTime;
    }

    /**
     * get available appointment intervals.
     *
     * @param array $availableIntervals
     * @param array $unavailableIntervals
     *
     * @return array
     */
    public function getAvailableIntervals(&$availableIntervals, $unavailableIntervals)
    {
        $parsedAvailablePeriod = [];

        ksort($availableIntervals);
        ksort($unavailableIntervals);

        foreach ($availableIntervals as $available) {
            $parsedAvailablePeriod[] = $available;

            foreach ($unavailableIntervals as $unavailable) {
                if ($parsedAvailablePeriod) {
                    $lastAvailablePeriod = $parsedAvailablePeriod[sizeof($parsedAvailablePeriod) - 1];

                    if ($unavailable[0] >= $lastAvailablePeriod[0] && $unavailable[1] <= $lastAvailablePeriod[1]) {
                        // unavailable interval is inside available interval
                        $fixedPeriod = array_pop($parsedAvailablePeriod);

                        if ($fixedPeriod[0] !== $unavailable[0]) {
                            $parsedAvailablePeriod[] = [$fixedPeriod[0], $unavailable[0], $fixedPeriod[2]];
                        }

                        if ($unavailable[1] !== $fixedPeriod[1]) {
                            $parsedAvailablePeriod[] = [$unavailable[1], $fixedPeriod[1], $fixedPeriod[2]];
                        }
                    } elseif ($unavailable[0] <= $lastAvailablePeriod[0] && $unavailable[1] >= $lastAvailablePeriod[1]) {
                        // available interval is inside unavailable interval
                        array_pop($parsedAvailablePeriod);
                    } elseif (
                        $unavailable[0] <= $lastAvailablePeriod[0] &&
                        $unavailable[1] >= $lastAvailablePeriod[0] &&
                        $unavailable[1] <= $lastAvailablePeriod[1]
                    ) {
                        // unavailable interval intersect start of available interval
                        $fixedPeriod = array_pop($parsedAvailablePeriod);

                        if ($unavailable[1] !== $fixedPeriod[1]) {
                            $parsedAvailablePeriod[] = [$unavailable[1], $fixedPeriod[1], $fixedPeriod[2]];
                        }
                    } elseif (
                        $unavailable[0] >= $lastAvailablePeriod[0] &&
                        $unavailable[0] <= $lastAvailablePeriod[1] &&
                        $unavailable[1] >= $lastAvailablePeriod[1]
                    ) {
                        // unavailable interval intersect end of available interval
                        $fixedPeriod = array_pop($parsedAvailablePeriod);

                        if ($fixedPeriod[0] !== $unavailable[0]) {
                            $parsedAvailablePeriod[] = [$fixedPeriod[0], $unavailable[0], $fixedPeriod[2]];
                        }
                    }
                }
            }
        }

        return $parsedAvailablePeriod;
    }

    /**
     * @param array $data
     * @param int   $startTime
     * @param int   $endTime
     *
     * @return array
     */
    public function getFreeIntervals($data, $startTime, $endTime)
    {
        $result = [];

        ksort($data);

        $firstIntervalTime = true;

        $lastStartTime = $startTime;

        foreach ((array)$data as &$interval) {
            // Appointment is out of working hours
            if ($interval[0] >= $endTime || $interval[1] <= $startTime) {
                continue;
            }

            // Beginning or End of the Appointment is out of working hours
            if ($interval[0] < $startTime && $interval[1] <= $endTime) {
                $interval[0] = $startTime;
            } elseif ($interval[0] >= $startTime && $interval[1] > $endTime) {
                $interval[1] = $endTime;
            }

            if ($lastStartTime !== $interval[0] && ($lastStartTime !== $startTime || ($firstIntervalTime && $lastStartTime !== $interval[0]))) {
                $firstIntervalTime = false;

                $result[$lastStartTime] = [
                    $lastStartTime,
                    $interval[0]
                ];
            }

            $lastStartTime = $interval[1];
        }

        if ($lastStartTime !== $endTime) {
            $result[$lastStartTime] = [
                $lastStartTime,
                $endTime
            ];
        }

        return $result;
    }
}
