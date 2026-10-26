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

// Fetch timetable
$result = mysqli_query($conn, "SELECT * FROM timetable ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), time_slot");

// Organize data
$timetable = [];
while ($row = mysqli_fetch_assoc($result)) {
    $timetable[$row['day']][] = $row;
}

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Timetable</title>
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
        .day-section {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .day-section h2 {
            color: #00d4ff;
            margin-bottom: 15px;
            border-bottom: 1px solid #333;
            padding-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            color: #aaa;
        }
        .no-data {
            color: #888;
            margin-top: 20px;
        }
        .break {
            color: #ff9800;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Class Timetable</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="menu">
        <a href="student_dashboard.php">My Profile</a>
        <a href="student_attendance.php">My Attendance</a>
        <a href="student_marks.php">My Marks</a>
        <a href="student_timetable.php">Timetable</a>
        <a href="student_material.php">Study Materials</a>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 10px;">
        <h2 style="color:#00d4ff; margin:0; font-size:1.35rem;">Weekly Schedule</h2>
        <div style="background: #1e293b; border: 1px solid #334155; padding: 6px 14px; border-radius: 8px; font-size: 0.88rem; color: #94a3b8;">
            Class: <strong style="color: white;"><?php echo htmlspecialchars($stu_info['course']); ?> • Sem <?php echo htmlspecialchars($stu_info['semester']); ?></strong>
            &bull; Section: 
            <strong style="color: #38bdf8;">
                <?php echo !empty($stu_info['section']) ? 'Section ' . htmlspecialchars($stu_info['section']) : 'Not Allotted'; ?>
            </strong>
        </div>
    </div>

    <?php if (empty($timetable)) { ?>
        <p class="no-data">Timetable has not been set yet.</p>
    <?php } else { ?>
        <?php foreach ($days as $day) { 
            if (!isset($timetable[$day])) continue;
        ?>
            <div class="day-section">
                <h2><?php echo $day; ?></h2>
                <table>
                    <tr>
                        <th>Time</th>
                        <th>Subject</th>
                        <th>Teacher</th>
                    </tr>
                    <?php foreach ($timetable[$day] as $class) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['time_slot']); ?></td>
                            <td class="<?php echo ($class['subject'] == 'Break') ? 'break' : ''; ?>">
                                <?php echo htmlspecialchars($class['subject']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($class['teacher']); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } ?>
    <?php } ?>
</body>
</html>