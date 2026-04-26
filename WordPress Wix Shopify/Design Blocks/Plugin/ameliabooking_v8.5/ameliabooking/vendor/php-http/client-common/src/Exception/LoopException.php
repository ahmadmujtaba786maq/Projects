<?php

namespace AmeliaHttp\Client\Common\Exception;

use AmeliaHttp\Client\Exception\RequestException;

/**
 * Thrown when the Plugin Client detects an endless loop.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class LoopException extends RequestException
{
}
