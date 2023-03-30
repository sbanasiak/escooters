<?php

declare(strict_types=1);

namespace EScooters\Importers;

use DOMElement;
use EScooters\Importers\DataSources\HtmlDataSource;
use Symfony\Component\DomCrawler\Crawler;

class HulajDataImporter extends DataImporter implements HtmlDataSource
{
    protected const FIXED_COUNTRY = "Poland";
    protected Crawler $sections;

    public function getBackground(): string
    {
        return "#D6213F";
    }

    public function extract(): static
    {
        $html = file_get_contents("https://hulaj.eu/miasta/");

        $crawler = new Crawler($html);
        $this->sections = $crawler->filter('.wp-block-heading');
        return $this;
    }


    public function transform(): static
    {
        $country = $this->countries->retrieve(static::FIXED_COUNTRY);

        foreach ($this->sections as $section) {
            $cityText = $section->nodeValue;
            if (str_contains($cityText, "przerwa")) {
                continue;
            } else {
                $city = $this->cities->retrieve($section->nodeValue, $country);
                $this->provider->addCity($city);
            }
        }
        return $this;
    }

}