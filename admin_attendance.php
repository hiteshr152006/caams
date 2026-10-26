<?php
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$message = "";
$message_type = "message";

// Get subjects from subjects table if available, else default list
$db_subjects = [];
$sub_query = mysqli_query($conn, "SELECT subject_name FROM subjects ORDER BY subject_name");
if ($sub_query && mysqli_num_rows($sub_query) > 0) {
    while ($s_row = mysqli_fetch_assoc($sub_query)) {
        $db_subjects[] = $s_row['subject_name'];
    }
}
if (empty($db_subjects)) {
    $db_subjects = [
        "Programming in C",
        "Database Management",
        "Web Development",
        "Data Structures",
        "Computer Networks"
    ];
}

// Retain selections from GET or POST
$selected_course  = isset($_REQUEST['course']) ? trim($_REQUEST['course']) : 'all';
$selected_sem     = isset($_REQUEST['sem']) ? trim($_REQUEST['sem']) : 'all';
$selected_section = isset($_REQUEST['section']) ? trim($_REQUEST['section']) : 'all';
$selected_date    = isset($_REQUEST['date']) ? trim($_REQUEST['date']) : date('Y-m-d');
$selected_subject = isset($_REQUEST['subject']) ? trim($_REQUEST['subject']) : '';

// When attendance form is submitted
if (isset($_POST['submit_attendance'])) {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $statuses = isset($_POST['attendance_status']) ? $_POST['attendance_status'] : [];

    $count_marked = 0;
    foreach ($statuses as $student_id => $status) {
        $safe_stu_id = mysqli_real_escape_string($conn, $student_id);
        $safe_status = ($status === 'Absent') ? 'Absent' : 'Present';

        // Check if attendance already exists for this student on this date and subject
        $check = mysqli_query($conn, "SELECT id FROM attendance WHERE student_id='$safe_stu_id' AND date='$date' AND subject='$subject'");
        
        if (mysqli_num_rows($check) == 0) {
            $sql = "INSERT INTO attendance (student_id, subject, date, status) 
                    VALUES ('$safe_stu_id', '$subject', '$date', '$safe_status')";
        } else {
            $sql = "UPDATE attendance SET status='$safe_status' 
                    WHERE student_id='$safe_stu_id' AND date='$date' AND subject='$subject'";
        }
        if (mysqli_query($conn, $sql)) {
            $count_marked++;
        }
    }
    $message = "Attendance saved successfully for $count_marked student(s) in $subject on $date.";
    $message_type = "message";
}

// Build query to fetch students matching selected Course, Sem, and Section
$where_clauses = ["status = 'Approved'"];

if ($selected_course !== 'all' && $selected_course !== '') {
    $safe_course = mysqli_real_escape_string($conn, $selected_course);
    $where_clauses[] = "course = '$safe_course'";
}

if ($selected_sem !== 'all' && $selected_sem !== '') {
    $safe_sem = (int)$selected_sem;
    $where_clauses[] = "semester = $safe_sem";
}

if ($selected_section === 'unassigned') {
    $where_clauses[] = "(section IS NULL OR section = '' OR section = 'Unassigned')";
} elseif ($selected_section !== 'all' && $selected_section !== '') {
    $safe_sec = mysqli_real_escape_string($conn, $selected_section);
    $where_clauses[] = "section = '$safe_sec'";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);
$students = mysqli_query($conn, "SELECT * FROM students $where_sql ORDER BY course ASC, semester ASC, section ASC, student_id ASC");

