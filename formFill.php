<?php
	header('Access-Control-Allow-Origin: *');

	$servername = "localhost";
	$username = "root";
	$password = "notsecurepassword";

	// connect to database
	$conn = new mysqli($servername, $username, $password);

	$sql = "USE xdbl0zz14_biodiversity";

	$conn->query($sql);

	$request = $_GET['request'];

	if ($request == 'species' || $request == 'species_site') {
		$sql = "SELECT option_id, option_name FROM options WHERE struc = 'mammal' OR struc = 'bird' OR struc='notinlist' OR struc='noanimal'";
	} elseif ($request == 'trapper' || $request == 'trapper_site') {
		$sql = "SELECT DISTINCT person_id AS option_id, person_id AS option_name FROM photo";
	} elseif ($request == 'gender') {
		$sql = "SELECT option_id, option_name FROM options WHERE struc = 'gender'";
	} elseif ($request == 'age') {
		$sql = "SELECT option_id, option_name FROM options WHERE struc = 'age'";
	} elseif ($request == 'site' || $request == 'area') {
		$sql = "SELECT site_id AS option_id, site_name AS option_name FROM site";
	}elseif ($request == 'habitat' || $request == 'habitat_site'){
		$sql = "SELECT option_id, option_name FROM options WHERE struc = 'habitat'";
	}

	$result = $conn->query($sql);

	$species = array();

	if ($result->num_rows > 0) {
		// output data of each row
		while($row = $result->fetch_assoc()) {
			array_push($species, array("option_id"=>$row["option_id"],"option_name"=>$row["option_name"]));
		}
	}

	$json = json_encode($species);

	echo $json;
?>