<?php declare(strict_types=1);
namespace DATOSCZ\MapyCzGeocoder\Tests\Providers;

use DATOSCZ\MapyCzGeocoder\Providers\MapyCZ;
use Geocoder\Exception\InvalidArgument;
use Geocoder\IntegrationTest\BaseTestCase;
use Geocoder\Model\Coordinates;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

class MapyCZTest extends BaseTestCase
{
	protected function getCacheDir()
	{
		return __DIR__ . '/.cached_responses';
	}

	public function testGeocodeQueryOneAddress()
	{
		$provider = new MapyCZ($this->getHttpClient());
		$address = 'Moravské náměstí 3, Brno, 602 00';
		$coordinates = $provider->geocodeQuery(GeocodeQuery::create($address));
		$this->assertEquals(1, $coordinates->count());
		$this->assertEquals($coordinates->first()->getCoordinates()->getLongitude(), '16.6082');
		$this->assertEquals($coordinates->first()->getCoordinates()->getLatitude(), '49.1974');
	}

	public function testGeocodeQueryMultipleAddress()
	{
		$provider = new MapyCZ($this->getHttpClient());
		$address = 'Nad Vodojemem';
		$coordinates = $provider->geocodeQuery(GeocodeQuery::create($address));
		$this->assertTrue($coordinates->count() > 2);
	}

	public function testGeocodeQueryNotExistingAddress()
	{
		$provider = new MapyCZ($this->getHttpClient());
		$address = 'Terryho Pratchetta';
		$coordinates = $provider->geocodeQuery(GeocodeQuery::create($address));
		$this->assertEquals(0, $coordinates->count());
	}

	public function testGeocodeQueryEmptyAddress()
	{
		$provider = new MapyCZ($this->getHttpClient());
		$address = '';
		$this->expectException(InvalidArgument::class);
		$provider->geocodeQuery(GeocodeQuery::create($address));
	}

	public function testReverseQuery()
	{
		$provider = new MapyCZ($this->getHttpClient());
		$coordinates = new Coordinates(50.131282, 14.418415);
		$collection = $provider->reverseQuery(ReverseQuery::create($coordinates));
		$this->assertEquals(1, $collection->count());
		$this->assertEquals($collection->first()->getCoordinates()->getLatitude(), 50.131282);
		$this->assertEquals($collection->first()->getCoordinates()->getLongitude(), 14.418415);
		$this->assertEquals($collection->first()->getCountry(), 'Česko');
		$this->assertEquals($collection->first()->getStreetName(), 'Lodžská');
		$this->assertEquals($collection->first()->getStreetNumber(), null);
		$this->assertEquals($collection->first()->getLocality(), 'Praha');
		$adminLevels = $collection->first()->getAdminLevels();
		$this->assertEquals($adminLevels->get(1)->getName(), 'kraj Hlavní město Praha');
		$this->assertEquals($adminLevels->get(2)->getName(), 'Hlavní město Praha');
		$this->assertEquals($adminLevels->get(3)->getName(), 'Bohnice');
		$this->assertEquals($adminLevels->get(4)->getName(), 'Praha 8');
	}

	public function testReverseQueryWithNumber()
	{
		$provider = new MapyCZ($this->getHttpClient());
		$coordinates = new Coordinates(49.1974, 16.6082);
		$collection = $provider->reverseQuery(ReverseQuery::create($coordinates));
		$this->assertEquals(1, $collection->count());
		$this->assertEquals($collection->first()->getCoordinates()->getLatitude(), 49.1974);
		$this->assertEquals($collection->first()->getCoordinates()->getLongitude(), 16.6082);
		$this->assertEquals($collection->first()->getCountry(), 'Česko');
		$this->assertEquals($collection->first()->getStreetName(), 'Moravské náměstí');
		$this->assertEquals($collection->first()->getStreetNumber(), '127/3');
		$this->assertEquals($collection->first()->getLocality(), 'Brno');
		$adminLevels = $collection->first()->getAdminLevels();
		$this->assertEquals($adminLevels->get(1)->getName(), 'Jihomoravský kraj');
		$this->assertEquals($adminLevels->get(2)->getName(), 'Brno-město');
		$this->assertEquals($adminLevels->get(3)->getName(), 'Brno-město');
		$this->assertEquals($adminLevels->get(4)->getName(), 'Brno-střed');
	}
}
