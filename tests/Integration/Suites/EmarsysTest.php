<?php

namespace Snowcap\Emarsys\Tests\Integration;

use GuzzleHttp\Client as GuzzleClient;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use Snowcap\Emarsys\Client;
use Snowcap\Emarsys\ClientInterface;
use Snowcap\Emarsys\Exception\ClientException;
use Snowcap\Emarsys\Exception\ServerException;

class EmarsysTest extends TestCase
{
    private Client $client;

    /**
     * @throws ClientException
     * @throws ServerException
     */
    protected function setUp(): void
    {
        $username = getenv('EMARSYS_API_USERNAME');
        $secret = getenv('EMARSYS_API_SECRET');
        if (! $username || ! $secret) {
            $this->markTestSkipped('No Emarsys credentials are specified');
        }

        $httpClient = new GuzzleClient();
        $this->client = new Client(
            $httpClient,
            new RequestFactory(),
            new StreamFactory(),
            $username,
            $secret,
            'https://suite0.emarsys.net/api/v2/'
        );

        $connectionTestResponse = $this->client->getLanguages();

        if (0 !== $connectionTestResponse->getReplyCode()) {
            $this->markTestSkipped(
                'Problem connecting to Emarsys. Check credentials in phpunit.xml.dist or in github secrets'
            );
        }
    }

    /**
     * @throws ServerException
     * @throws ClientException
     * @throws Exception
     */
    public function testItShouldGetAvailableLanguages(): void
    {
        $response = $this->client->getLanguages();

        // The language string values used to be all lowercase, but now they are capitalized
        $expectation = [
            'id'       => 'en',
            'language' => 'English',
        ];

        $this->assertContains($expectation, $response->getData());
    }

    /**
     * @throws ServerException
     * @throws ClientException
     * @throws Exception
     */
    public function testItShouldGetAvailableFields(): void
    {
        $response = $this->client->getFields();
        $expectation =
            [
                'id'               => 1,
                'name'             => 'First Name',
                'application_type' => 'shorttext',
                'string_id'        => 'first_name',
            ];

        $this->assertContains($expectation, $response->getData());
    }
}
