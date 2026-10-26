<?php
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$message = "";

// ========== CREATE TABLES IF NOT EXISTS ==========
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_name VARCHAR(50) NOT NULL
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_name VARCHAR(100) NOT NULL
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL
)");

// ========== ADD TIME SLOT ==========
if (isset($_POST['add_slot'])) {
    $slot = trim($_POST['new_slot']);
    if ($slot != "") {
        mysqli_query($conn, "INSERT INTO time_slots (slot_name) VALUES ('$slot')");
        $message = "Time slot added!";
    }
}

// ========== DELETE TIME SLOT ==========
if (isset($_GET['delete_slot'])) {
    $id = intval($_GET['delete_slot']);
    mysqli_query($conn, "DELETE FROM time_slots WHERE id = $id");
    header("Location: admin_timetable.php");
    exit();
}

// ========== ADD TEACHER ==========
if (isset($_POST['add_teacher'])) {
    $teacher = trim($_POST['new_teacher']);
    if ($teacher != "") {
        mysqli_query($conn, "INSERT INTO teachers (teacher_name) VALUES ('$teacher')");
        $message = "Teacher added!";
    }
}

// ========== DELETE TEACHER ==========
if (isset($_GET['delete_teacher'])) {
    $id = intval($_GET['delete_teacher']);
    mysqli_query($conn, "DELETE FROM teachers WHERE id = $id");
    header("Location: admin_timetable.php");
    exit();
}

// ========== ADD SUBJECT ==========
if (isset($_POST['add_subject'])) {
    $subject = trim($_POST['new_subject']);
    if ($subject != "") {
        mysqli_query($conn, "INSERT INTO subjects (subject_name) VALUES ('$subject')");
        $message = "Subject added!";
    }
}

// ========== DELETE SUBJECT ==========
if (isset($_GET['delete_subject'])) {
    $id = intval($_GET['delete_subject']);
    mysqli_query($conn, "DELETE FROM subjects WHERE id = $id");
    header("Location: admin_timetable.php");
    exit();
}

