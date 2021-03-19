<?php

namespace Snowcap\Emarsys\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Snowcap\Emarsys\CurlClient;
use Snowcap\Emarsys\Exception\ClientException;

/**
 * @covers \Snowcap\Emarsys\CurlClient
 */
class CurlClientTest extends TestCase
{
	/**
	 * @var CurlClient
	 */
	private $client;

	protected function setUp(): void
	{
		$this->client = new CurlClient();
	}

    public function testRequestToNonExistingHostFails(): void
    {
        $this->expectException(ClientException::class);
        $this->client->send('POST', 'http://foo.bar');
	}

	public function testRequestReturnsOutput(): void
    {
		$result = $this->client->send('GET', 'http://google.com', array(), array('foo' => 'bar'));

		self::assertStringContainsString('<html', $result);
	}
}
