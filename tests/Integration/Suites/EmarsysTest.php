<?php

namespace Snowcap\Emarsys\Tests\Integration;

use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use Snowcap\Emarsys\Client;
use Snowcap\Emarsys\ClientInterface;
use Snowcap\Emarsys\Exception\ClientException;
use Snowcap\Emarsys\Exception\ServerException;
use GuzzleHttp\Client as GuzzleClient;

class EmarsysTest extends TestCase
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @throws ClientException
     * @throws ServerException
     */
    protected function setUp(): void
    {
        if ( ! defined('EMARSYS_API_USERNAME') || ! defined('EMARSYS_API_SECRET')) {
            self::markTestSkipped('No Emarsys credentials are specified');
        }

        $httpClient = new GuzzleClient();
        $this->client = new Client(
            $httpClient,
            new RequestFactory(),
            new StreamFactory(),
            'EMARSYS_API_USERNAME',
            'EMARSYS_API_SECRET',
            'https://trunk-int.s.emarsys.com/api/v2/'
        );

        $connectionTestResponse = $this->client->getLanguages();

        if (0 !== $connectionTestResponse->getReplyCode()) {
            self::markTestSkipped('Problem connecting to Emarsys. Check credentials in phpunit.xml.dist.');
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
        $expectation = [
            'id'       => 'en',
            'language' => 'english',
        ];

        self::assertContains($expectation, $response->getData());
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

        self::assertContains($expectation, $response->getData());
    }
}
