<?php
// Debugging: Display the contents of the $_POST array 
echo "<pre>"; 
print_r($_POST); 
echo "</pre>";

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['reservation_date'], $_POST['reservation_time'], $_POST['adult_count'], $_POST['child_count'])) {
        $reservation_date = $conn->real_escape_string($_POST['reservation_date']);
        $reservation_time = $conn->real_escape_string($_POST['reservation_time']);
        $adult_count = (int)$_POST['adult_count'];
        $child_count = (int)$_POST['child_count'];
        
        $stmt = $conn->prepare("INSERT INTO reservations (reservation_date, reservation_time, adult_count, child_count) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $reservation_date, $reservation_time, $adult_count, $child_count);
        
        if ($stmt->execute()) {
            echo "Reservation successfully created!";
        } else {
            echo "Error: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        echo "Error: Missing form data.";
    }
}

$conn->close();
?>