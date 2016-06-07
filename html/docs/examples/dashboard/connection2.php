<?php 
/*$con=mysql_connect('ec2-52-27-111-35.us-west-2.compute.amazonaws.com',"","");  
if (!$con)
{
    die('Could not connect: ' . mysql_error());
} */  

$numStocks = 10;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ticktalk";


// Create connection
$con = new mysqli($servername, $username, $password, $dbname);

// Check connection
if (!$con) 
{
    die("Connection failed: " . mysqli_connect_error());
}


//$sql = "SELECT TOP " .$numStocks. " * FROM  ticktalk.stocks";
//$sql = "SELECT * FROM (SELECT * FROM ticktalk.daySummaries order by id desc limit ".$numStocks.") unsorted order by id asc";
//$sql = "SELECT * FROM (SELECT * FROM ticktalk.daySummaries order by id desc limit ".$numStocks.") unsorted order by stockName asc";


//$sql = "SELECT * FROM (SELECT * FROM ticktalk.daySummaries order by id desc limit ".$numStocks.") unsorted order by trim(leading 'the ' from lower(stockName)) asc";

$sql = "SELECT * FROM (SELECT * FROM ticktalk.recommendations order by time_alt desc limit ".$numStocks.") unsorted order by trim(leading 'the ' from lower(symbol)) asc";
$result = mysqli_query($con, $sql);

