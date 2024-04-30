<?php 
require_once "vendor/autoload.php";

$service_account_key_file_path = "../cedar-module-421115-6db17b1cdedc.json";

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;

try {

    // Authenticate using a keyfile path
    $client = new BetaAnalyticsDataClient([
        'credentials' => $service_account_key_file_path
    ]);

    // CHANGE THIS
    $property_id = '277757220';
    $dimensions = [ new \Google\Analytics\Data\V1beta\Dimension(['name' => 'pagePath']), new \Google\Analytics\Data\V1beta\Dimension(['name' => 'pageTitle'])];
    $metrics = [new \Google\Analytics\Data\V1beta\Metric(['name' => 'totalusers'])];
    $dateRange = [new \Google\Analytics\Data\V1beta\DateRange(['start_date' => '2020-01-01', 'end_date' => '2024-12-31'])];

    $response =  $client->runRealtimeReport ([
        'property' => 'properties/' . $property_id,
        'dimensions' => $dimensions,
        'metrics' => $metrics,
        'date_ranges' => $dateRange
    ]);

    foreach ($response->getRows() as $row) {
        foreach ($row->getDimensionValues() as $dimensionValue) {
            print 'Dimension Value: ' . $dimensionValue->getValue() . PHP_EOL;
        }
        foreach ($row->getMetricValues() as $metricValue) {
            print 'Metric Value: ' . $metricValue->getValue() . PHP_EOL;
        }
    }

} catch (\Google\ApiCore\ValidationException $e) {
    printf($e);
}