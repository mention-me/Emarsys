<?php

namespace Snowcap\Emarsys\Tests\Unit;

use Http\Message\MessageFactory\GuzzleMessageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Exception;
use Psr\Http\Message\ResponseInterface;
use Snowcap\Emarsys\Client;
use Snowcap\Emarsys\Exception\ClientException;
use Http\Mock\Client as MockClient;
use Snowcap\Emarsys\Exception\ServerException;
use Snowcap\Emarsys\Response;

/**
 * @covers \Snowcap\Emarsys\Client
 * @uses   \Snowcap\Emarsys\Response
 */
class ClientTest extends TestCase
{
    /**
     * @var MockObject|Client
     */
    private $client;

    /**
     * @var MockObject|MockClient
     */
    private $stubHttpClient;

    protected function setUp(): void
    {
        $this->stubHttpClient = new MockClient();
        $this->client = new Client(
            $this->stubHttpClient,
            new GuzzleMessageFactory(),
            'dummy-api-username',
            'dummy-api-secret',
        );
    }

    /**
     * @throws ClientException
     */
    public function testItAddsFieldsMapping(): void
    {
        $customField1Id = 7147;
        $customField1StringId = 'myCustomField1';
        $customField2Id = 7148;
        $customField2StringId = 'myCustomField2';

        $mapping = [
            $customField1StringId => $customField1Id,
            $customField2StringId => $customField2Id,
        ];

        $this->client->addFieldsMapping($mapping);

        $resultField1Id = $this->client->getFieldId($customField1StringId);
        $resultField1StringId = $this->client->getFieldStringId($customField1Id);
        $resultField2Id = $this->client->getFieldId($customField2StringId);
        $resultField2StringId = $this->client->getFieldStringId($customField2Id);

        self::assertEquals($customField1Id, $resultField1Id);
        self::assertEquals($customField1StringId, $resultField1StringId);
        self::assertEquals($customField2Id, $resultField2Id);
        self::assertEquals($customField2StringId, $resultField2StringId);
    }

    /**
     * @throws ClientException
     */
    public function testItAddsChoicesMapping(): void
    {
        $customFieldId = 9999;
        $customFieldStringId = 'myCustomField';
        $customChoice1Id = 1;
        $customChoice1Name = 'myCustomChoice1';
        $customChoice2Id = 2;
        $customChoice2Name = 'myCustomChoice2';
        $customChoice3Id = 3;
        $customChoice3Name = 'myCustomChoice3';

        $this->client->addFieldsMapping([$customFieldStringId => $customFieldId]);

        $mapping = [
            $customFieldStringId => [
                $customChoice1Name => $customChoice1Id,
            ],
        ];

        /* Adding one choice first to test later that it is not overwritten by adding more choices */
        $this->client->addChoicesMapping($mapping);

        $mapping = [
            $customFieldStringId => [
                $customChoice2Name => $customChoice2Id,
                $customChoice3Name => $customChoice3Id,
            ],
        ];

        $this->client->addChoicesMapping($mapping);

        $resultChoice1Id = $this->client->getChoiceId($customFieldStringId, $customChoice1Name);
        $resultChoice1Name = $this->client->getChoiceName($customFieldStringId, $customChoice1Id);
        $resultChoice2Id = $this->client->getChoiceId($customFieldStringId, $customChoice2Name);
        $resultChoice2Name = $this->client->getChoiceName($customFieldStringId, $customChoice2Id);
        $resultChoice3Id = $this->client->getChoiceId($customFieldStringId, $customChoice3Name);
        $resultChoice3Name = $this->client->getChoiceName($customFieldStringId, $customChoice3Id);

        self::assertEquals($customChoice1Id, $resultChoice1Id);
        self::assertEquals($customChoice1Name, $resultChoice1Name);
        self::assertEquals($customChoice2Id, $resultChoice2Id);
        self::assertEquals($customChoice2Name, $resultChoice2Name);
        self::assertEquals($customChoice3Id, $resultChoice3Id);
        self::assertEquals($customChoice3Name, $resultChoice3Name);
    }

    /**
     * @throws ClientException
     */
    public function testItThrowsAnExceptionIfFieldDoesNotExist(): void
    {
        $this->expectException(ClientException::class);
        $this->expectErrorMessage('Unrecognized field name "non-existing-field-name"');
        $this->client->getFieldId('non-existing-field-name');
    }

    /**
     * @throws ClientException
     */
    public function testItThrowsAnExceptionIfChoiceFieldDoesNotExist(): void
    {
        $this->expectException(ClientException::class);
        $this->expectErrorMessage('Unrecognized field "non-existing-field-name" for choice "choice-name"');
        $this->client->getChoiceId('non-existing-field-name', 'choice-name');
    }

    /**
     * @throws ClientException
     */
    public function testItThrowsAnExceptionIfChoiceDoesNotExist(): void
    {
        $this->expectException(ClientException::class);
        $this->expectErrorMessage('Unrecognized choice "choice-name" for field "myCustomField"');
        $fieldName = 'myCustomField';
        $mapping = [$fieldName => []];

        $this->client->addChoicesMapping($mapping);
        $this->client->getChoiceId($fieldName, 'choice-name');
    }

