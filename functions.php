<?php
error_reporting(E_ALL);
// Hubway functions:
// Function to get all the hubway data (as an array/object)
function getHubwayData() {
	$strHubwayData = "http://www.thehubway.com/data/stations/bikeStations.xml";	
	$objXml = simplexml_load_file($strHubwayData);
	return($objXml);

}


// Google maps functions:
// convert address to lat/long
function convertAddressToCoordinates($strGoogleApiKey, $strAddress) {
	$strUrlBase = "https://maps.googleapis.com/maps/api/geocode/xml?";
	$strUrlRequest = $strUrlBase . "key=" . $strGoogleApiKey . "&address=".urlencode($strAddress);
	//echo "\n\n" . $strUrlRequest . "\n\n";
	$objXml = simplexml_load_file($strUrlRequest);
	//print_r($objXml);
	$objCoordinates = $objXml->result->geometry->location;
	$aryCoordinates = (array) $objCoordinates;
	//var_dump($objXml->result);
	return($aryCoordinates);
}

function convertPlaceToCoordinates($strGoogleApiKey, $strPlaceId) {
	$strUrlBase = "https://maps.googleapis.com/maps/api/place/details/xml?";
	$strUrlRequest = $strUrlBase . "key=" . $strGoogleApiKey . "&placeid=".urlencode($strPlaceId);
	//echo "\n\n" . $strUrlRequest . "\n\n";
	$objXml = simplexml_load_file($strUrlRequest);
	//print_r($objXml);
	$objCoordinates = $objXml->result->geometry->location;
	$aryCoordinates = (array) $objCoordinates;
	//var_dump($objXml->result);
	return($aryCoordinates);
}


// As the crow flies distance. 
// Euclidian would be pretty reasonable
// But I'll use haversine approximation
function calculateDistance($aryCoordinates1, $aryCoordinates2) {
	$fltR = 3959; # radius of earth in miles
	$fltLat1 = deg2rad($aryCoordinates1['lat']);
	$fltLat2 = deg2rad($aryCoordinates2['lat']);
	$fltDLat = ($fltLat2 - $fltLat1);
	$fltDLng = deg2rad($aryCoordinates2['lng'] - $aryCoordinates1['lng']);

	$fltA = pow(sin($fltDLat/2),2) + 
				cos($fltLat1 * $fltLat2) *
				pow(sin($fltDLng/2),2);
	$fltC = 2 * atan2(sqrt($fltA),sqrt(1-$fltA));
	$fltD = $fltR * $fltC;
	return($fltD);
}

//echo calculateDistance($aryHome, $arySchool);

// Suddenly realizing this will be way more useful to do in jscript.
// Will have to change it all.
// Javascript is useful because then I can dynamically place pins, and ideally even allow the user to click on the starting/ending locations.
// Probably can also just use the google tools to get current location in that case.

// Function to get hubway stations closest to a destination
function findClosestStations($objHubway, $aryCoordinates, $blnPickup = true, $intNumberStations = 3) {
	// Iterate through all hubway stations and this lat/long and calculate distances.
	// Return the station information for the closest $intNumberStations.
	$aryStationDistances = array();
	foreach($objHubway->station as $objStation) {

		if(	$objStation->installed == 'true' && 
			$objStation->locked == 'false' &&
			(($objStation->nbBikes > 0 && $blnPickup) || ($objStation->nbEmptyDocks > 0 && !$blnPickup)) ) {
			//var_dump($objStation);
			$aryStationCoord = array(
				"lat" => (float) $objStation->lat,
				"lng" => (float) $objStation->long);

			$aryStationDistances[(string) $objStation->id] = calculateDistance($aryCoordinates,$aryStationCoord);
			//var_dump($aryStationDistances);
		} else {
			$aryStationDistances[(string) $objStation->id] = 999999;
		}
	}

	# Now get the ids of the closest ones.
	asort($aryStationDistances);
	$aryClosest = array_slice($aryStationDistances, 0, $intNumberStations, true);

	foreach($aryClosest as $intId=>$fltDistance) {
		foreach($objHubway->station as $objStation) {
			if($objStation->id == $intId) {
				$aryStation = (array) $objStation;
				$aryStation['distance'] = $fltDistance;
				$aryClosest[$intId] = $aryStation;
			}
		}
	}

	return $aryClosest;
}
//var_dump($objHubway);
//$aryClosest = findClosestStations($objHubway, $aryHome);
//var_dump($aryClosest);


