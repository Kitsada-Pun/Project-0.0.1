<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['user_id'])) {
    // ถ้าไม่ได้ล็อกอิน ให้เปลี่ยนเส้นทางไปหน้า login
    header("Location: ../login.php");
    exit();
}

// --- การตั้งค่าการเชื่อมต่อฐานข้อมูล (ใช้ mysqli) ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pixellink"; // <--- ตรวจสอบว่าชื่อฐานข้อมูลถูกต้อง

$condb = new mysqli($servername, $username, $password, $dbname);
if ($condb->connect_error) {
    error_log("Connection failed: " . $condb->connect_error);
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาลองใหม่อีกครั้ง");
}
$condb->set_charset("utf8mb4");

// ดึง user_id จาก URL (GET parameter)
$user_id_to_view = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

$profile_data = null;

// ตรวจสอบข้อมูลผู้ใช้ที่เข้าสู่ระบบสำหรับ Navbar
$loggedInUserName = '';
if (isset($_SESSION['user_id'])) {
    $loggedInUserName = $_SESSION['username'] ?? $_SESSION['full_name'] ?? '';
    // Fallback ดึงจากฐานข้อมูลหาก session ไม่มีข้อมูล
    if (empty($loggedInUserName)) {
        $user_id_session = $_SESSION['user_id'];
        $sql_loggedInUser = "SELECT first_name, last_name FROM users WHERE user_id = ?";
        $stmt_loggedInUser = $condb->prepare($sql_loggedInUser);
        if ($stmt_loggedInUser) {
            $stmt_loggedInUser->bind_param("i", $user_id_session);
            $stmt_loggedInUser->execute();
            $result_loggedInUser = $stmt_loggedInUser->get_result();
            if ($result_loggedInUser->num_rows === 1) {
                $user_info = $result_loggedInUser->fetch_assoc();
                $loggedInUserName = $user_info['first_name'] . ' ' . $user_info['last_name'];
                // บันทึกกลับเข้า session เพื่อใช้ในอนาคต
                $_SESSION['first_name'] = $user_info['first_name'];
                $_SESSION['last_name'] = $user_info['last_name'];
                $_SESSION['full_name'] = $loggedInUserName;
            }
            $stmt_loggedInUser->close();
        }
    }
}


if ($user_id_to_view > 0) {
    // ดึงข้อมูลโปรไฟล์จากตาราง 'profiles' และ 'users'
    $sql_profile = "SELECT
                        p.address,
                        p.company_name,
                        p.bio AS profile_bio,
                        p.portfolio_url,
                        p.skills,
                        p.profile_picture_url AS profile_pic_from_profiles,
                        u.first_name AS user_first_name,
                        u.last_name AS user_last_name,
                        u.email AS user_email,
                        u.phone_number AS user_tel,
                        u.username AS username_from_users
                    FROM profiles AS p
                    JOIN users AS u ON p.user_id = u.user_id
                    WHERE p.user_id = ?";

    $stmt_profile = $condb->prepare($sql_profile);
    if ($stmt_profile === false) {
        error_log("SQL Prepare Error (profile): " . $condb->error);
        die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $condb->error);
    } else {
        $stmt_profile->bind_param("i", $user_id_to_view);
        $stmt_profile->execute();
        $result_profile = $stmt_profile->get_result();
        $profile_data = $result_profile->fetch_assoc();
        $stmt_profile->close();
    }

    // --- PHP Logic สำหรับดึงงานที่ผู้ใช้งานที่กำลังดูโปรไฟล์เป็นผู้ประกาศ (ผลงานของฉัน) ---
    $job_postings_for_profile = []; // เปลี่ยนชื่อตัวแปรจาก available_jobs เพื่อให้ชัดเจนและไม่ซ้ำกับตัวแปรหลักอื่น
    $sql_job_postings_for_profile = "SELECT
                                jp.post_id,
                                jp.title,
                                jp.description,
                                jp.price_range,
                                jp.posted_date,
                                u.first_name,
                                u.last_name,
                                jc.category_name
                            FROM job_postings AS jp
                            JOIN users AS u ON jp.designer_id = u.user_id 
                            LEFT JOIN job_categories AS jc ON jp.category_id = jc.category_id
                            WHERE jp.designer_id = ? AND jp.status = 'active'
                            ORDER BY jp.posted_date DESC"; // เรียงตามวันที่โพสต์ล่าสุด

    $stmt_job_postings_for_profile = $condb->prepare($sql_job_postings_for_profile);
    if ($stmt_job_postings_for_profile === false) {
        error_log("SQL Prepare Error (job_postings_for_profile): " . $condb->error);
    } else {
        $stmt_job_postings_for_profile->bind_param("i", $user_id_to_view); // ผูก user_id_to_view
        $stmt_job_postings_for_profile->execute();
        $result_job_postings_for_profile = $stmt_job_postings_for_profile->get_result();
        $job_postings_for_profile = $result_job_postings_for_profile->fetch_all(MYSQLI_ASSOC);
        $stmt_job_postings_for_profile->close();
    }

} else {
    // หาก user_id ไม่ได้ระบุใน URL หรือเป็น 0, ให้ถือว่าไม่พบโปรไฟล์
    $profile_data = null; 
}

