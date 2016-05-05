

<?php
	$photoUrl = array();
	$photoids = array();
	//make it so mysql throws errors
	mysqli_report(MYSQLI_REPORT_STRICT);
	
	try{
		// connect to database
		$conn = new mysqli("localhost", "root", "notsecurepassword", "Xdbl0zz14_biodiversity");
		
		//get the get requests into a usable format, which is more complex than it should be,
		//since there can be multiple GET values for any given key.
		//Therefore manipulate the url and manually extract the GET values.
		$url = $_SERVER['REQUEST_URI'];
		$url = str_replace("/mammalwebDemo", "", $url);
		$url = str_replace("/queryuser.php?", "", $url);
		//split the string around "&"
		$urlArray = explode("&", $url);
		if (count($urlArray) > 1){ //therefore some query has been made.
			foreach($urlArray as $value){
				//split each entry around "="
				$tempArray = explode("=", $value);
				//if to the right of the equals there is something:
				if ($tempArray[1] != ""){
					//what was to the left of the "=" becomes the key, to the right, the value.
					if (array_key_exists($tempArray[0], $urlArray)){
						//add the value to the existing array value associated with this key
						array_push($urlArray[$tempArray[0]], $tempArray[1]);
					}
					else{
						$key = $tempArray[0];
						$urlArray[$key] = array($tempArray[1]);
					}
				}
			}
			 
			$getArray = array();
			$selectString;
			if (isset($_GET['filterImages'])){
					$urlArray['selectParameters'] = array("site_id", "filename", "person_id", "photo_id");
					$selectString = "photo_id, filename, site_id, person_id";
				if (!empty($_GET['species'])){	
					$string = getString("species", $urlArray['species']);
					$getArray['species'] = "(photo_id IN (SELECT photo_id 
														FROM xclassification 
														WHERE $string))";
				}
				if (!empty($_GET['trapper'])){
					$string = getString("person_id", $urlArray['trapper']);
					$getArray['trapper'] = "($string)";
				}
				if (!empty($_GET['age'])){
					$string = getString("age", $urlArray['age']);
					$getArray['age'] = "(photo_id IN (SELECT photo_id
													FROM animal
													WHERE $string))";
				}
				if (!empty($_GET['gender'])){
					$string = getString("gender", $urlArray['gender']);
					$getArray['gender'] = "photo_id IN (SELECT photo_id
														FROM animal
														WHERE $string)";
				}
				if (!empty($_GET['site'])){
					$string = getString("site_id", $urlArray['site']);
					$getArray['site'] = "($string)";
				}
				if (!empty($_GET['image_sequence_number'])){
					$string = getString("sequence_num", $urlArray['image_sequence_number']);
					$getArray['sequenceNum'] = "($string)";
				}
				if (!empty($_GET['habitat'])){
					$string = getString("habitat_id", $urlArray['habitat']);
					$getArray['habitat'] = "(site_id in (SELECT site_id
															FROM site
															WHERE $string))";
				}
				if (!empty($_GET['human_present'])){
					if (count($urlArray['human_present']) == 1){
						//just true
						if ($_GET['human_present'] == "true"){
								$getArray['humanPresent'] = "(photo_id IN (SELECT photo_id
																		FROM xclassification
																		WHERE species = 87))";				
						}
						//just false
						else{
							$getArray['humanPresent'] = "(photo_id IN (SELECT photo_id
																		FROM xclassification
																		WHERE species != 87))";
						}
					}
					//if both true and false, or neither, then do nothing as this accounts for all cases.
				}
				if (!empty($_GET['animal_present'])){
					if (count($urlArray['animal_present']) == 1){
						if ($_GET['animal_present'] == "true"){
							$getArray['animalPresent'] = "(photo_id IN (SELECT photo_id
																		FROM xclassification
																		WHERE species != 87 OR 86))";					
						}
						else{
							$getArray['animalPresent'] = "(photo_id IN (SELECT photo_id
																		FROM xclassification
																		WHERE species = 87 OR 86))";
						}
					}
				}
				if (!empty($_GET['number_of_classifications'])){
					$string = getString("",$urlArray['number_of_classifications']);
					$getArray['numberClassifications'] = "($string = (SELECT COUNT(photo_id)
																	FROM animal
																	WHERE animal.photo_id = photo.photo_id))";
				}
				if (!empty($_GET['decided_or_unresolvable'])){
					$string = getString("state", $urlArray['decided_or_unresolvable']);
					$getArray['decided_or_unresolvable'] = "((photo_id IN (SELECT photo_id
																		FROM photostate
																		WHERE classified)))";
				}
				//assume if startDay is given, then all times are given:
				if (!empty($_GET['time_period_start_day'])){
					$startDay = $_GET['time_period_start_day'];
					$startMonth = $_GET['time_period_start_month'];
					$startYear = $_GET['time_period_start_year'];
					$startSecond = $_GET['time_period_start_second'];
					$startMinute = $_GET['time_period_start_minute'];
					$startHour = $_GET['time_period_start_hour'];
					$endDay = $_GET['time_period_end_day'];
					$endMonth = $_GET['time_period_end_month'];
					$endYear = $_GET['time_period_end_year'];
					$endSecond = $_GET['time_period_end_second'];
					$endMinute = $_GET['time_period_end_minute'];
					$endHour = $_GET['time_period_end_hour'];
					$startTime = $startYear . "/" . $startMonth . "/" . $startDay ." " . $startHour .":". $startMinute . ":" . $startSecond;
					$endTime = $endYear . "/" . $endMonth . "/" . $endDay ." " . $endHour .":". $endMinute . ":" . $endSecond;
					
					//remove the "'" so that they do not interfere with the query syntax
					$startTime = str_replace("'", "", $startTime);
					$endTime = str_replace("'", "", $endTime);
					$getArray['taken'] = "(taken between '$startTime' AND '$endTime')";
				}
				
				$photourls;

				$sql = "SELECT $selectString FROM photo WHERE ".array_shift($getArray);
				foreach($getArray as $value){
					$sql = $sql." AND ".$value;
				}
				$result = $conn->query($sql);
				if ($result->num_rows > 0) {
					$string = array_shift($urlArray['selectParameters']);
					// output data of each row
					while($row = $result->fetch_assoc()) {
						$echoString = $string . ": " . $row[$string];
							$personid =  $row['person_id'];
							$siteid = $row['site_id'];
							$filename = $row['filename'];
							$photoid = $row['photo_id'];

							$img = "http:\\\\www.mammalweb.org\\biodivimages\\person_$personid\\site_$siteid\\$filename";
							$photoids[]=$photoid;
							$photoUrl[]=$img;
					}
				}
			}
		}
		json_encode($photoUrl);
		json_encode($photoids);
		$conn->close();
	}
	catch (Exception $e){		
		echo "The database could not be accessed.";
	}
	
	function getString($choice, $array){
		if($choice==""){
			$string=array_shift($array);
			foreach($array as $value){
				$string= $string." OR ".$value;
			}
			return $string;
		}else{
			//put first element of $array at beginning of string
			$string = $choice . "=" . array_shift($array);
			foreach($array as $value){
				$string = $string . " OR " . $choice . "=" . $value;
			}
			return $string;
		}
	}
