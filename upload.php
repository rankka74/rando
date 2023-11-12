<!DOCTYPE html>
<html>
<body>

<h1>Upload a gpx file</h1>

<?php
# https://www.w3schools.com/PHP/php_file_upload.asp
# require __DIR__ . '/coords2dist.php';
# include "coords2dist.php";
# echo "Start<BR>\n";

require_once "../../external_includes/mysql_pw.php";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

if (!empty($_POST['events'])) {
	$eventId = $_POST['events'];
	# echo "Event: " . $eventId ."<BR>";
}

echo "Overwrite:" . $_POST['overwrite'] . "<BR>\r\n";

$tmpName = $_FILES['fileToUpload']['tmp_name'];
$gpxFileName = htmlspecialchars( basename( $_FILES['fileToUpload']['name']));

echo "Temporary file: " . $tmpName ."<BR>\r\n";


# https://www.w3schools.com/php/php_file_upload.asp
$target_dir = "gpx/";
$target_file = $target_dir . $gpxFileName;
$uploadOk = 1;
$fileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

# https://stackoverflow.com/questions/20359840/file-upload-in-php-validating-that-a-file-is-gpx
$xml = new XMLReader();
$xmlcontents = XMLReader::open($tmpName);
$xmlcontents->setParserProperty(XMLReader::VALIDATE, true);
if($xmlcontents->isValid() and ($xml->xml($gpxFileName, NULL, LIBXML_DTDVALID))) {
} else {
	echo "Not a valid GPS file!<BR>\r\n";            
	$uploadOk = 0;
}

// Check if file already exists
if (file_exists($target_file) && $_POST['overwrite'] != 'overwrite') {
	echo "Sorry, file already exists.<BR>\r\n";
	$uploadOk = 0;
}
elseif(file_exists($target_file) && $_POST['overwrite'] == 'overwrite') {
	if (move_uploaded_file($tmpName, $target_file)) {
		echo "The file ". $target_file . " has been updated.<BR>\r\n";
		$sql = "UPDATE rando_files SET time_start='0000-00-00 00:00:00', time_end='0000-00-00 00:00:00' WHERE filename='$gpxFileName'";
		# echo $sql ."<BR>\r\n";
		if ($conn->query($sql) === TRUE) {
			echo "rando_files updated successfully.<BR>\r\n";
		} else {
			echo "Error: " . $sql . "<br>>\r\n" . $conn->error;
		}
		$sql = "UPDATE rando_events SET analyzed='0' WHERE event_id='$eventId'";
		if ($conn->query($sql) === TRUE) {
			echo "rando_events updated successfully.<BR>\r\n";
		} else {
			echo "Error: " . $sql . "<br>>\r\n" . $conn->error;
		}
	} else {
		echo "Sorry, there was an error uploading your file.<BR>\r\n";
		$uploadOk = 0;
	}
}
else {
	if (move_uploaded_file($tmpName, $target_file)) {
		echo "The file ". $target_file . " has been uploaded.<BR>\r\n";
		$sql = "INSERT INTO rando_files (event_id, filename) VALUES ('$eventId', '$gpxFileName')";
		# echo $sql ."<BR>\r\n";
		if ($conn->query($sql) === TRUE) {
			echo "New record created successfully.<BR>\r\n";
		} else {
			echo "Error: " . $sql . "<br>>\r\n" . $conn->error;
		}
	} else {
		echo "Sorry, there was an error uploading your file.<BR>\r\n";
		$uploadOk = 0;
	}
}

/*
if (file_exists($target_file)) {
	$gpx = simplexml_load_file($target_file);
	echo "File " . $target_file . " exists<BR>\r\n";
} else {
	echo "Failed to open" . $tmpName . "<BR>\r\n";
	$uploadOk = 0;
}
*/

// Allow certain file formats
if($fileType != "gpx" ) {
	echo "Sorry, only gpx files are allowed.<BR>\r\n";
	$uploadOk = 0;
}

$conn->close();
?>

</body>
</html>
