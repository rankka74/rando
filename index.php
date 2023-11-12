
<!DOCTYPE html>
<html>
<body>

  <p>
    <h1>Create new event</h1>
    <form action="event.php" method="post">
        <input type="text" name="eventName" id="eventName">
	<input type="submit" value="Create new event" name="submit">

    </form>
  
  <hr>
  
  <p>
    <h1>Upload new file</h1>
    <form action="upload.php" method="post" enctype="multipart/form-data">
  Select file to upload:
  <input type="file" name="fileToUpload" id="fileToUpload">
  <label for="overwrite"> Overwrite? </label>
  <input type="checkbox" id="overwrite" name="overwrite" value="overwrite">
  <label for="events">Choose an event:</label>
  <select name="events">
    <?php

require_once "../../external_includes/mysql_pw.php";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
	   $sql = "SELECT event_id, event_name FROM rando_events ORDER BY event_name";
	   $result = $conn->query($sql);
	   if ($result->num_rows > 0) {

	   // output data of each row
	   while($row = $result->fetch_assoc()) {
	   echo "<option value='" . $row["event_id"]. "'>" . $row["event_name"] . "</option>\r\n";
	     }
	     } else {
	     echo "0 results";
	     }
	     ?>

	 </select>
	 <input type="submit" value="Upload gpx file" name="submit">
    </form>

    <hr>
   
  <p>
    <h1>Analyze</h1>
    <form action="analyzator.php" method="post">
        <label for="events">Choose an event:</label>
	<select name="events">
	  <?php
          $result = $conn->query($sql);
	   if ($result->num_rows > 0) {
	  while($row = $result->fetch_assoc()) {
	  echo "<option value='" . $row["event_id"]. "'>" . $row["event_name"] . "</option>\r\n";
	  }
	  } else {
	  echo "0 results";
	  }
	  ?>

	 </select>
	 <input type="submit" value="Analyze event" name="submit">
    </form>
	
    <hr>

	<table border="1">
	<tr>
	<th>Event name
	<th>Distance
	<th>Time
	  <?php
		$sql = "SELECT event_id, event_name FROM rando_events WHERE analyzed=1 ORDER BY event_name";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$eventName = $row["event_name"];
				$eventId = $row["event_id"];
				# https://stackoverflow.com/questions/11036420/double-quotes-within-php-script-echo
				echo "<tr>\r\n<td><a href=\"show_map.php?event_id=" . $eventId. "\">" . $eventName . "</a>\r\n";
			}
		} else {
			echo "0 results";
		}

		$conn->close();
	  ?>
	</table>

</body>
</html>
