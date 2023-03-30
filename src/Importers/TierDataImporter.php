<?php

declare(strict_types=1);

namespace EScooters\Importers;

use EScooters\Importers\DataSources\JsonDataSource;

class TierDataImporter extends DataImporter implements JsonDataSource
{
    protected array $entries;

    public function getBackground(): string
    {
        return "#0E1A50";
    }

    public function extract(): static
    {
        $json = file_get_contents("https://www.tier.app/page-data/sq/d/134304685.json");
        $this->entries = json_decode($json, associative: true)["data"]["craft"]["entries"];

        $this->entries = array_filter($this->entries, fn($entry) => $entry["siteId"] === 3);
        return $this;
    }

    public function transform(): static
    {
        $previousCountryName = null;

        foreach ($this->entries as $entry) {
            if ($previousCountryName === $entry["title"] || !isset($entry["headline"])) {
                continue;
            }

            $previousCountryName = $entry["title"];

            $country = $this->countries->retrieve($entry["title"]);

            $cities = explode(",", $entry["headline"]);
            foreach ($cities as $cityName) {
                $cityName = trim($cityName);

                $city = $this->cities->retrieve($cityName, $country);
                $this->provider->addCity($city);
            }
        }

        return $this;
    }
}
