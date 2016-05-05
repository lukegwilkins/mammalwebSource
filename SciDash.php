<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0-beta1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.js"></script>
<link href='https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300,300italic,700' rel='stylesheet' type='text/css'>

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
                    <li><a href="index.html">Filter for Scientists</a></li>
                    <li><a href="carouselfilter.html">Carousel Filter for Scientists</a></li>
                    <li class="active"><a href="SciDash.php">Data Hub</a></li>
                    <li><a href="fr4.html">Unresolved Classification</a></li>
                    <li><a href="classifyData.html">Scientist Classification settings</a></li>
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



    <?php

        $connection = new mysqli("localhost", "root", "notsecurepassword", "xdbl0zz14_biodiversity");

        $query="SELECT photo_id FROM photostate
                WHERE state='unresolvable'";

        $result=$connection->query($query);

        $unresol = mysqli_num_rows($result);
        $data["unresolved"]=$unresol;

        $query="SELECT photo_id FROM photostate
                WHERE state='classified'";

        $result1=$connection->query($query);

        $classified = mysqli_num_rows($result1);
        $data["classified"]=$classified;

        $query="SELECT photo_id FROM photostate
                WHERE state='new'";
        $result2=$connection->query($query);

        $new = mysqli_num_rows($result2);
        $data["new"]=$new;

        $query="SELECT photo_id FROM photostate
                WHERE state='in_circulation'";
        $result3=$connection->query($query);

        $in_circulation = mysqli_num_rows($result3);
        $data["in_circulation"]=$in_circulation;
        
    ?>

    <!--
        <?php

        $finalBarData = [];
        $connection2 = new mysqli("localhost", "root", "", "xdbl0zz14_biodiversity");

        $query5="SELECT valid_classifications FROM photostate, xclassification
                WHERE xclassification.photo_id=photostate.photo_id AND(xclassification.species=10 OR xclassification.species=11)";

        $result5=$connection2->query($query5);
        echo json_encode($result5);

        $unresol2 = mysqli_num_rows($result5);
        echo $unresol2;
        
        /*if($result5->num_rows>0){
            $arrayOfPhotos=[];
            while($row = $result->fetch_assoc()){
            $savedResults = mysqli_num_rows($row);
                $arrayOfPhotos[]=$savedResults;
            }
        }
        */
    ?>*/
    -->

        <script>
            $(document).ready(function(){
                var ctx = $("#canvas").get(0).getContext("2d");
                //pie chart data
                //sum of values = 360

                var data = [
                    {
                        value: <?php echo $data['unresolved'] ?>,
                        color: "cornflowerblue",
                        highlight: "lightskyblue",
                        label: "Unresolved"
                    },
                    {
                        value: <?php echo $data['classified'] ?>,
                        color: "lightgreen",
                        highlight: "yellowgreen",
                        label: "Classified"
                    },
                    {
                        value: <?php echo $data['in_circulation'] ?>,
                        color: "orange",
                        highlight: "darkorange",
                        label: "In Circulation"
                    },
                    {
                        value: <?php echo $data['new'] ?>,
                        color: "purple",
                        highlight: "indigo",
                        label: "New"
                    }
                ];
                //draw
                var myChart = new Chart(ctx).Pie(data, {
                responsive: true
            });

            var barData = {
                labels : ["badger","deer"],
                datasets : [
                    {
                        fillColor : "#48A497",
                        strokeColor : "#48A4D1",
                        data : <?php echo $unresol;?>
                    },
                    {
                        fillColor : "rgba(73,188,170,0.4)",
                        strokeColor : "rgba(72,174,209,0.4)",
                        data : <?php echo $unresol;?>
                    }
                ]
            }
            // get bar chart canvas
                var bar = $("#classified").get(0).getContext("2d");
            // draw bar chart
             var myBarChart = new Chart(bar).Bar(barData);
        });

        </script>

<div style="font-family: 'Open Sans Condensed', serif;">
    <div class="container">
        <div class="row">
        <div class="col-sm-4">
        </div>
        <div class="col-sm-4">
            <h1 align="center"><font size="5">Break down of photo classifications</font></h1>
            </br>
            <canvas id="canvas" width="200" height="200" align="center"></canvas>
            <p align="center"><font size="5">Break down of the classified, unresolved, new and in circulation photos currently on the database.
            </font></p>
        </div>
        <div class="col-sm-4">
        </div>
        </div>
    </div>
</div>
     <!--   <div class="col-sm-6">
                    <h1 align="center"><font size="5">Break down of species classifications</font></h1>
            <canvas id="classified" width="600" height="400"></canvas>
                        <p><font size="5">Amount of Classified in a chosen species and average required amount to classify a species.

        </div> -->
</body>
</html>



