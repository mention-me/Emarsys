<?php

namespace Snowcap\Emarsys;

use Snowcap\Emarsys\Exception\ClientException;

/**
 * All endpoints return data using the standard Emarsys API response JSON schema, as follows:
 *
 * {
 *   "replyCode": "integer",
 *   "replyText": "summary",
 *   "data": {}
 * }
 */
class Response
{
    /**
     * Successful requests return with replyCode 0.
     *
     * https://dev.emarsys.com/v2/response-codes/error-codes
     */
    public const REPLY_CODE_OK = 0;

    public const REPLY_CODE_INTERNAL_ERROR = 1;

    public const REPLY_CODE_INVALID_KEY_FIELD = 2004;

    public const REPLY_CODE_MISSING_KEY_FIELD = 2005;

    public const REPLY_CODE_CONTACT_NOT_FOUND = 2008;

    public const REPLY_CODE_NON_UNIQUE_RESULT = 2010;

    public const REPLY_CODE_INVALID_STATUS = 6003;

    public const REPLY_CODE_INVALID_DATA = 10001;

    /**
     * @var int
     */
    protected $replyCode;

    /**
     * @var string
     */
    protected $replyText;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @throws ClientException
     */
    public function __construct(array $result = [])
    {
        if ( ! isset($result['replyCode']) || ! isset($result['replyText'])) {
            throw ClientException::invalidResponseStructure();
        }

        $this->replyCode = $result['replyCode'];
        $this->replyText = $result['replyText'];
        $this->data = $result['data'] ?? [];
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getReplyCode(): int
    {
        return $this->replyCode;
    }

    public function getReplyText(): string
    {
        return $this->replyText;
    }
}
