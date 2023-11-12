<!DOCTYPE html>
<html>
<body>

<h1>Analyze the gpx files of an event</h1>

<?php

require_once "../../external_includes/mysql_pw.php";
$analysisSuccess = 1;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
	$analysisSuccess = 0;
}

if (!empty($_POST['events'])) {
	$eventId = $_POST['events'];
	#echo "Event: " . $eventId ."<BR>";
}

# Read event_id from the database
$sql = "SELECT event_name FROM rando_events WHERE event_id='$eventId'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	$row = $result -> fetch_row();
} else {
	echo "0 results";
	$analysisSuccess = 0;
}

# Open/create a geoJSON files
# $jsonFile = "./json/" . $row[0] . ".geojson";
$jsonFileBreaks = $row[0] . "_breaks.geojson";
echo "jsonFile: " . $jsonFileBreaks . "<BR>\r\n";
$myfile = fopen($jsonFileBreaks, "w") or die("Unable to open file!");
fclose($myfile);
$jsonFileRoute = $row[0] . ".geojson";
echo "jsonFile: " . $jsonFileRoute . "<BR>\r\n";
$myfile = fopen($jsonFileRoute, "w") or die("Unable to open file!");
fclose($myfile);

# sql to delete a record
$sql = "DELETE FROM rando_breaks WHERE event_id='$eventId'";
if ($conn->query($sql) === TRUE) {
	#	echo "Record deleted successfully";
	} else {
	#	echo "Error deleting record: " . $conn->error;
	$analysisSuccess = 0;
}

# Find the start times of the gpx files
$sql = "SELECT event_id, file_id, filename FROM rando_files WHERE event_id='$eventId'";
$result = $conn->query($sql);

# https://stackoverflow.com/questions/27079213/how-do-i-read-values-from-a-gpx-file-in-php
# https://stackoverflow.com/questions/1450157/how-can-i-get-the-current-array-index-in-a-foreach-loop/14966376
if ($result->num_rows > 0) {
	while ($row = $result -> fetch_row()) {
		if (file_exists("gpx/" . $row[2])) {
			$gpx = simplexml_load_file("gpx/" . $row[2]);
		} else {
			exit('Failed to open $tmpName.');
		}
		echo "File: " . "gpx/" . $row[2] . "<BR>\r\n";
		#echo $gpx->trk->trkseg->trkpt[0]->time ."<BR>\r\n";
		$timeStart = date("Y-m-d H:i:s", strtotime((string) $gpx->trk->trkseg->trkpt[0]->time));
		$sql = "UPDATE rando_files SET time_start='$timeStart' WHERE file_id=$row[1]";
		echo $sql . "<BR>\r\n";
		if ($conn->query($sql) === TRUE) {
			echo "Record updated successfully.<BR>\r\n";
		} else {
			echo "Error: " . $sql . "<br>" . $conn->error;
		}
	}
} else {
	echo "0 results";
	$analysisSuccess = 0;
}

# https://gist.github.com/wboykinm/5730504
$geojsonBreaks = array(
	'type'      => 'FeatureCollection',
	'features'  => array()
);
$geojsonRoute = array(
	'type'      => 'FeatureCollection',
	'features'  => array()
);

