<?php

namespace AmeliaBooking\Application\Common\Exceptions;

use Exception;

/**
 * Class AccessDeniedException
 *
 * @package AmeliaBooking\Application\Common\Exceptions
 */
class AccessDeniedException extends Exception
{
    /**
     * AccessDeniedException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct(
        $message = 'You are not allowed to perform this action',
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
