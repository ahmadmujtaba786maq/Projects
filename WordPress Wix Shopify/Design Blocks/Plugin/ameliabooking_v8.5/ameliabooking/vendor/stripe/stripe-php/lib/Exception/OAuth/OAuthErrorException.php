<?php

namespace AmeliaStripe\Exception\OAuth;

/**
 * Implements properties and methods common to all (non-SPL) Stripe OAuth
 * exceptions.
 */
abstract class OAuthErrorException extends \AmeliaStripe\Exception\ApiErrorException
{
    protected function constructErrorObject()
    {
        if (null === $this->jsonBody) {
            return null;
        }

        return \AmeliaStripe\OAuthErrorObject::constructFrom($this->jsonBody);
    }
}
