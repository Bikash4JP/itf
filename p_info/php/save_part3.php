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
    $high_school_name = $_POST['high_school_name'] ?? '';
    $high_school_major = $_POST['high_school_major'] ?? '';
    $high_school_status = $_POST['high_school_status'] ?? '';
    $high_school_joining = $_POST['high_school_joining'] ?? '';
    $high_school_graduated = ($high_school_status === 'Graduated') ? ($_POST['high_school_graduated'] ?? '') : '';

    $vocational_name = $_POST['vocational_name'] ?? '';
    $vocational_major = $_POST['vocational_major'] ?? '';
    $vocational_status = $_POST['vocational_status'] ?? '';
    $vocational_joining = $_POST['vocational_joining'] ?? '';
    $vocational_graduated = ($vocational_status === 'Graduated') ? ($_POST['vocational_graduated'] ?? '') : '';

    $university_name = $_POST['university_name'] ?? '';
    $university_major = $_POST['university_major'] ?? '';
    $university_status = $_POST['university_status'] ?? '';
    $university_joining = $_POST['university_joining'] ?? '';
    $university_graduated = ($university_status === 'Graduated') ? ($_POST['university_graduated'] ?? '') : '';

    $jlpt_level = $_POST['jlpt_level'] ?? '';

    // Combine education details into a JSON string
    $education_details = json_encode([
        'high_school' => [
            'name' => $high_school_name,
            'major' => $high_school_major,
            'status' => $high_school_status,
            'joining' => $high_school_joining,
            'graduated' => $high_school_graduated
        ],
        'vocational' => [
            'name' => $vocational_name,
            'major' => $vocational_major,
            'status' => $vocational_status,
            'joining' => $vocational_joining,
            'graduated' => $vocational_graduated
        ],
        'university' => [
            'name' => $university_name,
            'major' => $university_major,
            'status' => $university_status,
            'joining' => $university_joining,
            'graduated' => $university_graduated
        ],
        'additional' => []
    ]);

    // Handle additional education entries
    $additional_education = [];
    if (isset($_POST['additional_institution'])) {
        $institutions = $_POST['additional_institution'];
        $majors = $_POST['additional_major'];
        $statuses = $_POST['additional_status'];
        $joinings = $_POST['additional_joining'];
        $graduateds = $_POST['additional_graduated'];

        for ($i = 0; $i < count($institutions); $i++) {
            $graduated_date = ($statuses[$i] === 'Graduated') ? ($graduateds[$i] ?? '') : '';
            $additional_education[] = [
                'institution' => $institutions[$i] ?? '',
                'major' => $majors[$i] ?? '',
                'status' => $statuses[$i] ?? '',
                'joining' => $joinings[$i] ?? '',
                'graduated' => $graduated_date
            ];
        }
    }
    $education_details = json_decode($education_details, true);
    $education_details['additional'] = $additional_education;
    $education_details = json_encode($education_details);

    $application_id = $_SESSION['application_id'];
    
    $sql = "UPDATE applications 
            SET education='$education_details', jlpt_level='$jlpt_level' 
            WHERE id = $application_id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: ../part4.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>