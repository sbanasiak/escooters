<?php

declare(strict_types=1);

namespace EScooters\Importers;

use DOMElement;
use EScooters\Exceptions\CityNotAssignedToAnyCountryException;
use EScooters\Importers\DataSources\HtmlDataSource;
use EScooters\Utils\HardcodedCitiesToCountriesAssigner;
use Symfony\Component\DomCrawler\Crawler;
use EScooters\Services\MapboxGeocodingService;

class BirdDataImporter extends DataImporter implements HtmlDataSource
{
    protected Crawler $sections;

    public function getBackground(): string
    {
        return "#26CCF0";
    }

    public function extract(): static
    {
        $html = file_get_contents("https://www.bird.co/map/");
        $crawler = new Crawler($html);

        $this->sections = $crawler->filter('body script')->first();

        return $this;
    }

    public function transform(): static
    {    
        $mapbox = MapboxGeocodingService::getInstance();

        $javascriptCode = $this->sections->text();
        
        preg_match('/let features = \[(.*?)\];/', $javascriptCode, $matches);
        preg_match_all('/new google.maps.LatLng\(([^\)]+)\)/', $matches[1], $matches);
    
        foreach ($matches[1] as $match) {
            [$latitude, $longitude] = array_map('trim', explode(',', $match));
            if (!empty($latitude) && !empty($longitude)) {
                $location = $mapbox->getPlaceFromCoordinates((float)$latitude, (float)$longitude);
                $country = $this->countries->retrieve($location[1]);
                $city = $this->cities->retrieve($location[0], $country);
                $this->provider->addCity($city);
            }
        }
    
        return $this;
    }
    
    
}
