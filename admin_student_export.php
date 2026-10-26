<?php
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$selected_course = isset($_GET['course']) ? trim($_GET['course']) : 'all';
$selected_sem = isset($_GET['sem']) ? trim($_GET['sem']) : 'all';
$selected_section = isset($_GET['section']) ? trim($_GET['section']) : 'all';
$selected_status = isset($_GET['status']) ? trim($_GET['status']) : 'Approved';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

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

$where_sql = count($where_clauses) > 0 ? ' WHERE ' . implode(' AND ', $where_clauses) : '';
$student_query = "SELECT * FROM students $where_sql ORDER BY course ASC, semester ASC, section ASC, student_id ASC, id DESC";
$result = mysqli_query($conn, $student_query);

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $export_result = mysqli_query($conn, $student_query);
    $filename = 'student_export_' . date('Ymd_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    $write_row = function ($values) use ($output) {
        $excel_safe = [];
        foreach ($values as $value) {
            $str = (string) $value;
            $clean = str_replace(["\r", "\n", "\t"], [' ', ' ', ' '], $str);
            if ($clean === '') {
                $excel_safe[] = '';
            } elseif (preg_match('/^(\d{7,15}|\d{1,3}(?:,\d{3})+|\d+\.?\d*)$/', $clean) || preg_match('/^\+?\d[\d\s\-()]+$/', $clean)) {
                $excel_safe[] = "'" . $clean;
            } else {
                $excel_safe[] = $clean;
            }
        }
        fputcsv($output, $excel_safe, "\t");
    };

    $write_row(['Student Name', 'Student ID', 'Course', 'Semester', 'Section', 'Status', 'Email', 'Phone']);

    if ($export_result) {
        while ($row = mysqli_fetch_assoc($export_result)) {
            $section_value = !empty($row['section']) ? $row['section'] : 'Not Allotted';
            $write_row([
                $row['full_name'] ?? '',
                $row['student_id'] ?? '',
                $row['course'] ?? '',
                $row['semester'] ?? '',
                $section_value,
                $row['status'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? ''
            ]);
        }
    }

    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Export - Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: block;
            padding: 24px 20px;
            max-width: 1200px;
            margin: 0 auto;
            box-sizing: border-box;
            background: linear-gradient(180deg, #0b1220 0%, #111827 100%);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 12px;
            flex-wrap: wrap;
            padding: 18px 22px;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
        }
        .header h1 {
            color: #00d4ff;
            font-size: 1.7rem;
            margin: 0;
            font-weight: 800;
            letter-spacing: 0.02em;
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
            background: rgba(15, 23, 42, 0.9);
            padding: 8px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow: 0 3px 12px rgba(0,0,0,0.14);
        }
        .menu a {
            color: var(--text-muted);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 500;
        }
        .menu a:hover, .menu a.active {
            background: rgba(59, 130, 246, 0.15);
            color: #00d4ff;
        }
        .filter-panel {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 24px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.18);
        }
        .filter-panel h3 {
            color: #00d4ff;
            margin: 0 0 18px 0;
            font-weight: 800;
            font-size: 1.12rem;
            letter-spacing: 0.02em;
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
            gap: 8px;
        }
        .filter-group label {
            color: #e2e8f0;
            font-size: 0.88rem;
            font-weight: 700;
        }
        .filter-group select,
        .filter-group input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #475569;
            border-radius: 10px;
            color: #f8fafc;
            padding: 10px 12px;
            font-size: 0.95rem;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.3);
        }
        .filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .btn-filter, .btn-reset {
            display: inline-block;
            border: none;
            border-radius: 10px;
            padding: 10px 18px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .btn-filter {
            background: linear-gradient(135deg, #00d4ff, #2563eb);
            color: #fff;
        }
        .btn-reset {
            background: #1f2937;
            color: #e2e8f0;
            border: 1px solid #374151;
        }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin: 20px 0 24px;
        }
        .stat-card {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.12);
        }
        .stat-card .label {
            display: block;
            font-size: 0.8rem;
            color: #cbd5e1;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .stat-card .number {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffffff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        }
        th, td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            text-align: left;
            vertical-align: top;
        }
        th {
            background: rgba(30, 41, 59, 0.95);
            color: #00d4ff;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 0.78rem;
        }
        td {
            color: #e2e8f0;
            border-left: 1px solid rgba(148, 163, 184, 0.12);
        }
        tbody tr:hover {
            background: rgba(59, 130, 246, 0.06);
        }
        .badge-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-approved { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .badge-pending { background: rgba(250, 204, 21, 0.15); color: #facc15; }
        .badge-rejected { background: rgba(248, 113, 113, 0.15); color: #f87171; }
        .empty-box {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 12px;
            padding: 32px 20px;
            text-align: center;
            color: #94a3b8;
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        }
        @media (max-width: 640px) {
            .menu {
                gap: 6px;
            }
            .menu a {
                padding: 7px 10px;
                font-size: 0.78rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Export Center</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="menu">
        <a href="admin_dashboard.php">Admission Requests</a>
        <a href="admin_sections.php">Section Allotment</a>
        <a href="admin_student_export.php" class="active" style="color: #00d4ff; font-weight: bold;">Student Export</a>
        <a href="admin_attendance.php">Attendance</a>
        <a href="admin_marks.php">Marks</a>
        <a href="admin_timetable.php">Timetable</a>
        <a href="admin_materials.php">Study Materials</a>
    </div>

    <div class="filter-panel">
        <h3>📤 Export Students by Course, Semester, Section, or Status</h3>
        <form method="GET" action="admin_student_export.php">
            <div class="filter-form-grid">
                <div class="filter-group">
                    <label>Course</label>
                    <select name="course">
                        <option value="all" <?php if ($selected_course === 'all') echo 'selected'; ?>>All Courses</option>
                        <option value="BCA" <?php if ($selected_course === 'BCA') echo 'selected'; ?>>BCA</option>
                        <option value="BBA" <?php if ($selected_course === 'BBA') echo 'selected'; ?>>BBA</option>
                        <option value="BSc" <?php if ($selected_course === 'BSc') echo 'selected'; ?>>BSc</option>
                        <option value="BA" <?php if ($selected_course === 'BA') echo 'selected'; ?>>BA</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Semester</label>
                    <select name="sem">
                        <option value="all" <?php if ($selected_sem === 'all') echo 'selected'; ?>>All Semesters</option>
                        <?php for ($s = 1; $s <= 8; $s++) { ?>
                            <option value="<?php echo $s; ?>" <?php if ($selected_sem === (string)$s) echo 'selected'; ?>>Semester <?php echo $s; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Section</label>
                    <select name="section">
                        <option value="all" <?php if ($selected_section === 'all') echo 'selected'; ?>>All Sections</option>
                        <option value="unassigned" <?php if ($selected_section === 'unassigned') echo 'selected'; ?>>Unassigned / Not Allotted</option>
                        <option value="A" <?php if ($selected_section === 'A') echo 'selected'; ?>>Section A</option>
                        <option value="B" <?php if ($selected_section === 'B') echo 'selected'; ?>>Section B</option>
                        <option value="C" <?php if ($selected_section === 'C') echo 'selected'; ?>>Section C</option>
                        <option value="D" <?php if ($selected_section === 'D') echo 'selected'; ?>>Section D</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Student Status</label>
                    <select name="status">
                        <option value="Approved" <?php if ($selected_status === 'Approved') echo 'selected'; ?>>Approved Students</option>
                        <option value="all" <?php if ($selected_status === 'all') echo 'selected'; ?>>All</option>
                        <option value="Pending" <?php if ($selected_status === 'Pending') echo 'selected'; ?>>Pending Only</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Search Student</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Name or Student ID...">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-filter">Apply Filters</button>
                    <a href="admin_student_export.php?export=excel&course=<?php echo urlencode($selected_course); ?>&sem=<?php echo urlencode($selected_sem); ?>&section=<?php echo urlencode($selected_section); ?>&status=<?php echo urlencode($selected_status); ?>&search=<?php echo urlencode($search_query); ?>" class="btn-filter" style="text-decoration:none;">Export Excel</a>
                    <a href="admin_student_export.php" class="btn-reset">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="stats-bar">
        <?php
        $count_sql = "SELECT COUNT(*) as total FROM students" . ($where_sql ?: '');
        $count_result = mysqli_query($conn, $count_sql);
        $count_row = $count_result ? mysqli_fetch_assoc($count_result) : ['total' => 0];
        ?>
        <div class="stat-card">
            <span class="label">Total Listed</span>
            <span class="number"><?php echo (int)$count_row['total']; ?></span>
        </div>
    </div>

    <?php if ($result && mysqli_num_rows($result) > 0) { ?>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Course</th>
                    <th>Semester</th>
                    <th>Section</th>
                    <th>Status</th>
                    <th>Email</th>
                    <th>Phone</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['student_id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['course'] ?? ''); ?></td>
                        <td>Semester <?php echo htmlspecialchars($row['semester'] ?? ''); ?></td>
                        <td><?php echo !empty($row['section']) ? htmlspecialchars($row['section']) : 'Not Allotted'; ?></td>
                        <td>
                            <span class="badge-status badge-<?php echo strtolower($row['status'] ?? 'approved'); ?>"><?php echo htmlspecialchars($row['status'] ?? 'Approved'); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <div class="empty-box">
            <h3 style="color: white; margin-bottom: 8px;">No Students Found</h3>
            <p>No students match the current filters.</p>
        </div>
    <?php } ?>
</body>
</html>
