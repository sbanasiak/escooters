<?php

require "./vendor/autoload.php";

use Dotenv\Dotenv;
use EScooters\Importers\BirdDataImporter;
use EScooters\Importers\BoltDataImporter;
use EScooters\Importers\DataImporter;
use EScooters\Importers\DottDataImporter;
use EScooters\Importers\HelbizDataImporter;
use EScooters\Importers\LimeDataImporter;
use EScooters\Importers\LinkDataImporter;
use EScooters\Importers\NeuronDataImporter;
use EScooters\Importers\QuickDataImporter;
use EScooters\Importers\SpinDataImporter;
use EScooters\Importers\TierDataImporter;
use EScooters\Importers\VoiDataImporter;
use EScooters\Importers\BITMobilityDataImporter;
use EScooters\Importers\WhooshDataImporter;
use EScooters\Importers\HulajDataImporter;
use EScooters\Models\Repositories\Cities;
use EScooters\Models\Repositories\Countries;
use EScooters\Models\Repositories\Providers;
use EScooters\Services\MapboxGeocodingService;
use EScooters\Services\QuickChartIconsService;
use EScooters\Utils\BuildInfo;

Dotenv::createImmutable(__DIR__)->load();

$mapbox = MapboxGeocodingService::getInstance();

$cities = new Cities();
$countries = new Countries();
$providers = new Providers();

/** @var array<DataImporter> $dataImporters */
$dataImporters = [
    new TierDataImporter($cities, $countries, $mapbox),
    new BITMobilityDataImporter($cities, $countries),
    new HulajDataImporter($cities, $countries),
    new VoiDataImporter($cities, $countries),
    new BirdDataImporter($cities, $countries),
    new BoltDataImporter($cities, $countries),
    new DottDataImporter($cities, $countries),
    new HelbizDataImporter($cities, $countries),
    //new LimeDataImporter($cities, $countries),
    new LinkDataImporter($cities, $countries),
    new NeuronDataImporter($cities, $countries),
    new QuickDataImporter($cities, $countries),
    new SpinDataImporter($cities, $countries),
    new WhooshDataImporter($cities, $countries),
];

$timestamp = date("Y-m-d H:i:s");
echo "Build date: $timestamp" . PHP_EOL . PHP_EOL;

foreach ($dataImporters as $dataImporter) {
    try {
        $provider = $dataImporter->extract()->transform()->load();
    } catch (Throwable $exception) {
        echo $exception->getMessage() . PHP_EOL;
        continue;
    }

    $providers->add($provider);

    echo "{$provider->getCities()->count()} cities fetched for {$provider->getName()}." . PHP_EOL;
}

$count = count($cities->all());
echo PHP_EOL . "$count cities fetched." . PHP_EOL;

$mapbox->setCoordinatesToCities($cities);

$icons = new QuickChartIconsService();
$icons->generateCityIcons($cities);

$info = new BuildInfo(
    timestamp: $timestamp,
    citiesCount: $count,
    providersCount: count($providers->jsonSerialize()),
);

file_put_contents("./public/data/cities.json", json_encode($cities, JSON_UNESCAPED_UNICODE));
file_put_contents("./public/data/countries.json", json_encode($countries, JSON_UNESCAPED_UNICODE));
file_put_contents("./public/data/providers.json", json_encode($providers, JSON_UNESCAPED_UNICODE));
file_put_contents("./public/data/info.json", json_encode($info, JSON_UNESCAPED_UNICODE));
