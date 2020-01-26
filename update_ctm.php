<?php

include 'vendor/autoload.php';


// Configuration
//
// Call Tracking Metrics Account ID
$accountId = '208655';
// Call Tracking Metrics Api Key
$apiAuthKey = 'YTIwODY1NWQ3ZGNiY2M5NTRjOTc5YzViODg1ODU5MjY1Yjc4MDk2NDpkMzhhYzQwNDdkOWFmY2EyN2UwZjY5ZGI3ZGYyMTA5OTE4ZDE=';
//
// Orders CSV Url
$ordersUrl = 'http://dev.globalsempartners.com/medicalsupplydepot/Medical_Supply_Depot_Phone_Orders_'.date('m-d-Y').'.csv';


// Read orders file & parse it as csv into array
$ordersCSVFile = Requests::get($ordersUrl);
$ordersCSVLines = explode("\n", $ordersCSVFile->body);

$orderCSVCollection = [];
foreach($ordersCSVLines as $line) {
    $orderCSVCollection[] = explode(',', $line);
}
// Magic: remove last row from csv (empty row after explode)
array_pop($orderCSVCollection);
// Magic: remove first row from csv (headers)
array_shift($orderCSVCollection);

// Prepare data
$ordersCollection = [];
foreach($orderCSVCollection as $order) {
    // Create transaction in collection, if transaction does not exists (in collection)
    if (!key_exists($order[2], $ordersCollection)) {
		$date = explode('/', $order[0]);
		
        $ordersCollection[$order[2]] = array(
			'aid' => $accountId,
            'num' => $order[1],
            'val' => 0,
			'sale_date' => $date[2].'-'.$date[0].'-'.$date[1]
        );
    }
    if (!$order[13]) {
        $order[13] = 0;
    }

    // Increase total cost for transaction
    $ordersCollection[$order[2]]['val'] += $order[13];
}



// Headers for Call Tracking Metrics API
$headers = array(
    'Content-Type'  => 'application/json',
    'Authorization' => "Basic {$apiAuthKey}"
);

// Get GA UserID for each transaction
foreach ($ordersCollection as $key => $order) {
    $clientDetails = Requests::get("https://api.calltrackingmetrics.com/api/v1/accounts/{$accountId}/calls?filter={$order['num']}", $headers);

    $clientDetails = json_decode($clientDetails->body, true);
	
    

	if (count($clientDetails['calls']) > 0) {
		if (isset($clientDetails['calls'][0]['id'])) {
			$ordersCollection[$key]['cid'] = $clientDetails['calls'][0]['id'];
		}

	}

    // Sleep to prevent requests limit
    //sleep(1);
}

foreach($ordersCollection as $key => $order) {

	if (!isset($order['num']) || trim($order['num']) == "") {
		continue;
	}
	
	if (!isset($order['cid'])) {
		continue;
	}
	
	echo Requests::post("https://api.calltrackingmetrics.com/api/v1/accounts/{$accountId}/calls/{$order['cid']}/sale", $headers, json_encode(['value' => $order['val'], 'conversion' => '1', 'sale_date' => $order['sale_date']]))->url;

}
