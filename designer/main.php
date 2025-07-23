<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ และเป็น 'designer'
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'designer') {
    // ถ้าไม่ได้ล็อกอินหรือไม่ใช่ designer ให้เปลี่ยนเส้นทางไปหน้า login
    header("Location: ../login.php");
    exit();
}

// --- การตั้งค่าการเชื่อมต่อฐานข้อมูล (ใช้ mysqli) ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pixellink"; // <--- เปลี่ยนเป็นชื่อฐานข้อมูล 'pixellink'

$condb = new mysqli($servername, $username, $password, $dbname);
if ($condb->connect_error) {
    error_log("Connection failed: " . $condb->connect_error);
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาลองใหม่อีกครั้ง");
}
$condb->set_charset("utf8mb4");

// ดึงข้อมูลผู้ใช้ปัจจุบัน (Designer)
$designer_id = $_SESSION['user_id'];
$designer_name = $_SESSION['username'] ?? $_SESSION['full_name']; // ใช้ full_name ถ้ามี, ไม่งั้นใช้ username

// --- PHP Logic สำหรับดึงงานที่นักออกแบบได้รับมอบหมาย หรือ งานที่น่าสนใจสำหรับนักออกแบบ ---
$assigned_jobs = [];
// ตัวอย่าง: ดึงงานที่ 'designer' ได้รับมอบหมาย (สถานะ 'in_progress', 'pending_review')
$sql_assigned_jobs = "SELECT
                            jp.post_id,
                            jp.title,
                            jp.description,
                            jp.price_range,
                            jp.posted_date,
                            jp.status AS job_status,
                            u.first_name AS client_first_name,
                            u.last_name AS client_last_name,
                            jc.category_name
                        FROM job_postings AS jp
                        JOIN users AS u ON jp.client_id = u.user_id -- สมมติว่ามี client_id ใน job_postings
                        LEFT JOIN job_categories AS jc ON jp.category_id = jc.category_id
                        WHERE jp.designer_id = ? AND jp.status IN ('in_progress', 'pending_review')
                        ORDER BY jp.posted_date DESC
                        LIMIT 6";

$stmt_assigned_jobs = $condb->prepare($sql_assigned_jobs);
if ($stmt_assigned_jobs === false) {
    error_log("SQL Prepare Error (assigned_jobs): " . $condb->error);
} else {
    $stmt_assigned_jobs->bind_param("i", $designer_id);
    $stmt_assigned_jobs->execute();
    $result_assigned_jobs = $stmt_assigned_jobs->get_result();
    $assigned_jobs = $result_assigned_jobs->fetch_all(MYSQLI_ASSOC);
    $stmt_assigned_jobs->close();
}

// --- PHP Logic สำหรับดึงงานที่ยังเปิดรับ (สำหรับ Designer ไปเสนอราคา) ---
$available_jobs = [];
$sql_available_jobs = "SELECT
                            jp.post_id,
                            jp.title,
                            jp.description,
                            jp.price_range,
                            jp.posted_date,
                            u.first_name,
                            u.last_name,
                            jc.category_name
                        FROM job_postings AS jp
                        JOIN users AS u ON jp.designer_id = u.user_id -- หรือ client_id ถ้าโพสต์โดยลูกค้า
                        LEFT JOIN job_categories AS jc ON jp.category_id = jc.category_id
                        WHERE jp.status = 'active' 
                        ORDER BY RAND()
                        LIMIT 12";


$stmt_available_jobs = $condb->prepare($sql_available_jobs);
if ($stmt_available_jobs === false) {
    error_log("SQL Prepare Error (available_jobs): " . $condb->error);
} else {
    $stmt_available_jobs->execute();
    $result_available_jobs = $stmt_available_jobs->get_result();
    $available_jobs = $result_available_jobs->fetch_all(MYSQLI_ASSOC);
    $stmt_available_jobs->close();
}