// Calculate walking/biking times between locations (lat/long)
// Mode is 'bicycling', 'walking', 'driving', or 'transit'
function calculateTravelRoute($strGoogleApiKey, $aryStartCoordinates, $aryEndCoordinates, $strMode) {
	$strUrlRequest = "https://maps.googleapis.com/maps/api/directions/xml?" . 
						"key=" . $strGoogleApiKey . "&" . 
						"origin=" . $aryStartCoordinates['lat'] . "," . $aryStartCoordinates['lng'] . "&" .
						"destination=" . $aryEndCoordinates['lat'] . "," . $aryEndCoordinates['lng'] . "&" . 
						"mode=" . $strMode . "&" . 
						"alternatives=false";
	$objXml = simplexml_load_file($strUrlRequest);
	return($objXml);
}

// Calculate walking/biking times between locations (lat/long)
// Mode is 'bicycling', 'walking', 'driving', or 'transit'
function calculateTravelTime($strGoogleApiKey, $aryStartCoordinates, $aryEndCoordinates, $strMode) {
	$objXml = calculateTravelRoute($strGoogleApiKey, $aryStartCoordinates, $aryEndCoordinates, $strMode);
	return((int) $objXml->route->leg->duration->value);
}

//var_dump(calculateTravelTime($strGoogleApiKey, $aryHome, $arySchool, "walking"));

// Look at all permutations from departure location, destination, and the appropriate set of waypoints
// For now, just looking at bikeshare (+walking), transit, wakling, driving. 
// Not thinking about more complicated routes (uber + transit, bikeshare + transit, etc)
function compareRoutes($strGoogleApiKey, $strStartAddress, $strEndAddress) {
	// First, get the appropriate sets of bike share locations
	$aryCoordinatesStart = convertAddressToCoordinates($strGoogleApiKey, $strStartAddress);
	$aryCoordinatesEnd = convertAddressToCoordinates($strGoogleApiKey, $strEndAddress);
	$objHubway = getHubwayData();

	$aryBSStart = findClosestStations($objHubway, $aryCoordinatesStart, true, 2);
	$aryBSEnd = findClosestStations($objHubway, $aryCoordinatesEnd, false, 2);

	$aryWalkTimeStart = array();
	foreach($aryBSStart as $intId=>$aryStation) {
		$aryStationCoord = array(
				"lat" => $aryStation['lat'],
				"lng" => $aryStation['long']);

		$aryWalkTimeStart[$intId] = calculateTravelTime($strGoogleApiKey, $aryCoordinatesStart, $aryStationCoord, "walking");
	}

	$aryWalkTimeEnd = array();
	foreach($aryBSEnd as $intId=>$aryStation) {
		$aryStationCoord = array(
				"lat" => $aryStation['lat'],
				"lng" => $aryStation['long']);

		$aryWalkTimeEnd[$intId] = calculateTravelTime($strGoogleApiKey, $aryCoordinatesEnd, $aryStationCoord, "walking");
	}
	
	$aryBSOptions = array();

	foreach($aryBSStart as $intStartId=>$aryStartStation) {
		$aryStartCoord = array(
			"lat" => $aryStartStation['lat'],
			"lng" => $aryStartStation['long']);
		foreach($aryBSEnd as $intEndId=>$aryEndStation) {
			$aryEndCoord = array(
				"lat" => $aryEndStation['lat'],
				"lng" => $aryEndStation['long']);
			$fltBikeTime = calculateTravelTime($strGoogleApiKey, $aryStartCoord, $aryEndCoord, "bicycling");
			
			$aryBSOptions[] = array(
				"total_time" => $aryWalkTimeStart[$intStartId] + $fltBikeTime + $aryWalkTimeEnd[$intEndId],
				"waypoints" => array(
					array(
						"start" => $aryCoordinatesStart, 
						"end" => $aryStartCoord, 
						"mode" => "walking",
						"time" => $aryWalkTimeStart[$intStartId]),
					array(
						"start" => $aryStartCoord,
						"end" => $aryEndCoord,
						"mode" => "bicycling",
						"time" => $fltBikeTime),
					array(
						"start" => $aryEndCoord,
						"end" => $aryCoordinatesEnd,
						"mode" => "walking",
						"time" => $aryWalkTimeEnd[$intEndId])
					)
				);
		}
	}

	// Keep the quickest one.
	$arySpeed = array();
	foreach($aryBSOptions as $aryBSRoute) {
		$arySpeed[] = $aryBSRoute["total_time"];
	}

	$intQuickest = array_keys($arySpeed, min($arySpeed))[0];

	$aryOptions = array("bikeshare" => $aryBSOptions[$intQuickest]);


	//var_dump($aryBSOptions);
	//return($aryOptions);
	// Then calculate the direct walking, driving, and transit routes.
	$aryModes = array("walking","driving","transit");
	$aryTimes = array();
	foreach($aryModes as $strMode) {
		$intTime = calculateTravelTime($strGoogleApiKey, $aryCoordinatesStart, $aryCoordinatesEnd, $strMode);
		$aryTimes[$strMode] = array(
			"total_time" => $intTime,
			"waypoints" => array(
				array(
					"start" => $aryCoordinatesStart,
					"end" => $aryCoordinatesEnd,
					"mode" => $strMode)));
	}
	$aryOptions = array_merge($aryOptions, $aryTimes);
	return($aryOptions);

}
// echo "\n\n\n\n\n";
// var_dump($aryRoutes);



