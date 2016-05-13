<?php
require_once('functions.php');
error_reporting(E_ALL);

$strGoogleApiKey = "AIzaSyCQODAY6twd-kCYbCc8qOP7PqdBNc1mOmU";
$strPlaceIdStart = "ChIJod-VN2h344kRK6Xf_xVVwg4";
$strPlaceIdEnd = "ChIJn9mkAb1w44kR0ptXzlVG5iI";

$strAddressStart = "1306 Massachusetts Ave, Cambridge, MA 02138, USA";
$strAddressEnd = "100 Cambridgeside Pl, Cambridge, MA 02141, United States";

print_r(compareRoutes($strGoogleApiKey, $strAddressStart, $strAddressEnd));

print_r(getBikeShareRoute($strGoogleApiKey, $strPlaceIdStart, $strPlaceIdEnd));


?>