?>

<html>
	<head>
		<!-- Semantic-UI and JQuery Import -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.0/jquery.js"></script>
	    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">    
	    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
	    <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.1.8/semantic.js"></script>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.1.8/semantic.css"/>
		<link href='https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300,300italic,700' rel='stylesheet' type='text/css'>


		<title>Scientist Dashboard</title>
	</head>

		<body style="background:Cornsilk;">

<nav class="navbar navbar-inverse">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span> 
      </button>
      <a class="navbar-brand" href="home.html"><img src="MammalWeb.png" height="35" width="130"> </img></a>
    </div>
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
        <li><a href="home.html"><img src="home.png" height="35" width="70"> </img></a></li>
        <li class="dropdown">
          <a href="SciDash.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><img src="Scifilter.png" height="35" width="70"> </img> <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li class="active"><a href="index.html">Filter for Scientists</a></li>
            <li><a href="SciDash.php">Data Hub</a></li>
            <li><a href="fr4.html">Unresolved Classification</a></li>
            <li><a href="classifyData.html">Scientist Classification settings</a></li>
            <li role="separator" class="divider"></li>
          </ul>
        <li><a href="user.html"><img src="user.png" height="35" width="70"> </img></a></li> </a></li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li><a href="#"><span class="glyphicon glyphicon-user"></span> Sign Up</a></li>
        <li><a href="#"><span class="glyphicon glyphicon-log-in"></span> Login</a></li>
      </ul>
    </div>
  </div>
</nav>
		<br>


    <div class="bs-example">
    <div id="carousel-example-captions" class="carousel slide" data-ride="carousel" style="width: 600px; margin: 0 auto">
        <div class="carousel-inner">
<?php
    $counter = 1;
    $i=0;
    foreach($photoUrl as $row){
?>
            <div class="item<?php if($counter <= 1){echo " active"; } ?>">
                <a href="">
                    <img data-src="holder.js/900x800/auto/#777:#777" src="<?php echo $row ?>"/>
                          <div class="carousel-caption">
        					<p><font color="blue" size="6"><?php echo $photoids[$i] ?></font></p>
      </div>
                </a>
            </div>
<?php
    $i++;
    $counter++;
    }
?>
    <!-- Carousel controls -->
    <a class="carousel-control left" href="#carousel-example-captions" data-slide="prev">
        <span class="glyphicon glyphicon-chevron-left"></span>
    </a>
    <a class="carousel-control right" href="#carousel-example-captions" data-slide="next">
        <span class="glyphicon glyphicon-chevron-right"></span>
    </a>
        </div>
	</div>
</div>
</div>
		<br>
    <div class="container">
        <div class="row">
        <div class="col-md-12" align="center">
			<button type="button" class="btn btn-success btn-md" onclick="window.location.href='user.html'">Click to return to Carousel Filter</button>
		</div>
		</div>
		</div>
</body>
</html>
