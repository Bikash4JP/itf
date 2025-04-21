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
    $work_experience = $_POST['work_experience'];
    $application_id = $_SESSION['application_id'];
    
    $sql = "UPDATE applications 
            SET work_experience='$work_experience' 
            WHERE id = $application_id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: ../part5.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>