<?php
/**
 * Script to fetch the addresses of places from the latitude and longitude supplied
 * A set of latitudes and longitudes are connverted to addresses
 * Authors: Bobcares
 * Date: 02 dec, 2014
 */

// fetch the request params
$offset = sanitize_text_field($_GET['offset']);
$limit = sanitize_text_field($_GET['limit']);
$total = sanitize_text_field($_GET['total']);
$block = sanitize_text_field($_GET['block']);

// decode the data
$data = json_decode($_GET['places']);

$curl = curl_init();
$tableData = array();

// loop through the list of latitudes and longitudes
foreach ($data as $place) {
    $lat = $place->geometry->location->k;
    $lon = $place->geometry->location->D;

    // call google API function to fetch the address
    $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . $lat . "," . $lon;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);

    // decode the result and convert to array
    $jsonDecode = json_decode($output);
    $arrayData = (Array) $jsonDecode;

    $address = $arrayData['results'][0]->formatted_address;
    $tableData[] = $address;
    sleep(1.5);
}

// output the encoded data
echo json_encode($tableData);
curl_close($curl);
?>
