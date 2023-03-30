<?php

declare(strict_types=1);

namespace EScooters\Importers;

use EScooters\Importers\DataSources\HtmlDataSource;
use Symfony\Component\DomCrawler\Crawler;

class BITMobilityDataImporter extends DataImporter implements HtmlDataSource
{
    protected const FIXED_COUNTRY = "Italy";

    protected Crawler $sections;

    public function getBackground(): string
    {
        return "#D6213F";
    }

    public function extract(): static
    {
        $html = file_get_contents("https://bitmobility.it/dove-siamo/");

        $crawler = new Crawler($html);
        $this->sections = $crawler->filter(".wpb_column.vc_column_container.vc_col-sm-6");
        return $this;
    }

    public function transform(): static
    {
        $country = $this->countries->retrieve(static::FIXED_COUNTRY);

        foreach ($this->sections as $section) {
            foreach ($section->getElementsByTagName("a") as $city) {
                $cityText = trim($city->nodeValue);
                $city = $this->cities->retrieve($cityText, $country);
                $this->provider->addCity($city);
            }
        }
        return $this;
    }
}
