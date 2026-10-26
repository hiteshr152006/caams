<?php
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$message = "";
$message_type = "message";

// Handle single student section & semester update
if (isset($_POST['update_single_section'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = "Invalid security token. Please refresh the page and try again.";
        $message_type = "error";
    } else {
        $student_id = (int)$_POST['student_db_id'];
        $new_section = trim($_POST['section']);
        $new_semester = isset($_POST['semester']) ? (int)$_POST['semester'] : 1;

        $section_val = ($new_section === "" || $new_section === "Unassigned") ? "NULL" : "'" . mysqli_real_escape_string($conn, $new_section) . "'";
        
        $sql = "UPDATE students SET section = $section_val, semester = $new_semester WHERE id = $student_id";
        if (mysqli_query($conn, $sql)) {
            $message = "Section updated successfully for student!";
            $message_type = "message";
        } else {
            $message = "Error updating section: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// Handle bulk section allotment
if (isset($_POST['bulk_allot_section'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = "Invalid security token. Please refresh the page and try again.";
        $message_type = "error";
    } else {
        $selected_students = isset($_POST['selected_students']) ? $_POST['selected_students'] : [];
        $bulk_section = trim($_POST['bulk_section']);

        if (!empty($selected_students)) {
            $ids = array_map('intval', $selected_students);
            $ids_list = implode(',', $ids);
            
            $section_val = ($bulk_section === "" || $bulk_section === "Unassigned") ? "NULL" : "'" . mysqli_real_escape_string($conn, $bulk_section) . "'";
            $sql = "UPDATE students SET section = $section_val WHERE id IN ($ids_list)";
            
            if (mysqli_query($conn, $sql)) {
                $count = count($ids);
                $target_name = ($bulk_section === "" || $bulk_section === "Unassigned") ? "Unassigned" : "Section " . $bulk_section;
                $message = "Successfully updated $count student(s) to $target_name!";
                $message_type = "message";
            } else {
                $message = "Error during bulk update: " . mysqli_error($conn);
                $message_type = "error";
            }
        } else {
            $message = "Please select at least one student for bulk allotment.";
            $message_type = "error";
        }
    }
}

// Filter values
$selected_course = isset($_GET['course']) ? trim($_GET['course']) : 'all';
$selected_sem = isset($_GET['sem']) ? trim($_GET['sem']) : 'all';
$selected_section = isset($_GET['section']) ? trim($_GET['section']) : 'all';
$selected_status = isset($_GET['status']) ? trim($_GET['status']) : 'Approved';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_clauses = [];

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

if ($selected_status !== 'all' && $selected_status !== '') {
    $safe_status = mysqli_real_escape_string($conn, $selected_status);
    $where_clauses[] = "status = '$safe_status'";
}

if ($search_query !== '') {
    $safe_search = mysqli_real_escape_string($conn, $search_query);
    $where_clauses[] = "(full_name LIKE '%$safe_search%' OR student_id LIKE '%$safe_search%' OR email LIKE '%$safe_search%')";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Fetch filtered students
$query = "SELECT * FROM students $where_sql ORDER BY course ASC, semester ASC, section ASC, student_id ASC, id DESC";
$result = mysqli_query($conn, $query);

// Fetch overall counts for stat pills based on current Course + Sem filter
$stat_where = [];
if ($selected_course !== 'all' && $selected_course !== '') {
    $safe_course = mysqli_real_escape_string($conn, $selected_course);
    $stat_where[] = "course = '$safe_course'";
}
if ($selected_sem !== 'all' && $selected_sem !== '') {
    $safe_sem = (int)$selected_sem;
    $stat_where[] = "semester = $safe_sem";
}
if ($selected_status !== 'all' && $selected_status !== '') {
    $safe_status = mysqli_real_escape_string($conn, $selected_status);
    $stat_where[] = "status = '$safe_status'";
}
$stat_sql = count($stat_where) > 0 ? "WHERE " . implode(" AND ", $stat_where) : "";

$counts = [
    'total' => 0,
    'A' => 0,
    'B' => 0,
    'C' => 0,
    'D' => 0,
    'unassigned' => 0
];

$count_res = mysqli_query($conn, "SELECT section, COUNT(*) as cnt FROM students $stat_sql GROUP BY section");
if ($count_res) {
    while ($c_row = mysqli_fetch_assoc($count_res)) {
        $sec = $c_row['section'];
        $cnt = (int)$c_row['cnt'];
        $counts['total'] += $cnt;
        if (empty($sec) || $sec === 'Unassigned') {
            $counts['unassigned'] += $cnt;
        } elseif (isset($counts[$sec])) {
            $counts[$sec] += $cnt;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section Allotment - Admin Dashboard</title>
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
        
        /* Filter Card */
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
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
            letter-spacing: 0.04em;
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
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        .btn-filter {
            background: #0284c7;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .btn-filter:hover {
            background: #0369a1;
        }
        .btn-reset {
            background: #334155;
            color: #cbd5e1;
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .btn-reset:hover {
            background: #475569;
            color: white;
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #131b2e;
            border: 1px solid #1e293b;
            padding: 14px 18px;
            border-radius: 10px;
            flex: 1;
            min-width: 130px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .stat-card .label {
            font-size: 0.78rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
        }
        .stat-card .number {
            font-size: 1.45rem;
            font-weight: 700;
            color: white;
        }
        .stat-card.sec-a .number { color: #10b981; }
        .stat-card.sec-b .number { color: #38bdf8; }
        .stat-card.sec-c .number { color: #a855f7; }
        .stat-card.sec-d .number { color: #f59e0b; }
        .stat-card.unassigned .number { color: #ef4444; }

        /* Bulk Action Bar */
        .bulk-bar {
            background: #1e293b;
            border: 1px solid #334155;
            padding: 12px 18px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .bulk-bar-left {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: #e2e8f0;
            font-weight: 500;
        }
        .bulk-bar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .bulk-bar select {
            padding: 7px 12px;
            background: #0f172a;
            border: 1px solid #475569;
            border-radius: 6px;
            color: white;
            font-size: 0.88rem;
            margin-bottom: 0;
            width: auto;
        }
        .btn-bulk-apply {
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-bulk-apply:hover {
            background: #059669;
        }

        /* Table styles */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            min-width: 820px;
            border-collapse: collapse;
            background: var(--card-bg);
        }
        th, td {
            padding: 12px 15px;
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
        
        /* Badges */
        .badge-sec {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .badge-sec-a { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-sec-b { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .badge-sec-c { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
        .badge-sec-d { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-sec-none { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        .inline-allot-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .inline-allot-form select {
            padding: 6px 10px;
            background: #090e17;
            border: 1px solid #334155;
            border-radius: 6px;
            color: white;
            font-size: 0.85rem;
            margin-bottom: 0;
            width: auto;
        }
        .btn-inline-save {
            background: #0284c7;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-inline-save:hover {
            background: #0369a1;
        }

        .avatar-thumb {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #38bdf8;
        }
        .avatar-placeholder {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #1e293b;
            color: #38bdf8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            border: 1px solid #334155;
        }
        .student-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            body { padding: 12px 10px !important; }
            .header h1 { font-size: 1.25rem !important; }
            .filter-form-grid { grid-template-columns: 1fr; }
            .stats-bar { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Section Allotment</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="menu">
         <a href="admin_dashboard.php">Admission Requests</a>
         <a href="admin_sections.php" class="active" style="color: #00d4ff; font-weight: bold;">Section Allotment</a>
         <a href="admin_student_export.php">Student Export</a>
         <a href="admin_attendance.php">Attendance</a>
         <a href="admin_marks.php">Marks</a>
         <a href="admin_timetable.php">Timetable</a>
         <a href="admin_materials.php">Study Materials</a>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="<?php echo $message_type === 'error' ? 'error' : 'message'; ?>" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <!-- 1. Selection & Filter Panel -->
    <div class="filter-panel">
        <h3>🔍 Select Course, Semester & Section</h3>
        <form method="GET" action="admin_sections.php" id="filterForm">
            <div class="filter-form-grid">
                <!-- Course Select -->
                <div class="filter-group">
                    <label>Course</label>
                    <select name="course" onchange="document.getElementById('filterForm').submit()">
                        <option value="all" <?php if ($selected_course === 'all') echo 'selected'; ?>>All Courses</option>
                        <option value="BCA" <?php if ($selected_course === 'BCA') echo 'selected'; ?>>BCA</option>
                        <option value="BBA" <?php if ($selected_course === 'BBA') echo 'selected'; ?>>BBA</option>
                        <option value="BSc" <?php if ($selected_course === 'BSc') echo 'selected'; ?>>BSc</option>
                        <option value="BA" <?php if ($selected_course === 'BA') echo 'selected'; ?>>BA</option>
                    </select>
                </div>

                <!-- Semester Select -->
                <div class="filter-group">
                    <label>Semester</label>
                    <select name="sem" onchange="document.getElementById('filterForm').submit()">
                        <option value="all" <?php if ($selected_sem === 'all') echo 'selected'; ?>>All Semesters</option>
                        <?php for ($s = 1; $s <= 8; $s++) { ?>
                            <option value="<?php echo $s; ?>" <?php if ($selected_sem === (string)$s) echo 'selected'; ?>>
                                Semester <?php echo $s; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Section Select -->
                <div class="filter-group">
                    <label>Section</label>
                    <select name="section" onchange="document.getElementById('filterForm').submit()">
                        <option value="all" <?php if ($selected_section === 'all') echo 'selected'; ?>>All Sections</option>
                        <option value="unassigned" <?php if ($selected_section === 'unassigned') echo 'selected'; ?>>Unassigned / Not Allotted</option>
                        <option value="A" <?php if ($selected_section === 'A') echo 'selected'; ?>>Section A</option>
                        <option value="B" <?php if ($selected_section === 'B') echo 'selected'; ?>>Section B</option>
                        <option value="C" <?php if ($selected_section === 'C') echo 'selected'; ?>>Section C</option>
                        <option value="D" <?php if ($selected_section === 'D') echo 'selected'; ?>>Section D</option>
                    </select>
                </div>

                <!-- Status Select -->
                <div class="filter-group">
                    <label>Student Status</label>
                    <select name="status" onchange="document.getElementById('filterForm').submit()">
                        <option value="Approved" <?php if ($selected_status === 'Approved') echo 'selected'; ?>>Approved Students</option>
                        <option value="all" <?php if ($selected_status === 'all') echo 'selected'; ?>>All (Approved, Pending, Rejected)</option>
                        <option value="Pending" <?php if ($selected_status === 'Pending') echo 'selected'; ?>>Pending Only</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="filter-group">
                    <label>Search Student</label>
                    <input type="text" name="search" placeholder="Name or Student ID..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>

                <!-- Filter Actions -->
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">Filter</button>
                    <a href="admin_sections.php" class="btn-reset">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- 2. Statistics Summary Cards -->
    <div class="stats-bar">
        <div class="stat-card">
            <span class="label">Total Students</span>
            <span class="number"><?php echo $counts['total']; ?></span>
        </div>
        <div class="stat-card sec-a">
            <span class="label">Section A</span>
            <span class="number"><?php echo $counts['A']; ?></span>
        </div>
        <div class="stat-card sec-b">
            <span class="label">Section B</span>
            <span class="number"><?php echo $counts['B']; ?></span>
        </div>
        <div class="stat-card sec-c">
            <span class="label">Section C</span>
            <span class="number"><?php echo $counts['C']; ?></span>
        </div>
        <div class="stat-card sec-d">
            <span class="label">Section D</span>
            <span class="number"><?php echo $counts['D']; ?></span>
        </div>
        <div class="stat-card unassigned">
            <span class="label">Not Allotted</span>
            <span class="number"><?php echo $counts['unassigned']; ?></span>
        </div>
    </div>

    <!-- 3. Form for Bulk Actions & Student List Table -->
    <form method="POST" action="admin_sections.php?course=<?php echo urlencode($selected_course); ?>&sem=<?php echo urlencode($selected_sem); ?>&section=<?php echo urlencode($selected_section); ?>&status=<?php echo urlencode($selected_status); ?>&search=<?php echo urlencode($search_query); ?>" id="bulkForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
        
        <div class="bulk-bar">
            <div class="bulk-bar-left">
                <input type="checkbox" id="selectAllCheckbox" style="cursor: pointer; width: 18px; height: 18px; margin: 0;">
                <label for="selectAllCheckbox" style="margin: 0; cursor: pointer; color: white;">Select All Listed</label>
                <span id="selectedCount" style="color: #38bdf8; margin-left: 8px;">(0 selected)</span>
            </div>
            <div class="bulk-bar-right">
                <label style="margin: 0; font-size: 0.88rem; color: #cbd5e1;">Allot Selected To:</label>
                <select name="bulk_section" required>
                    <option value="">-- Choose Section --</option>
                    <option value="A">Section A</option>
                    <option value="B">Section B</option>
                    <option value="C">Section C</option>
                    <option value="D">Section D</option>
                    <option value="Unassigned">Unassigned (Clear Section)</option>
                </select>
                <button type="submit" name="bulk_allot_section" class="btn-bulk-apply" onclick="return confirmBulk();">
                    ✓ Apply Section
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">✓</th>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Course</th>
                        <th>Semester</th>
                        <th>Current Section</th>
                        <th>Status</th>
                        <th style="min-width: 250px;">Allot / Change Section</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $num_rows = mysqli_num_rows($result);
                if ($num_rows > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $has_photo = (!empty($row['photo']) && file_exists($row['photo']));
                        $sec = !empty($row['section']) ? $row['section'] : '';
                ?>
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" name="selected_students[]" value="<?php echo $row['id']; ?>" class="student-checkbox" style="cursor: pointer; width: 17px; height: 17px; margin: 0;">
                        </td>
                        <td>
                            <div class="student-cell">
                                <?php if ($has_photo) { ?>
                                    <img src="<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" class="avatar-thumb">
                                <?php } else { ?>
                                    <div class="avatar-placeholder">
                                        <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                                    </div>
                                <?php } ?>
                                <div>
                                    <strong style="color: white;"><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                    <div style="font-size: 0.8rem; color: #94a3b8;">
                                        <?php echo htmlspecialchars($row['email']); ?>
                                        <?php if (!empty($row['phone'])) echo ' &bull; ' . htmlspecialchars($row['phone']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php 
                            if (!empty($row['student_id'])) {
                                echo '<span style="color: #38bdf8; font-weight: 600;">' . htmlspecialchars($row['student_id']) . '</span>';
                            } else {
                                echo '<span style="color: #64748b;">Not Generated</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #f1f5f9;"><?php echo htmlspecialchars($row['course']); ?></span>
                        </td>
                        <td>
                            <span style="color: #e2e8f0; font-weight: 500;">Sem <?php echo htmlspecialchars($row['semester']); ?></span>
                        </td>
                        <td>
                            <?php if ($sec === 'A') { ?>
                                <span class="badge-sec badge-sec-a">Section A</span>
                            <?php } elseif ($sec === 'B') { ?>
                                <span class="badge-sec badge-sec-b">Section B</span>
                            <?php } elseif ($sec === 'C') { ?>
                                <span class="badge-sec badge-sec-c">Section C</span>
                            <?php } elseif ($sec === 'D') { ?>
                                <span class="badge-sec badge-sec-d">Section D</span>
                            <?php } elseif (!empty($sec)) { ?>
                                <span class="badge-sec badge-sec-b">Section <?php echo htmlspecialchars($sec); ?></span>
                            <?php } else { ?>
                                <span class="badge-sec badge-sec-none">Not Allotted</span>
                            <?php } ?>
                        </td>
                        <td>
                            <span class="badge-status badge-<?php echo strtolower($row['status']); ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <!-- Form to allot / change section individually -->
                            <div class="inline-allot-form">
                                <input type="hidden" name="filter_params" value="<?php echo http_build_query($_GET); ?>">
                                
                                <select id="sec_select_<?php echo $row['id']; ?>" style="min-width: 110px;">
                                    <option value="Unassigned" <?php if (empty($sec) || $sec === 'Unassigned') echo 'selected'; ?>>Not Allotted</option>
                                    <option value="A" <?php if ($sec === 'A') echo 'selected'; ?>>Section A</option>
                                    <option value="B" <?php if ($sec === 'B') echo 'selected'; ?>>Section B</option>
                                    <option value="C" <?php if ($sec === 'C') echo 'selected'; ?>>Section C</option>
                                    <option value="D" <?php if ($sec === 'D') echo 'selected'; ?>>Section D</option>
                                </select>

                                <select id="sem_select_<?php echo $row['id']; ?>" style="min-width: 85px;" title="Change Semester">
                                    <?php for ($s = 1; $s <= 8; $s++) { ?>
                                        <option value="<?php echo $s; ?>" <?php if ((int)$row['semester'] === $s) echo 'selected'; ?>>Sem <?php echo $s; ?></option>
                                    <?php } ?>
                                </select>

                                <button type="button" class="btn-inline-save" onclick="saveSingleSection(<?php echo $row['id']; ?>)">
                                    Save
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php 
                    } 
                } else {
                ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                            <div style="font-size: 2.2rem; margin-bottom: 10px;">📋</div>
                            <h3 style="color: white; margin-bottom: 6px;">No Students Found</h3>
                            <p style="font-size: 0.9rem; margin-bottom: 16px;">
                                No students match the current Course, Semester, Section, or Search filter.
                            </p>
                            <a href="admin_sections.php" class="btn-filter" style="display: inline-block; text-decoration: none;">Reset All Filters</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </form>

    <!-- Hidden form for individual student updates -->
    <form id="singleUpdateForm" method="POST" action="admin_sections.php?course=<?php echo urlencode($selected_course); ?>&sem=<?php echo urlencode($selected_sem); ?>&section=<?php echo urlencode($selected_section); ?>&status=<?php echo urlencode($selected_status); ?>&search=<?php echo urlencode($search_query); ?>" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
        <input type="hidden" name="update_single_section" value="1">
        <input type="hidden" name="student_db_id" id="hidden_student_id">
        <input type="hidden" name="section" id="hidden_section">
        <input type="hidden" name="semester" id="hidden_semester">
    </form>

    <script>
        // Select All Checkbox Handler
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const studentCheckboxes = document.querySelectorAll('.student-checkbox');
        const selectedCount = document.getElementById('selectedCount');

        function updateCount() {
            const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
            selectedCount.textContent = `(${checkedBoxes.length} selected)`;
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                studentCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
                updateCount();
            });
        }

        studentCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const total = studentCheckboxes.length;
                const checked = document.querySelectorAll('.student-checkbox:checked').length;
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = (total > 0 && total === checked);
                }
                updateCount();
            });
        });

        // Confirm Bulk Update
        function confirmBulk() {
            const checked = document.querySelectorAll('.student-checkbox:checked');
            if (checked.length === 0) {
                alert('Please select at least one student checkbox to allot or change section.');
                return false;
            }
            const bulkSelect = document.querySelector('select[name="bulk_section"]');
            if (!bulkSelect.value) {
                alert('Please choose which section to allot to the selected students.');
                return false;
            }
            return confirm(`Are you sure you want to allot/change section for ${checked.length} student(s) to "${bulkSelect.value}"?`);
        }

        // Save Single Student Section
        function saveSingleSection(studentId) {
            const secVal = document.getElementById('sec_select_' + studentId).value;
            const semVal = document.getElementById('sem_select_' + studentId).value;

            document.getElementById('hidden_student_id').value = studentId;
            document.getElementById('hidden_section').value = secVal;
            document.getElementById('hidden_semester').value = semVal;

            document.getElementById('singleUpdateForm').submit();
        }
    </script>
</body>
</html>
