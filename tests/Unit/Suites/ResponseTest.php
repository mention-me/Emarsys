<?php

namespace Snowcap\Emarsys\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Snowcap\Emarsys\Exception\ClientException;
use Snowcap\Emarsys\Response;

#[CoversClass(Response::class)]
class ResponseTest extends TestCase
{
    /**
     * @throws ClientException
     */
    public function testItThrowsClientException(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Unexpect response structure, no replyCode or replyText');
        $dummyResult = ['dummy'];
        new Response($dummyResult);
    }

    /**
     * @throws ClientException
     */
    public function testItGetsResponseData(): void
    {
        $expectedResponse = $this->createExpectedResponse('createContact');
        $response = new Response($expectedResponse);

        $this->assertNotEmpty($response);
    }

    /**
     * @throws ClientException
     */
    public function testItSetsAndGetsReplyCode(): void
    {
        $expectedResponse = $this->createExpectedResponse('createContact');
        $response = new Response($expectedResponse);

        $this->assertSame(Response::REPLY_CODE_OK, $response->getReplyCode());
    }

    /**
     * @throws ClientException
     */
    public function testItSetsAndGetsReplyText(): void
    {
        $expectedResponse = $this->createExpectedResponse('createContact');
        $response = new Response($expectedResponse);

        $this->assertEquals('OK', $response->getReplyText());
    }

    /**
     * @throws ClientException
     */
    public function testItResponseWithoutData(): void
    {
        $expectedResponse = $this->createExpectedResponse('insertRecord');
        $response = new Response($expectedResponse);

        $this->assertEmpty($response->getData());
    }

    private function createExpectedResponse(string $fileName): array
    {
        $fileContent = file_get_contents(__DIR__ . '/TestData/' . $fileName . '.json');

        return json_decode($fileContent, true);
    }
}
