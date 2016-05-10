<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- <link rel="icon" href="../../favicon.ico"> -->

    <title>Hubway Map</title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/starter-template.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    
    <script>
      function updateMap(strUrl) {
        $("#mapframe").attr("src", strUrl);
        return false;
      }
    </script>
  </head>

  <body>

  	<div class="container">
	  	<div class="row">
	    <div class="col-md-4 col-md-offset-4">
        <h1>Hubway Mapper</h1>

          <?php
          require_once('functions.php');
          
          $objHubway = getHubwayData();
          $strGoogleApiKey = "AIzaSyCQODAY6twd-kCYbCc8qOP7PqdBNc1mOmU";

          $aryStart = convertAddressToCoordinates($strGoogleApiKey, $_REQUEST["starting"]);
          $aryEnd = convertAddressToCoordinates($strGoogleApiKey, $_REQUEST['ending']);

          $aryRoutes = compareRoutes($strGoogleApiKey, $_REQUEST['starting'], $_REQUEST['ending']);

          generateMaps($strGoogleApiKey, $aryRoutes);
          $strTable = "<table class='table'>" .
                      "<tr>
                        <th>Mode</th>
                        <th>Time</th>
                      </tr>";
          foreach($aryRoutes as $strMode=>$aryDetails) {
            $strTable .=  "<tr>" . 
                              "<td><a href='#' onclick=updateMap('".$aryDetails['url']."')>" . $strMode . "</a></td>" . 
                              "<td>" . ($aryDetails['total_time'] >= 60*60 ? gmdate("H:i:s", $aryDetails['total_time']) : gmdate("i:s", $aryDetails['total_time'])) . "</td>" . 
                          "</tr>";
          }
          $strTable .= "</table>";
          $strBaseMap =   "https://www.google.com/maps/embed/v1/view?" . 
                          "key=" . $strGoogleApiKey . "&" . 
                          "center=42.3728137,-71.1175233" . "&" . // Could do this in js too -- make it the current location.
                          "zoom=16";

          echo($strTable);


          ?>
          <iframe id="mapframe" width="100%" height="600" style = "border:0" src=<?php echo "'".$strBaseMap."'";?></iframe>

		  </div> <!-- /.col -->
		</div><!-- /.row-->
	</div><!-- /.container -->

  </body>
</html>