$condb->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Designer | PixelLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />

    <style>
        * {
            font-family: 'Kanit', sans-serif;
            font-style: normal;
            font-weight: 400;
        }

        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #e8edf3 100%);
            color: #2c3e50;
            overflow-x: hidden;
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
        }

        .card-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
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
                height: 160px;
            }

            .sm\:grid-cols-2 {
                grid-template-columns: 1fr;
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
            <a href="main.php" class="transition duration-300 hover:opacity-80">
                <img src="../dist/img/logo.png" alt="PixelLink Logo" class="h-12">
            </a>
            <div class="space-x-2 sm:space-x-4 flex items-center">
                <span class="text-gray-700 font-medium">สวัสดี, <?= htmlspecialchars($designer_name) ?>!</span>

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
            </div>
        </div>
    </nav>

    <header class="hero-section flex-grow flex items-center justify-center text-white py-16 relative overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('../dist/img/cover.png');">
        </div>

        <div class="text-center p-6 md:p-10 rounded-xl shadow-2xl max-w-4xl relative z-10 mx-4 bg-white bg-opacity-0">
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-extralight mb-4 md:mb-6 leading-tight">
                พื้นที่ทำงานนักออกแบบ
            </h1>
            <p class="text-base sm:text-lg md:text-xl mb-6 md:mb-8 leading-relaxed opacity-90 font-light">
                จัดการโครงการของคุณ, ค้นหางานใหม่, และนำเสนอผลงานสู่ผู้ว่าจ้าง
            </p>
            <div class="space-x-0 sm:space-x-4 flex flex-col sm:flex-row justify-center items-center">
                <a href="#available-jobs" class="
                    bg-emerald-500 text-white
                    px-6 py-3 sm:px-8 sm:py-4
                    text-base sm:text-lg rounded-lg font-semibold
                    shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300
                    w-full sm:w-auto mb-3 sm:mb-0
                    hover:bg-emerald-600 focus:outline-none focus:ring-4 focus:ring-emerald-300
                    whitespace-nowrap 
                ">
                    <i class="fas fa-tasks mr-2"></i> หางานใหม่
                </a>
                <a href="my_projects.php" class="
                    bg-blue-500 text-white
                    px-6 py-3 sm:px-8 sm:py-4
                    text-base sm:text-lg rounded-lg font-semibold
                    shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300
                    w-full sm:w-auto mb-3 sm:mb-0
                    hover:bg-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-300
                    whitespace-nowrap
                ">
                    <i class="fas fa-search ml-2"></i> โปรเจกต์ของคุณ
                </a>
                <a href="post_portfolio.php" class="
                    bg-gray-200 text-gray-800
                    px-6 py-3 sm:px-8 sm:py-4
                    text-base sm:text-lg rounded-lg font-semibold
                    shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300
                    w-full sm:w-auto mb-3 sm:mb-0
                    hover:bg-gray-300 focus:outline-none focus:ring-4 focus:ring-gray-300
                    whitespace-nowrap
                ">
                    <i class="fas fa-upload ml-2"></i> แชร์ผลงานของคุณ
                </a>
                <a href="create_job_post.php" class="
                    bg-indigo-600 text-white
                    px-6 py-3 sm:px-8 sm:py-4
                    text-base sm:text-lg rounded-lg font-semibold
                    shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300
                    w-full sm:w-auto
                    hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300
                    whitespace-nowrap
                ">
                    <i class="fas fa-plus-circle mr-2"></i> สร้างโพสต์งานของคุณ
                </a>
            </div>
        </div>
    </header>

    <section id="assigned-jobs" class="py-12 md:py-16 bg-gradient-to-br from-blue-50 to-gray-50">
        <div class="container mx-auto px-4 md:px-6">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-8 md:mb-10">
                <h2
                    class="text-2xl sm:text-3xl md:text-4xl font-semibold text-gray-800 mb-4 sm:mb-0 text-center sm:text-left text-gradient">
                    งานที่ได้รับมอบหมาย
                </h2>
                <a href="job_listings.php?type=assigned"
                    class="btn-secondary px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg font-medium text-sm md:text-base">
                    ดูทั้งหมด <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>

            <?php if (empty($assigned_jobs)): ?>
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg relative text-center">
                    <span class="block sm:inline">ยังไม่มีงานที่ได้รับมอบหมายในขณะนี้</span>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                    <?php foreach ($assigned_jobs as $job): ?>
                        <div class="card-item animate-card-appear">
                            <img src="https://source.unsplash.com/400x250/?design-project,<?= urlencode($job['category_name'] ?? 'graphic-design') ?>"
                                alt="งานที่ได้รับมอบหมาย: <?= htmlspecialchars($job['title']) ?>" class="card-image"
                                onerror="this.onerror=null;this.src='../dist/img/pdpa02.jpg';">
                            <div class="p-4 md:p-6 flex-grow flex flex-col justify-between">
                                <div>
                                    <h3 class="text-lg md:text-xl font-semibold text-gray-900 mb-1 md:mb-2 line-clamp-2">
                                        <?= htmlspecialchars($job['title']) ?>
                                    </h3>
                                    <p class="text-xs md:text-sm text-gray-600 mb-1 md:mb-2">ผู้ว่าจ้าง: <span
                                            class="font-medium text-blue-700"><?= htmlspecialchars($job['client_first_name'] . ' ' . $job['client_last_name']) ?></span>
                                    </p>
                                    <p class="text-xs md:text-sm text-gray-500 mb-2 md:mb-4">
                                        <i class="fas fa-tag mr-1 text-blue-500"></i> หมวดหมู่: <span
                                            class="font-normal"><?= htmlspecialchars($job['category_name'] ?? 'ไม่ระบุ') ?></span>
                                    </p>
                                    <p class="text-sm md:text-base text-gray-700 mb-2 md:mb-4 line-clamp-3 font-light">
                                        <?= htmlspecialchars($job['description']) ?>
                                    </p>
                                </div>
                                <div class="mt-2 md:mt-4">
                                    <p class="text-base md:text-lg font-semibold text-green-700 mb-1 md:mb-2">สถานะ:
                                        <span
                                            class="text-blue-600"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $job['job_status']))) ?></span>
                                    </p>
                                    <p class="text-xs text-gray-500 mb-2 md:mb-4">มอบหมายเมื่อ: <span
                                            class="font-light"><?= date('d M Y', strtotime($job['posted_date'])) ?></span></p>
                                    <a href="../job_detail.php?id=<?= $job['post_id'] ?>&type=posting"
                                        class="btn-primary px-4 py-2 sm:px-5 sm:py-2 rounded-lg font-medium shadow-lg">
                                        ดูรายละเอียด <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="available-jobs" class="py-12 md:py-16 bg-white">
        <div class="container mx-auto px-4 md:px-6">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-8 md:mb-10">
                <h2
                    class="text-2xl sm:text-3xl md:text-4xl font-semibold text-gray-800 mb-4 sm:mb-0 text-center sm:text-left text-gradient">
                    งานที่เปิดรับ (สำหรับคุณ)
                </h2>
                <a href="../job_listings.php?type=postings"
                    class="btn-secondary px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg font-medium text-sm md:text-base">
                    ดูทั้งหมด <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>

            <?php if (empty($available_jobs)): ?>
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg relative text-center">
                    <span class="block sm:inline">ยังไม่มีงานที่เปิดรับในขณะนี้</span>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                    <?php foreach ($available_jobs as $job): ?>
                        <div class="card-item animate-card-appear">
                            <img src="https://source.unsplash.com/400x250/?creative-design,<?= urlencode($job['category_name'] ?? 'web-design') ?>"
                                alt="งานที่เปิดรับ: <?= htmlspecialchars($job['title']) ?>" class="card-image"
                                onerror="this.onerror=null;this.src='../dist/img/pdpa02.jpg';">
                            <div class="p-4 md:p-6 flex-grow flex flex-col justify-between">
                                <div>
                                    <h3 class="text-lg md:text-xl font-semibold text-gray-900 mb-1 md:mb-2 line-clamp-2">
                                        <?= htmlspecialchars($job['title']) ?>
                                    </h3>
                                    <p class="text-xs md:text-sm text-gray-600 mb-1 md:mb-2">โดย: <span
                                            class="font-medium text-blue-700"><?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']) ?></span>
                                    </p>
                                    <p class="text-xs md:text-sm text-gray-500 mb-2 md:mb-4">
                                        <i class="fas fa-tag mr-1 text-blue-500"></i> หมวดหมู่: <span
                                            class="font-normal"><?= htmlspecialchars($job['category_name'] ?? 'ไม่ระบุ') ?></span>
                                    </p>
                                    <p class="text-sm md:text-base text-gray-700 mb-2 md:mb-4 line-clamp-3 font-light">
                                        <?= htmlspecialchars($job['description']) ?>
                                    </p>
                                </div>
                                <div class="mt-2 md:mt-4">
                                    <p class="text-base md:text-lg font-semibold text-green-700 mb-1 md:mb-2">ราคา:
                                        <?= htmlspecialchars($job['price_range'] ?? 'สอบถาม') ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mb-2 md:mb-4">ประกาศเมื่อ: <span
                                            class="font-light"><?= date('d M Y', strtotime($job['posted_date'])) ?></span></p>
                                    <a href="../job_detail.php?id=<?= $job['post_id'] ?>&type=posting"
                                        class="btn-primary px-4 py-2 sm:px-5 sm:py-2 rounded-lg font-medium shadow-lg">
                                        ดูรายละเอียด <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="bg-gray-900 text-gray-300 py-8">
        <div class="container mx-auto px-4 md:px-6 text-center">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <a href="main.php"
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
        // Optional: JavaScript for smooth scrolling to sections
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Optional: Animate cards on scroll (same as index.php)
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.animate-card-appear');
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '0';
                        entry.target.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            entry.target.style.transition =
                                'opacity 0.6s ease-out, transform 0.6s ease-out';
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, 200);
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            cards.forEach(card => {
                observer.observe(card);
            });
        });
    </script>
</body>

</html>