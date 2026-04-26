<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Report\Spout;

use AmeliaBooking\Domain\Services\Report\AbstractReportService;
use AmeliaBooking\Domain\Services\Report\ReportServiceInterface;
use Box\Spout\Common\Type;
use Box\Spout\Writer\CSV\Writer;
use Box\Spout\Writer\WriterFactory;

/**
 * Class CsvService
 */
class CsvService extends AbstractReportService implements ReportServiceInterface
{
    /**
     * @param array  $rows
     * @param String $name
     * @param String $delimiter
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function generateReport($rows, $name, $delimiter)
    {
        /* @var Writer $writer */
        $writer = WriterFactory::create(Type::CSV);
        if ($writer instanceof Writer) {
            $writer->openToBrowser($name . '.csv');
            $writer->setFieldDelimiter($delimiter);

            if ($rows) {
                $writer->addRow(array_keys($rows[0]));

                foreach ($rows as $row) {
                    $writer->addRow($row);
                }
            }
        }
    }
}
