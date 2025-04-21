<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ITF";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $nationality = $_POST['nationality'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    
    $sql = "INSERT INTO applications (name, dob, nationality, email, gender, created_at) 
            VALUES ('$name', '$dob', '$nationality', '$email', '$gender', NOW())";
    
    if ($conn->query($sql) === TRUE) {
        $_SESSION['application_id'] = $conn->insert_id;
        header("Location: ../part2.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>