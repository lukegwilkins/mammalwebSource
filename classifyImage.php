<?php
/*
The flow of this program is split into two scenarios:
	1) When the median number of species reported by people is equal to 1, the program considers each classification of species to be separate. It computes the median count of the top species by simply considering all counts in classifications belonging to that species.
	2) When the median number of species reported by volunteers is higher than 1, the program considers all different classifications made by 1 person to be that of a single species (speciesAsString is a concatenation of those into one). It computes the median count of the top species by considering only those counts in classification containing EXACTLY those species which are in speciesAsString.

Consider these three classifications and biased results of the algorithm:
	2x rabbit, 2x fox 			4x rabbit 				4x rabbit

	ad 1) RABBIT WINS: rabbit: 3 votes, fox: 1 vote, median number of rabbits: 4
	ad 2) RABBIT,FOX WINS: rabbit,fox: 1 vote, rabbit: 2 votes, median number of rabbit,fox: 2,2
*/

function classifyImage($connection, $photoId, $minVotes, $maxVotes, $evennessThreshold)
{
	// key: person_id
	// value: array (classification) of 	key: species
	//										value: count
	$peopleAndClassifications = array();

	// key: person_id
	// value: array of 		key: classificationID
	//						value: count
	$peopleAndUniqueClassifications = array();

	// array (vote) of 	key: {speciesAsString; species; counts}
	//					value: {concatenated species string; array of species; array of 	key: species
	//																						value: count}
	// each vote contains only one species (be it concatenated or normal)
	$votes = array();

	// key: speciesAsString
	// value: number of votes
	$speciesAndVotes = array();

	// key: speciesAsString
	// value: median count
	$topSpeciesMedianCounts = array();

	// the median of species reported (votes) per user
	$medianNumberOfSpecies = 0;

	// the number of different votes
	$differentSpeciesReported = 0;

	// the number of votes
	$allSpeciesReported = 0;

	$evenness = 0;

	// the aggragated answer
	$topSpecies = "";

	// save image data into $peopleAndClassifications array and check if it's relevant
	if (extractPhotoData($connection, $photoId, $peopleAndClassifications, $peopleAndUniqueClassifications))
	{
		// save the median number of species into $medianNumberOfSpecies variable
		countSpecies($peopleAndClassifications, $medianNumberOfSpecies);

		// save votes into $votes array
		createVotes($peopleAndClassifications, $medianNumberOfSpecies, $votes);

		$allSpeciesReported = count($votes);

		// don't bother going through the rest of the classification when not enough classifications are available
		if ($allSpeciesReported < $minVotes)
		{
			$classificationResult = array("result" => "not enough classifications",
										"voteCount" => $allSpeciesReported);
			return $classificationResult;
		}

		// save numbers of votes into $speciesAndVotes array and set $differentSpeciesReported and $allSpeciesReported variables
		countVotes($votes, $speciesAndVotes, $differentSpeciesReported, $allSpeciesReported);

		// save the top species into $topSpecies and save evenness into $evenness
		computeEvenness($speciesAndVotes, $differentSpeciesReported, $allSpeciesReported, $topSpecies, $evenness);

		// save the aggregated numbers of top species into $topSpeciesMedianCounts array
		countChosenIndividuals($votes, $topSpecies, $topSpeciesMedianCounts);

		return outputResults($evennessThreshold, $maxVotes, $allSpeciesReported, $evenness, $speciesAndVotes, $topSpecies, $topSpeciesMedianCounts);

	}
	// if no data was found in the database
	else
	{
		$classificationResult = array("result" => "no data found for photo $photoId",
									"voteCount" => 0);
		return $classificationResult;
	}
}

function extractPhotoData($connection, $photoId, &$peopleAndClassifications, &$peopleAndUniqueClassifications)
{
	// create a query
	$query = "SELECT * FROM animal WHERE photo_id = $photoId";

	// send the query to the database and save the result
	$result = $connection->query($query);

	// if at least one row matches the query
	if ($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc())
		{
			$classificationId = "" . $row["species"] . "," . $row["gender"] . "," . $row["age"];

			// check if this classification is only a like (<=> species 97)
			if (classificationIsLike($row))
			{
				// ignore this classification
				continue;
			}

			// check if the person who made this classification is to be ignored because of non-unique classifications
			// or because of classifying "nothing here" AND "something there" at the same time
			// and update $peopleAndClassifications and $peopleAndUniqueClassifications accordingly
			if (personIsIgnored($row, $classificationId, $peopleAndClassifications, $peopleAndUniqueClassifications))
			{
				// don't save any extra information about this person
				continue;
			}

			// person has provided only unique (if any) classifications so far, we can proceed
			// if this is the first row of this person for this photo
			if (!array_key_exists($row["person_id"], $peopleAndClassifications))
			{
				// for each person we store the species and the number of individuals of each species
				$peopleAndClassifications[$row["person_id"]] = array($row["species"] => $row["number"]);
			}

			// if this person has already spotted this species in this photo (and just classifies different gender or age of the same species)
			else if (array_key_exists($row["species"], $peopleAndClassifications[$row["person_id"]]))
			{
				// increase the number of individuals of this species
				$peopleAndClassifications[$row["person_id"]][$row["species"]] += $row["number"];
			}

			// if this person has already spotted different species in the photo and this is a new one
			else
			{
				// add new species-count pair under the same person
				$peopleAndClassifications[$row["person_id"]][$row["species"]] = $row["number"];
			}
		}

		// check if there are any valid votes in current data
		if (allVotesInvalid($peopleAndClassifications))
		{
			// no relevant votes found
			return False;
		}

		// votes found and processed
		return True;
	}
	else
	{
		// no relevant votes found
		return False;
	}
}

