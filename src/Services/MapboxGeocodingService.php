<?php

declare(strict_types=1);

namespace EScooters\Services;

use EScooters\Models\City;
use EScooters\Models\Repositories\Cities;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class MapboxGeocodingService
{
    protected const CACHE_FILENAMES = [
        'city' => "./.mapbox_city_cache",
        'country' => "./.mapbox_country_cache",
        'coordinates' => "./.mapbox_coordinates_cache",
    ];

    protected array $cache = [
        'city' => [],
        'country' => [],
        'coordinates' => [],
    ];

    public function __construct(
        protected string $token,
    ) {
        foreach (self::CACHE_FILENAMES as $type => $filename) {
            if (file_exists($filename)) {
                echo "Cache {$type} loaded." . PHP_EOL;
                $this->cache[$type] = json_decode(file_get_contents($filename), true);
            }
        }
    }

    public function setCoordinatesToCities(Cities $cities): static
    {
        foreach ($cities->all() as $city) {
            $cityId = $city->getId();
            $coordinates = $this->getFromCacheOrCreate(
                'city',
                $cityId,
                fn() => $this->getCityFromAPI($city)
            );
            if ($coordinates !== null) {
                $city->setCoordinates($coordinates);
            }
        }

        return $this;
    }

    protected function getCityFromAPI(City $city): ?array
    {
        $client = new Client();
        $name = $city->getName() . ", " . $city->getCountry()->getName();

        try {
            $response = $client->get(
                "https://api.mapbox.com/geocoding/v5/mapbox.places/{$name}.json?access_token={$this->token}&country={$city->getCountry()->getId()}",
            );
            $responseArray = json_decode($response->getBody()->getContents(), true);

            if (empty($responseArray["features"])) {
                echo "City $name was not found." . PHP_EOL;
                return null;
            }
            $coordinates = $responseArray["features"][0]["center"];

            $this->updateCache('city', $city->getId(), $coordinates);
            return $coordinates;
        } catch (GuzzleException) {
            echo "Coordinates for {$name} were not fetched." . PHP_EOL;
            return null;
        }
    }

    public function normalizeCountryName(string $unnormalizedName): ?string
    {
        return $this->getFromCacheOrCreate(
            'country',
            $unnormalizedName,
            fn() => $this->getCountryFromAPI($unnormalizedName)
        );
    }

    protected function getCountryFromAPI(string $unnormalizedName): ?string
    {
        $client = new Client();

        try {
            $response = $client->get(
                "https://api.mapbox.com/geocoding/v5/mapbox.places/{$unnormalizedName}.json?access_token={$this->token}&types=country&limit=1&autocomplete=true",
            );

            $responseArray = json_decode($response->getBody()->getContents(), true);

            if (empty($responseArray["features"])) {
                echo "Country name for unnormalized name {$unnormalizedName} was not found." . PHP_EOL;
                return null;
            }

            $normalizedCountry = $responseArray["features"][0]["text"];
            $this->updateCache('country', $unnormalizedName, $normalizedCountry);

            return $normalizedCountry;
        } catch (GuzzleException) {
            echo "Normalized country name for {$unnormalizedName} was not fetched." . PHP_EOL;
            return null;
        }
    }

    public function getPlaceFromCoordinates(float $latitude, float $longitude): ?array
    {
        $coordinatesKey = "{$latitude},{$longitude}";
    
        return $this->getFromCacheOrCreate(
            'coordinates',
            $coordinatesKey,
            fn() => $this->getPlaceFromAPI($latitude, $longitude)
        );
    }
    
    protected function getPlaceFromAPI(float $latitude, float $longitude): ?array
    {
        $client = new Client();
    
        try {
            $response = $client->get(
                "https://api.mapbox.com/geocoding/v5/mapbox.places/{$longitude},{$latitude}.json?access_token={$this->token}&types=place,country",
            );
    
            $responseArray = json_decode($response->getBody()->getContents(), true);
    
            if (empty($responseArray["features"])) {
                echo "City and country name for coordinates {$latitude},{$longitude} was not found." . PHP_EOL;
                return null;
            }
    
            $cityName = $responseArray["features"][0]["text"];
            $countryName = $responseArray["features"][1]["text"];
            $this->updateCache('coordinates', "{$latitude},{$longitude}", [$cityName, $countryName]);
    
            return [$cityName, $countryName];
        } catch (GuzzleException) {
            echo "City and country name for coordinates {$latitude},{$longitude} was not fetched." . PHP_EOL;
            return null;
        }
    }
    

    protected function updateCache(string $type, string $key, mixed $value): void
    {
        $this->cache[$type][$key] = $value;
        file_put_contents(self::CACHE_FILENAMES[$type], json_encode($this->cache[$type], JSON_UNESCAPED_UNICODE));
    }

    protected function getFromCacheOrCreate(string $type, string $key, callable $creator): mixed
    {
        if (array_key_exists($key, $this->cache[$type])) {
            return $this->cache[$type][$key];
        }

        $value = $creator();

        if ($value !== null) {
            $this->updateCache($type, $key, $value);
        }

        return $value;
    }
}