// Generate links for each option (or embed one of them).
// Not precise because I can't exactly show the details. Maybe I can assume they'll know how to walk to the bikeshare?
function generateMaps($strGoogleApiKey, &$aryRoutes) {
	foreach($aryRoutes as $strMode => &$aryRoute) {
		if($strMode == "bikeshare") {
			$aryStart = $aryRoute['waypoints'][1]['start'];
			$aryEnd = $aryRoute['waypoints'][1]['end'];
			$strModeMap = $aryRoute['waypoints'][1]['mode'];
		} else {
			$aryStart = $aryRoute['waypoints'][0]['start'];
			$aryEnd = $aryRoute['waypoints'][0]['end'];
			$strModeMap = $aryRoute['waypoints'][0]['mode'];
		}

		$strUrl = 	"https://www.google.com/maps/embed/v1/directions?" . 
					"key=" . $strGoogleApiKey . "&" . 
					"origin=" . $aryStart['lat'] . "," . $aryStart['lng'] . "&" . 
					"destination=" . $aryEnd['lat'] . "," . $aryEnd['lng'] . "&" . 
					"mode=" . $strModeMap;
		$aryRoute["url"] = $strUrl;
	}
	return(true);
}

// Get the bike sharing route
// Returns lots of details about that route -- specific coordinates needed to draw polylines.
// $strStartPlace and $strEndPlace are both google place ids
function getBikeShareRoute($strGoogleApiKey, $strStartPlace, $strEndPlace) {

	// First, get the appropriate sets of bike share locations
	$aryCoordinatesStart = convertPlaceToCoordinates($strGoogleApiKey, $strStartPlace);
	$aryCoordinatesEnd = convertPlaceToCoordinates($strGoogleApiKey, $strEndPlace);
	$objHubway = getHubwayData();

	$aryBSStart = findClosestStations($objHubway, $aryCoordinatesStart, true, 2);
	$aryBSEnd = findClosestStations($objHubway, $aryCoordinatesEnd, false, 2);

	$aryWalkRouteStart = array();
	foreach($aryBSStart as $intId=>$aryStation) {
		$aryStationCoord = array(
				"lat" => $aryStation['lat'],
				"lng" => $aryStation['long']);
		
		$aryWalkRouteStart[$intId] = calculateTravelRoute($strGoogleApiKey, $aryCoordinatesStart, $aryStationCoord, "walking");
	}

	$aryWalkRouteEnd = array();
	foreach($aryBSEnd as $intId=>$aryStation) {
		$aryStationCoord = array(
				"lat" => $aryStation['lat'],
				"lng" => $aryStation['long']);

		$aryWalkRouteEnd[$intId] = calculateTravelRoute($strGoogleApiKey, $aryStationCoord, $aryCoordinatesEnd, "walking");
	}
	
	$aryBSOptions = array();

	foreach($aryBSStart as $intStartId=>$aryStartStation) {
		$aryStartCoord = array(
			"lat" => $aryStartStation['lat'],
			"lng" => $aryStartStation['long']);
		foreach($aryBSEnd as $intEndId=>$aryEndStation) {
			$aryEndCoord = array(
				"lat" => $aryEndStation['lat'],
				"lng" => $aryEndStation['long']);
			$objBikeRoute = calculateTravelRoute($strGoogleApiKey, $aryStartCoord, $aryEndCoord, "bicycling");
			$fltBikeTime = $objBikeRoute->route->leg->duration->value;
			
			$fltWalkTimeStart = $aryWalkRouteStart[$intStartId]->route->leg->duration->value;
			$fltWalkTimeEnd = $aryWalkRouteEnd[$intEndId]->route->leg->duration->value;

			$aryBSOptions[] = array(
				"total_time" => $fltWalkTimeStart + $fltBikeTime + $fltWalkTimeEnd,
				"waypoints" => array(
					array(
						"start" => $aryCoordinatesStart, 
						"end" => $aryStartCoord, 
						"mode" => "walking",
						"time" => $fltWalkTimeStart,
						"route" => $aryWalkRouteStart[$intStartId]),
					array(
						"start" => $aryStartCoord,
						"end" => $aryEndCoord,
						"mode" => "bicycling",
						"time" => $fltBikeTime,
						"route" => $objBikeRoute),
					array(
						"start" => $aryEndCoord,
						"end" => $aryCoordinatesEnd,
						"mode" => "walking",
						"time" => $fltWalkTimeEnd,
						"route" => $aryWalkRouteEnd[$intEndId])
					)
				);
		}
	}

	// Keep the quickest one.
	$arySpeed = array();
	foreach($aryBSOptions as $aryBSRoute) {
		$arySpeed[] = $aryBSRoute["total_time"];
	}

	$intQuickest = array_keys($arySpeed, min($arySpeed))[0];

	return($aryBSOptions[$intQuickest]);
}


