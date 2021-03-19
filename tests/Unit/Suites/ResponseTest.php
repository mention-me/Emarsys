<?php

namespace Snowcap\Emarsys\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Snowcap\Emarsys\Exception\ClientException;
use Snowcap\Emarsys\Response;

/**
 * @covers \Snowcap\Emarsys\Response
 */
class ResponseTest extends TestCase
{
    /**
     * @throws ClientException
     */
	public function testItThrowsClientException(): void
    {
        $this->expectException(ClientException::class);
        $this->expectErrorMessage('Unexpect response structure, no replyCode or replyText');
		$dummyResult = array('dummy');
		new Response($dummyResult);
	}

    /**
     * @throws ClientException
     */
	public function testItGetsResponseData(): void
    {
		$expectedResponse = $this->createExpectedResponse('createContact');
		$result = new Response($expectedResponse);

		self::assertNotEmpty($result);

	}

    /**
     * @throws ClientException
     */
	public function testItSetsAndGetsReplyCode(): void
    {
		$expectedResponse = $this->createExpectedResponse('createContact');
		$result = new Response($expectedResponse);

		self::assertSame(Response::REPLY_CODE_OK, $result->getReplyCode());
	}

    /**
     * @throws ClientException
     */
	public function testItSetsAndGetsReplyText(): void
    {
		$expectedResponse = $this->createExpectedResponse('createContact');
		$result = new Response($expectedResponse);

		self::assertEquals('OK', $result->getReplyText());
	}

    /**
     * @throws ClientException
     */
	public function testItResponseWithoutData(): void
    {
		$expectedResponse = $this->createExpectedResponse('insertRecord');
		$result = new Response($expectedResponse);

		self::assertEmpty($result->getData());
	}

	/**
     * @param string $fileName
     * @return mixed
     */
    private function createExpectedResponse(string $fileName)
    {
        $fileContent = file_get_contents(__DIR__ . '/TestData/' . $fileName . '.json');

        return json_decode($fileContent, true);
    }
}
