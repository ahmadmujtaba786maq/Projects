<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Services\Report;

/**
 * Class AbstractReportService
 *
 * @package AmeliaBooking\Domain\Services\Report
 */
abstract class AbstractReportService
{
    /**
     * @param array  $rows
     * @param String $name
     * @param String $delimiter
     *
     * @return mixed|void
     */
    abstract public function generateReport($rows, $name, $delimiter);
}
