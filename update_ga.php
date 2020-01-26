<?php

include 'vendor/autoload.php';


// Configuration
//
// Google Analytics ID
$tid = 'UA-449437-2';
//
// Call Tracking Metrics Account ID
$accountId = '208655';
// Call Tracking Metrics Api Key
$apiAuthKey = 'YTIwODY1NWQ3ZGNiY2M5NTRjOTc5YzViODg1ODU5MjY1Yjc4MDk2NDpkMzhhYzQwNDdkOWFmY2EyN2UwZjY5ZGI3ZGYyMTA5OTE4ZDE=';
//
// Orders CSV Url
$ordersUrl = 'http://dev.globalsempartners.com/medicalsupplydepot/Medical_Supply_Depot_Phone_Orders_'.date('m-d-Y').'.csv';
//
// Currency
$currency = 'USD';


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
        $ordersCollection[$order[2]] = array(
            'phone' => $order[1],
            'products' => array(),
            'tax'   => $order[10],
            'shipping' => $order[11],
            'total_cost' => 0
        );
    }

    // Add product to transaction
    $ordersCollection[$order[2]]['products'][] = array(
        'name'  => $order[3],
        'sku'   => $order[4],
        'quantity'  => $order[5],
        'category'  => $order[14],
        'cost'  => $order[12],
        'total_cost' => $order[13]
    );

    if (!$order[13]) {
        $order[13] = 0;
    }

    // Increase total cost for transaction
    $ordersCollection[$order[2]]['total_cost'] += $order[13];
}



// Headers for Call Tracking Metrics API
$headers = array(
    'Content-Type'  => 'application/json',
    'Authorization' => "Basic {$apiAuthKey}"
);

// Get GA UserID for each transaction
foreach ($ordersCollection as $key => $order) {
    $clientDetails = Requests::get("https://api.calltrackingmetrics.com/api/v1/accounts/{$accountId}/calls?filter={$order['phone']}", $headers);

    $clientDetails = json_decode($clientDetails->body, true);

    // Define random cid for user
    $ordersCollection[$key]['cid'] = 'GA1.2.'.rand(1000000000, 2147483647) . '.' . time();

    // But, if user is found in Call Tracking Metrics, define cid from API response
    if (count($clientDetails['calls']) != 0) {
         $ordersCollection[$key]['cid'] = $clientDetails['calls'][0]['ga']['cid'];
    }

    // Sleep to prevent requests limit
    sleep(1);
}


// Send data to GA
foreach($ordersCollection as $key => $order) {

    // Send transaction info
    $orderDetail = array(
        'v' => '1',
        't' => 'transaction',
        'tid' => $tid,
        'cid' => str_replace('GA1.2.', '', $order['cid']),

        'ti' => $key,
        'tt' => $order['tax'],
        'tr' => $order['total_cost'],
        'ts' => $order['shipping'],
        'cu' => $currency
    );

    echo Requests::get('https://www.google-analytics.com/collect?'.http_build_query($orderDetail))->url;

    // Send products info
    foreach ($order['products'] as $product) {

        $orderItem = array(
            'v' => '1',
            't' => 'item',
            'tid' => $tid,
            'cid' => str_replace('GA1.2.', '', $order['cid']),

            'ti' => $key,

            "ic" => $product['sku'],
            "in" => $product['name'],
            "ip" => $product['cost'],
            "iv" => $product['category'],
            "iq" => $product['quantity']
        );


        echo Requests::get('https://www.google-analytics.com/collect?'.http_build_query($orderItem))->url;
    }

}



// Done
