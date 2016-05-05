<?php
	
	mysqli_report(MYSQLI_REPORT_STRICT);
	try{
		$servername = "localhost";
		$username = "root";
		$password = "notsecurepassword";

		// connect to database
		$conn = new mysqli($servername, $username, $password);

		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}

		// select database
		$sql = "USE Xdbl0zz14_biodiversity";
		
		//get the get requests into a usable format, which is more complex than it should be,
		//since there can be multiple GET values for any given key.
		//Therefore manipulate the url and manually extract the GET values.
		$url = $_SERVER['REQUEST_URI'];
		//remove the file location of query.php from the url
		$url = str_replace("/mammalwebDemo", "", $url);
		$url = str_replace("/query.php?", "", $url);
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
				if (!empty($_GET['selectParameters'])){
					$selectString = array_shift($urlArray['selectParameters']);
					//save this string so that it can be added back into the array;
					$startString = $selectString;
					foreach($urlArray['selectParameters'] as $value){
						$selectString = $selectString . ", " . $value;
					}
					array_unshift($urlArray['selectParameters'], $startString);
				}
				else{
					$urlArray['selectParameters'] = array("photo_id", "filename");
					$selectString = "photo_id, filename";
				}
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
																			WHERE '$string')))";
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
				
				/*
				$gender = $getArray['gender']; //to do
				$age = $getArray['age']; //to do*/
				
				if ($conn->query($sql) == TRUE) {
					echo "Database selected<br>";
					
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
							foreach($urlArray['selectParameters'] as $value){
								$echoString = $echoString . " - " . $value . ": " . $row[$value];
							}
							echo $echoString."<br>";
						}
						
						//with each filtering, a csv file will automatically be generated on the server 
						//that the scientist can download for themselves. 
						//TO DO: after download made / new filter query made / page is exited, the file needs to be deleted
						//so there are not an endless creation of csv files.
						$f = fopen("csvFile.csv", "w");
						//write data to file
						foreach($result as $row){
							fputcsv($f, $row);
						}
						//close file.
						fclose($f);
					}
					
				} else {
					echo "Error selecting database: " . $conn->error;
				}
			}
			else if(isset($_GET['filterSites'])){
				if (!empty($_GET['selectParameters'])){
					$selectString = array_shift($urlArray['selectParameters']);
					//save this string so that it can be added back into the array;
					$startString = $selectString;
					foreach($urlArray['selectParameters'] as $value){
						$selectString = $selectString . ", " . $value;
					}
					array_unshift($urlArray['selectParameters'], $startString);
				}
				else{
					$urlArray['selectParameters'] = array("site_id", "site_name");
					$selectString = "site_id, site_name";
				}
				if (!empty($_GET['area'])){
					$string = getString("site_id", $urlArray['area']);
					$getArray['area'] = "($string)";
				}
				if (!empty($_GET['trapper_site'])){
					$string = getString("person_id", $urlArray['trapper_site']);
					$getArray['trapper'] = "($string)";
				}
				if (!empty($_GET['species_site'])){
					$string = getString("species", $urlArray['species_site']);
					$getArray['species'] = "(site_id IN (SELECT site_id 
														FROM photo 
														WHERE photo_id IN (SELECT photo_id 
																			FROM xclassification 
																			WHERE $string)))";
					/*echo("(site_id IN (SELECT site_id 
														FROM photo 
														WHERE photo_id IN (SELECT photo_id 
																			FROM xclassification 
																			WHERE $string)))");*/
				}
				if (!empty($_GET['number_of_photos'])){
					$string = getString("", $urlArray['number_of_photos']);
					$getArray['photoNumber'] = "($string = (SELECT COUNT(photo_id)
																		FROM photo
																		WHERE photo.site_id = site.site_id))";
				}
				if (!empty($_GET['number_of_sequences'])){
					$string = getString("", $urlArray['number_of_sequences']);
					$getArray['sequenceNumber'] = "($string = (SELECT COUNT(DISTINCT sequence_num)
																			FROM photo
																			WHERE photo.site_id = site.site_id))";
				}
				if (!empty($_GET['time_period_start_day_site'])){
					$startDay = $_GET['time_period_start_day_site'];
					$startMonth = $_GET['time_period_start_month_site'];
					$startYear = $_GET['time_period_start_year_site'];
					$startSecond = $_GET['time_period_start_second_site'];
					$startMinute = $_GET['time_period_start_minute_site'];
					$startHour = $_GET['time_period_start_hour_site'];
					$endDay = $_GET['time_period_end_day_site'];
					$endMonth = $_GET['time_period_end_month_site'];
					$endYear = $_GET['time_period_end_year_site'];
					$endSecond = $_GET['time_period_end_second_site'];
					$endMinute = $_GET['time_period_end_minute_site'];
					$endHour = $_GET['time_period_end_hour_site'];
					$startTime = $startYear . "/" . $startMonth . "/" . $startDay ." " . $startHour .":". $startMinute . ":" . $startSecond;
					$endTime = $endYear . "/" . $endMonth . "/" . $endDay ." " . $endHour .":". $endMinute . ":" . $endSecond;
					
					//remove the "'" so that they do not interfere with the query syntax
					$startTime = str_replace("'", "", $startTime);
					$endTime = str_replace("'", "", $endTime);
					
					//at least one photo that was taken in the site was taken between the start and end time:
					$getArray['taken'] = "(SELECT site_id 
										FROM site
										WHERE (SELECT photo_id 
												FROM photo 
												WHERE photo.site_id = site.site_id) EXISTS (SELECT 1 
																							FROM photo 
																							WHERE taken '$startTime' AND '$endTime'))";
					echo $getArray['taken'];
				}
				if (!empty($_GET['habitat_site'])){
					$getArray['habitat'] = "(habitat_id = $_GET[habitat_site])";
				}
				if (!empty($_GET['human_present_site'])){
					if (count($urlArray['human_present_site']) == 1){
						if ($_GET['human_present_site'] == "true"){
							$getArray['humanPresent'] = "(site_id IN (SELECT DISTINCT site_id
																	FROM photo
																	WHERE photo_id IN (SELECT photo_id
																						FROM xclassification
																						WHERE species = 87)))";
						}
						else{
							$getArray['humanPresent'] = "(site_id IN (SELECT DISTINCT site_id
																	FROM photo
																	WHERE photo_id IN (SELECT photo_id
																						FROM xclassification
																						WHERE species != 87)))";
						}
					}
				}
				
				if ($conn->query($sql) == TRUE) {			
					echo "Database selected<br>";
					$sql = "SELECT $selectString FROM site WHERE ".array_shift($getArray);
					foreach($getArray as $value){
						$sql = $sql." AND ".$value;
					}		
					$result = $conn->query($sql);
					if ($result->num_rows > 0) {
						$string = array_shift($urlArray['selectParameters']);
						//output data of each row
						while($row = $result->fetch_assoc()) {
							$echoString = $string . ": " . $row[$string];
							foreach($urlArray['selectParameters'] as $value){
								$echoString = $echoString . " - " . $value . ": " . $row[$value];
							}
							echo $echoString."<br>";
						}
						
						//with each filtering, a csv file will automatically be generated on the server 
						//that the scientist can download for themselves. 
						//TO DO: after download made / new filter query made / page is exitted, the file needs to be deleted
						//so there are not an endless creation of csv files.
						$f = fopen("csvFile.csv", "w");
						//write data to file
						foreach($result as $row){
							fputcsv($f, $row);
						}
						//close file.
						fclose($f);
					}
					
				} else {
					echo "Error selecting database: " . $conn->error;
				}
			}
			
			$conn->close();
	}
	}
	catch(Exception $e){
		echo("The database could not be accessed");
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.0/jquery.js"></script>
<br>
<form action="csvFile.csv">
	<button type="submit" > Click to download as csv file </button>
</form>
<button type="submit" onclick="window.location.href='index.html'"> Click to return to the scientist dashboard </button>