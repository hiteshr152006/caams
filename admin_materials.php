<?php
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$message = "";

// Create uploads/materials folder if not exists
if (!file_exists('uploads/materials')) {
    mkdir('uploads/materials', 0777, true);
}

// Upload new material
if (isset($_POST['upload'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = "Invalid security token. Please refresh the page and try again.";
    } else {
    $title = trim($_POST['title'] ?? '');
    $subject = trim($_POST['subject'] ?? '');

    if ($title != "" && $subject != "" && isset($_FILES['pdf_file'])) {
        $file_name = $_FILES['pdf_file']['name'];
        $file_tmp  = $_FILES['pdf_file']['tmp_name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_ext === "pdf" && ($_FILES['pdf_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file($file_tmp) && mime_content_type($file_tmp) === 'application/pdf') {
            $new_name = bin2hex(random_bytes(12)) . ".pdf";
            $file_path = "uploads/materials/" . $new_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                $stmt = $conn->prepare("INSERT INTO study_materials (title, subject, file_path) VALUES (?, ?, ?)");
                $stmt->bind_param('sss', $title, $subject, $file_path);
                $stmt->execute();
                $message = "PDF uploaded successfully!";
            } else {
                $message = "Failed to upload file.";
            }
        } else {
            $message = "Only PDF files are allowed.";
        }
    } else {
        $message = "Please provide a title, subject, and PDF file.";
    }
    }
}

// Delete material
if (isset($_POST['delete'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        exit('Invalid security token. Please reload the page and try again.');
    }

    $id = (int)$_POST['delete'];

    // Get file path to delete the actual file
    $select_stmt = $conn->prepare("SELECT file_path FROM study_materials WHERE id = ? LIMIT 1");
    $select_stmt->bind_param('i', $id);
    $select_stmt->execute();
    $row = $select_stmt->get_result()->fetch_assoc();
    if ($row) {
        $materials_dir = realpath(__DIR__ . '/uploads/materials');
        $file_path = realpath(__DIR__ . '/' . $row['file_path']);
        if ($materials_dir !== false && $file_path !== false && str_starts_with($file_path, $materials_dir . DIRECTORY_SEPARATOR) && is_file($file_path)) {
            unlink($file_path);
        }
    }

    $delete_stmt = $conn->prepare("DELETE FROM study_materials WHERE id = ?");
    $delete_stmt->bind_param('i', $id);
    $delete_stmt->execute();
    header("Location: admin_materials.php");
    exit();
}

// Fetch all materials
$materials = $conn->query("SELECT * FROM study_materials ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Materials</title>
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
            margin-right: 20px;
            text-decoration: none;
            font-weight: bold;
        }
        .form-box {
            background-color: #1a1a1a;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #333;
            margin-bottom: 30px;
            max-width: 500px;
        }
        label {
            color: #ccc;
            display: block;
            margin-bottom: 6px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            background-color: #0f0f0f;
            border: 1px solid #444;
            border-radius: 8px;
            color: white;
            margin-bottom: 15px;
        }
        button {
            padding: 12px 25px;
            background-color: #00d4ff;
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            background-color: #111;
            color: #00d4ff;
        }
        .delete-btn {
            background-color: #ff4d4d;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .download-btn {
            background-color: #00c853;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            margin-right: 8px;
        }
        .message {
            background-color: #003322;
            color: #00ff88;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Study Materials</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="menu">
        <a href="admin_dashboard.php">Admission Requests</a>
        <a href="admin_sections.php">Section Allotment</a>
        <a href="admin_attendance.php">Attendance</a>
        <a href="admin_marks.php">Marks</a>
        <a href="admin_timetable.php">Timetable</a>
        <a href="admin_materials.php" class="active" style="color: #00d4ff; font-weight: bold;">Study Materials</a>
    </div>

    <?php if ($message != "") { ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <div class="form-box">
        <h3 style="color:#00d4ff; margin-bottom:20px;">Upload New Material (PDF)</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
            <label>Title (Example: Chapter 1 Notes + Important Questions)</label>
            <input type="text" name="title" required>

            <label>Subject</label>
            <input type="text" name="subject" placeholder="Example: Web Development" required>

            <label>Select PDF File</label>
            <input type="file" name="pdf_file" accept=".pdf" required>

            <button type="submit" name="upload">Upload PDF</button>
        </form>
    </div>

    <h2 style="color:#ccc; margin-bottom:15px;">Uploaded Materials</h2>

    <table>
        <tr>
            <th>Title</th>
            <th>Subject</th>
            <th>Uploaded On</th>
            <th>Action</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($materials)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                <td><?php echo date("d M Y", strtotime($row['uploaded_at'])); ?></td>
                <td>
                            <a href="<?php echo htmlspecialchars($row['file_path']); ?>" class="download-btn" target="_blank" rel="noopener">View / Download</a>
                            <form method="POST" class="inline-action-form" onsubmit="return confirm('Delete this material?')">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                                <button type="submit" name="delete" value="<?php echo (int)$row['id']; ?>" class="delete-btn">Delete</button>
                            </form>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>