// Look at all permutations from departure location, destination, and the appropriate set of waypoints
// For now, just looking at transit, wakling, driving. 
// Not thinking about more complicated routes (uber + transit, bikeshare + transit, etc)
function compareAlternatives($strGoogleApiKey, $strStartPlace, $strEndPlace) {
	// First, get the appropriate sets of bike share locations
	$aryCoordinatesStart = convertPlaceToCoordinates($strGoogleApiKey, $strStartPlace);
	$aryCoordinatesEnd = convertPlaceToCoordinates($strGoogleApiKey, $strEndPlace);

	$objHubway = getHubwayData();

	$aryOptions = array();


	//var_dump($aryBSOptions);
	//return($aryOptions);
	// Then calculate the direct walking, driving, and transit routes.
	$aryModes = array("walking","driving","transit");
	$aryTimes = array();
	foreach($aryModes as $strMode) {
		$intTime = calculateTravelTime($strGoogleApiKey, $aryCoordinatesStart, $aryCoordinatesEnd, $strMode);
		$aryTimes[$strMode] = array(
			"total_time" => $intTime,
			"waypoints" => array(
				array(
					"start" => $aryCoordinatesStart,
					"end" => $aryCoordinatesEnd,
					"mode" => $strMode)));
	}
	$aryOptions = array_merge($aryOptions, $aryTimes);
	return($aryOptions);

}

// Realistically, could move a lot of this to JS.
?>