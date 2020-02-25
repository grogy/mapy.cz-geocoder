<?php declare(strict_types=1);
namespace DATOSCZ\MapyCzGeocoder\Providers;

use Geocoder\Collection;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

final class MapyCZ extends AbstractHttpProvider implements Provider
{
	private const GEOCODE_URI = 'https://api.mapy.cz/geocode';
	private const REVERSE_URI = 'https://api.mapy.cz/rgeocode';

	private const RE_STREET = '(?:(?:\s?ulice\s*)?(?P<streetName>(?:[0-9]+(?=[^/,]+))?[^/,0-9]+(?<![\s\,])))';
	private const RE_NUMBER = '(?:(?<!č\.p\.)(?:č\.p\.\s+)?(?P<streetNumber>[0-9]+(?:\/[0-9]+)?[a-z]?))';

	public function getName(): string
	{
		return 'mapy_cz';
	}

	public function geocodeQuery(GeocodeQuery $query): Collection
	{
		$address = $query->getText();
		if (filter_var($address, FILTER_VALIDATE_IP)) {
			throw new UnsupportedOperation('The MapyCZ does not support IP addresses');
		}

		$results = [];
		$xml = $this->executeQuery(self::GEOCODE_URI, ['query' => $address]);

		/** @var \SimpleXMLElement $point */
		$point = $xml->point;
		$itemCount = count($point->children());
		/** @var \SimpleXMLElement $item */
		foreach ($point->children() as $item) {
			if (count($results) == $query->getLimit()) {
				break;
			}

			/** @var \SimpleXMLElement $attrs */
			$attrs = $item->attributes();
			if ($itemCount > 1 && stripos((string)$attrs->title, $address) === false) {
				continue;
			}
			if (!in_array((string)$attrs->source, ['addr', 'stre'], TRUE)) {
				continue;
			}
			$builder = new AddressBuilder($this->getName());
			$builder->setCoordinates((float)$attrs->y, (float)$attrs->x);
			$results[] = $builder->build();
		}

		return new AddressCollection($results);
	}

	public function reverseQuery(ReverseQuery $query): Collection
	{
		$coordinates = $query->getCoordinates();
		$xml = $this->executeQuery(self::REVERSE_URI, $query = ['lat' => $coordinates->getLatitude(), 'lon' => $coordinates->getLongitude()]);
		$builder = new AddressBuilder($this->getName());
		$builder->setCoordinates($coordinates->getLatitude(), $coordinates->getLongitude());
		foreach ($xml->children() as $item) {
			/** @var \SimpleXMLElement $attrs */
			$attrs = $item->attributes();
			$type = (string) $attrs->type;
			switch ($type) {
				case 'addr':
					if (preg_match('~' . self::RE_NUMBER . '\\s*\\z~i', (string)$attrs->name, $m)) {
						$builder->setStreetNumber($m['streetNumber']);
					}

					if (preg_match('~^' . self::RE_STREET . '?\\s*' . self::RE_NUMBER . '\\s*\\z~i', (string)$attrs->name, $m)) {
						$builder->setStreetName($m['streetName']);
					}
					break;

				case 'stre':
					$builder->setStreetName(preg_replace('~^(ulice\s)~', '', (string)$attrs->name));
					break;

				case 'quar':
					$builder->addAdminLevel(4, (string)$attrs->name);
					break;

				case 'ward':
					$builder->addAdminLevel(3, preg_replace('~^(část obce\s)~', '', (string)$attrs->name));
					break;

				case 'muni':
					$builder->setLocality((string)$attrs->name);
					break;

				case 'dist':
					$builder->addAdminLevel(2, preg_replace('~^(okres\s)~', '', (string)$attrs->name));
					break;

				case 'regi':
					$builder->addAdminLevel(1, (string)$attrs->name);
					break;

				case 'coun':
					$builder->setCountry((string)$attrs->name);
					break;
			}
		}
		return new AddressCollection([$builder->build()]);
	}

	private function executeQuery(string $endpoint, array $query): \SimpleXMLElement
	{
		$url = $endpoint . '?' . http_build_query($query, '', '&');
		$content = $this->getUrlContents($url);

		try {
			libxml_use_internal_errors(true);
			return new \SimpleXMLElement($content);

		} catch (\Exception $e) {
			throw new InvalidServerResponse(sprintf('Invalid result %s', json_encode($query)), 0, $e);
		}
	}
}
