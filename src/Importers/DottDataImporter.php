<?php

declare(strict_types=1);

namespace EScooters\Importers;

use DOMElement;
use EScooters\Importers\DataSources\HtmlDataSource;
use Symfony\Component\DomCrawler\Crawler;

class DottDataImporter extends DataImporter implements HtmlDataSource
{
    protected Crawler $sections;

    public function getBackground(): string
    {
        return "#F5C605";
    }

    public function extract(): static
    {
        $html = file_get_contents("https://ridedott.com/ride-with-us/paris/");

        $crawler = new Crawler($html);
        $this->sections = $crawler->filter('li.mb-4.last\:mb-0');
        return $this;
    }


    public function transform(): static
    {
        foreach ($this->sections as $section) {
            $countryText = trim($section->getElementsByTagName('span')[0]->nodeValue);
            $country = $this->countries->retrieve($countryText);

            foreach ($section->getElementsByTagName('a') as $city) {
                $cityText = trim($city->nodeValue);
                $city = $this->cities->retrieve($cityText, $country);
                $this->provider->addCity($city);
            }
        }

        return $this;
    }

}