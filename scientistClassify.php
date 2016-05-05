<?php

$proceed = true;
if (isset($_GET['species'])){
	$speciesArray = [];
	foreach ($_GET['species'] as $key=>$value){
		array_push($speciesArray, $value);
	}
}
else{
	$proceed = false;
}
if (isset($_GET['gender'])){
	$genderArray = [];
	foreach ($_GET['gender'] as $key=>$value){
		array_push($genderArray, $value);
	}
}
else{
	$proceed = false;
}
if (isset($_GET['age'])){
	$ageArray = [];
	foreach ($_GET['age'] as $key=>$value){
		array_push($ageArray, $value);
	}
}
else{
	$proceed = false;
}

//can assume that species, age and gender arrays will always have same length due to checking in fr4.html
if (isset($_GET['photo_id']) && $proceed){
	
	//if there are multiple identical entries (i.e. the species, gender and age of two or more of the classifications
	//are the same), remove all but one of these entries, and increase the number of individuals value appropriately.
	$numArray = [];
	$toDelete = [];
	$arraySize = count($speciesArray);
	for ($i=0; $i<$arraySize; $i++){
		$numArray[$i] = 1;
		for ($j=$i+1; $j<$arraySize; $j++){
			if (($speciesArray[$i] == $speciesArray[$j]) && ($genderArray[$i] == $genderArray[$j]) && ($ageArray[$i] == $ageArray[$j])){
			//therefore the same classification has been made, so increase the classification number.
				$numArray[$i] += 1;
				//mark this entry for deletion
				array_push($toDelete, $j);
			}
		}
	}
	//delete all classification entries that have been marked for deletion.
	for ($i=0; $i<count($toDelete); $i++){
		$value = $toDelete[$i];
		//use array splice to normalize integer keys.
		array_splice($speciesArray, $value, 1);
		array_splice($genderArray, $value, 1);
		array_splice($ageArray, $value, 1);
		array_splice($numArray, $value, 1);
	}

	$pId = $_GET['photo_id'];
	$numAnimals = count($speciesArray);
	// connect to database
		
	//make it so mysql throws errors
	mysqli_report(MYSQLI_REPORT_STRICT);
	
	$connection = new mysqli("localhost", "root", "notsecurepassword", "xdbl0zz14_biodiversity");
	try{	
		for ($i = 0; $i<$numAnimals; $i++){
			$speciesId = $speciesArray[$i];	
			$numClass = $numArray[$i];
			//insert the newly classified photo into xclassification
			$query="INSERT INTO xclassification (photo_id,species,number_of_individuals)
					VALUES($pId,$speciesId, $numClass)";
			echo "<br>".$query;
			$connection->query($query);
			
			//insert the gender and age classification into animal
			$gender = $genderArray[$i];
			$age = $ageArray[$i];
			$timestamp = "CURRENT_TIMESTAMP"; //database manipulates CURRENT_TIMESTAMP itself.
			//animal_id is auto increment so do not need to specify it
			//person_id is arbitrarily set to 999, however this will need to be modified depending on 
			//the person_id of the scientist using the system at the time.
			$query="INSERT INTO animal (photo_id, person_id, species, gender, age, number, timestamp)
					VALUES ($pId, 999, $speciesId, $gender, $age, $numClass, $timestamp)";
					echo $query;
			$connection->query($query);
			
			//if this is the first species in the photo to be classified, then set photo in photostate to classified too.
			if ($i == 0){
				//set the photo in photostate that was previously unresolvable to classified.
				$query="UPDATE photostate
						SET state='classified'
						WHERE photo_id=$pId";
				$connection->query($query);
			}
		}
	}
	catch (Exception $e){
		echo "The database could not be accessed.";
	}
	
}
?>