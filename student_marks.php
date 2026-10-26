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

// Fetch marks
$query = "SELECT * FROM marks WHERE student_id = '$student_id' ORDER BY subject";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Marks</title>
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
        .no-data {
            color: #888;
            margin-top: 30px;
        }
        .total {
            font-weight: bold;
            color: #00d4ff;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>My Marks</h1>
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
        <h2 style="color:#ccc; margin:0;">Internal Marks</h2>
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
                <th>IA Marks</th>
                <th>Lab Marks</th>
                <th>Sessional Marks</th>
                <th>Total</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)) { 
                $total = $row['ia_marks'] + $row['lab_marks'] + $row['sessional_marks'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                    <td><?php echo $row['ia_marks']; ?></td>
                    <td><?php echo $row['lab_marks']; ?></td>
                    <td><?php echo $row['sessional_marks']; ?></td>
                    <td class="total"><?php echo $total; ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p class="no-data">No marks have been entered yet.</p>
    <?php } ?>
</body>
</html>