$sql = "SELECT event_id, file_id, filename FROM rando_files WHERE event_id='$eventId' ORDER BY time_start";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	$i = -1;
	while ($row = $result -> fetch_row()) {
		$fileName = $row[2];
		if (file_exists("gpx/" . $fileName)) {
			$gpx = simplexml_load_file("gpx/" . $fileName);
		} else {
			exit('Failed to open $tmpName.');
		}
		echo "File: " . "gpx/" . $fileName . "<BR>\r\n";
		$pointList=array();
		foreach ($gpx->trk->trkseg->trkpt as $pt) {
			$i++;
			$tim = strtotime((string) $pt->time);
			$lat = (float) $pt['lat'];
			$lon = (float) $pt['lon'];
			if ($i == 0) {
				$deltaT = 0;
				$dist = 0;
				$tot_dist = 0;
				$tot_time = 0;
				$secFromPreviousBreak = 0;
				$total_break_duration = 0;
				$distFromPreviousBreak = 0;
			} else {
				$deltaT = $tim - $timFrom;
				# Distance in km
				$dist = vincentyGreatCircleDistance($latiFrom, $longFrom, $lat, $lon) / 1000;
				$tot_dist = $tot_dist + $dist;
				$tot_time = $tot_time + $deltaT;
				$secFromPreviousBreak = $secFromPreviousBreak + $deltaT;
				$distFromPreviousBreak = $distFromPreviousBreak + $dist;
			}
			# echo $deltaT ."<BR>\r\n";
			if ($deltaT >= 30) {
				$fileId = $row[1];
				$new_timeNow = date("Y-m-d H:i:s", $tim);
				$new_timeFrom = date("Y-m-d H:i:s", $timFrom);
				$secFromPreviousBreak = $secFromPreviousBreak - $deltaT;
				$total_break_duration = $total_break_duration + $deltaT;
				$efficiency = ($tot_time - $total_break_duration) / $tot_time * 100;
				if ($deltaT >= 30 && $deltaT < 300) {
					$durationClass = "short";
				} elseif ($deltaT >= 300 && $deltaT < 7200) {
					$durationClass = "mid";
				} elseif ($deltaT > 7200) {
					$durationClass = "long";
				}
				# echo $durationClass . "<BR>\r\n";
				# https://bookdown.org/sammigachuhi/book-leaflet/using-geojson-in-leaflet.html
				$feature = array(
					'type' => 'Feature', 
					'geometry' => array(
						'type' => 'Point',
						# Pass Longitude and Latitude Columns here
						'coordinates' => array($lon, $lat)
					),
					'properties' => array(
						# Convert seconds to H:MM:SS
						'Duration' => sprintf('%01d:%02d:%02d', $deltaT / 3600, ($deltaT % 3600) / 60, $deltaT % 60),
						'Time' => (string) date("Y-m-d H:i:s", $timFrom),
						'Distance' => (string) $tot_dist,
						'Class' => $durationClass
					)
				);
				# Add feature arrays to feature collection array
				array_push($geojsonBreaks['features'], $feature);
				# Add break info to the database
				$sql = "INSERT INTO rando_breaks (event_id, file_id, time_start, time_end, dist, lat, lon, duration, sec_from_start, sec_from_break, total_break_duration_sec, efficiency, dist_from_break) VALUES ('$eventId', '$fileId', '$new_timeFrom', '$new_timeNow', '$tot_dist', '$lat', '$lon', '$deltaT', '$tot_time', '$secFromPreviousBreak', '$total_break_duration', '$efficiency', '$distFromPreviousBreak')";
				# echo $sql ."<BR>\r\n";
				if ($conn->query($sql) === TRUE) {
					# echo "New record created successfully.<BR>\r\n";
				} else {
					echo "Error: " . $sql . "<br>" . $conn->error;
				}
				$secFromPreviousBreak = 0;
				$distFromPreviousBreak = 0;
			}
			$timFrom = $tim;
			$latiFrom = $lat;
			$longFrom = $lon;
			$pointList[] = array($lon, $lat);
		}
		
		$timeEnd = date("Y-m-d H:i:s", strtotime((string) $pt->time));
		$sql = "UPDATE rando_files SET time_end='$timeEnd' WHERE file_id=$row[1]";
		echo $sql . "<BR>\r\n";
		if ($conn->query($sql) === TRUE) {
			echo "Record updated successfully.<BR>\r\n";
		} else {
			echo "Error: " . $sql . "<br>" . $conn->error;
			$analysisSuccess = 0;
		}
		# https://stackoverflow.com/questions/53608790/how-can-i-create-a-linestring-geojson-with-data-from-data-base
		$feature = array(
		'type' => 'Feature', 
		'geometry' => array(
			'type' => 'LineString',
			# Pass Longitude and Latitude Columns here
			'coordinates' => $pointList
			),
			'properties' => array(
				# Convert seconds to H:MM:SS
				'Name' => $fileName
					)
		);
		array_push($geojsonRoute['features'], $feature);
	}
} else {
	echo "0 results";
	$analysisSuccess = 0;
}
#header('Content-type: application/json');
#echo json_encode($geojson, JSON_PRETTY_PRINT);
if(file_put_contents($jsonFileBreaks, json_encode($geojsonBreaks, JSON_PRETTY_PRINT))) {
	echo "geoJSON breaks file saved successfully.<BR>\r\n";
} else {
	echo "geoJSON breaks file saving failed.<BR>\r\n";
	$analysisSuccess = 0;
}
if(file_put_contents($jsonFileRoute, json_encode($geojsonRoute, JSON_PRETTY_PRINT))) {
	echo "geoJSON route file saved successfully.<BR>\r\n";
} else {
	echo "geoJSON route file saving failed.<BR>\r\n";
	$analysisSuccess = 0;
}

if($analysisSuccess == 1) {
	$sql = "UPDATE rando_events SET analyzed=1 WHERE event_id=$eventId";
	if ($conn->query($sql) === TRUE) {
		echo "Record updated successfully.<BR>\r\n";
	} else {
		echo "Error: " . $sql . "<br>" . $conn->error;
	}
}


$conn->close();
/**
 * Calculates the great-circle distance between two points, with
 * the Vincenty formula.
 * @param float $latitudeFrom Latitude of start point in [deg decimal]
 * @param float $longitudeFrom Longitude of start point in [deg decimal]
 * @param float $latitudeTo Latitude of target point in [deg decimal]
 * @param float $longitudeTo Longitude of target point in [deg decimal]
 * @param float $earthRadius Mean earth radius in [m]
 * @return float Distance between points in [m] (same as earthRadius)
 */
function vincentyGreatCircleDistance(
  $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
{
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $lonDelta = $lonTo - $lonFrom;
  $a = pow(cos($latTo) * sin($lonDelta), 2) +
    pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
  $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

  $angle = atan2(sqrt($a), $b);
  return $angle * $earthRadius;
}

?>
  
</body>
</html>
