<?php
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$message = "";

// Approve Student
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];

    // Generate Student ID (Example: STU2024001)
    $student_id = "STU" . date("Y") . str_pad($id, 3, "0", STR_PAD_LEFT);
    $default_password = 'student123';
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE students SET status='Approved', student_id=?, password=? WHERE id=?");
    $stmt->bind_param('ssi', $student_id, $hashed_password, $id);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?msg=approved");
        exit();
    }
}

// Reject Student
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $sql = "UPDATE students SET status='Rejected' WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        header("Location: admin_dashboard.php?msg=rejected");
        exit();
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'approved') {
        $message = "Student application approved successfully!";
    } elseif ($_GET['msg'] === 'rejected') {
        $message = "Student application rejected.";
    }
}

// Fetch all students
$result = mysqli_query($conn, "SELECT * FROM students ORDER BY id DESC");
$students_data = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Applications</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: block;
            padding: 24px 20px;
            max-width: 100vw;
            overflow-x: hidden;
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
        .menu a:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #00d4ff;
        }
        .table-responsive {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            min-width: 740px;
            border-collapse: collapse;
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 13px 15px;
            text-align: left;
            border-bottom: 1px solid #333;
            vertical-align: middle;
        }
        th {
            background-color: #111;
            color: #00d4ff;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .btn-action-group {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-quick-view {
            background: #1e293b;
            color: #38bdf8;
            border: 1px solid #334155;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .btn-quick-view:hover {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }
        .applicant-subinfo {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 3px;
        }
        .doc-badge-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        @media (max-width: 768px) {
            body {
                padding: 12px 10px !important;
            }
            .header h1 {
                font-size: 1.25rem !important;
            }
            .logout {
                padding: 7px 12px;
                font-size: 0.82rem;
            }
            .menu {
                gap: 5px;
                padding: 6px;
            }
            .menu a {
                padding: 6px 10px;
                font-size: 0.78rem;
            }
            th, td {
                padding: 10px 12px;
                font-size: 0.84rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin Dashboard</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="menu">
         <a href="admin_dashboard.php" class="active" style="color: #00d4ff; font-weight: bold;">Admission Requests</a>
         <a href="admin_sections.php">Section Allotment</a>
         <a href="admin_student_export.php">Student Export</a>
         <a href="admin_attendance.php">Attendance</a>
         <a href="admin_marks.php">Marks</a>
         <a href="admin_timetable.php">Timetable</a>
         <a href="admin_materials.php">Study Materials</a>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="message" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h2 style="color: #ccc; font-size: 1.4rem;">Student Applications & Admission Review</h2>
        <span style="color: var(--text-muted); font-size: 0.9rem;">
            Click <strong>Review</strong> or <strong>Quick View</strong> to inspect uploaded marks cards & applicant details
        </span>
    </div>

    <div class="table-responsive">
    <table>
    <thead>
    <tr>
        <th>#</th>
        <th>Photo</th>
        <th>Applicant Name</th>
        <th>Student ID</th>
        <th>Course & Section</th>
        <th>Marks Cards & Documents</th>
        <th>Status</th>
        <th style="min-width: 250px;">Review & Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php 
    while ($row = mysqli_fetch_assoc($result)) { 
        $has_photo = (!empty($row['photo']) && file_exists($row['photo']));
        $has_10th = (!empty($row['marksheet_10th']) && file_exists($row['marksheet_10th']));
        $has_12th = (!empty($row['marksheet_12th']) && file_exists($row['marksheet_12th']));
        $sec = !empty($row['section']) ? $row['section'] : '';
        
        // Save for client-side quick modal
        $students_data[$row['id']] = [
            'id' => $row['id'],
            'student_id' => $row['student_id'] ?: 'Pending Assignment',
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'dob' => $row['dob'],
            'gender' => $row['gender'],
            'address' => $row['address'],
            'course' => $row['course'],
            'semester' => !empty($row['semester']) ? $row['semester'] : 1,
            'section' => $sec ?: 'Unassigned',
            'status' => $row['status'],
            'photo' => $has_photo ? $row['photo'] : '',
            'marksheet_10th' => $has_10th ? $row['marksheet_10th'] : '',
            'marksheet_12th' => $has_12th ? $row['marksheet_12th'] : '',
            'created_at' => !empty($row['created_at']) ? $row['created_at'] : ''
        ];
    ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td>
                <?php if ($has_photo) { ?>
                    <img src="<?php echo htmlspecialchars($row['photo']); ?>" 
                         alt="Photo" 
                         class="avatar-mini"
                         onclick="openQuickModal(<?php echo $row['id']; ?>)"
                         style="cursor: pointer;"
                         title="Click to view details">
                <?php } else { ?>
                    <div class="avatar-placeholder" onclick="openQuickModal(<?php echo $row['id']; ?>)" style="cursor: pointer;">
                        <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                    </div>
                <?php } ?>
            </td>
            <td>
                <strong style="color: white;"><?php echo htmlspecialchars($row['full_name']); ?></strong>
                <div class="applicant-subinfo">
                    <span><?php echo htmlspecialchars($row['email']); ?></span>
                    <?php if (!empty($row['phone'])) { echo ' &bull; ' . htmlspecialchars($row['phone']); } ?>
                </div>
            </td>
            <td>
                <?php 
                    if (!empty($row['student_id'])) {
                        echo '<span style="color: #38bdf8; font-weight: 600;">' . htmlspecialchars($row['student_id']) . '</span>';
                    } else {
                        echo '<span style="color: #64748b;">Pending</span>';
                    }
                ?>
            </td>
            <td>
                <div style="font-weight: 600; color: #f1f5f9;">
                    <?php echo htmlspecialchars($row['course']); ?> • Sem <?php echo htmlspecialchars(!empty($row['semester']) ? $row['semester'] : 1); ?>
                </div>
                <div style="margin-top: 4px;">
                    <?php 
                    if ($sec === 'A') {
                        echo '<span class="badge-sec badge-sec-a">Section A</span>';
                    } elseif ($sec === 'B') {
                        echo '<span class="badge-sec badge-sec-b">Section B</span>';
                    } elseif ($sec === 'C') {
                        echo '<span class="badge-sec badge-sec-c">Section C</span>';
                    } elseif ($sec === 'D') {
                        echo '<span class="badge-sec badge-sec-d">Section D</span>';
                    } elseif (!empty($sec)) {
                        echo '<span class="badge-sec badge-sec-b">Section ' . htmlspecialchars($sec) . '</span>';
                    } else {
                        echo '<span class="badge-sec badge-sec-none">Not Allotted</span>';
                    }
                    ?>
                </div>
            </td>
            <td>
                <div class="doc-badge-list">
                    <div>
                        <?php if ($has_10th) { ?>
                            <span class="doc-tag available" title="10th Marksheet Uploaded">&#x2713; 10th Marksheet</span>
                        <?php } else { ?>
                            <span class="doc-tag missing" title="10th Marksheet Missing">&cross; 10th Missing</span>
                        <?php } ?>
                    </div>
                    <div>
                        <?php if ($has_12th) { ?>
                            <span class="doc-tag available" title="12th Marksheet Uploaded">&#x2713; 12th Marksheet</span>
                        <?php } else { ?>
                            <span class="doc-tag missing" title="12th Marksheet Missing">&cross; 12th Missing</span>
                        <?php } ?>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge-status badge-<?php echo strtolower($row['status']); ?>">
                    <?php echo htmlspecialchars($row['status']); ?>
                </span>
            </td>
            <td>
                <div class="btn-action-group">
                    <!-- Option to review full marks cards & details on dedicated page -->
                    <a href="admin_review_student.php?id=<?php echo $row['id']; ?>" 
                       class="btn-review" 
                       title="Review full application and marks cards">
                       <span>&#x1F441;</span> Review
                    </a>

                    <!-- Quick View modal trigger -->
                    <button type="button" 
                            class="btn-quick-view" 
                            onclick="openQuickModal(<?php echo $row['id']; ?>)" 
                            title="Quick preview details and documents">
                        <span>&#x26A1;</span> Quick View
                    </button>

                    <!-- Allot / Change Section shortcut -->
                    <a href="admin_sections.php?search=<?php echo urlencode(!empty($row['student_id']) ? $row['student_id'] : $row['full_name']); ?>" 
                       class="btn-quick-view" 
                       style="color: #c084fc; border-color: rgba(192, 132, 252, 0.4);" 
                       title="Allot or change student section">
                       <span>🏷️</span> Section
                    </a>

                    <?php if ($row['status'] == 'Pending') { ?>
                        <a href="admin_dashboard.php?approve=<?php echo $row['id']; ?>" 
                           class="btn approve" 
                           onclick="return confirm('Approve admission for <?php echo htmlspecialchars(addslashes($row['full_name'])); ?>?');"
                           title="Approve Application">
                           &#x2713;
                        </a>
                        <a href="admin_dashboard.php?reject=<?php echo $row['id']; ?>" 
                           class="btn reject" 
                           onclick="return confirm('Reject admission for <?php echo htmlspecialchars(addslashes($row['full_name'])); ?>?');"
                           title="Reject Application">
                           &times;
                        </a>
                    <?php } ?>
                </div>
            </td>
        </tr>
    <?php } ?>
    </tbody>
    </table>
    </div>

    <!-- Quick Review Modal -->
    <div id="quickModal" class="modal-overlay" onclick="handleModalBackdropClick(event)">
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="modalStudentTitle">Review Student Application</h3>
                <button type="button" class="modal-close-btn" onclick="closeQuickModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalStudentBody">
                <!-- Content injected dynamically via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        const studentsDatabase = <?php echo json_encode($students_data); ?>;

        function openQuickModal(studentId) {
            const student = studentsDatabase[studentId];
            if (!student) return;

            document.getElementById('modalStudentTitle').innerHTML = 
                `<span>Review Application:</span> <span style="color:#00d4ff;">${escapeHtml(student.full_name)}</span>`;

            let photoHtml = student.photo 
                ? `<img src="${student.photo}" style="width:100px; height:100px; border-radius:12px; object-fit:cover; border:2px solid #00d4ff;">`
                : `<div class="avatar-placeholder" style="width:100px; height:100px; font-size:2rem; border-radius:12px;">${student.full_name.charAt(0).toUpperCase()}</div>`;

            let doc10Html = student.marksheet_10th 
                ? renderDocPreview(student.marksheet_10th, '10th Standard Marksheet')
                : `<div class="empty-doc-box"><p style="color:#f87171;">&cross; No 10th Marksheet uploaded</p></div>`;

            let doc12Html = student.marksheet_12th 
                ? renderDocPreview(student.marksheet_12th, '12th Standard Marksheet')
                : `<div class="empty-doc-box"><p style="color:#f87171;">&cross; No 12th Marksheet uploaded</p></div>`;

            let statusClass = 'badge-' + student.status.toLowerCase();

            let actionButtonsHtml = '';
            if (student.status === 'Pending') {
                actionButtonsHtml = `
                    <a href="admin_dashboard.php?approve=${student.id}" class="btn approve" onclick="return confirm('Approve application?');">&#x2713; Approve Student</a>
                    <a href="admin_dashboard.php?reject=${student.id}" class="btn reject" onclick="return confirm('Reject application?');">&times; Reject Student</a>
                `;
            } else if (student.status === 'Rejected') {
                actionButtonsHtml = `
                    <a href="admin_dashboard.php?approve=${student.id}" class="btn approve" onclick="return confirm('Re-approve application?');">&#x2713; Re-Approve Student</a>
                `;
            } else if (student.status === 'Approved') {
                actionButtonsHtml = `
                    <a href="admin_dashboard.php?reject=${student.id}" class="btn reject" onclick="return confirm('Revoke approval?');">&times; Revoke to Rejected</a>
                `;
            }

            document.getElementById('modalStudentBody').innerHTML = `
                <div style="display: flex; gap: 24px; margin-bottom: 24px; flex-wrap: wrap;">
                    <div style="text-align: center;">
                        ${photoHtml}
                        <div style="margin-top: 10px;">
                            <span class="badge-status ${statusClass}">${escapeHtml(student.status)}</span>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 260px;">
                        <table class="info-table" style="background:transparent; border:none;">
                            <tr>
                                <td class="label">Course & Sem</td>
                                <td class="val" style="color:#00d4ff;">${escapeHtml(student.course)} • Sem ${escapeHtml(student.semester)}</td>
                            </tr>
                            <tr>
                                <td class="label">Section</td>
                                <td class="val">
                                    <span class="badge-sec ${student.section && student.section !== 'Unassigned' ? 'badge-sec-' + student.section.toLowerCase() : 'badge-sec-none'}">
                                        ${student.section && student.section !== 'Unassigned' ? 'Section ' + escapeHtml(student.section) : 'Not Allotted'}
                                    </span>
                                    <a href="admin_sections.php?search=${encodeURIComponent(student.student_id || student.full_name)}" style="color:#38bdf8; font-size:0.8rem; margin-left:8px; text-decoration:underline;">Allot/Change</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Student ID</td>
                                <td class="val">${escapeHtml(student.student_id)}</td>
                            </tr>
                            <tr>
                                <td class="label">Email</td>
                                <td class="val">${escapeHtml(student.email)}</td>
                            </tr>
                            <tr>
                                <td class="label">Phone</td>
                                <td class="val">${escapeHtml(student.phone)}</td>
                            </tr>
                            <tr>
                                <td class="label">Date of Birth</td>
                                <td class="val">${escapeHtml(student.dob)}</td>
                            </tr>
                            <tr>
                                <td class="label">Gender</td>
                                <td class="val">${escapeHtml(student.gender)}</td>
                            </tr>
                            <tr>
                                <td class="label">Address</td>
                                <td class="val">${escapeHtml(student.address)}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--border); padding-top: 20px; margin-bottom: 20px;">
                    <h4 style="color: white; margin-bottom: 16px; font-size: 1.1rem;">Submitted Marks Cards & Documents</h4>
                    <div class="modal-docs-grid">
                        <div class="doc-box">
                            <div class="doc-box-header">
                                <h3><span>🎓</span> 10th Marksheet</h3>
                                ${student.marksheet_10th ? `<a href="${student.marksheet_10th}" target="_blank" class="download-btn">Open File</a>` : ''}
                            </div>
                            <div class="doc-preview-area" style="min-height: 180px;">
                                ${doc10Html}
                            </div>
                        </div>

                        <div class="doc-box">
                            <div class="doc-box-header">
                                <h3><span>📜</span> 12th Marksheet</h3>
                                ${student.marksheet_12th ? `<a href="${student.marksheet_12th}" target="_blank" class="download-btn">Open File</a>` : ''}
                            </div>
                            <div class="doc-preview-area" style="min-height: 180px;">
                                ${doc12Html}
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 16px; flex-wrap: wrap; gap: 10px;">
                    <a href="admin_review_student.php?id=${student.id}" class="btn-review" style="padding: 10px 18px; font-size: 0.95rem;">
                        &#x1F441; Open Dedicated Review Page
                    </a>
                    <div style="display: flex; gap: 8px;">
                        ${actionButtonsHtml}
                    </div>
                </div>
            `;

            document.getElementById('quickModal').style.display = 'block';
        }

        function renderDocPreview(filePath, title) {
            const isPdf = filePath.toLowerCase().endsWith('.pdf');
            if (isPdf) {
                return `<iframe src="${filePath}" style="width:100%; height:200px; border:none;" title="${title}"></iframe>`;
            } else {
                return `<a href="${filePath}" target="_blank"><img src="${filePath}" alt="${title}" style="max-height:200px; border-radius:6px; cursor:zoom-in;"></a>`;
            }
        }

        function closeQuickModal() {
            document.getElementById('quickModal').style.display = 'none';
        }

        function handleModalBackdropClick(e) {
            if (e.target.id === 'quickModal') {
                closeQuickModal();
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeQuickModal();
            }
        });
    </script>
</body>
</html>