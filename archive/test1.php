<?php
require_once('functions.php');
error_reporting(E_ALL);


$strGoogleApiKey = "AIzaSyCQODAY6twd-kCYbCc8qOP7PqdBNc1mOmU";
print(json_encode(getBikeShareRoute($strGoogleApiKey, "1306 Mass Ave., Cambridge, MA", "6 Marie Ave, Cambridge, MA 02139")));


?>