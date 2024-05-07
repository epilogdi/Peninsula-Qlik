<?php 
require_once "../../vendor/autoload.php";
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;

$service_account_key_file_path = "../../../cedar-module-421115-6db17b1cdedc.json";
$client = new BetaAnalyticsDataClient([
  'credentials' => $service_account_key_file_path
]);

$property_id = '362175915';

$request = (new RunReportRequest())
  ->setProperty('properties/' . $property_id)
  ->setDateRanges([
    new DateRange([
      'start_date' => '2020-01-01',
      'end_date' => '2024-12-31',
    ]),
  ])
  ->setDimensions([new Dimension([
    'name' => 'pagePath',
  ]),new Dimension([
    'name' => 'pageTitle',
  ]),new Dimension([
    'name' => 'eventName',
  ]),new Dimension([
    'name' => 'deviceCategory',
  ]),new Dimension([
    'name' => 'country',
  ]),new Dimension([
    'name' => 'city',
  ])
                  ])
  ->setMetrics([new Metric([
    'name' => 'totalusers',
  ]),new Metric([
    'name' => 'sessions',
  ]),new Metric([
    'name' => 'sessionsPerUser',
  ]),new Metric([
    'name' => 'averageSessionDuration',
  ])
               ]);

  
$response = $client->runReport($request);


foreach ($response->getRows() as $row) {
  echo $row->getDimensionValues()[0]->getValue()."<br>";
  echo $row->getDimensionValues()[1]->getValue()."<br>";
  echo $row->getDimensionValues()[2]->getValue()."<br>";
  echo $row->getDimensionValues()[3]->getValue()."<br>";
  echo $row->getDimensionValues()[4]->getValue()."<br>";
  echo $row->getDimensionValues()[5]->getValue()."<br>";
  echo $row->getMetricValues()[0]->getValue()."<br>";
  echo $row->getMetricValues()[1]->getValue()."<br>";
  echo $row->getMetricValues()[2]->getValue()."<br>";
  echo $row->getMetricValues()[3]->getValue()."<br>";
  echo "<hr>";
}








/*

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;

$VIEW_ID = "308641638"
$KEY_FILE_LOCATION = "'../seismic-iridium-421119-fa5b27d1cef4.json'"




function get_ga4_data(){
$client = new BetaAnalyticsDataClient([
'credentials' => json_decode(file_get_contents('../seismic-iridium-421119-fa5b27d1cef4.json'), true)
]);

}




















$dimensions = [ new \Google\Analytics\Data\V1beta\Dimension(['name' => 'fullPageUrl']), new \Google\Analytics\Data\V1beta\Dimension(['name' => 'pageTitle'])];
$metrics = [new \Google\Analytics\Data\V1beta\Metric(['name' => 'totalusers'])];
$response =  $client->runRealtimeReport ([
'property' => 'properties/' . $property_id,
'dimensions' => $dimensions,
'metrics' => $metrics,
]);





/*$response = $client->runReport(
'property' => 'properties/308641638',
'dimensions' => [{"name": "fullPageUrl"}, {"name": "pageTitle"}],
'date_ranges' => [{"start_date": "2024-01-01", "end_date": "today"}],
'metrics' => [{"name": "totalusers"}],
);*/

/*foreach ($response->getRows() as $row) {
foreach ($row->getDimensionValues() as $dimensionValue) {
print 'Dimension Value: ' . $dimensionValue->getValue() . PHP_EOL;
}
}



use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;


$property_id = '308641638';

// Using a default constructor instructs the client to use the credentials
// specified in GOOGLE_APPLICATION_CREDENTIALS environment variable.
$client = new BetaAnalyticsDataClient();

// Make an API call.
$request = (new RunReportRequest())
->setProperty('properties/' . $property_id)
->setDateRanges([
new DateRange([
'start_date' => '2020-03-31',
'end_date' => 'today',
]),
])
->setDimensions([new Dimension([
'name' => 'city',
]),
])
->setMetrics([new Metric([
'name' => 'activeUsers',
])
]);
$response = $client->runReport($request);

// Print results of an API call.
print 'Report result: ' . PHP_EOL;

foreach ($response->getRows() as $row) {
print $row->getDimensionValues()[0]->getValue()
. ' ' . $row->getMetricValues()[0]->getValue() . PHP_EOL;
}*/
?>