function countSpecies($peopleAndClassifications, &$medianNumberOfSpecies)
{
	// numbers of species reported by each user
	$numbersOfSpecies = array();

	foreach ($peopleAndClassifications as $person => $classification)
	{
		// skip classifications of ignored people ($classification == "INVALID" in those cases)
		if (!is_array($classification))
		{
			continue;
		}

		array_push($numbersOfSpecies, count($classification));
	}

	// get the median
	sort($numbersOfSpecies, SORT_NUMERIC);
	$middle = count($numbersOfSpecies) / 2;

	$medianNumberOfSpecies = $numbersOfSpecies[$middle];
}

function createVotes($peopleAndClassifications, $medianNumberOfSpecies, &$votes)
{
	foreach ($peopleAndClassifications as $person => $classification)
	{
		// person should be ignored because of non-unique classifications
		if ($classification == "INVALID")
		{
			continue;
		}

		// consider each species separately
		if ($medianNumberOfSpecies == 1)
		{
			foreach ($classification as $species => $count)
			{
				array_push($votes, array("speciesAsString" => $species, "species" => array($species), "counts" => array($species => $count)));
			}
		}
		// squeeze species from one person
		else
		{
			// if this person has classified more than one species in the photo
			if (count($classification) > 1)
			{
				$speciesList = array();
				$speciesAsString = "";

				// create an array of species
				foreach ($classification as $species => $count)
				{
					array_push($speciesList, $species);
				}

				// two classification with a different order of species are the same
				sort($speciesList, SORT_NUMERIC);

				// create a string of species
				foreach ($speciesList as $key => $species)
				{
					$speciesAsString = $speciesAsString . $species . ",";
				}

				array_push($votes, array("speciesAsString" => $speciesAsString, "species" => $speciesList, "counts" => $classification));
			}
			else
			{
				// there is only one element in $classification
				$species = key($classification);
				
				array_push($votes, array("speciesAsString" => $species, "species" => array($species), "counts" => $classification));
			}
		}
	}
}

function countVotes($votes, &$speciesAndVotes, &$differentSpeciesReported)
{
	foreach ($votes as $key => $vote)
	{
		// this species isn't in the $speciesAndVotes array yet
		if (!array_key_exists($vote["speciesAsString"], $speciesAndVotes))
		{
			$speciesAndVotes[$vote["speciesAsString"]] = 1;
			$differentSpeciesReported += 1;
		}
		else
		{
			$speciesAndVotes[$vote["speciesAsString"]] += 1;
		}
	}
}

function computeEvenness($speciesAndVotes, $differentSpeciesReported, $allSpeciesReported, &$topSpecies, &$evenness)
{
	$evennessNumerator = 0;
	$evennessDenominator = 0;

	foreach ($speciesAndVotes as $species => $numberOfVotes)
	{
		if ($topSpecies == "")
		{
			$topSpecies = $species;
		}
		else if ($numberOfVotes > $speciesAndVotes[$topSpecies])
		{
			$topSpecies = $species;		
		}

		// proportion of classifications received by current species
		$temp = $numberOfVotes / $allSpeciesReported;

		$evennessNumerator = $evennessNumerator + $temp * log($temp);
	}
	
	// finish computing evenness - if there is only one species reported, evenness is automatically 0 (total agreement)
	if ($differentSpeciesReported != 1)
	{
		$evennessDenominator = log($differentSpeciesReported);
		$evenness = -1 * $evennessNumerator / $evennessDenominator;
	}
	else
	{
		$evenness = 0;
	}
}

