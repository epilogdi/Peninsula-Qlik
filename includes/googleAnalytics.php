<?php 
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;

$client = new BetaAnalyticsDataClient([
  'credentials' => "../../../cedar-module-421115-6db17b1cdedc.json"
]);

function getData($response,$project){
  $data = array();
  foreach ($response->getRows() as $row) {
    $obj = new stdClass(); 
    $obj->viewId = $project->viewId;
    $obj->name = $project->nameAlias;
    $obj->pagePath = $row->getDimensionValues()[0]->getValue();
    $obj->pageTitle = $row->getDimensionValues()[1]->getValue();
    $obj->eventName = $row->getDimensionValues()[2]->getValue();
    $obj->deviceCategory = $row->getDimensionValues()[3]->getValue();
    $obj->country = $row->getDimensionValues()[4]->getValue();
    $obj->city = $row->getDimensionValues()[5]->getValue();
    $obj->date = $row->getDimensionValues()[6]->getValue();
    $obj->totalusers = $row->getMetricValues()[0]->getValue();
    $obj->sessions = $row->getMetricValues()[1]->getValue();
    $obj->sessionsPerUser = $row->getMetricValues()[2]->getValue();
    $obj->averageSessionDuration = $row->getMetricValues()[3]->getValue();
    array_push($data, $obj);   
  }
  return $data;
}

function extractAnalytics($project){
  global $client;
  global $database;
  $request = (new RunReportRequest())
    ->setProperty('properties/' . $project->viewId)
    ->setDateRanges([
      new DateRange([
       'start_date' => 'yesterday',
        'end_date' => 'today',
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
    ]),new Dimension([
      'name' => 'date',
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

  return $client->runReport($request);
}






?>