$condb->close();

// กำหนดค่าเริ่มต้นสำหรับข้อมูลที่จะแสดงผล
// ใช้ ?? '' สำหรับค่าว่าง เพื่อป้องกัน Warning หาก key ไม่มีอยู่
$display_name = trim(($profile_data['user_first_name'] ?? '') . ' ' . ($profile_data['user_last_name'] ?? '')) ?: ($profile_data['username_from_users'] ?? 'ไม่ระบุชื่อ');

$display_email = $profile_data['user_email'] ?? 'ไม่ระบุอีเมล';
$display_tel = $profile_data['user_tel'] ?? 'ไม่ระบุเบอร์โทรศัพท์';
$display_rating = 'ยังไม่มีคะแนน'; // ต้องดึงคะแนนรีวิวจาก DB
$display_address = $profile_data['address'] ?? 'ไม่ระบุที่อยู่';
$display_company_name = $profile_data['company_name'] ?? 'ไม่ระบุบริษัท';
$display_bio = $profile_data['profile_bio'] ?? 'ยังไม่มีประวัติ';
$display_portfolio_url = $profile_data['portfolio_url'] ?? null;
$display_skills = $profile_data['skills'] ? explode(',', $profile_data['skills']) : [];
$display_profile_pic = $profile_data['profile_pic_from_profiles'] ?? '../dist/img/default_profile.png';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของ <?= htmlspecialchars($display_name) ?> | PixelLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            font-family: 'Kanit', sans-serif;
            font-style: normal;
            font-weight: 400;
        }

        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #e8edf3 100%);
            color: #2c3e50;
            overflow-x: hidden; /* Added to ensure no horizontal scroll on body */
        }

        .navbar {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .btn-primary {
            background: linear-gradient(45deg, #0a5f97 0%, #0d96d2 100%);
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(13, 150, 210, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #0d96d2 0%, #0a5f97 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 150, 210, 0.5);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2);
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(108, 117, 125, 0.4);
        }

        .text-gradient {
            background: linear-gradient(45deg, #0a5f97, #0d96d2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .pixellink-logo {
            font-weight: 700;
            font-size: 2.25rem;
            background: linear-gradient(45deg, #0a5f97, #0d96d2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .pixellink-logo b {
            color: #0d96d2;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-item {
            background-color: #e0f2f7;
            /* Light blue */
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
        }

        .card-item {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid rgba(0, 0, 0, 0.05);
            flex-shrink: 0;
            /* REMOVED fixed width: width: 300px; */
        }

        .card-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .card-image {
            width: 100%;
            aspect-ratio: 16/9; /* Maintain 16:9 aspect ratio */
            object-fit: cover; /* Cover the area, cropping if necessary */
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
        }

        .feature-icon {
            color: #0d96d2;
            transition: transform 0.3s ease;
        }

        .card-item:hover .feature-icon {
            transform: translateY(-3px);
        }

        /* Carousel Styles */
        .carousel-container {
            position: relative;
            display: flex;
            align-items: center;
            padding: 0 2rem; /* Add padding to make space for buttons */
        }

        .carousel-content {
            display: grid;
            grid-auto-flow: column; /* Allows items to flow horizontally */
            /* grid-auto-columns: 100%; /* Each slide takes 100% of the container width (เดิม) */
            gap: 1.5rem; /* Gap between cards */
            overflow-x: scroll; /* Enable horizontal scrolling */
            scroll-behavior: smooth; /* Smooth scrolling effect */
            -webkit-overflow-scrolling: touch;
            flex-grow: 1;
            /* Hide scrollbar */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none;  /* IE and Edge */
        }

        /* Hide scrollbar for Chrome, Safari, Opera */
        .carousel-content::-webkit-scrollbar {
            display: none;
        }

        /* Adjust grid-auto-columns for multiple cards visible (now truly controls width) */
        @media (min-width: 768px) {
            .carousel-content {
                /* For medium screens and up, show 2 cards with a gap */
                grid-auto-columns: calc(50% - 0.75rem); /* 50% width minus half the gap */
            }
        }

        @media (min-width: 1024px) {
            .carousel-content {
                /* For large screens and up, show 3 cards with gaps */
                grid-auto-columns: calc(33.333% - 1rem); /* 33.333% width minus 2/3 of the gap */
            }
        }

        .carousel-button {
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 0.75rem 0.5rem;
            cursor: pointer;
            z-index: 10;
            border-radius: 9999px; /* Fully rounded */
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            transition: background-color 0.3s ease, opacity 0.3s ease;
            opacity: 0.8;
            width: 2.5rem; /* Fixed width for consistent button size */
            height: 2.5rem; /* Fixed height for consistent button size */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .carousel-button:hover {
            background-color: rgba(0, 0, 0, 0.7);
            opacity: 1;
        }

        .carousel-button.left {
            left: 0;
        }

        .carousel-button.right {
            right: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-section {
                padding: 6rem 0;
            }

            .hero-section h1 {
                font-size: 2.8rem;
            }

            .hero-section p {
                font-size: 1rem;
            }

            .hero-section .space-x-0 {
                flex-direction: column;
                gap: 1rem;
            }

            .hero-section .btn-primary,
            .hero-section .btn-secondary {
                width: 90%;
                max-width: none;
                font-size: 0.9rem;
                padding: 0.75rem 1.25rem;
            }

            .pixellink-logo {
                font-size: 1.6rem;
            }

            .navbar .px-5 {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .navbar .py-2 {
                padding-top: 0.3rem;
                padding-bottom: 0.3rem;
            }

            h2 {
                font-size: 1.8rem;
            }

            .card-item {
                border-radius: 0.75rem;
                padding: 1rem;
            }

            .card-image {
                height: auto; /* Allow auto height with aspect ratio */
            }

            .flex-col.sm\:flex-row {
                flex-direction: column;
            }

            .flex-col.sm\:flex-row>*:not(:last-child) {
                margin-bottom: 1rem;
            }

            .md\:mb-0 {
                margin-bottom: 1rem;
            }

            .footer-links {
                flex-direction: column;
                gap: 0.5rem;
            }

            /* Adjust carousel buttons for small screens */
            .carousel-button.left {
                left: 0.5rem;
            }

            .carousel-button.right {
                right: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .hero-section h1 {
                font-size: 2.2rem;
            }

            .hero-section p {
                font-size: 0.875rem;
            }

            .pixellink-logo {
                font-size: 1.4rem;
            }

            h2 {
                font-size: 1.5rem;
            }

            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .px-6 {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .p-10 {
                padding: 1.5rem;
            }

            .card-item {
                padding: 0.75rem;
            }

            .card-image {
                height: 120px;
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">

    <nav class="navbar p-4 shadow-md sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <a href="../designer/main.php" class="transition duration-300 hover:opacity-80">
                <img src="../dist/img/logo.png" alt="PixelLink Logo" class="h-12">
            </a>
            <div class="space-x-2 sm:space-x-4 flex items-center">
                <?php if (isset($_SESSION['user_id'])) : ?>
                    <span class="text-gray-700 font-medium">สวัสดี, <?= htmlspecialchars($loggedInUserName) ?>!</span>

                    <a href="view_profile.php?user_id=<?php echo $_SESSION['user_id']; ?>" class="
                        bg-blue-500 text-white
                        px-3 py-1.5 sm:px-5 sm:py-2
                        rounded-lg font-medium
                        shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300
                        focus:outline-none focus:ring-2 focus:ring-blue-300
                    ">
                        <i class="fas fa-user-circle mr-1"></i> ดูโปรไฟล์
                    </a>

                    <a href="../logout.php" class="
                        bg-red-500 text-white
                        px-3 py-1.5 sm:px-5 sm:py-2
                        rounded-lg font-medium
                        shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300
                        focus:outline-none focus:ring-2 focus:ring-red-300
                    ">
                        <i class="fas fa-sign-out-alt mr-1"></i> ออกจากระบบ
                    </a>
                <?php else : ?>
                    <a href="../login.php"
                        class="px-5 py-2 rounded-lg font-semibold border-2 border-transparent hover:border-blue-500 hover:text-blue-500 transition duration-300">เข้าสู่ระบบ</a>
                    <a href="../register.php"
                        class="btn-primary px-5 py-2 rounded-lg font-semibold shadow-lg">สมัครสมาชิก</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-8 md:py-12">
        <?php if (!$profile_data): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative text-center">
                <span class="block sm:inline">ไม่พบข้อมูลโปรไฟล์สำหรับผู้ใช้งานนี้ หรือรหัสผู้ใช้งานไม่ถูกต้อง</span>
            </div>
        <?php else: ?>
            <div class="profile-card p-6 md:p-10 max-w-full mx-auto">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6 md:gap-10 mb-8">
                    <div class="flex-shrink-0">
                        <img src="../dist/img/user1-128x128.jpg" alt="รูปโปรไฟล์"
                            class="w-32 h-32 md:w-40 md:h-40 rounded-full object-cover shadow-lg border-4 border-white">
                    </div>
                    <div class="text-center md:text-left flex-grow">
                        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2 leading-tight">
                            <?= htmlspecialchars($display_name) ?>
                        </h1>
                        <p class="text-md text-gray-600 mb-1">
                            <i class="fas fa-envelope mr-2"></i><?= htmlspecialchars($display_email) ?>
                        </p>
                        <p class="text-md text-gray-600 mb-1">
                            <i class="fas fa-phone mr-2"></i><?= htmlspecialchars($display_tel) ?>
                        </p>
                        <p class="text-md text-gray-600 mb-4">
                            <i class="fas fa-building mr-2"></i><?= htmlspecialchars($display_company_name) ?>
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="stat-item">
                                <p class="text-gray-700 font-semibold text-lg">คะแนนรีวิว</p>
                                <p class="text-blue-600 text-xl font-bold"><?= htmlspecialchars($display_rating) ?> <i
                                        class="fas fa-star text-yellow-500 ml-1"></i></p>
                            </div>
                            <div class="stat-item">
                                <p class="text-gray-700 font-semibold text-lg">ที่อยู่</p>
                                <p class="text-blue-600 text-xl font-bold line-clamp-1">
                                    <?= htmlspecialchars($display_address) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-semibold text-gradient mb-4">เกี่ยวกับฉัน</h2>
                    <p class="text-gray-700 leading-relaxed">
                        <?= nl2br(htmlspecialchars($display_bio)) ?>
                    </p>
                </div>

                <?php if (!empty($display_skills)): ?>
                    <div class="mb-8">
                        <h2 class="text-2xl font-semibold text-gradient mb-4">ทักษะ</h2>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($display_skills as $skill): ?>
                                <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                                    <?= htmlspecialchars(trim($skill)) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($display_portfolio_url): ?>
                    <div class="mb-8">
                        <h2 class="text-2xl font-semibold text-gradient mb-4">แชร์ผลงานของคุณ</h2>
                        <p class="text-gray-700">
                            <i class="fas fa-link mr-2"></i><a href="<?= htmlspecialchars($display_portfolio_url) ?>"
                                target="_blank" class="text-blue-600 hover:underline break-words">
                                <?= htmlspecialchars($display_portfolio_url) ?>
                            </a>
                        </p>
                        <p class="text-sm text-gray-500 mt-1">ลิงก์ผลงานของคุณ</p>
                    </div>
                <?php else: ?>

                    <div class="mb-8">
                        <h2 class="text-2xl font-semibold text-gradient mb-4">พอร์ตโฟลิโอ</h2>
                        <p class="text-gray-500">
                            ยังไม่ได้ระบุลิงก์พอร์ตโฟลิโอ
                        </p>
                        <?php if ($user_id_to_view == $_SESSION['user_id']): // ถ้าเป็นโปรไฟล์ของตัวเอง ให้แสดงปุ่มให้ไปเพิ่มลิงก์ ?>
                            <a href="post_portfolio.php" class="text-blue-600 hover:underline mt-2 inline-block">
                                คลิกที่นี่เพื่อเพิ่มลิงก์พอร์ตโฟลิโอ
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="mb-8">
                    <h2 class="text-2xl font-semibold text-gradient mb-1">โพสประกาศงานของคุณ</h2>
                    <section id="available-jobs" class="py-4 md:py-6 bg-white">
                        <div class="container mx-auto px-4 md:px-6">
                            <?php if (empty($job_postings_for_profile)): /* ใช้ตัวแปรที่แก้ไขชื่อแล้ว */ ?>
                                <div
                                    class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg relative text-center">
                                    <span class="block sm:inline">ยังไม่มีงานที่แสดงในขณะนี้</span>
                                </div>
                            <?php else: ?>
                                <div class="carousel-container relative">
                                    <button id="prevBtn" class="carousel-button left">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>

                                    <div class="carousel-content" id="carouselContent">
                                        <?php foreach ($job_postings_for_profile as $job): /* ใช้ตัวแปรที่แก้ไขชื่อแล้ว */ ?>
                                            <div class="card-item animate-card-appear">
                                                <img src="https://source.unsplash.com/400x250/?creative-design,<?= urlencode($job['category_name'] ?? 'web-design') ?>"
                                                    alt="งานที่เปิดรับ: <?= htmlspecialchars($job['title']) ?>" class="card-image"
                                                    onerror="this.onerror=null;this.src='../dist/img/pdpa02.jpg';">
                                                <div class="p-4 md:p-6 flex-grow flex flex-col justify-between">
                                                    <div>
                                                        <h3
                                                            class="text-lg md:text-xl font-semibold text-gray-900 mb-1 md:mb-2 line-clamp-2">
                                                            <?= htmlspecialchars($job['title']) ?>
                                                        </h3>
                                                        <p class="text-xs md:text-sm text-gray-600 mb-1 md:mb-2">โดย: <span
                                                                class="font-medium text-blue-700"><?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']) ?></span>
                                                        </p>
                                                        <p class="text-xs md:text-sm text-gray-500 mb-2 md:mb-4">
                                                            <i class="fas fa-tag mr-1 text-blue-500"></i> หมวดหมู่: <span
                                                                class="font-normal"><?= htmlspecialchars($job['category_name'] ?? 'ไม่ระบุ') ?></span>
                                                        </p>
                                                        <p
                                                            class="text-sm md:text-base text-gray-700 mb-2 md:mb-4 line-clamp-3 font-light">
                                                            <?= htmlspecialchars($job['description']) ?>
                                                        </p>
                                                    </div>
                                                    <div class="mt-2 md:mt-4">
                                                        <p class="text-base md:text-lg font-semibold text-green-700 mb-1 md:mb-2">ราคา:
                                                            <?= htmlspecialchars($job['price_range'] ?? 'สอบถาม') ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500 mb-2 md:mb-4">ประกาศเมื่อ: <span
                                                                class="font-light"><?= date('d M Y', strtotime($job['posted_date'])) ?></span>
                                                        </p>
                                                        <a href="../job_detail.php?id=<?= $job['post_id'] ?>&type=posting"
                                                            class="btn-primary px-4 py-2 sm:px-5 sm:py-2 rounded-lg font-medium shadow-lg">
                                                            ดูรายละเอียด <i class="fas fa-arrow-right ml-1"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button id="nextBtn" class="carousel-button right">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8 mt-auto">
        <div class="container mx-auto px-4 md:px-6 text-center">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <a href="../designer/main.php"
                    class="text-2xl sm:text-3xl font-bold pixellink-logo mb-4 md:mb-0 transition duration-300 hover:opacity-80">Pixel<b>Link</b></a>
                <div class="flex flex-wrap justify-center space-x-2 md:space-x-6 text-sm md:text-base footer-links">
                    <a href="#"
                        class="hover:text-white transition duration-300 mb-2 md:mb-0 font-light">เกี่ยวกับเรา</a>
                    <a href="#" class="hover:text-white transition duration-300 mb-2 md:mb-0 font-light">ติดต่อเรา</a>
                    <a href="#"
                        class="hover:text-white transition duration-300 mb-2 md:mb-0 font-light">เงื่อนไขการใช้งาน</a>
                    <a href="#"
                        class="hover:text-white transition duration-300 mb-2 md:mb-0 font-light">นโยบายความเป็นส่วนตัว</a>
                </div>
            </div>
            <hr class="border-gray-700 my-6">
            <p class="text-xs md:text-sm font-light">&copy; <?php echo date('Y'); ?> PixelLink. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carouselContent = document.getElementById('carouselContent');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            // Check if elements exist before trying to query children
            const cardItems = carouselContent ? carouselContent.querySelectorAll('.card-item') : [];


            let scrollInterval; // To store the interval ID for continuous scrolling
            const scrollAmount = 200; // Amount to scroll per interval

            if (!carouselContent || cardItems.length === 0) {
                // Hide buttons if no content or carousel not found
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
                return;
            }

            // Function to update button visibility based on scroll position
            function updateButtonVisibility() {
                // A small tolerance is added due to potential sub-pixel rendering issues
                const tolerance = 5;

                if (carouselContent.scrollLeft <= tolerance) { // Check if at the very beginning
                    prevBtn.style.display = 'none';
                } else {
                    prevBtn.style.display = 'flex'; // Use 'flex' to maintain center alignment of icon
                }

                if (carouselContent.scrollLeft + carouselContent.clientWidth >= carouselContent.scrollWidth - tolerance) {
                    nextBtn.style.display = 'none';
                } else {
                    nextBtn.style.display = 'flex'; // Use 'flex'
                }

                // If content is not scrollable at all, hide both
                if (carouselContent.scrollWidth <= carouselContent.clientWidth + tolerance) {
                    prevBtn.style.display = 'none';
                    nextBtn.style.display = 'none';
                }
            }

            // Event listeners for "Next" button for press and hold
            nextBtn.addEventListener('mousedown', () => {
                clearInterval(scrollInterval); // Clear any existing interval to prevent multiple intervals
                scrollInterval = setInterval(() => {
                    carouselContent.scrollBy({
                        left: scrollAmount,
                        behavior: 'smooth'
                    });
                    // Update visibility immediately after scroll to ensure responsiveness
                    updateButtonVisibility();
                }, 100); // Scroll every 100 milliseconds
            });

            // Stop scrolling when mouse button is released or mouse leaves the button
            nextBtn.addEventListener('mouseup', () => {
                clearInterval(scrollInterval);
            });
            nextBtn.addEventListener('mouseleave', () => {
                clearInterval(scrollInterval);
            });

            // Event listeners for "Previous" button for press and hold
            prevBtn.addEventListener('mousedown', () => {
                clearInterval(scrollInterval); // Clear any existing interval
                scrollInterval = setInterval(() => {
                    carouselContent.scrollBy({
                        left: -scrollAmount,
                        behavior: 'smooth'
                    });
                    // Update visibility immediately after scroll
                    updateButtonVisibility();
                }, 100); // Scroll every 100 milliseconds
            });

            // Stop scrolling when mouse button is released or mouse leaves the button
            prevBtn.addEventListener('mouseup', () => {
                clearInterval(scrollInterval);
            });
            prevBtn.addEventListener('mouseleave', () => {
                clearInterval(scrollInterval);
            });

            // Listen for actual scroll events to ensure button visibility is always accurate
            // This handles cases where scrolling might occur due to other means (e.g., trackpad, keyboard)
            carouselContent.addEventListener('scroll', updateButtonVisibility);

            // Initial check on load
            updateButtonVisibility();

            // Re-check on window resize to adjust button visibility for responsive layout changes
            window.addEventListener('resize', updateButtonVisibility);
        });
    </script>
</body>

</html>