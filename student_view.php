<?php
include 'config.php';

$student_id = trim($_GET['id'] ?? '');
if ($student_id === '') {
    http_response_code(400);
    exit('A student ID is required.');
}

$stmt = $conn->prepare("SELECT student_id, full_name, course, semester, section, photo FROM students WHERE student_id = ? AND status = 'Approved' LIMIT 1");
$stmt->bind_param('s', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    http_response_code(404);
    exit('Student record not found.');
}

$photo_path = $student['photo'] ?? '';
$has_photo = $photo_path !== '' && is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $photo_path));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID - <?php echo htmlspecialchars($student['student_id']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="student-view-page">
    <main class="student-view-card">
        <p class="student-view-label">College Admission &amp; Academic Management System</p>
        <?php if ($has_photo) { ?>
            <img class="student-view-photo" src="<?php echo htmlspecialchars($photo_path); ?>" alt="Student photo">
        <?php } else { ?>
            <div class="student-view-photo student-view-placeholder" aria-hidden="true">CAAMS</div>
        <?php } ?>
        <h1><?php echo htmlspecialchars($student['full_name']); ?></h1>
        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
        <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?></p>
        <p><strong>Semester:</strong> <?php echo htmlspecialchars((string)($student['semester'] ?: 1)); ?></p>
        <p><strong>Section:</strong> <?php echo htmlspecialchars($student['section'] ?: 'Not allotted'); ?></p>
        <p class="student-view-note">This digital ID displays public academic details only.</p>
    </main>
</body>
</html>
