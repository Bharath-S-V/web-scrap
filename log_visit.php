<?php
// Database connection configuration
$servername = "localhost"; // Change if necessary
$username = "root"; // Your MySQL username
$password = ""; // Your MySQL password
$dbname = "tracking_db"; // The database you created earlier

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the JSON data from the request body
$data = json_decode(file_get_contents('php://input'), true);

// Extract the IP address, URL, location data from the JSON data
$ipAddress = $conn->real_escape_string($data['ip_address']);
$url = $conn->real_escape_string($data['url']);
$city = $conn->real_escape_string($data['city']);
$state = $conn->real_escape_string($data['state']);
$country = $conn->real_escape_string($data['country']);
$visitDate = date('Y-m-d H:i:s'); // Get the current date and time

// Check if the IP address already exists in the database
$sql = "SELECT * FROM visits WHERE ip_address = '$ipAddress'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // If IP exists, update the visit count
    $row = $result->fetch_assoc();
    $newVisitCount = $row['visit_count'] + 1;
    $updateSql = "UPDATE visits SET visit_count = $newVisitCount, url = '$url', city = '$city', state = '$state', country = '$country', visit_date = '$visitDate' WHERE ip_address = '$ipAddress'";
    if ($conn->query($updateSql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Visit count updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating visit count: ' . $conn->error]);
    }
} else {
    // If IP does not exist, insert a new record with visit_count set to 1
    $insertSql = "INSERT INTO visits (ip_address, url, city, state, country, visit_date, visit_count) VALUES ('$ipAddress', '$url', '$city', '$state', '$country', '$visitDate', 1)";
    if ($conn->query($insertSql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Visit logged successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error logging visit: ' . $conn->error]);
    }
}

// Close the database connection
$conn->close();
