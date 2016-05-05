<?php
header('Access-Control-Allow-Origin: *');

$connection = new mysqli("localhost", "root", "notsecurepassword", "xdbl0zz14_biodiversity");
$query="SELECT photo_id FROM photostate
		WHERE state='unresolvable'";
$result=$connection->query($query);

if($result->num_rows>0){
	$arrayOfPhotos=[];
	while($row = $result->fetch_assoc()){
		$pId=$row["photo_id"];
		$query="SELECT filename, site_id, person_id FROM photo
				WHERE photo_id=$pId";
		$photoQueryResult=$connection->query($query);
		if($photoQueryResult->num_rows>0){
			$photoData=$photoQueryResult->fetch_assoc();
			$siteId=$photoData["site_id"];
			$personId=$photoData["person_id"];
			$filename=$photoData["filename"];
			$photoUrl="<img src='http:\\\\www.mammalweb.org\\biodivimages\\person_$personId\\site_$siteId\\$filename' height='400' width='600'>";
			$photoIdImagePair=[$pId, $photoUrl];
			$arrayOfPhotos[]=$photoIdImagePair;
		}
	}
	$jsonString=json_encode($arrayOfPhotos);
	echo("$jsonString");
}
?>