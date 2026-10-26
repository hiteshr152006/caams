<?php
include 'config.php';

$message = "";

if (isset($_POST['submit'])) {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone     = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $dob       = mysqli_real_escape_string($conn, trim($_POST['dob']));
    $gender    = mysqli_real_escape_string($conn, trim($_POST['gender']));
    $address   = mysqli_real_escape_string($conn, trim($_POST['address']));
    $course    = mysqli_real_escape_string($conn, trim($_POST['course']));
    $semester  = isset($_POST['semester']) ? (int)$_POST['semester'] : 1;

    // File upload handling
    $photo_name = basename($_FILES['photo']['name']);
    $mark10_name = basename($_FILES['marksheet_10th']['name']);
    $mark12_name = basename($_FILES['marksheet_12th']['name']);

    $photo_tmp = $_FILES['photo']['tmp_name'];
    $mark10_tmp = $_FILES['marksheet_10th']['tmp_name'];
    $mark12_tmp = $_FILES['marksheet_12th']['tmp_name'];

    // Validate document extensions (PDF and images)
    $allowed_images = ['jpg', 'jpeg', 'png', 'webp'];
    $allowed_docs   = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    $photo_ext  = strtolower(pathinfo($photo_name, PATHINFO_EXTENSION));
    $mark10_ext = strtolower(pathinfo($mark10_name, PATHINFO_EXTENSION));
    $mark12_ext = strtolower(pathinfo($mark12_name, PATHINFO_EXTENSION));

    if (!in_array($photo_ext, $allowed_images)) {
        $message = "Error: Passport photo must be an image file (JPG, PNG, WEBP).";
    } elseif (!in_array($mark10_ext, $allowed_docs)) {
        $message = "Error: 10th Marksheet must be a PDF or Image (PDF, JPG, PNG).";
    } elseif (!in_array($mark12_ext, $allowed_docs)) {
        $message = "Error: 12th Marksheet must be a PDF or Image (PDF, JPG, PNG).";
    } else {
        // Create uploads folder if not exists
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }

        // Sanitize filenames to prevent issues with spaces or special characters
        $safe_photo  = preg_replace("/[^a-zA-Z0-9._-]/", "_", $photo_name);
        $safe_mark10 = preg_replace("/[^a-zA-Z0-9._-]/", "_", $mark10_name);
        $safe_mark12 = preg_replace("/[^a-zA-Z0-9._-]/", "_", $mark12_name);

        $photo_path  = "uploads/" . time() . "_photo_" . $safe_photo;
        $mark10_path = "uploads/" . time() . "_10th_" . $safe_mark10;
        $mark12_path = "uploads/" . time() . "_12th_" . $safe_mark12;

        if (move_uploaded_file($photo_tmp, $photo_path) &&
            move_uploaded_file($mark10_tmp, $mark10_path) &&
            move_uploaded_file($mark12_tmp, $mark12_path)) {

            $sql = "INSERT INTO students (full_name, email, phone, dob, gender, address, course, semester, section, photo, marksheet_10th, marksheet_12th) 
                    VALUES ('$full_name', '$email', '$phone', '$dob', '$gender', '$address', '$course', $semester, NULL, '$photo_path', '$mark10_path', '$mark12_path')";

            if (mysqli_query($conn, $sql)) {
                $message = "Application submitted successfully! Please wait for admin approval.";
            } else {
                $message = "Error: " . mysqli_error($conn);
            }
        } else {
            $message = "Error: Failed to upload documents. Please check file sizes and try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Form</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: var(--bg);
            color: var(--text);
            padding: 24px 14px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
        }
        .form-container {
            background-color: #1a1a1a;
            padding: 36px 28px;
            border-radius: 16px;
            width: 100%;
            max-width: 580px;
            margin: 0 auto;
            border: 1px solid #333;
            box-sizing: border-box;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
        .form-container h2 {
            text-align: center;
            color: #00d4ff;
            margin-bottom: 24px;
            font-size: 1.6rem;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #ccc;
            font-size: 0.9rem;
            font-weight: 500;
        }
        input, select, textarea {
            width: 100%;
            max-width: 100%;
            padding: 12px 14px;
            background-color: #0f0f0f;
            border: 1px solid #444;
            border-radius: 8px;
            color: white;
            font-size: 16px; /* Prevents auto-zoom on mobile */
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        input[type="file"] {
            padding: 9px 12px;
            background-color: #111827;
            font-size: 0.88rem;
            cursor: pointer;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #00d4ff;
        }
        button {
            width: 100%;
            padding: 13px;
            background-color: #00d4ff;
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: #00b8e0;
        }
        .message {
            text-align: center;
            margin-bottom: 20px;
            color: #00ff88;
            padding: 12px;
            background: rgba(0, 255, 136, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(0, 255, 136, 0.25);
            font-size: 0.95rem;
        }
        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #00d4ff;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .back:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            body {
                padding: 14px 10px;
                display: block;
            }
            .form-container {
                padding: 22px 16px;
                border-radius: 12px;
                margin: 10px auto;
            }
            .form-container h2 {
                font-size: 1.35rem;
                margin-bottom: 18px;
            }
            .form-group {
                margin-bottom: 14px;
            }
            button {
                padding: 12px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Student Admission Form</h2>

        <?php if ($message != "") { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" required>
            </div>

            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="dob" required>
            </div>

            <div class="form-group">
                <label>Gender</label>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label>Course</label>
                <select name="course" required>
                    <option value="">Select Course</option>
                    <option value="BCA">BCA</option>
                    <option value="BBA">BBA</option>
                    <option value="BSc">BSc</option>
                    <option value="BA">BA</option>
                </select>
            </div>

            <div class="form-group">
                <label>Applying for Semester</label>
                <select name="semester" required>
                    <option value="1" selected>Semester 1 (Fresh Admission)</option>
                    <option value="2">Semester 2</option>
                    <option value="3">Semester 3 (Direct / Lateral Entry)</option>
                    <option value="4">Semester 4</option>
                    <option value="5">Semester 5</option>
                    <option value="6">Semester 6</option>
                </select>
            </div>

            <div class="form-group">
                <label>Passport Size Photo</label>
                <input type="file" name="photo" accept="image/*" required>
            </div>

            <div class="form-group">
                <label>10th Marksheet</label>
                <input type="file" name="marksheet_10th" accept="image/*,.pdf" required>
            </div>

            <div class="form-group">
                <label>12th Marksheet</label>
                <input type="file" name="marksheet_12th" accept="image/*,.pdf" required>
            </div>

            <button type="submit" name="submit">Submit Application</button>
        </form>

        <a href="index.php" class="back">← Back to Home</a>
    </div>
</body>
</html> 