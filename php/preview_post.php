<?php
session_start();
header("Content-Type: text/html");

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo '<p>無効なリクエストです。</p>';
    exit;
}

$form_type = $_POST['form_type'] ?? '';
$title = htmlspecialchars($_POST['title'] ?? '');
$summary = htmlspecialchars($_POST['summary'] ?? '');
$category = htmlspecialchars($_POST['category'] ?? '');
$post_type = htmlspecialchars($_POST['post_type'] ?? '');
$date = htmlspecialchars($_POST['date'] ?? date('Y-m-d'));
$posted_by = htmlspecialchars($_POST['posted_by'] ?? '');
$image_preview = $_POST['image_preview'] ?? '';

$company_name = htmlspecialchars($_POST['company_name'] ?? '');
$job_location = htmlspecialchars($_POST['job_location'] ?? '');
$job_category = htmlspecialchars($_POST['job_category'] ?? '');
$job_type = htmlspecialchars($_POST['job_type'] ?? '');
$salary = htmlspecialchars($_POST['salary'] ?? '');
$bonuses = isset($_POST['bonuses']) && $_POST['bonuses'] == '1' ? 'Yes' : 'No';
$bonus_amount = htmlspecialchars($_POST['bonus_amount'] ?? '');
$living_support = isset($_POST['living_support']) && $_POST['living_support'] == '1' ? 'Yes' : 'No';
$rent_support = htmlspecialchars($_POST['rent_support'] ?? '');
$insurance = isset($_POST['insurance']) && $_POST['insurance'] == '1' ? 'Yes' : 'No';
$transportation_charges = isset($_POST['transportation_charges']) && $_POST['transportation_charges'] == '1' ? 'Yes' : 'No';
$transport_amount_limit = htmlspecialchars($_POST['transport_amount_limit'] ?? '');
$salary_increment = isset($_POST['salary_increment']) && $_POST['salary_increment'] == '1' ? 'Yes' : 'No';
$increment_condition = htmlspecialchars($_POST['increment_condition'] ?? '');
$japanese_level = htmlspecialchars($_POST['japanese_level'] ?? '');
$experience = htmlspecialchars($_POST['experience'] ?? '');
$minimum_leave_per_year = htmlspecialchars($_POST['minimum_leave_per_year'] ?? '');
$employee_size = htmlspecialchars($_POST['employee_size'] ?? '');
$required_vacancy = htmlspecialchars($_POST['required_vacancy'] ?? '');

echo '<div class="post">';
if ($image_preview) {
    echo '<div class="post-image"><img src="' . $image_preview . '" alt="Preview Image"></div>';
} else {
    echo '<div class="post-image">No Image</div>';
}
echo '<div class="post-content">';
echo '<p class="meta"><em>' . $date . ', ' . $post_type . ', Posted By: ' . $posted_by . '</em></p>';
echo '<p class="title"><a href="#">' . $title . '</a></p>';

if ($form_type === 'posts') {
    echo '<p>Category: ' . $category . '</p>';
    echo '<p>' . nl2br($summary) . '</p>';
} elseif ($form_type === 'jobs') {
    echo '<p>Company: ' . $company_name . '</p>';
    echo '<p>Location: ' . $job_location . '</p>';
    echo '<p>Job Category: ' . $job_category . '</p>';
    echo '<p>Job Type: ' . $job_type . '</p>';
    echo '<p>Salary: ' . ($salary ?: 'Not specified') . '</p>';
    echo '<p>Bonuses: ' . $bonuses . ($bonus_amount ? ' (' . $bonus_amount . ')' : '') . '</p>';
    echo '<p>Living Support: ' . $living_support . ($rent_support ? ' (Rent: ' . $rent_support . ')' : '') . '</p>';
    echo '<p>Insurance: ' . $insurance . '</p>';
    echo '<p>Transportation Charges: ' . $transportation_charges . ($transport_amount_limit ? ' (' . $transport_amount_limit . '/month)' : '') . '</p>';
    echo '<p>Salary Increment: ' . $salary_increment . ($increment_condition ? ' (' . $increment_condition . ')' : '') . '</p>';
    echo '<p>Japanese Level: ' . $japanese_level . '</p>';
    echo '<p>Experience: ' . ($experience ?: 'Not specified') . '</p>';
    echo '<p>Minimum Leave: ' . ($minimum_leave_per_year ?: 'Not specified') . ' days/year</p>';
    echo '<p>Employee Size: ' . ($employee_size ?: 'Not specified') . '</p>';
    echo '<p>Required Vacancy: ' . ($required_vacancy ?: 'Not specified') . '</p>';
    if ($summary) {
        echo '<p>Description: ' . nl2br($summary) . '</p>';
    }
}
echo '</div></div>';
?>