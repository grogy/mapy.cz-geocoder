<?php declare(strict_types=1);
namespace DATOSCZ\MapyCzGeocoder\Tests\Providers;

use DATOSCZ\MapyCzGeocoder\Providers\MapyCZ;
use Geocoder\IntegrationTest\ProviderIntegrationTest;
use Http\Client\HttpClient;

class IntegrationTest extends ProviderIntegrationTest
{

	protected $skippedTests = [
		'testGeocodeQuery' => 'Wrong cords',
		'testReverseQueryWithNoResults' => 'Has Result',
	];

	protected $testAddress = true;

	protected $testReverse = true;

	protected $testIpv4 = false;

	protected $testIpv6 = false;

	protected function createProvider(HttpClient $httpClient)
	{
		return new MapyCZ($httpClient);
	}

	protected function getCacheDir()
	{
		return __DIR__ . '/.cached_responses';
	}

	protected function getApiKey()
	{
		return null;
	}
}