// ========== SAVE TIMETABLE ==========
if (isset($_POST['save_timetable'])) {
    mysqli_query($conn, "DELETE FROM timetable");

    $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    $slots_result = mysqli_query($conn, "SELECT * FROM time_slots ORDER BY id");

    while ($slot_row = mysqli_fetch_assoc($slots_result)) {
        $slot = $slot_row['slot_name'];
        foreach ($days as $day) {
            $key = $day . "_" . $slot_row['id'];
            $subject = $_POST['subject'][$key] ?? '';
            $teacher = $_POST['teacher'][$key] ?? '';

            if ($subject != "") {
                mysqli_query($conn, "INSERT INTO timetable (day, time_slot, subject, teacher) 
                                     VALUES ('$day', '$slot', '$subject', '$teacher')");
            }
        }
    }
    $message = "Timetable saved successfully!";
}

// Fetch data
$slots = mysqli_query($conn, "SELECT * FROM time_slots ORDER BY id");
$teachers_list = mysqli_query($conn, "SELECT * FROM teachers ORDER BY teacher_name");
$subjects_list = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name");

// Existing timetable
$existing = [];
$res = mysqli_query($conn, "SELECT * FROM timetable");
while ($row = mysqli_fetch_assoc($res)) {
    $existing[$row['day'] . "_" . $row['time_slot']] = $row;
}

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Timetable</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: block; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { color: #00d4ff; }
        .logout { background-color: #ff4d4d; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; }
        .menu { margin-bottom: 30px; }
        .menu a { color: #00d4ff; margin-right: 20px; text-decoration: none; font-weight: bold; }
        .section { background-color: #1a1a1a; padding: 20px; border-radius: 12px; border: 1px solid #333; margin-bottom: 25px; }
        .section h3 { color: #00d4ff; margin-bottom: 15px; }
        input, select { padding: 8px; background-color: #0f0f0f; border: 1px solid #444; border-radius: 6px; color: white; margin-right: 10px; }
        button { padding: 8px 16px; background-color: #00d4ff; color: #000; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .delete-btn { background-color: #ff4d4d; color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 0.85rem; }
        table { width: 100%; border-collapse: collapse; background-color: #1a1a1a; font-size: 0.9rem; }
        th, td { padding: 8px; border: 1px solid #333; text-align: center; }
        th { background-color: #111; color: #00d4ff; }
        .message { background-color: #003322; color: #00ff88; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .flex { display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
        .item-list { margin-top: 12px; }
        .item-list div { margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Flexible Timetable</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="menu">
        <a href="admin_dashboard.php">Admission Requests</a>
        <a href="admin_sections.php">Section Allotment</a>
        <a href="admin_attendance.php">Attendance</a>
        <a href="admin_marks.php">Marks</a>
        <a href="admin_timetable.php" class="active" style="color: #00d4ff; font-weight: bold;">Timetable</a>
        <a href="admin_materials.php">Study Materials</a>
    </div>

    <?php if ($message) { ?>
        <div class="message"><?php echo $message; ?></div>
    <?php } ?>

    <!-- 1. Manage Time Slots -->
    <div class="section">
        <h3>1. Manage Time Slots (Periods)</h3>
        <form method="POST" class="flex">
            <input type="text" name="new_slot" placeholder="Example: 09:00 AM - 10:00 AM" required>
            <button type="submit" name="add_slot">Add Time Slot</button>
        </form>
        <div class="item-list">
            <?php 
            mysqli_data_seek($slots, 0);
            while ($s = mysqli_fetch_assoc($slots)) { ?>
                <div>
                    <?php echo htmlspecialchars($s['slot_name']); ?>
                    <a href="?delete_slot=<?php echo $s['id']; ?>" class="delete-btn" onclick="return confirm('Delete this time slot?')">Delete</a>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- 2. Manage Teachers -->
    <div class="section">
        <h3>2. Manage Teachers</h3>
        <form method="POST" class="flex">
            <input type="text" name="new_teacher" placeholder="Teacher Name" required>
            <button type="submit" name="add_teacher">Add Teacher</button>
        </form>
        <div class="item-list">
            <?php 
            mysqli_data_seek($teachers_list, 0);
            while ($t = mysqli_fetch_assoc($teachers_list)) { ?>
                <div>
                    <?php echo htmlspecialchars($t['teacher_name']); ?>
                    <a href="?delete_teacher=<?php echo $t['id']; ?>" class="delete-btn" onclick="return confirm('Delete this teacher?')">Delete</a>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- 3. Manage Subjects -->
    <div class="section">
        <h3>3. Manage Subjects</h3>
        <form method="POST" class="flex">
            <input type="text" name="new_subject" placeholder="Subject Name (e.g. Break, Web Development)" required>
            <button type="submit" name="add_subject">Add Subject</button>
        </form>
        <div class="item-list">
            <?php 
            mysqli_data_seek($subjects_list, 0);
            while ($sub = mysqli_fetch_assoc($subjects_list)) { ?>
                <div>
                    <?php echo htmlspecialchars($sub['subject_name']); ?>
                    <a href="?delete_subject=<?php echo $sub['id']; ?>" class="delete-btn" onclick="return confirm('Delete this subject?')">Delete</a>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- 4. Weekly Grid -->
    <div class="section">
        <h3>4. Weekly Timetable Grid</h3>
        <form method="POST">
            <table>
                <tr>
                    <th>Time / Day</th>
                    <?php foreach ($days as $day) { ?>
                        <th><?php echo $day; ?></th>
                    <?php } ?>
                </tr>

                <?php 
                mysqli_data_seek($slots, 0);
                while ($slot_row = mysqli_fetch_assoc($slots)) { 
                    $slot = $slot_row['slot_name'];
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($slot); ?></strong></td>
                        <?php foreach ($days as $day) { 
                            $key = $day . "_" . $slot_row['id'];
                            $current = $existing[$day . "_" . $slot] ?? null;
                        ?>
                            <td>
                                <select name="subject[<?php echo $key; ?>]">
                                    <option value="">-- Subject --</option>
                                    <?php 
                                    mysqli_data_seek($subjects_list, 0);
                                    while ($sub = mysqli_fetch_assoc($subjects_list)) { ?>
                                        <option value="<?php echo htmlspecialchars($sub['subject_name']); ?>" 
                                            <?php if($current && $current['subject'] == $sub['subject_name']) echo "selected"; ?>>
                                            <?php echo htmlspecialchars($sub['subject_name']); ?>
                                        </option>
                                    <?php } ?>
                                </select>

                                <select name="teacher[<?php echo $key; ?>]">
                                    <option value="">-- Teacher --</option>
                                    <?php 
                                    mysqli_data_seek($teachers_list, 0);
                                    while ($t = mysqli_fetch_assoc($teachers_list)) { ?>
                                        <option value="<?php echo htmlspecialchars($t['teacher_name']); ?>" 
                                            <?php if($current && $current['teacher'] == $t['teacher_name']) echo "selected"; ?>>
                                            <?php echo htmlspecialchars($t['teacher_name']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </table>
            <br>
            <button type="submit" name="save_timetable">Save Full Timetable</button>
        </form>
    </div>
</body>
</html>