if (mysqli_num_rows($result) > 0) 
{
    // output data of each row
	echo "<table class=\"table table-striped\" >
			<thead>
				<tr>
					<th align=center><b>Company</b></th>
					<th align=center><b>Symbol</b></th>
					<th align=center><b>Recommendation</b></th>
					<th align=center><b>Previous Closing Price</b></th>
					<th align=center><b>More Data</b></th>
				</tr>
			</thead>";
			
	$counter = 0;
    while($row = mysqli_fetch_assoc($result)) {
		  
		$counter = $counter + 1;
		  
		$symbol = $row["symbol"];
		$stockName = $row["issuer_name"];
		$recommendation = $row["recommendation"];
		$end_price = $row["end_price"];
		$tweet_id = $row["influential_tweet_id"];
		$time_period = "7d";
		
		$per_avg_daily_mentions = $row["per_avg_daily_mentions"];
		$per_above_avg = $row["per_above_avg"];
		$per_below_avg = $row["per_below_avg"];
		  
		$url = "https://publish.twitter.com/oembed?url=https%3A%2F%2Ftwitter.com%2FInterior%2Fstatus%2F".$tweet_id."";
		$json = file_get_contents($url);
		$json_data = json_decode($json, true);
		$tweet = $json_data["html"];  
		  
		  
		//Table Data
		
		echo "<tr>";
			echo "<td>".$stockName."</td>";
			echo "<td>".$symbol."</td>";
			echo "<td>".$recommendation."</td>";
			echo "<td>".round($end_price,2)."</td>";
			echo "<td><a href=\"#\" id=\"show_".$counter."\">Show Data</a></td>";
		echo "</tr>";
          
		//More Data
		echo "<tr>";
			echo " <td colspan=\"5\">";
				echo "<div id=\"extra_".$counter."\" style=\"display: none;\">";
          
		//More Data (Calculated)
		
		$svg_side_length = 200;
		$spacer_side_length = 100;
		$default_rad = $svg_side_length/2;
		$per_daily_avg = round($per_avg_daily_mentions,0);
		$per_neg_tweets = round($per_below_avg,0);
		$per_pos_tweets = round($per_above_avg,0);
		
		//$daily_avg_radius = pow($per_daily_avg/100 * $default_rad, 0.5); //this worked as planned, but circles ended up being too small
		$daily_avg_radius = $default_rad;
		$neg_tweets_radius = pow($per_neg_tweets/100,0.5) * $daily_avg_radius;
		$pos_tweets_radius = pow($per_pos_tweets/100,0.5) * $daily_avg_radius;
		
		$svg_neg_side_length = 2 * $neg_tweets_radius;
		if ($svg_neg_side_length < 100)
		{
			$svg_neg_side_length = 100;
		}
		
		$svg_daily_avg_side_length = 2 * $daily_avg_radius;
		if ($svg_daily_avg_side_length < 100)
		{
			$svg_daily_avg_side_length = 100;
		}
		
		$svg_pos_side_length = 2 * $pos_tweets_radius;
		if ($svg_pos_side_length < 100)
		{
			$svg_pos_side_length = 100;
		}
		
		$fontsize = 36;
		$label_fontsize = 24;
		$margin = 24;

		//percentages
		//https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/text-anchor
		//http://stackoverflow.com/questions/442164/how-to-get-an-outline-effect-on-text-in-svg
		
							//<svg height=".($svg_neg_side_length+label_fontsize +$margin)." width=".$svg_neg_side_length.">

							//<text fontsize=".$label_fontsize."px text-anchor=\"middle\" alignment-baseline=\"middle\" x=".$neg_tweets_radius." y=".($neg_tweets_radius*2+$label_fontsize/2)." fill=\"white\">Tweets below average sentiment</text>

		
		echo"	
		<div class=\"container\" style=\"text-align:center;\">
				<svg height=".$spacer_side_length." width=".$spacer_side_length."></svg>
				<svg height=".($svg_neg_side_length)." width=".$svg_neg_side_length.">
					<circle cx=".($svg_neg_side_length/2)." cy=".($svg_neg_side_length/2)." r=".$neg_tweets_radius." stroke=\"black\" stroke-width=\"0\" fill=\"lightcoral\" />
					<text text-anchor=\"middle\" alignment-baseline=\"middle\" x=".$neg_tweets_radius." y=".($neg_tweets_radius)." fill=\"white\">".$per_neg_tweets."%</text>
					<style><![CDATA[text{font: bold ".$fontsize."px Helvetica, sans-serif; stroke-width: 1px; stroke: #000000;}]]></style>
				</svg>
				<svg height=".$spacer_side_length." width=".$spacer_side_length."></svg>
				<svg height=".$svg_daily_avg_side_length." width=".$svg_daily_avg_side_length.">
					<circle cx=".($svg_daily_avg_side_length/2)." cy=".($svg_daily_avg_side_length/2)." r=".$daily_avg_radius." stroke=\"black\" stroke-width=\"0\" fill=\"grey\" />
					<text text-anchor=\"middle\" alignment-baseline=\"middle\" x=".$daily_avg_radius." y=".($daily_avg_radius)." fill=\"white\">".$per_daily_avg."%</text>
					<style><![CDATA[text{font: bold ".$fontsize."px Helvetica, sans-serif; stroke-width: 1px; stroke: #000000;}]]></style>
				</svg>
				<svg height=".$spacer_side_length." width=".$spacer_side_length."></svg>
				<svg height=".$svg_pos_side_length." width=".$svg_pos_side_length.">
					<circle cx=".($svg_pos_side_length/2)." cy=".($svg_pos_side_length/2)." r=".$pos_tweets_radius." stroke=\"black\" stroke-width=\"0\" fill=\"lightgreen\" />
					<text text-anchor=\"middle\" alignment-baseline=\"middle\" x=".$pos_tweets_radius." y=".($pos_tweets_radius)." fill=\"white\">".$per_pos_tweets."%</text>
					<style><![CDATA[text{font: bold ".$fontsize."px Helvetica, sans-serif; stroke-width: 1px; stroke: #000000;}]]></style>
				</svg>
			</div>";
		
		//historical table
		
		$sql_historical = "SELECT * FROM ticktalk.historical_performance where symbol='".$symbol."' order by date desc";
		$result_historical = mysqli_query($con, $sql_historical);
		
echo"		<div class=\"container\">
  <h2>Bordered Table</h2>
  <p>The .table-bordered class adds borders to a table:</p>            
  <table class=\"table table-bordered\">
    <thead>
      <tr>
	  
	  <th></th>";
	  
	  $recommendation_arr = array();
	  $performance_arr = array();
	  while($row_historical = mysqli_fetch_assoc($result_historical))
	  {
		  $recommendation_arr[] = $row_historical["recommendation"];
		  $performance_arr[] = round($row_historical["performance"]*100);
		  
		  $date_arr = explode('-',$row_historical["date"]);
		  $date_formatted = $date_arr[1]."/".$date_arr[2]."/".$date_arr[0];
		  echo "<th>".$date_formatted."</th>";
	  }
		
		
     echo "</tr>
    </thead>
    <tbody>
      <tr>
        <td>Recommendation</td>";
        foreach ($recommendation_arr as $rec)
		{
			echo "<td>".$rec."</td>";
		}
     echo "</tr>
      <tr>
        <td>Performance</td>";
        
		 foreach ($performance_arr as $per)
		{
			echo "<td>".$per."%</td>";
		}
      
	  echo "</tr>
    </tbody>
  </table>
</div>";

		
		
//carousel
	echo "	
		<div id=\"myCarousel\" class=\"carousel slide\" data-ride=\"carousel\">
  <!-- Indicators -->
  <ol class=\"carousel-indicators\">
    <li data-target=\"#myCarousel\" data-slide-to=\"0\" class=\"active\"></li>
    <li data-target=\"#myCarousel\" data-slide-to=\"1\"></li>
    <li data-target=\"#myCarousel\" data-slide-to=\"2\"></li>
    <li data-target=\"#myCarousel\" data-slide-to=\"3\"></li>
  </ol>

  <!-- Wrapper for slides -->
  <div class=\"carousel-inner div-responsive center-block\" role=\"listbox\" overflow:hidden>
    <div class=\"item active hidden-container\">
      ".$tweet."
    </div>

    <div class=\"item div-responsive hidden-container center-block\">
      ".$tweet."
    </div>

    <div class=\"item div-responsive hidden-container center-block\">
      ".$tweet."
    </div>

    <div class=\"item div-responsive hidden-container center-block\">
      ".$tweet."
    </div>
  </div>

  <!-- Left and right controls -->
  <a class=\"left carousel-control\" href=\"#myCarousel\" role=\"button\" data-slide=\"prev\">
    <span class=\"glyphicon glyphicon-chevron-left\" aria-hidden=\"true\"></span>
    <span class=\"sr-only\">Previous</span>
  </a>
  <a class=\"right carousel-control\" href=\"#myCarousel\" role=\"button\" data-slide=\"next\">
    <span class=\"glyphicon glyphicon-chevron-right\" aria-hidden=\"true\"></span>
    <span class=\"sr-only\">Next</span>
  </a>
</div>
		";
		
		
/* 		echo "<ul class=\"bxslider\">";
	
			echo "<li>tweet</li>";
			echo "<li>tweet</li>";
			echo "<li>tweet</li>";
			
		echo "</ul>";  */
		
		//echo $tweet;
		
		
		
		//charts
		 //More Data (Yahoo-populated)
		 echo  "<div class=\"table-responsive\" align=\"center\">";

			echo " <table class=\"table\">";
				echo "<caption> Yahoo Data </caption>";
		 //stock charts
		 
					echo "<thead>";
						echo " <tr>";
			//print header
							echo "<th colspan=\"2\">";
								echo "<p><font size=\"4\">Day and Week Charts for ".$stockName."</font></p>";
							echo "</th>";
						echo "</tr>";
					echo "</thead>";
		 
					echo "  <tr>";
						//1 day
						echo " <td align=\"center\">";
							echo "<br>";
							echo "<img align=\"center\" src=\"http://chart.finance.yahoo.com/z?s=".$symbol."&t=1d&q=l&l=on&z=s\"/>";
						echo "  </td>";
						//7 days
						echo " <td align=\"center\">";
							echo "<br>";
							echo "<img align=\"center\" src=\"http://chart.finance.yahoo.com/z?s=".$symbol."&t=7d&q=l&l=on&z=s\"/>";
						echo "  </td>";
		 
					echo " </tr>";
			echo "</table>";
		echo "</div>";
		  
		  
		  //headlines
		  
		  //consyruct the url with the necessary ticker symbol
		  $path_prefix = "https://feeds.finance.yahoo.com/rss/2.0/headline?s=";
			$ticker = $symbol;
			$path_suffix = "&lg=us&region=US&lang=en-US";

			$path = $path_prefix.$ticker.$path_suffix;

			//retrieve the file from the url
			$xml_file = file_get_contents($path);
			
			//extract the data from the file
			$xml = simplexml_load_string($xml_file);
			
			//get data in useable form
			$channel = $xml->channel;
			$channel_title = $channel->title;
			$channel_description = $channel->description;
			
			//headline table
			echo  "<div class=\"table-responsive\" align=\"center\">";
				echo "<table class=\"table table-no-border\">";
					echo "<thead>";
						echo " <tr>";
			//print header
							echo "<th colspan=\"2\">";
								//echo "<p><font size=\"5\">".$channel_title."</font></p>"; //unnecessary
								echo "<p><font size=\"4\">".$channel_description."</font></p>";
							echo "</th>";
						echo "</tr>";
					echo "</thead>";
			//print headlines
					foreach ($channel->item as $item)
					{
						$title = $item->title;
						$link = $item->link;
						$descr = $item->description;
						echo "<tr>";
							echo "<td colspan=\"2\">";
								echo "<font size=\"3\"><a href='".$link."'>".$title."</a></font>";
								//echo "<p><font size=\"3\"><a href='".$link."'>".$title."</a></font></p>";
								//echo "<p>".$descr."</p>";
							echo "</td>";
						echo " </tr>";
					}
		  	  
				echo "</table>";
			echo "</div>";
		  
          echo "          </div>";
          echo "        </td>";
          echo "      </tr>";
		  
	}

	echo "</table>";
		
}
 else 
{
    echo "0 results";
}






?>
