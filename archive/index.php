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

  </head>

  <body>

  	<div class="container">
	  	<div class="row">
	    <div class="col-md-4 col-md-offset-4">

	        <h1>Hubway Mapper</h1>

		      <form role="form" class="form" action="results.php" method="post">
		        <div class="form-group">
		        	<input type="text" name="starting" class="form-control" placeholder="Starting" required autofocus>
		    	</div>
		    	<div class="form-group">
			        <input type="text" name="ending" class="form-control" placeholder="Ending" required>
			    </div>
		        <button class="btn btn-lg btn-primary btn-block" type="submit">Get Directions</button>
		      </form>

		  </div> <!-- /.col -->
		</div><!-- /.row-->
	</div><!-- /.container -->

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>
