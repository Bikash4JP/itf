<?php
session_start();
if (!isset($_SESSION['application_id'])) {
    header("Location: ../index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ITF";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $visa_status = $_POST['visa_status'];
    $visa_expiry = $_POST['visa_expiry'];
    $passport_number = $_POST['passport_number'];
    $application_id = $_SESSION['application_id'];
    
    $sql = "UPDATE applications 
            SET visa_status='$visa_status', visa_expiry='$visa_expiry', passport_number='$passport_number' 
            WHERE id = $application_id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: ../part3.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>