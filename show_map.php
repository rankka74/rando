<!DOCTYPE html>
<html>
<head>

<!-- Initializing  Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>

<!-- Make sure you put this AFTER Leaflet's CSS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-ajax/2.1.0/leaflet.ajax.min.js"></script>

<style>
#map { height: 100vh;
width: 100%}
</style>

</head>
<body>

<div id="map"></div>

<?php

require_once "../../external_includes/mysql_pw.php";

$eventId = filter_input(INPUT_GET, 'event_id', FILTER_SANITIZE_URL);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT MAX(lat), MIN(lat), MAX(lon), MIN(lon) FROM rando_breaks WHERE event_id=$eventId";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	$row = $result -> fetch_row();
} else {
	echo "0 results";
}
$latCenter = ($row[0] + $row[1]) / 2;
$lonCenter = ($row[2] + $row[3]) / 2;
echo "<script>\r\nvar latCenter = '$latCenter';\r\n";
echo "var lonCenter = '$lonCenter';\r\n</script>\r\n";

$sql = "SELECT event_name FROM rando_events WHERE event_id=$eventId";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	$row = $result -> fetch_row();
} else {
	echo "0 results";
}
$eventNameBreaks = $row[0] . "_breaks.geojson";
echo "<script>\r\nvar eventNameBreaks = '$eventNameBreaks';\r\n";
$eventNameRoute = $row[0] . ".geojson";
echo "var eventNameRoute = '$eventNameRoute';\r\n</script>\r\n";

?>

<script>

var map = L.map('map').setView([latCenter, lonCenter], 5);

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

var hotelIcon = L.icon({
    iconUrl: 'icons/baseline_night_shelter_black_24dp.png',
    iconSize:     [38, 95], // size of the icon
    iconAnchor:   [22, 94], // point of the icon which will correspond to marker's location
    popupAnchor:  [-3, -76] // point from which the popup should open relative to the iconAnchor
});

// https://bookdown.org/sammigachuhi/book-leaflet/using-geojson-in-leaflet.html
// https://gis.stackexchange.com/questions/189988/filtering-geojson-data-to-include-in-leaflet-map
// https://spatialgalaxy.net/2019/02/06/leaflet-day-10-adding-a-link-to-a-popup/
//var breakLayer = new L.geoJson.ajax("2017-Ruska-Olli.geojson", {filter: breakFilter})
var geojsonLayer = new L.geoJson.ajax(eventNameBreaks, {filter: breakFilter} 
	).bindPopup(function (layer) {
        return `Break duration: ${layer.feature.properties.Duration}<BR>
		Time: ${layer.feature.properties.Time}<BR>
		<a href="https://www.google.com/maps/@?api=1&map_action=map&center=${layer.feature.geometry.coordinates[1]},${layer.feature.geometry.coordinates[0]}&zoom=19&basemap=satellite" target="_blank">Google Maps</a><br>`
}).addTo(map);

var geojsonLayer = new L.geoJson.ajax(eventNameRoute, {filter: routeFilter}).addTo(map);

function breakFilter(feature) {
  if (feature.geometry.type === "Point") return true
}

function breakFilterLong(feature) {
  if (feature.geometry.type === "Point" && feature.properties.Class === "long") return true
}

function routeFilter(feature) {
  if (feature.geometry.type === "LineString") return true
}

</script>
<!--
	<table border="1">
	<tr>
	<th>Start time
	<th>Duration
	<th>Disrance from start

<?php

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT time_start, duration, dist FROM rando_breaks WHERE event_id=$eventId";
$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$timeStart = $row["time_start"];
				$duration = sprintf('%01d:%02d:%02d', $row["duration"] / 3600, ($row["duration"] % 3600) / 60, $row["duration"] % 60);
				$distanceFromStart = round($row["dist"], 1);
				# https://stackoverflow.com/questions/11036420/double-quotes-within-php-script-echo
				echo "<tr>\r\n<td>$timeStart\r\n<td>$duration\r\n<td>$distanceFromStart\r\n";
			}
		} else {
			echo "0 results";
		}

$conn->close();
?>

	</table>
-->
</body>
</html>