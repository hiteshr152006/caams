<?php
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = "";
$message_type = "message";

if ($id <= 0) {
    header("Location: admin_dashboard.php");
    exit();
}

// Handle Approve Action
if (isset($_GET['action']) && $_GET['action'] === 'approve') {
    $student_id = "STU" . date("Y") . str_pad($id, 3, "0", STR_PAD_LEFT);
    $default_password = 'student123';
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE students SET status='Approved', student_id=?, password=? WHERE id=?");
    $stmt->bind_param('ssi', $student_id, $hashed_password, $id);

    if ($stmt->execute()) {
        $message = "Application has been Approved successfully! Student ID assigned: " . $student_id;
        $message_type = "message";
    } else {
        $message = "Database error: " . $conn->error;
        $message_type = "error";
    }
}

// Handle Reject Action
if (isset($_GET['action']) && $_GET['action'] === 'reject') {
    $update_sql = "UPDATE students SET status='Rejected' WHERE id=$id";
    if (mysqli_query($conn, $update_sql)) {
        $message = "Application has been marked as Rejected.";
        $message_type = "error";
    } else {
        $message = "Database error: " . mysqli_error($conn);
        $message_type = "error";
    }
}

// Handle Update Course / Semester / Section Allotment
if (isset($_POST['update_academic'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = "Invalid security token. Please refresh the page and try again.";
        $message_type = "error";
    } else {
        $up_course = mysqli_real_escape_string($conn, trim($_POST['course']));
        $up_sem = (int)$_POST['semester'];
        $up_sec = trim($_POST['section']);
        $sec_sql = ($up_sec === '' || $up_sec === 'Unassigned') ? "NULL" : "'" . mysqli_real_escape_string($conn, $up_sec) . "'";

        $up_sql = "UPDATE students SET course='$up_course', semester=$up_sem, section=$sec_sql WHERE id=$id";
        if (mysqli_query($conn, $up_sql)) {
            $message = "Academic allocation saved (Course: $up_course, Sem $up_sem, Section: " . ($up_sec ?: 'Not Allotted') . ")!";
            $message_type = "message";
        } else {
            $message = "Failed to update academic details: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// Fetch student details
$query = "SELECT * FROM students WHERE id = $id LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Student application not found. <a href='admin_dashboard.php'>Back to Dashboard</a>");
}

$student = mysqli_fetch_assoc($result);

if (!function_exists('is_pdf_file')) {
    function is_pdf_file($filePath) {
        if (!$filePath) return false;
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return ($ext === 'pdf');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Application - <?php echo htmlspecialchars($student['full_name']); ?></title>
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
        .back-nav {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #38bdf8;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            font-size: 0.95rem;
            transition: transform 0.15s;
        }
        .back-nav:hover {
            transform: translateX(-3px);
            text-decoration: underline;
        }
        .doc-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .doc-tab-btn {
            background: #1e293b;
            color: #94a3b8;
            border: 1px solid var(--border);
            padding: 9px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.88rem;
            transition: all 0.2s;
        }
        .doc-tab-btn.active, .doc-tab-btn:hover {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }
        .lightbox-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.92);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .lightbox-modal img {
            max-width: 95%;
            max-height: 90vh;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 2.5rem;
            cursor: pointer;
            font-weight: bold;
        }
        .empty-doc-box {
            padding: 30px 16px;
            color: var(--text-muted);
            text-align: center;
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
                margin-bottom: 18px;
            }
            .menu a {
                padding: 6px 10px;
                font-size: 0.78rem;
            }
            .review-header {
                padding: 16px 14px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .review-header-info h2 {
                font-size: 1.3rem;
            }
            .review-actions {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .review-grid {
                grid-template-columns: 1fr !important;
                gap: 16px;
            }
            .applicant-card, .docs-card {
                padding: 18px 14px;
            }
            .applicant-photo {
                width: 100px;
                height: 100px;
            }
            .info-table td {
                padding: 8px 2px;
                font-size: 0.86rem;
            }
            .info-table td.label {
                width: 38%;
                min-width: 90px;
            }
            .doc-box {
                padding: 14px 12px;
            }
            .doc-box-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .doc-btn-group {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
                gap: 6px;
            }
            .doc-preview-area {
                min-height: 160px;
            }
            .doc-preview-area img {
                max-height: 280px;
            }
            .doc-preview-area iframe {
                height: 280px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin Portal - Application Review</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="menu">
        <a href="admin_dashboard.php">Admission Requests</a>
        <a href="admin_sections.php">Section Allotment</a>
        <a href="admin_attendance.php">Attendance</a>
        <a href="admin_marks.php">Marks</a>
        <a href="admin_timetable.php">Timetable</a>
        <a href="admin_materials.php">Study Materials</a>
    </div>

    <a href="admin_dashboard.php" class="back-nav">&larr; Back to Student Applications</a>

    <?php if (!empty($message)) { ?>
        <div class="<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <!-- Header bar with student status & actions -->
    <div class="review-header">
        <div class="review-header-info">
            <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
            <p>Application #<?php echo $student['id']; ?> &bull; Applied for <strong><?php echo htmlspecialchars($student['course']); ?></strong> &bull; Submitted: <?php echo !empty($student['created_at']) ? htmlspecialchars($student['created_at']) : 'Recently'; ?></p>
        </div>
        <div class="review-actions">
            <span class="badge-status badge-<?php echo strtolower($student['status']); ?>">
                Status: <?php echo htmlspecialchars($student['status']); ?>
            </span>

            <?php if ($student['status'] === 'Pending') { ?>
                <a href="admin_review_student.php?id=<?php echo $student['id']; ?>&action=approve" 
                   class="btn approve" 
                   onclick="return confirm('Are you sure you want to approve this student application?');">
                   &check; Approve Application
                </a>
                <a href="admin_review_student.php?id=<?php echo $student['id']; ?>&action=reject" 
                   class="btn reject" 
                   onclick="return confirm('Are you sure you want to reject this student application?');">
                   &times; Reject Application
                </a>
            <?php } elseif ($student['status'] === 'Rejected') { ?>
                <a href="admin_review_student.php?id=<?php echo $student['id']; ?>&action=approve" 
                   class="btn approve" 
                   onclick="return confirm('Re-approve this application?');">
                   &check; Re-Approve Application
                </a>
            <?php } elseif ($student['status'] === 'Approved') { ?>
                <a href="admin_review_student.php?id=<?php echo $student['id']; ?>&action=reject" 
                   class="btn reject" 
                   onclick="return confirm('Revert to Rejected?');">
                   &times; Revoke / Reject
                </a>
            <?php } ?>
        </div>
    </div>

    <div class="review-grid">
        <!-- Left: Applicant Details Card -->
        <div class="applicant-card">
            <div class="applicant-photo-wrap">
                <?php if (!empty($student['photo']) && file_exists($student['photo'])) { ?>
                    <img src="<?php echo htmlspecialchars($student['photo']); ?>" 
                         alt="Passport Photo" 
                         class="applicant-photo" 
                         onclick="openLightbox('<?php echo htmlspecialchars($student['photo']); ?>')"
                         title="Click to view full photo"
                         style="cursor: pointer;">
                    <p style="margin-top: 8px; font-size: 0.8rem; color: #38bdf8;">(Click photo to zoom)</p>
                <?php } else { ?>
                    <div class="avatar-placeholder" style="width: 110px; height: 110px; font-size: 2.2rem; border-radius: 16px; margin: 0 auto;">
                        <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                    </div>
                    <p style="margin-top: 8px; font-size: 0.8rem; color: #94a3b8;">No photo uploaded</p>
                <?php } ?>
            </div>

            <h3 style="color: #38bdf8; font-size: 1.15rem; margin-bottom: 15px;">Applicant Information</h3>

            <table class="info-table">
                <tr>
                    <td class="label">Full Name</td>
                    <td class="val"><?php echo htmlspecialchars($student['full_name']); ?></td>
                </tr>
                <tr>
                    <td class="label">Student ID</td>
                    <td class="val">
                        <?php echo !empty($student['student_id']) ? htmlspecialchars($student['student_id']) : '<span style="color:#94a3b8;">Not Assigned Yet</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Course Applied</td>
                    <td class="val"><span style="color: #38bdf8; font-weight: bold;"><?php echo htmlspecialchars($student['course']); ?></span></td>
                </tr>
                <tr>
                    <td class="label">Semester</td>
                    <td class="val">Semester <?php echo htmlspecialchars(!empty($student['semester']) ? $student['semester'] : 1); ?></td>
                </tr>
                <tr>
                    <td class="label">Section</td>
                    <td class="val">
                        <?php 
                        $sec = !empty($student['section']) ? $student['section'] : '';
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
                    </td>
                </tr>
                <tr>
                    <td class="label">Email</td>
                    <td class="val"><a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" style="color: #38bdf8; text-decoration: none;"><?php echo htmlspecialchars($student['email']); ?></a></td>
                </tr>
                <tr>
                    <td class="label">Phone</td>
                    <td class="val"><a href="tel:<?php echo htmlspecialchars($student['phone']); ?>" style="color: #38bdf8; text-decoration: none;"><?php echo htmlspecialchars($student['phone']); ?></a></td>
                </tr>
                <tr>
                    <td class="label">Date of Birth</td>
                    <td class="val"><?php echo htmlspecialchars($student['dob']); ?></td>
                </tr>
                <tr>
                    <td class="label">Gender</td>
                    <td class="val"><?php echo htmlspecialchars($student['gender']); ?></td>
                </tr>
                <tr>
                    <td class="label">Address</td>
                    <td class="val"><?php echo nl2br(htmlspecialchars($student['address'])); ?></td>
                </tr>
                <tr>
                    <td class="label">Current Status</td>
                    <td class="val">
                        <span class="badge-status badge-<?php echo strtolower($student['status']); ?>">
                            <?php echo htmlspecialchars($student['status']); ?>
                        </span>
                    </td>
                </tr>
            </table>

            <!-- Quick Course, Sem & Section Allotment Editor -->
            <div style="margin-top: 24px; padding: 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px;">
                <h4 style="color: #00d4ff; font-size: 0.95rem; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <span>🏷️</span> Academic & Section Allotment
                </h4>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                    <div style="margin-bottom: 10px;">
                        <label style="font-size: 0.78rem; text-transform: uppercase;">Course</label>
                        <select name="course" style="padding: 7px 10px; font-size: 0.88rem; margin-bottom: 0;">
                            <option value="BCA" <?php if ($student['course'] === 'BCA') echo 'selected'; ?>>BCA</option>
                            <option value="BBA" <?php if ($student['course'] === 'BBA') echo 'selected'; ?>>BBA</option>
                            <option value="BSc" <?php if ($student['course'] === 'BSc') echo 'selected'; ?>>BSc</option>
                            <option value="BA" <?php if ($student['course'] === 'BA') echo 'selected'; ?>>BA</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.78rem; text-transform: uppercase;">Semester</label>
                            <select name="semester" style="padding: 7px 10px; font-size: 0.88rem; margin-bottom: 0;">
                                <?php for ($s = 1; $s <= 8; $s++) { ?>
                                    <option value="<?php echo $s; ?>" <?php if ((int)$student['semester'] === $s) echo 'selected'; ?>>Sem <?php echo $s; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 0.78rem; text-transform: uppercase;">Section</label>
                            <select name="section" style="padding: 7px 10px; font-size: 0.88rem; margin-bottom: 0;">
                                <option value="Unassigned" <?php if (empty($sec) || $sec === 'Unassigned') echo 'selected'; ?>>Not Allotted</option>
                                <option value="A" <?php if ($sec === 'A') echo 'selected'; ?>>Section A</option>
                                <option value="B" <?php if ($sec === 'B') echo 'selected'; ?>>Section B</option>
                                <option value="C" <?php if ($sec === 'C') echo 'selected'; ?>>Section C</option>
                                <option value="D" <?php if ($sec === 'D') echo 'selected'; ?>>Section D</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update_academic" style="width: 100%; padding: 8px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
                        ✓ Save Allotment
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Marks Cards & Verification Documents -->
        <div class="docs-card">
            <h3 style="color: white; font-size: 1.3rem; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
                <span>📄</span> Uploaded Marksheets & Documents Review
            </h3>

            <!-- 10th Standard Marksheet Review -->
            <div class="doc-box">
                <div class="doc-box-header">
                    <h3><span>🎓</span> 10th Standard Marksheet</h3>
                    <div class="doc-btn-group">
                        <?php if (!empty($student['marksheet_10th']) && file_exists($student['marksheet_10th'])) { ?>
                            <a href="<?php echo htmlspecialchars($student['marksheet_10th']); ?>" target="_blank" class="download-btn">
                                &#x29C9; Open in New Tab
                            </a>
                            <a href="<?php echo htmlspecialchars($student['marksheet_10th']); ?>" download class="download-btn" style="background:#2563eb;">
                                &darr; Download
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="doc-preview-area">
                    <?php 
                    $doc10 = $student['marksheet_10th'];
                    if (!empty($doc10) && file_exists($doc10)) {
                        if (is_pdf_file($doc10)) {
                    ?>
                        <iframe src="<?php echo htmlspecialchars($doc10); ?>" title="10th Marksheet PDF"></iframe>
                    <?php } else { ?>
                        <img src="<?php echo htmlspecialchars($doc10); ?>" 
                             alt="10th Marksheet" 
                             onclick="openLightbox('<?php echo htmlspecialchars($doc10); ?>')" 
                             title="Click to zoom in">
                    <?php } 
                    } else { ?>
                        <div class="empty-doc-box">
                            <p style="color: #f87171; font-weight: 500;">&cross; No 10th marksheet file uploaded or file not found on server.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <!-- 12th Standard Marksheet Review -->
            <div class="doc-box">
                <div class="doc-box-header">
                    <h3><span>📜</span> 12th Standard Marksheet</h3>
                    <div class="doc-btn-group">
                        <?php if (!empty($student['marksheet_12th']) && file_exists($student['marksheet_12th'])) { ?>
                            <a href="<?php echo htmlspecialchars($student['marksheet_12th']); ?>" target="_blank" class="download-btn">
                                &#x29C9; Open in New Tab
                            </a>
                            <a href="<?php echo htmlspecialchars($student['marksheet_12th']); ?>" download class="download-btn" style="background:#2563eb;">
                                &darr; Download
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="doc-preview-area">
                    <?php 
                    $doc12 = $student['marksheet_12th'];
                    if (!empty($doc12) && file_exists($doc12)) {
                        if (is_pdf_file($doc12)) {
                    ?>
                        <iframe src="<?php echo htmlspecialchars($doc12); ?>" title="12th Marksheet PDF"></iframe>
                    <?php } else { ?>
                        <img src="<?php echo htmlspecialchars($doc12); ?>" 
                             alt="12th Marksheet" 
                             onclick="openLightbox('<?php echo htmlspecialchars($doc12); ?>')" 
                             title="Click to zoom in">
                    <?php } 
                    } else { ?>
                        <div class="empty-doc-box">
                            <p style="color: #f87171; font-weight: 500;">&cross; No 12th marksheet file uploaded or file not found on server.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <!-- Passport Size Photo Document Review -->
            <div class="doc-box">
                <div class="doc-box-header">
                    <h3><span>📷</span> Passport Size Photograph</h3>
                    <div class="doc-btn-group">
                        <?php if (!empty($student['photo']) && file_exists($student['photo'])) { ?>
                            <a href="<?php echo htmlspecialchars($student['photo']); ?>" target="_blank" class="download-btn">
                                &#x29C9; Open in New Tab
                            </a>
                            <a href="<?php echo htmlspecialchars($student['photo']); ?>" download class="download-btn" style="background:#2563eb;">
                                &darr; Download
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="doc-preview-area" style="min-height: 150px;">
                    <?php if (!empty($student['photo']) && file_exists($student['photo'])) { ?>
                        <img src="<?php echo htmlspecialchars($student['photo']); ?>" 
                             alt="Passport Photo" 
                             style="max-height: 240px; border-radius: 8px;"
                             onclick="openLightbox('<?php echo htmlspecialchars($student['photo']); ?>')" 
                             title="Click to zoom in">
                    <?php } else { ?>
                        <div class="empty-doc-box">
                            <p style="color: #f87171; font-weight: 500;">&cross; No photo uploaded.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox Zoom Modal for Marksheets -->
    <div id="lightbox" class="lightbox-modal" onclick="closeLightbox()">
        <span class="lightbox-close">&times;</span>
        <img id="lightbox-img" src="" alt="Enlarged document preview">
    </div>

    <script>
        function openLightbox(src) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox').style.display = 'flex';
        }

        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
        }

        // Close lightbox on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
    </script>
</body>
</html>