    /**
     * @throws ClientException
     */
    public function testItReturnsChoiceIdIfChoiceNameIsNotFound(): void
    {
        $fieldName = 'myCustomField';
        $choiceId = 1;
        $mapping = [$fieldName => []];

        $this->client->addChoicesMapping($mapping);
        $result = $this->client->getChoiceName($fieldName, $choiceId);

        self::assertEquals($choiceId, $result);
    }

    /**
     * @throws ClientException
     * @throws ServerException
     * @throws Exception
     */
    public function testGetEmails(): void
    {
        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedResponse->method("getBody")->willReturn($this->createExpectedResponse('emails'));

        $this->stubHttpClient->addResponse($expectedResponse);
        $response = $this->client->getEmails();
        self::assertEquals(Response::REPLY_CODE_OK, $response->getReplyCode());

        $this->stubHttpClient->addResponse($expectedResponse);
        $response = $this->client->getEmails(Client::EMAIL_STATUS_READY);
        self::assertEquals(Response::REPLY_CODE_OK, $response->getReplyCode());

        $this->stubHttpClient->addResponse($expectedResponse);
        $response = $this->client->getEmails(null, 123);
        self::assertEquals(Response::REPLY_CODE_OK, $response->getReplyCode());

        $this->stubHttpClient->addResponse($expectedResponse);
        $response = $this->client->getEmails(Client::EMAIL_STATUS_READY, 123);
        self::assertEquals(Response::REPLY_CODE_OK, $response->getReplyCode());

        self::assertNotEmpty($response->getData());

        foreach ($response->getData() as $data) {
            self::assertArrayHasKey('id', $data);
            self::assertArrayHasKey('name', $data);
            self::assertArrayHasKey('status', $data);
        }
    }

    /**
     * @throws ClientException
     * @throws ServerException
     * @throws Exception
     */
    public function testCreateEmail(): void
    {
        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedResponse->method("getBody")->willReturn($this->createExpectedResponse('createContact'));
        $this->stubHttpClient->addResponse($expectedResponse);

        $data = [
            'language'       => 'en',
            'name'           => 'test api 010',
            'fromemail'      => 'sender@example.com',
            'fromname'       => 'sender email',
            'subject'        => 'subject here',
            'email_category' => '17',
            'html_source'    => '<html>Hello $First Name$,... </html>',
            'text_source'    => 'email text',
            'segment'        => 1121,
            'contactlist'    => 0,
            'unsubscribe'    => 1,
            'browse'         => 0,
        ];

        $response = $this->client->createEmail($data);

        self::assertEquals(Response::REPLY_CODE_OK, $response->getReplyCode());
        self::assertArrayHasKey('id', $response->getData());
    }

    /**
     * @throws ClientException
     * @throws ServerException
     */
    public function testGetContactIdSuccess(): void
    {
        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedResponse->method("getBody")->willReturn($this->createExpectedResponse('getContactId'));
        $this->stubHttpClient->addResponse($expectedResponse);

        $response = $this->client->getContactId('3', 'sender@example.com');

        $expectedData = json_decode($expectedResponse->getBody(), true);
        self::assertEquals($expectedData['data']['id'], $response);
    }

    /**
     * @throws ClientException
     * @throws ServerException
     * @throws Exception
     */
    public function testItReturnsContactData(): void
    {
        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedResponse->method("getBody")->willReturn($this->createExpectedResponse('getContactData'));
        $this->stubHttpClient->addResponse($expectedResponse);

        $response = $this->client->getContactData([]);

        self::assertEquals("123456", $response->getData()['result'][0]['emailId']);
    }

    /**
     * @throws ClientException
     * @throws ServerException
     * @throws Exception
     */
    public function testItCreatesContact(): void
    {
        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedResponse->method("getBody")->willReturn($this->createExpectedResponse('createContact'));
        $this->stubHttpClient->addResponse($expectedResponse);

        $data = [
            '3'      => 'recipient@example.com',
            'source' => '123',
        ];
        $response = $this->client->createContact($data);

        self::assertEquals(2140, $response->getData()['id']);
    }

    /**
     * @throws ClientException
     * @throws ServerException
     */
    public function testThrowsExceptionIfJsonDepthExceedsLimit(): void
    {
        $this->expectException(ClientException::class);
        $this->expectErrorMessage('JSON response could not be decoded, maximum depth reached.');
        $nestedStructure = [];
        for ($i = 0; $i < 511; $i++) {
            $nestedStructure = [$nestedStructure];
        }

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedResponse->method("getBody")->willReturn(json_encode($nestedStructure));
        $this->stubHttpClient->addResponse($expectedResponse);

        $this->client->createContact([]);
    }

    /**
     * Get a json test data and decode it
     *
     * @param string $fileName
     *
     * @return mixed
     */
    private function createExpectedResponse(string $fileName)
    {
        $fileContent = file_get_contents(__DIR__ . '/TestData/' . $fileName . '.json');

        return $fileContent;
    }
}
