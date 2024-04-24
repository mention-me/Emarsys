<?php

namespace Snowcap\Emarsys\Exception;

use Exception;

class ServerException extends Exception
{
    public const JSON_DECODING_EXCEPTION = 'JSON response could not be decoded:\n%s';
    public const JSON_RESPONSE_NOT_ARRAY_EXCEPTION = 'JSON response is not an array:\n%s';

    public static function jsonDecodingException(string $msg): ServerException
    {
        return new self(
            sprintf(
                self::JSON_DECODING_EXCEPTION,
                $msg
            )
        );
    }

    public static function jsonResponseNotArrayException(string $response): ServerException
    {
        return new self(
            sprintf(
                self::JSON_RESPONSE_NOT_ARRAY_EXCEPTION,
                $response
            )
        );
    }
}
