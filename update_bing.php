<?php

include 'vendor/autoload.php';


// Configuration
//
// Bing Ads Tracker ID
$ti = '4021148';
//
// Bing Ads Event Category
$ec = 'Offline';
//
// Bing Ads Event Action
$ea = 'Conversion';
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

function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);

        return strtolower($uuid);
    }
}

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
            'total_cost' => 0
        );
    }

    if (!$order[13]) {
        $order[13] = 0;
    }

    // Increase total cost for transaction
    $ordersCollection[$order[2]]['total_cost'] += $order[13];

    echo $order[13], "\r\n";
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


    // But, if user is found in Call Tracking Metrics, define cid from API response
    if (count($clientDetails['calls']) != 0) {
        if (isset($clientDetails['calls'][0]['ms'])) {
            $ordersCollection[$key]['msclkid'] = $clientDetails['calls'][0]['ms']['msclkid'];
        } else {
            unset($ordersCollection[$key]);
        }
    } else {

        unset($ordersCollection[$key]);
    }

    // Sleep to prevent requests limit
    sleep(1);
}


// Send data to GA
foreach($ordersCollection as $key => $order) {

    // Send transaction info
    $orderDetail = array(
        'ti' => $ti,
        'Ver' => '2',
        'mid' => $mid,
        'msclkid' => $order['msclkid'].'-0',
        'rn' => rand(100000,999999),

        'ec' => $ec,
        'ea' => $ea,
        'en' => 'Y',
        'evt' => 'custom',

        'gv' => 500,
        'gc' => $currency
    );

    echo Requests::get('https://bat.bing.com/action/0?'.http_build_query($orderDetail), ['Referer' => 'https://www.medicalsupplydepot.com/', 'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36'])->url;

}



// Done
//1871
