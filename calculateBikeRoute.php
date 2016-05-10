<?php
/*
Backend of the bike routing. 
The JS will send a GET request w/ starting and ending locations
This will respond with a JSON with the overall route and associated times.
*/

require_once('functions.php');
error_reporting(E_ALL);

$objHubway = getHubwayData();
$strGoogleApiKey = "AIzaSyCQODAY6twd-kCYbCc8qOP7PqdBNc1mOmU";

// $_REQUEST['start'] = "1306 MAssachusetts Ave., Cambridge, MA";
// $_REQUEST['end'] = "6 Marie Ave., Cambridge, MA";

// $aryStart = convertAddressToCoordinates($strGoogleApiKey, urldecode($_REQUEST["start"]));
// $aryEnd = convertAddressToCoordinates($strGoogleApiKey, urldecode($_REQUEST['end']));

$aryRoute = getBikeShareRoute($strGoogleApiKey, urldecode($_REQUEST["start"]), urldecode($_REQUEST['end']));

// Simplify the route and print a json-encoded array of the bare details (start, end, mode, route, time)

$aryRouteReturn = array();
foreach($aryRoute['waypoints'] as $aryWaypoint) {
	$aryReturn = array();
	$aryReturn['mode'] = (string) $aryWaypoint['mode'];
	$aryReturn['time'] = (int) $aryWaypoint['time'];
	$aryReturn['start'] = $aryWaypoint['start'];
	$aryReturn['end'] = $aryWaypoint['end'];

	// IF there are multiple options, Google will return this as an array (although it think it shouldn't based on the options used)
	if(is_array($aryWaypoint['route'])) {
		echo "\n\n\n";
		echo "Got an array!!! Argh!!!";
		die;
		$objRoute = $aryWaypoint['route'][array_keys($aryWaypoint['route'])[0]];
	} else {
		$objRoute = $aryWaypoint['route'];
	}
	$aryReturn['copyrights'] = (string) $objRoute->route->copyrights;
	$aryReturn['route'] = (string) $objRoute->route->overview_polyline->points;

	array_push($aryRouteReturn, $aryReturn);
}

$strJson = json_encode($aryRouteReturn);
print($strJson);

?>