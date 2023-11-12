<!DOCTYPE html>
<html>
<body>

<h1>My first PHP page</h1>

<?php

$servername = "db1.n.kapsi.fi";
$username = "oranta";
$password = "9K8pqUEYXE";
$dbname = "oranta";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$eventName = $_POST["eventName"];
$sql = "INSERT INTO rando_events (event_name) VALUES ('$eventName')";

if ($conn->query($sql) === TRUE) {
  echo "New record created successfully. <a href='index.php'>Return</a> to the main page";
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>

</body>
</html>
