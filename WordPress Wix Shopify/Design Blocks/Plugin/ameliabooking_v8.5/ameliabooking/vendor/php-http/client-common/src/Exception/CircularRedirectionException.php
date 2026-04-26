<?php

namespace AmeliaHttp\Client\Common\Exception;

use AmeliaHttp\Client\Exception\HttpException;

/**
 * Thrown when circular redirection is detected.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class CircularRedirectionException extends HttpException
{
}
