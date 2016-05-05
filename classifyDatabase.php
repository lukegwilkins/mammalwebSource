<?php
//we import the classifyImage function from classifyImage.php
require "classifyImage.php";
//this header lets people send Get requests
header('Access-Control-Allow-Origin: *');

//default values for evenness, minimum votes and maximum votes
$evenThresh=0.5;
$minVotes=5;
$maxVotes=10;
$classifyAll="false";
$circulationNum=200;
$validInput=true;

//if evenness was sent and it was a valid number
//to the value given in the get request
if(isset($_GET["evenness"])){
	
	//check if the evenness isn't an empty string
	if($_GET["evenness"]!=""){
		
		//check that evenness is a number
		if(is_numeric($_GET["evenness"])){
			
			$evenThresh=floatval($_GET["evenness"]);
			//check that the evenness is between 0 and 1
			if(!($evenThresh>=0 and $evenThresh<=1)){
				echo("The evenness threshold must be a number between 0 and 1<br>");
				$validInput=false;
			}
			
		}
		else{
			echo("The evenness threshold must be a number between 0 and 1<br>");
			$validInput=false;
		}
	}
}

//same thing with the minimum vote
if(isset($_GET["minV"])){
	
	if($_GET["minV"]!=""){
		
		//check that the minimum vote is an integer
		if(ctype_digit($_GET["minV"])){
			
			//check that the minVotes is greater than 1
			$minVotes=intval($_GET["minV"]);
			if($minVotes<1){
				echo("The minimum number of votes must be a number greater than 0<br>");
				$validInput=false;
			}
		}
		else{
			echo("The minimum number of votes must be a number greater than 0<br>");
			$validInput=false;
		}
	}
}

//same thing with the maximum vote
if(isset($_GET["maxV"])){
	
	if($_GET["maxV"]!=""){
		
		if(ctype_digit($_GET["maxV"])){
			
			//check that the maximum number of votes is greater than the min votes
			//and the maxvotes is greater than 0
			$maxVotes=intval($_GET["maxV"]);
			if($maxVotes<$minVotes or $maxVotes<0){
				echo("The maximum number of votes must be a number, that is not less than the minimum number of votes and is greater than 0<br>");
				$validInput=false;
			}
		}
		else{
			echo("The maximum number of votes must be a number, that is not less than the minimum number of votes<br>");
			$validInput=false;
		}
	}
}

//get whether to use whole database or not
if(isset($_GET["classifyAll"])){
	if($_GET["classifyAll"]="true"){
		$classifyAll="true";
	}
}

//get the number of photos that should be in circulation
if(isset($_GET["circNum"])){
	
	if($_GET["circNum"]!=""){
		
		//check it is an integer
		if(ctype_digit($_GET["circNum"])){
			
			//check that is greater than 1
			$circulationNum=intval($_GET["circNum"]);
			if($circulationNum<1){
				echo("At least 1 photo must in circulation<br>");
				$validInput=false;
			}
		}
		else{
			echo("At least 1 photo must in circulation<br>");
			$validInput=false;
		}
		
	}
}

//make it so mysql throws errors
mysqli_report(MYSQLI_REPORT_STRICT);

try{
	// set up a connection to the database
	$connection = new mysqli("localhost", "root", "notsecurepassword", "xdbl0zz14_biodiversity");

	try{
		//find any new photos and add them to the photostate table
		discoverNewPhotos($connection);

		try{
			if($validInput){
				//we get the ids of unclassified photos and new photos
				//if $classifyall="true"
				$photoIds=getPhotoIds($connection,$classifyAll);
				
				//we run the algorithm on the images
				runImageClassifications($connection,$photoIds,$evenThresh,$minVotes,$maxVotes);

				//we update it so that the correct number of photos are in circulation
				updateCirculationPhotos($connection,$circulationNum);
			}
		}
		//if an error occured while running the algorithm we output a message
		catch(Exception $e){
			echo("An error occured while running the algorithm");
		}
		
	}
	//if an error occurred while finding new photos we send back an error message
	catch(Exception $e){
		echo("An error occur while trying to identify any new images");
	}
}
//if an error occurred while connecting to the database then an error
//message is sent back
catch(Exception $e){
	echo("Couldn't access the database");
}



//function to discover any new photos that have been added to the database
function discoverNewPhotos($connection){
	//query to get the new photo ids
	$query="SELECT photo_id FROM photo p
		WHERE p.photo_id NOT IN(
			SELECT photo_id FROM photostate
		)";
	$result = $connection->query($query);
	//if we get any photo ids back then we insert  them into the photostate table
	//with state new
	if ($result->num_rows > 0){
		//we get each photoid for the new photos
		while($row = $result->fetch_assoc()){
			$number=$row["photo_id"];
			//we create the sql query to update the database
			$query="INSERT INTO photostate
				(photo_id, state)
				VALUES
				($number,'new')";
			//we run the query
			$insertResult = $connection->query($query);
			
		}
		//we echo how many new photos we have discovered
		$numberOfNewPhotos=$result->num_rows;
		echo("Discovered $numberOfNewPhotos new photos, the database has been updated accordingly<br>");
	}
}

