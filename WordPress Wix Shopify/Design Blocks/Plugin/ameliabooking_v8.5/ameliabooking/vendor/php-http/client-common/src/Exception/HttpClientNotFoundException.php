<?php

namespace AmeliaHttp\Client\Common\Exception;

use AmeliaHttp\Client\Exception\TransferException;

/**
 * Thrown when a http client cannot be chosen in a pool.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class HttpClientNotFoundException extends TransferException
{
}