function countChosenIndividuals($votes, $topSpecies, &$topSpeciesMedianCounts)
{
	$speciesAndCounts = array();

	foreach ($votes as $key => $vote)
	{
		// include counts only from people who voted for ALL of top species (ignore those who voted only for one of the top species)
		if ($topSpecies == $vote["speciesAsString"])
		{
			foreach ($vote["counts"] as $species => $count)
			{
				if (!array_key_exists($species, $speciesAndCounts))
				{
					$speciesAndCounts[$species] = array($count);
				}
				else
				{
					array_push($speciesAndCounts[$species], $count);
				}
			}
		}
	}

	foreach ($speciesAndCounts as $species => $counts)
	{
		sort($counts, SORT_NUMERIC);
		$middle = count($counts) / 2;

		$topSpeciesMedianCounts[$species] = $counts[$middle];
	}
}

function outputResults($evennessThreshold, $maxVotes, $allSpeciesReported, $evenness, $speciesAndVotes, $topSpecies, $topSpeciesMedianCounts)
{
	$classificationResult = array();

	// if the aggragated "species" is "I don't know" (<=> species 96), photo is unresolvable
	// $topSpecies is either one number, or many numbers like this: "n1,n2,n3,...,nk,"
	if (preg_match('/(^96,.*)|(.*,96,.*)|(^96$)/', $topSpecies))
	{
		$classificationResult = array("result" => "unresolvable",
									"voteCount" => $allSpeciesReported);
	}

	// if there is (relatively) no agreement among classifications
	else if ($evenness > $evennessThreshold)
	{
		// retirement criteria haven't been fulfilled
		if ($allSpeciesReported < $maxVotes)
		{
			
			$classificationResult = array("result" => "not enough classifications",
										"voteCount" => $allSpeciesReported);
		}
		else
		{	
			$classificationResult = array("result" => "unresolvable",
										"voteCount" => $allSpeciesReported);
		}
	}
	// there is agreement
	else
	{
		// save aggregated species and counts
		$speciesArray = array();
		foreach ($topSpeciesMedianCounts as $species => $count)
		{
			$speciesArray[] = array($count, $species);
		}

		$fractionSupport = $speciesAndVotes[$topSpecies] / $allSpeciesReported;

		// species 86 means "nothing here"
		if (array_key_exists("86", $speciesAndVotes))
		{
			$fractionBlanks = $speciesAndVotes["86"] / $allSpeciesReported;
		}
		else
		{
			$fractionBlanks = 0;
		}

		$classificationResult = array("result" => "success", "fractionSupp" => $fractionSupport,
										"fractionBlank" => $fractionBlanks, "evenness" => $evenness,
										"results" => $speciesArray, "voteCount" => $allSpeciesReported);
	}

	return $classificationResult;
}

function personIsIgnored($row, $classificationId, &$peopleAndClassifications, &$peopleAndUniqueClassifications)
{
	$nothingHereId = "" . "86,0,0";

	// check if the current person hasn't provided EXACTLY the same classification already
	// the first classification from a given person is always unique
	if (!array_key_exists($row["person_id"], $peopleAndUniqueClassifications))
	{
		$peopleAndUniqueClassifications[$row["person_id"]] = array($classificationId => 1);
		return False;
	}
	else
	{
		// person has already been ignored
		if ($peopleAndClassifications[$row["person_id"]] == "INVALID")
		{
			return True;
		}
		// person has provided the same classification before
		else if (array_key_exists($classificationId, $peopleAndUniqueClassifications[$row["person_id"]]))
		{
			// ignore this person
			$peopleAndClassifications[$row["person_id"]] = "INVALID";
			
			return True;
		}
		// this is a new and unique classification
		else
		{
			// it's a "nothing here" which means that the user said "nothing here" AND "something there"
			if ($classificationId == $nothingHereId)
			{
				// ignore this person
				$peopleAndClassifications[$row["person_id"]] = "INVALID";
			
				return True;
			}

			// it's not a "nothing here" but there already is a "nothing here" one for this person => same thing
			else if (array_key_exists($nothingHereId, $peopleAndUniqueClassifications[$row["person_id"]]))
			{
				// ignore this person
				$peopleAndClassifications[$row["person_id"]] = "INVALID";
			
				return True;
			}

			// it's a new and proper classification
			else
			{
				$peopleAndUniqueClassifications[$row["person_id"]][$classificationId] = 1;
				return False;
			}
		}
	}
}

function classificationIsLike($row)
{
	if ($row["species"] == 97)
	{
		return True;
	}
	else
	{
		return False;
	}
}

function allVotesInvalid($peopleAndClassifications)
{
	// there are no votes at all (they were only likes)
	if (count($peopleAndClassifications) == 0)
	{
		return True;
	}

	foreach ($peopleAndClassifications as $person => $classification)
	{
		// when a vote is invalid, $classification is just a string (otherwise it's an array)
		if (is_array($classification))
		{
			return False;
		}
	}

	return True;
}
?>