//function to get the list of photo_ids that will be classified
function getPhotoIds($connection,$classifyAll){
	
	//if $classifyAll=="true" we get both the 'new' images
	//and the images in circulation
	if($classifyAll=="true"){
		$query="SELECT photo_id FROM photostate
				WHERE state='new' OR state='in_circulation'";
	}
	//otherwise we just get the photos in circulation
	else{
		$query="SELECT photo_id FROM photostate
				WHERE state='in_circulation'";
	}
	
	//we create an array to store the results
	$photoIds=array();
	
	//we get the results of the query
	$result = $connection->query($query);
	
	//if we got any photos we add their ids to the array
	if ($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$number=$row["photo_id"];
			$photoIds[]=$number;
		}
	}
	
	//we return the photoids
	return $photoIds;
}

//function to classify the images
function runImageClassifications($connection,$photoIds,$evenThresh,$minVotes,$maxVotes){
	//set up variables to keep track of how many images were classified, determined to be unresolvable
	//and how many required more classifications
	$classifiedCount=0;
	$unresolvableCount=0;
	$noChangeCount=0;
	
	//we loop through all the photos
	foreach($photoIds as $pId){
		
		//we run classifyImage on the photo
		$classification=classifyImage($connection,$pId, $minVotes, $maxVotes, $evenThresh);
		
		//if no data was found for the image we store the number of valid classifications in the database and increase noChangeCount
		if ($classification["result"]=="no data found for photo $pId"){
			$noChangeCount+=1;
			$query="UPDATE photostate
					SET valid_classifications=".$classification["voteCount"]." 
					WHERE photo_id=$pId";
			$connection->query($query);
		}
		
		//if their aren't enough classifications we store the number of valid classifications in the database and increase noChangeCount
		elseif($classification["result"]=="not enough classifications"){
			$noChangeCount+=1;
			$query="UPDATE photostate
					SET valid_classifications=".$classification["voteCount"]." 
					WHERE photo_id=$pId";
			$connection->query($query);
		}
		
		//if the image is unresolvable we update the database so that, that image has state "unresolvable" and store the number of valid classifications
		//we also increase unresolvabledCount
		elseif ($classification["result"]=="unresolvable"){
			$unresolvableCount+=1;
			$query="UPDATE photostate
					SET state='unresolvable',valid_classifications=".$classification["voteCount"]." 
					WHERE photo_id=$pId";
			$connection->query($query);
		}
		
		//if the classification was success we increase the classifiedCount and insert the classifications into the database
		elseif ($classification["result"]=="success"){
			$classifiedCount+=1;
			
			//we get the results of the classification
			$classificationResult=$classification["results"];
			
			//for each species in the result we add a new row into xclassification
			foreach($classificationResult as $species){
				//we get the speciesId
				$speciesId=$species[1];
				//we get the number of times that species occurs in the image
				$numberOfIndividuals=$species[0];
				
				//we create a query to insert the classification into the database
				$query="INSERT INTO xclassification
						(photo_id,species,number_of_individuals)
						VALUES($pId,$speciesId, $numberOfIndividuals)";
				//we then run the query
				$connection->query($query);
			}
			//we then update the photo in photostate, so that it has state classified and the evenness, fraction blank, fraction support
			//and amount of valid classifications are stored aswell
			$query="UPDATE photostate
					SET state='classified',evenness=".$classification["evenness"].",fraction_blank=".$classification["fractionBlank"].",fraction_support=".$classification["fractionSupp"]."
					,valid_classifications=".$classification["voteCount"]."
					WHERE photo_id=$pId";
			//the classification is run
			$connection->query($query);
		}
		
	}
	//we echo out how many photos were classified, how many were unresolvable and how many were left alone
	echo("Classified $classifiedCount <br>");
	echo("Found $unresolvableCount images that were unresolvable <br>");
	echo("$noChangeCount images require more classifications <br>");
}

//function to update how many phohots are in circulations
function updateCirculationPhotos($connection,$circulationNum){
	//we get how many photos are in circulation
	$query="SELECT COUNT(photo_id) AS AmountOfPhotosInCirc FROM photostate
			WHERE state='in_circulation'";
	$result=$connection->query($query);
	$row = $result->fetch_assoc();
	$numInCirc=intval($row["AmountOfPhotosInCirc"]);
	
	//if there are less photos in circulation than there should be then add the appropriate number of photos to the circulation
	if($numInCirc<$circulationNum){
		//we get the difference between the amount of photos in the circulation and the amount required
		$noOfPhotosNeededInCirc=$circulationNum-$numInCirc;
		//we then get the amount of photos, with state new, that we need from the databse
		$query="SELECT photo_id FROM photostate
				WHERE state='new' LIMIT $noOfPhotosNeededInCirc";
		$result=$connection->query($query);
		
		//we then update each photos state so it has state "in_circulation"
		while($row = $result->fetch_assoc()){
			$photoId=$row["photo_id"];
			$query="UPDATE photostate
					SET state='in_circulation'
					WHERE photo_id=$photoId";
			$connection->query($query);
		}
		//we echo how many photos we added to the database
		echo("Added $noOfPhotosNeededInCirc to the number of photos in circulation<br>");
	}
	
}
?>