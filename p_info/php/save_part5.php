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
    $self_pr = $_POST['self_pr'];
    $motivation = $_POST['motivation'];
    $application_id = $_SESSION['application_id'];
    
    $sql = "UPDATE applications 
            SET self_pr='$self_pr', motivation='$motivation' 
            WHERE id = $application_id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: ../submit.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>