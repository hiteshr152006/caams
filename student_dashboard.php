<?php
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$name = $_SESSION['student_name'];

// Get student details
$sql = "SELECT * FROM students WHERE student_id = '$student_id'";
$result = mysqli_query($conn, $sql);
$student = mysqli_fetch_assoc($result);

// QR Code URL (points to a public view page)
$qr_data = "http:// 192.168.0.104/college_system/student_view.php?id=" . $student_id;
// If your folder is collage_system, change the above URL accordingly

$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($qr_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
        .content {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }
        .info-card, .id-card {
            background-color: #1a1a1a;
            padding: 28px;
            border-radius: 15px;
            border: 1px solid #333;
            box-sizing: border-box;
        }
        .info-card {
            flex: 1;
            min-width: 280px;
        }
        .info-card h2, .id-card h2 {
            color: #00d4ff;
            margin-bottom: 20px;
        }
        .info-card p {
            margin-bottom: 12px;
            color: #ccc;
            font-size: 1.05rem;
        }
        .info-card strong {
            color: #fff;
        }

        /* Digital ID Card Style */
        .id-card {
            width: 350px;
            max-width: 100%;
            text-align: center;
            background: linear-gradient(145deg, #1a1a1a, #111);
            border: 2px solid #00d4ff;
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
            .content {
                flex-direction: column;
                gap: 16px;
            }
            .info-card, .id-card {
                padding: 18px 14px;
                width: 100% !important;
                min-width: 0 !important;
            }
        }
        .id-card img.photo {
            width: 110px;
            height: 110px;
            border-radius: 10px;
            object-fit: cover;
            border: 3px solid #00d4ff;
            margin-bottom: 15px;
        }
        .id-card h3 {
            color: #00d4ff;
            margin-bottom: 5px;
        }
        .id-card p {
            color: #ccc;
            margin: 4px 0;
            font-size: 0.95rem;
        }
        .qr {
            margin-top: 20px;
        }
        .qr img {
            border: 5px solid white;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome, <?php echo htmlspecialchars($name); ?></h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="menu">
        <a href="student_dashboard.php">My Profile</a>
        <a href="student_attendance.php">My Attendance</a>
        <a href="student_marks.php">My Marks</a>
        <a href="student_timetable.php">Timetable</a>
        <a href="student_material.php">Study Materials</a>
    </div>

    <div class="content">
        <!-- Profile Info -->
        <div class="info-card">
            <h2>Student Profile</h2>
            <p><strong>Student ID:</strong> <?php echo $student['student_id']; ?></p>
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?></p>
            <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?></p>
            <p><strong>Semester:</strong> Semester <?php echo !empty($student['semester']) ? htmlspecialchars($student['semester']) : 1; ?></p>
            <p>
                <strong>Allotted Section:</strong> 
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
                    echo '<span class="badge-sec badge-sec-none">Not Allotted Yet</span>';
                }
                ?>
            </p>
            <p><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></p>
            <p><strong>Date of Birth:</strong> <?php echo $student['dob']; ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($student['address']); ?></p>
        </div>

        <!-- Digital ID Card + QR -->
        <div class="id-card">
            <h2>Digital ID Card</h2>

            <?php if (!empty($student['photo']) && file_exists($student['photo'])) { ?>
                <img src="<?php echo $student['photo']; ?>" class="photo" alt="Student Photo">
            <?php } else { ?>
                <div style="width:110px; height:110px; background:#333; margin:0 auto 15px; border-radius:10px; line-height:110px; color:#777;">No Photo</div>
            <?php } ?>

            <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
            <p><strong>ID:</strong> <?php echo $student['student_id']; ?></p>
            <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?> • Sem <?php echo !empty($student['semester']) ? htmlspecialchars($student['semester']) : 1; ?></p>
            <p>
                <strong>Section:</strong> 
                <?php echo !empty($student['section']) ? 'Section ' . htmlspecialchars($student['section']) : '<span style="color:#f87171;">Not Allotted</span>'; ?>
            </p>

            <div class="qr">
                <p style="margin-bottom:8px; color:#aaa;">Scan QR for Details</p>
                <img src="<?php echo $qr_url; ?>" alt="QR Code">
            </div>
        </div>
    </div>
</body>
</html>