// Fetch existing attendance for the selected date and subject to pre-populate
$existing_attendance = [];
if (!empty($selected_date) && !empty($selected_subject)) {
    $safe_d = mysqli_real_escape_string($conn, $selected_date);
    $safe_s = mysqli_real_escape_string($conn, $selected_subject);
    $att_res = mysqli_query($conn, "SELECT student_id, status FROM attendance WHERE date='$safe_d' AND subject='$safe_s'");
    if ($att_res) {
        while ($att_row = mysqli_fetch_assoc($att_res)) {
            $existing_attendance[$att_row['student_id']] = $att_row['status'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - College System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: block;
            padding: 24px 20px;
            max-width: 100vw;
            box-sizing: border-box;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .header h1 {
            color: #00d4ff;
            font-size: 1.6rem;
        }
        .logout {
            background-color: #ff4d4d;
            color: white;
            padding: 9px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .menu {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 24px;
            background: var(--card-bg);
            padding: 8px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        .menu a {
            color: var(--text-muted);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .menu a:hover, .menu a.active {
            background: rgba(59, 130, 246, 0.15);
            color: #00d4ff;
        }
        .filter-panel {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .filter-panel h3 {
            color: #00d4ff;
            font-size: 1.1rem;
            margin-bottom: 16px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 14px;
            align-items: end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            margin-bottom: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
        }
        .filter-group select, .filter-group input {
            padding: 9px 12px;
            background: #090e17;
            border: 1px solid #334155;
            border-radius: 8px;
            color: white;
            font-size: 0.9rem;
            margin-bottom: 0;
            width: 100%;
        }
        .btn-load {
            background: #0284c7;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            height: 40px;
        }
        .btn-load:hover {
            background: #0369a1;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
            background: var(--card-bg);
        }
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        th {
            background: #111827;
            color: #38bdf8;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Toggle switch */
        .status-toggle-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .status-pill {
            display: inline-flex;
            border-radius: 20px;
            background: #0f172a;
            border: 1px solid #334155;
            padding: 3px;
        }
        .status-pill label {
            margin: 0;
            padding: 5px 14px;
            border-radius: 16px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
            color: #94a3b8;
            transition: all 0.2s;
        }
        .status-pill input[type="radio"] {
            display: none;
        }
        .status-pill input[type="radio"]:checked + span {
            background: #10b981;
            color: white;
            padding: 5px 14px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
        }
        .status-pill input[type="radio"].radio-absent:checked + span {
            background: #ef4444;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        }

        .badge-sec {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .badge-sec-a { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        .badge-sec-b { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
        .badge-sec-c { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
        .badge-sec-d { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .badge-sec-none { background: rgba(239, 68, 68, 0.15); color: #f87171; }

        .btn-submit-att {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit-att:hover {
            background: #059669;
        }
        .quick-actions-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-small-action {
            background: #1e293b;
            color: #38bdf8;
            border: 1px solid #334155;
            padding: 5px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .btn-small-action:hover {
            background: #0284c7;
            color: white;
        }

        @media (max-width: 768px) {
            body { padding: 12px 10px !important; }
            .header h1 { font-size: 1.25rem !important; }
            .filter-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Mark Attendance</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="menu">
         <a href="admin_dashboard.php">Admission Requests</a>
         <a href="admin_sections.php">Section Allotment</a>
         <a href="admin_attendance.php" class="active" style="color: #00d4ff; font-weight: bold;">Attendance</a>
         <a href="admin_marks.php">Marks</a>
         <a href="admin_timetable.php">Timetable</a>
         <a href="admin_materials.php">Study Materials</a>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="<?php echo $message_type === 'error' ? 'error' : 'message'; ?>" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <!-- 1. Filter by Course, Semester, Section, Subject, Date -->
    <div class="filter-panel">
        <h3>📅 Select Class & Subject</h3>
        <form method="GET" action="admin_attendance.php" id="attendanceFilterForm">
            <div class="filter-grid">
                <!-- Course -->
                <div class="filter-group">
                    <label>Course</label>
                    <select name="course" onchange="document.getElementById('attendanceFilterForm').submit()">
                        <option value="all" <?php if ($selected_course === 'all') echo 'selected'; ?>>All Courses</option>
                        <option value="BCA" <?php if ($selected_course === 'BCA') echo 'selected'; ?>>BCA</option>
                        <option value="BBA" <?php if ($selected_course === 'BBA') echo 'selected'; ?>>BBA</option>
                        <option value="BSc" <?php if ($selected_course === 'BSc') echo 'selected'; ?>>BSc</option>
                        <option value="BA" <?php if ($selected_course === 'BA') echo 'selected'; ?>>BA</option>
                    </select>
                </div>

                <!-- Semester -->
                <div class="filter-group">
                    <label>Semester</label>
                    <select name="sem" onchange="document.getElementById('attendanceFilterForm').submit()">
                        <option value="all" <?php if ($selected_sem === 'all') echo 'selected'; ?>>All Semesters</option>
                        <?php for ($s = 1; $s <= 8; $s++) { ?>
                            <option value="<?php echo $s; ?>" <?php if ($selected_sem === (string)$s) echo 'selected'; ?>>
                                Semester <?php echo $s; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Section -->
                <div class="filter-group">
                    <label>Section</label>
                    <select name="section" onchange="document.getElementById('attendanceFilterForm').submit()">
                        <option value="all" <?php if ($selected_section === 'all') echo 'selected'; ?>>All Sections</option>
                        <option value="A" <?php if ($selected_section === 'A') echo 'selected'; ?>>Section A</option>
                        <option value="B" <?php if ($selected_section === 'B') echo 'selected'; ?>>Section B</option>
                        <option value="C" <?php if ($selected_section === 'C') echo 'selected'; ?>>Section C</option>
                        <option value="D" <?php if ($selected_section === 'D') echo 'selected'; ?>>Section D</option>
                        <option value="unassigned" <?php if ($selected_section === 'unassigned') echo 'selected'; ?>>Not Allotted</option>
                    </select>
                </div>

                <!-- Subject -->
                <div class="filter-group">
                    <label>Subject</label>
                    <select name="subject" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($db_subjects as $subj) { ?>
                            <option value="<?php echo htmlspecialchars($subj); ?>" <?php if ($selected_subject === $subj) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($subj); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Date -->
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" required>
                </div>

                <!-- Action -->
                <div>
                    <button type="submit" class="btn-load">Load Students</button>
                </div>
            </div>
        </form>
    </div>

    <!-- 2. Attendance Table -->
    <?php 
    $total_students = mysqli_num_rows($students);
    if ($total_students > 0) {
    ?>
        <form method="POST" action="admin_attendance.php?course=<?php echo urlencode($selected_course); ?>&sem=<?php echo urlencode($selected_sem); ?>&section=<?php echo urlencode($selected_section); ?>&date=<?php echo urlencode($selected_date); ?>&subject=<?php echo urlencode($selected_subject); ?>">
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
            <input type="hidden" name="subject" value="<?php echo htmlspecialchars($selected_subject); ?>">

            <div class="quick-actions-bar">
                <span style="color: #94a3b8; font-size: 0.88rem; font-weight: 500;">
                    Showing <strong><?php echo $total_students; ?></strong> student(s) for 
                    <strong><?php echo ($selected_course !== 'all') ? htmlspecialchars($selected_course) : 'All Courses'; ?></strong>
                    <?php if ($selected_sem !== 'all') echo ' • Sem ' . htmlspecialchars($selected_sem); ?>
                    <?php if ($selected_section !== 'all') echo ' • Section ' . htmlspecialchars($selected_section); ?>
                </span>
                <span style="flex: 1;"></span>
                <button type="button" class="btn-small-action" onclick="setAllAttendance('Present')">Mark All Present</button>
                <button type="button" class="btn-small-action" onclick="setAllAttendance('Absent')">Mark All Absent</button>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Semester</th>
                            <th>Section</th>
                            <th style="min-width: 200px;">Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        while ($row = mysqli_fetch_assoc($students)) { 
                            $stu_id = $row['student_id'] ?: 'ID_' . $row['id'];
                            $sec = !empty($row['section']) ? $row['section'] : '';
                            
                            // Check existing attendance or default to Present
                            $current_status = isset($existing_attendance[$stu_id]) ? $existing_attendance[$stu_id] : 'Present';
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: #38bdf8;"><?php echo htmlspecialchars($stu_id); ?></strong>
                                </td>
                                <td>
                                    <strong style="color: white;"><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($row['course']); ?></td>
                                <td>Sem <?php echo htmlspecialchars($row['semester']); ?></td>
                                <td>
                                    <?php if ($sec === 'A') { ?>
                                        <span class="badge-sec badge-sec-a">Sec A</span>
                                    <?php } elseif ($sec === 'B') { ?>
                                        <span class="badge-sec badge-sec-b">Sec B</span>
                                    <?php } elseif ($sec === 'C') { ?>
                                        <span class="badge-sec badge-sec-c">Sec C</span>
                                    <?php } elseif ($sec === 'D') { ?>
                                        <span class="badge-sec badge-sec-d">Sec D</span>
                                    <?php } else { ?>
                                        <span class="badge-sec badge-sec-none">Unassigned</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div class="status-pill">
                                        <label>
                                            <input type="radio" 
                                                   name="attendance_status[<?php echo htmlspecialchars($stu_id); ?>]" 
                                                   value="Present" 
                                                   class="att-radio-present"
                                                   <?php if ($current_status === 'Present') echo 'checked'; ?>>
                                            <span>Present</span>
                                        </label>
                                        <label>
                                            <input type="radio" 
                                                   name="attendance_status[<?php echo htmlspecialchars($stu_id); ?>]" 
                                                   value="Absent" 
                                                   class="att-radio-absent radio-absent"
                                                   <?php if ($current_status === 'Absent') echo 'checked'; ?>>
                                            <span>Absent</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 15px;">
                <button type="submit" name="submit_attendance" class="btn-submit-att">
                    💾 Save Attendance Records
                </button>
            </div>
        </form>
    <?php } else { ?>
        <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 40px; text-align: center; color: #94a3b8;">
            <div style="font-size: 2rem; margin-bottom: 8px;">📋</div>
            <h3 style="color: white; margin-bottom: 6px;">No Students Found</h3>
            <p style="font-size: 0.9rem;">No approved students found for the selected Course, Semester, or Section.</p>
        </div>
    <?php } ?>

    <script>
        function setAllAttendance(status) {
            if (status === 'Present') {
                document.querySelectorAll('.att-radio-present').forEach(r => r.checked = true);
            } else {
                document.querySelectorAll('.att-radio-absent').forEach(r => r.checked = true);
            }
        }
    </script>
</body>
</html>