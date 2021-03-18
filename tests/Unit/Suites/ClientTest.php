<?php

namespace Snowcap\Emarsys;

use PHPUnit_Framework_Exception;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use Snowcap\Emarsys\Exception\ClientException;
use Http\Mock\Client as MockClient;

/**
 * @covers \Snowcap\Emarsys\Client
 * @uses \Snowcap\Emarsys\Response
 */
class ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Client
     */
    private $client;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject|MockClient
	 */
	private $stubHttpClient;

    protected function setUp(): void
    {
	    $this->stubHttpClient = new MockClient();
	    $this->client = new Client($this->stubHttpClient, 'dummy-api-username', 'dummy-api-secret', new NullLogger());
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

		$mapping = array(
			$customField1StringId => $customField1Id,
			$customField2StringId => $customField2Id
		);

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
		$customFieldStringId = 'myCustomField';
		$customChoice1Id = 1;
		$customChoice1Name = 'myCustomChoice1';
		$customChoice2Id = 2;
		$customChoice2Name = 'myCustomChoice2';
		$customChoice3Id = 3;
		$customChoice3Name = 'myCustomChoice3';

		$mapping = array(
			$customFieldStringId => array(
				$customChoice1Name => $customChoice1Id
			)
		);

		/* Adding one choice first to test later that it is not overwritten by adding more choices */
		$this->client->addChoicesMapping($mapping);

		$mapping = array(
			$customFieldStringId => array(
				$customChoice2Name => $customChoice2Id,
				$customChoice3Name => $customChoice3Id
			)
		);

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
        $this->setExpectedException(ClientException::class, 'Unrecognized field name "non-existing-field-name"');
		$this->client->getFieldId('non-existing-field-name');
	}

    /**
     * @throws ClientException
     */
	public function testItThrowsAnExceptionIfChoiceFieldDoesNotExist(): void
    {
        $this->setExpectedException(ClientException::class, 'Unrecognized field "non-existing-field-name" for choice "choice-name"');
		$this->client->getChoiceId('non-existing-field-name', 'choice-name');
	}

    /**
     * @throws ClientException
     */
	public function testItThrowsAnExceptionIfChoiceDoesNotExist(): void
    {
        $this->setExpectedException(ClientException::class, 'Unrecognized choice "choice-name" for field "myCustomField"');
		$fieldName = 'myCustomField';
		$mapping = array($fieldName => array());

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
		$mapping = array($fieldName => array());

		$this->client->addChoicesMapping($mapping);
		$result = $this->client->getChoiceName($fieldName, $choiceId);

		self::assertEquals($choiceId, $result);
	}

    /**
     * @throws ClientException
     * @throws Exception\ServerException
     * @throws PHPUnit_Framework_Exception
     */
    public function testGetEmails(): void
    {
        $expectedResponse = $this->createExpectedResponse('emails');
        $this->stubHttpClient->method('sendRequest')->willReturn($expectedResponse);

        $response = $this->client->getEmails();

        self::assertEquals(Response::REPLY_CODE_OK, $response->getReplyCode());

        $response = $this->client->getEmails(Client::EMAIL_STATUS_READY);

        self::assertEquals(Response::REPLY_CODE_OK, $response->getReplyCode());

        $response = $this->client->getEmails(null, 123);

        self::assertEquals(Response::REPLY_CODE_OK, $response->getReplyCode());

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
     * @throws Exception\ServerException
     * @throws PHPUnit_Framework_Exception
     */
    public function testCreateEmail(): void
    {
        $expectedResponse = $this->createExpectedResponse('createContact');
	    $this->stubHttpClient->method('sendRequest')->willReturn($expectedResponse);

        $data = array(
            'language' => 'en',
            'name' => 'test api 010',
            'fromemail' => 'sender@example.com',
            'fromname' => 'sender email',
            'subject' => 'subject here',
            'email_category' => '17',
            'html_source' => '<html>Hello $First Name$,... </html>',
            'text_source' => 'email text',
            'segment' => 1121,
            'contactlist' => 0,
            'unsubscribe' => 1,
            'browse' => 0,
        );

        $response = $this->client->createEmail($data);

        self::assertEquals(Response::REPLY_CODE_OK, $response->getReplyCode());
        self::assertArrayHasKey('id', $response->getData());
    }

    /**
     * @throws ClientException
     * @throws Exception\ServerException
     */
    public function testGetContactIdSuccess(): void
    {
        $expectedResponse = $this->createExpectedResponse('getContactId');
	    $this->stubHttpClient->method('send')->willReturn($expectedResponse);

        $response = $this->client->getContactId('3', 'sender@example.com');

        $expectedData = json_decode($expectedResponse, true);
        self::assertEquals($expectedData['data']['id'], $response);
    }

    /**
     * @throws ClientException
     * @throws Exception\ServerException
     * @throws PHPUnit_Framework_Exception
     */
	public function testItReturnsContactData(): void
    {
		$expectedResponse = $this->createExpectedResponse('getContactData');
		$this->stubHttpClient->method('sendRequest')->willReturn($expectedResponse);

		$response = $this->client->getContactData(array());

		self::assertInstanceOf(Response::class, $response);
	}

    /**
     * @throws ClientException
     * @throws Exception\ServerException
     * @throws PHPUnit_Framework_Exception
     */
	public function testItCreatesContact(): void
    {
		$expectedResponse = $this->createExpectedResponse('createContact');
		$this->stubHttpClient->method('sendRequest')->willReturn($expectedResponse);

		$data = array(
			'3'         => 'recipient@example.com',
			'source'    => '123',
		);
		$response = $this->client->createContact($data);

		self::assertInstanceOf(Response::class, $response);
	}

    /**
     * @throws ClientException
     * @throws Exception\ServerException
     */
	public function testThrowsExceptionIfJsonDepthExceedsLimit(): void
    {
        $this->setExpectedException(ClientException::class, 'JSON response could not be decoded, maximum depth reached.');
	    $nestedStructure = array();
	    for ($i=0; $i<511; $i++) {
	        $nestedStructure = array($nestedStructure);
        }

        $this->stubHttpClient->method('sendRequest')->willReturn(json_encode($nestedStructure));

        $this->client->createContact(array());
	}

	/**
     * Get a json test data and decode it
     *
     * @param string $fileName
     * @return mixed
     */
    private function createExpectedResponse(string $fileName)
    {
        $fileContent = file_get_contents(__DIR__ . '/TestData/' . $fileName . '.json');

        return $fileContent;
    }
}
