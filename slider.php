
<?php

$photosaves = array();
$connection = new mysqli("localhost", "root", "", "xdbl0zz14_biodiversity");

$query="SELECT photo_id FROM photostate
		WHERE state='unresolvable'";

$result=$connection->query($query);

$array = array("photo_id" => null, "photo_url" => null);
$arr = array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5);
if($result->num_rows>0){
	while($row = $result->fetch_assoc()){
		$pId=$row["photo_id"];
		$array['photo_id'] = $pId;
		$query="SELECT filename, site_id, person_id FROM photo
				WHERE photo_id=$pId";

		$photoQueryResult=$connection->query($query);

		if($photoQueryResult->num_rows>0){
			$photoData=$photoQueryResult->fetch_assoc();
			$siteId=$photoData["site_id"];
			$personId=$photoData["person_id"];
			$filename=$photoData["filename"];
			$photoUrl="http:\\\\www.mammalweb.org\\biodivimages\\person_$personId\\site_$siteId\\$filename";
			$photosaves[] = $photoUrl;
			//break;
		}
		else{

			$array['photo_id'] = null;
		}
	}
}
json_encode($photosaves)
?>



    <div class="bs-example">
    <div id="carousel-example-captions" class="carousel slide" data-ride="carousel" style="width: 600px; margin: 0 auto">
        <div class="carousel-inner">
<?php
    $counter = 1;
    foreach($photosaves as $row){
?>
            <div class="item<?php if($counter <= 1){echo " active"; } ?>">
                <a href="">
                    <img data-src="holder.js/900x800/auto/#777:#777" src="<?php echo $row ?>"/>
                </a>
            </div>
<?php
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



