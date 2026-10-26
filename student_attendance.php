<?php
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$name = $_SESSION['student_name'];

$stu_res = mysqli_query($conn, "SELECT course, semester, section FROM students WHERE student_id = '$student_id'");
$stu_info = $stu_res ? mysqli_fetch_assoc($stu_res) : ['course' => '', 'semester' => 1, 'section' => ''];

// Fetch attendance records
$query = "SELECT subject, 
                 COUNT(*) as total_classes,
                 SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count
          FROM attendance 
          WHERE student_id = '$student_id'
          GROUP BY subject";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance</title>
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
        .good {
            color: #00c853;
            font-weight: bold;
        }
        .warning {
            color: #ff4d4d;
            font-weight: bold;
        }
        .no-data {
            color: #888;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>My Attendance</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="menu">
        <a href="student_dashboard.php">My Profile</a>
        <a href="student_attendance.php">My Attendance</a>
        <a href="student_marks.php">My Marks</a>
        <a href="student_timetable.php">Timetable</a>
        <a href="student_material.php">Study Materials</a>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h2 style="color:#ccc; margin:0;">Attendance Summary</h2>
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
                <th>Subject</th>
                <th>Total Classes</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Percentage</th>
                <th>Status</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)) { 
                $total = $row['total_classes'];
                $present = $row['present_count'];
                $absent = $total - $present;
                $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;
                $status_class = $percentage >= 75 ? "good" : "warning";
                $status_text = $percentage >= 75 ? "Good" : "Shortage (Below 75%)";
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                    <td><?php echo $total; ?></td>
                    <td><?php echo $present; ?></td>
                    <td><?php echo $absent; ?></td>
                    <td><?php echo $percentage; ?>%</td>
                    <td class="<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p class="no-data">No attendance records found yet.</p>
    <?php } ?>
</body>
</html>