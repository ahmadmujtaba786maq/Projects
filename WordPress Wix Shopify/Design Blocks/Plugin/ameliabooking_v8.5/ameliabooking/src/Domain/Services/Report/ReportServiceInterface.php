<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Services\Report;

/**
 * Interface ReportServiceInterface
 *
 * @package AmeliaBooking\Domain\Services\Report
 */
interface ReportServiceInterface
{
    /**
     * @param array  $rows
     * @param String $name
     * @param String $delimiter
     *
     * @return mixed
     */
    public function generateReport($rows, $name, $delimiter);
}
