<?php
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

$student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : '';
$name = $_SESSION['student_name'];

$stu_res = mysqli_query($conn, "SELECT course, semester, section FROM students WHERE student_id = '$student_id'");
$stu_info = $stu_res ? mysqli_fetch_assoc($stu_res) : ['course' => '', 'semester' => 1, 'section' => ''];

// Fetch all study materials
$result = mysqli_query($conn, "SELECT * FROM study_materials ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Materials</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: block;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #00d4ff;
        }
        .logout {
            background-color: #ff4d4d;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        .menu {
            margin-bottom: 30px;
        }
        .menu a {
            color: #00d4ff;
            margin-right: 25px;
            text-decoration: none;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            background-color: #111;
            color: #00d4ff;
        }
        .download-btn {
            background-color: #00c853;
            color: white;
            padding: 7px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .no-data {
            color: #888;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Study Materials</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="menu">
        <a href="student_dashboard.php">My Profile</a>
        <a href="student_attendance.php">My Attendance</a>
        <a href="student_marks.php">My Marks</a>
        <a href="student_timetable.php">Timetable</a>
        <a href="student_materials.php">Study Materials</a>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h2 style="color:#ccc; margin:0;">Available Notes & Important Questions</h2>
        <div style="background: #1e293b; border: 1px solid #334155; padding: 6px 14px; border-radius: 8px; font-size: 0.88rem; color: #94a3b8;">
            Class: <strong style="color: white;"><?php echo htmlspecialchars($stu_info['course']); ?> • Sem <?php echo htmlspecialchars($stu_info['semester']); ?></strong>
            &bull; Section: 
            <strong style="color: #38bdf8;">
                <?php echo !empty($stu_info['section']) ? 'Section ' . htmlspecialchars($stu_info['section']) : 'Not Allotted'; ?>
            </strong>
        </div>
    </div>

    <?php if (mysqli_num_rows($result) > 0) { ?>
        <table>
            <tr>
                <th>Title</th>
                <th>Subject</th>
                <th>Uploaded On</th>
                <th>Action</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                    <td><?php echo date("d M Y", strtotime($row['uploaded_at'])); ?></td>
                    <td>
                        <a href="<?php echo $row['file_path']; ?>" class="download-btn" target="_blank">
                            View / Download PDF
                        </a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p class="no-data">No study materials have been uploaded yet.</p>
    <?php } ?>
</body>
</html>