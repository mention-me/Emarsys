<?php

namespace Snowcap\Emarsys\Tests\Integration;

use PHPUnit_Framework_Exception;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use Snowcap\Emarsys\Client;
use Snowcap\Emarsys\CurlClient;
use Snowcap\Emarsys\Exception\ClientException;
use Snowcap\Emarsys\Exception\ServerException;

class EmarsysTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $client;

    protected function setUp(): void
    {
        if ( ! defined('EMARSYS_API_USERNAME') || ! defined('EMARSYS_API_SECRET')) {
            self::markTestSkipped('No Emarsys credentials are specified');
        }

        $httpClient = new CurlClient();
        $this->client = new Client($httpClient, EMARSYS_API_USERNAME, EMARSYS_API_SECRET, new NullLogger());

        $connectionTestResponse = $this->client->getLanguages();

        if (0 !== $connectionTestResponse->getReplyCode()) {
            self::markTestSkipped('Problem connecting to Emarsys. Check credentials in phpunit.xml.dist.');
        }
    }

    /**
     * @test
     * @throws ServerException
     * @throws ClientException
     * @throws PHPUnit_Framework_Exception
     */
    public function itShouldGetAvailableLanguages(): void
    {
        $response = $this->client->getLanguages();
        $expectation = ['id'       => 'en',
            'language' => 'english',
        ];

        self::assertContains($expectation, $response->getData());
    }

    /**
     * @test
     * @throws ServerException
     * @throws ClientException
     * @throws PHPUnit_Framework_Exception
     */
    public function itShouldGetAvailableFields(): void
    {
        $response = $this->client->getFields();
        $expectation = ['id'               => 1,
            'name'             => 'First Name',
            'application_type' => 'shorttext',
        ];

        self::assertContains($expectation, $response->getData());
    }
}
