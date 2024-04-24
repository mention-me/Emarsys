<?php

namespace Snowcap\Emarsys\Exception;

use Exception;

class ClientException extends Exception
{
    public const UNEXPECTED_RESPONSE_STRUCTURE_MESSAGE = 'Unexpect response structure, no replyCode or replyText';
    public const JSON_MAXIMUM_DEPTH_DECODE_EXCEPTION = 'JSON response could not be decoded, maximum depth reached.';
    public const UNRECOGNIZED_FIELD_NAME = 'Unrecognized field name "%s"';
    public const UNRECOGNIZED_FIELD_STRING_ID = 'Unrecognized field string_id "%s"';
    public const UNRECOGNIZED_FIELD_ID_FOR_CHOICE = 'Unrecognized field "%s" for choice "%s"';
    public const UNRECOGNIZED_CHOICE_FOR_FIELD_ID = 'Unrecognized choice "%s" for field "%s"';
    public const SYSTEM_TYPE_NOT_CREATABLE_EXCEPTION = 'Can\'t create this type of field, system type.';
    public const TYPE_NOT_CREATABLE_VIA_API_EXCEPTION = 'This type of field cannot be created via API. %s';

    /**
     * @return ClientException
     */
    public static function invalidResponseStructure(): ClientException
    {
        return new self(self::UNEXPECTED_RESPONSE_STRUCTURE_MESSAGE);
    }

    /**
     * @return ClientException
     */
    public static function jsonMaximumDepthDecodingException(): ClientException
    {
        return new self(self::JSON_MAXIMUM_DEPTH_DECODE_EXCEPTION);
    }

    /**
     * @param string $fieldName
     *
     * @return ClientException
     */
    public static function unrecognizedFieldName(string $fieldName): ClientException
    {
        return new self(
            sprintf(
                self::UNRECOGNIZED_FIELD_NAME,
                $fieldName
            )
        );
    }

    /**
     * @param string $fieldStringId
     *
     * @return ClientException
     */
    public static function unrecognizedFieldStringId(string $fieldStringId): ClientException
    {
        return new self(
            sprintf(
                self::UNRECOGNIZED_FIELD_STRING_ID,
                $fieldStringId
            )
        );
    }

    /**
     * @param string $fieldStringId
     * @param string $choice
     *
     * @return ClientException
     */
    public static function unrecognizedFieldStringIdForChoice(string $fieldStringId, string $choice): ClientException
    {
        return new self(
            sprintf(
                self::UNRECOGNIZED_FIELD_ID_FOR_CHOICE,
                $fieldStringId,
                $choice
            )
        );
    }

    /**
     * @param string $choice
     * @param string $fieldStringId
     *
     * @return ClientException
     */
    public static function unrecognizedChoiceForFieldStringId(string $choice, string $fieldStringId): ClientException
    {
        return new self(
            sprintf(
                self::UNRECOGNIZED_CHOICE_FOR_FIELD_ID,
                $choice,
                $fieldStringId
            )
        );
    }

    /**
     * @return ClientException
     */
    public static function cannotCreateSystemTypeException(): ClientException
    {
        return new self(self::SYSTEM_TYPE_NOT_CREATABLE_EXCEPTION);
    }

    /**
     * @param string $type
     *
     * @return ClientException
     */
    public static function typeNotCreatableViaApiException(string $type): ClientException
    {
        return new self(
            sprintf(
                self::TYPE_NOT_CREATABLE_VIA_API_EXCEPTION,
                $type
            )
        );
    }
}
