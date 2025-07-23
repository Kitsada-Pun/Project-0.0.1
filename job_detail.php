<?php
// public/job_detail.php
session_start();
date_default_timezone_set('Asia/Bangkok');

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

$job_data = null;
$error_message = '';
$job_type = ''; // 'posting' or 'request'
$loggedInUserName = ''; // Initialize variable for logged-in user's name

// Fetch logged-in user's name if session is active
if (isset($_SESSION['user_id'])) {
    // Try to get username or full_name from session first, as requested
    $loggedInUserName = $_SESSION['username'] ?? $_SESSION['full_name'] ?? '';

    // If name is still empty, fetch from database as a fallback
    if (empty($loggedInUserName)) {
        $user_id = $_SESSION['user_id'];
        $sql_user = "SELECT first_name, last_name FROM users WHERE user_id = ?";
        $stmt_user = $condb->prepare($sql_user);
        if ($stmt_user) {
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            if ($result_user->num_rows === 1) {
                $user_info = $result_user->fetch_assoc();
                $loggedInUserName = $user_info['first_name'] . ' ' . $user_info['last_name'];
            }
            $stmt_user->close();
        } else {
            error_log("SQL Prepare Error (user name fetch): " . $condb->error);
        }
    }
}


// ตรวจสอบว่ามี ID และ Type ถูกส่งมาใน URL หรือไม่
if (isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['type'])) {
    $job_id = (int)$_GET['id'];
    $job_type = $_GET['type'];

    if ($job_type === 'posting') {
        // ดึงข้อมูล Job Posting
        $sql = "SELECT
                    jp.post_id AS id,
                    jp.title,
                    jp.description,
                    jp.price_range,
                    jp.posted_date,
                    jp.status,
                    'job_posting' AS type_display,
                    u.user_id AS owner_id,
                    u.first_name,
                    u.last_name,
                    u.user_type AS owner_type,
                    jc.category_name
                FROM job_postings AS jp
                JOIN users AS u ON jp.designer_id = u.user_id
                LEFT JOIN job_categories AS jc ON jp.category_id = jc.category_id
                WHERE jp.post_id = ? AND jp.status = 'active'"; // แสดงเฉพาะงานที่ active

        $stmt = $condb->prepare($sql);
        if ($stmt === false) {
            error_log("SQL Prepare Error (job_posting detail): " . $condb->error);
            $error_message = "เกิดข้อผิดพลาดทางเทคนิคในการดึงข้อมูลประกาศรับงาน";
        } else {
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $job_data = $result->fetch_assoc();
            } else {
                $error_message = "ไม่พบประกาศรับงานนี้ หรือประกาศถูกปิดไปแล้ว";
            }
            $stmt->close();
        }

    } elseif ($job_type === 'request') {
        // ดึงข้อมูล Client Job Request
        $sql = "SELECT
                    cjr.request_id AS id,
                    cjr.title,
                    cjr.description,
                    cjr.budget,
                    cjr.deadline,
                    cjr.posted_date,
                    cjr.status,
                    'job_request' AS type_display,
                    u.user_id AS owner_id,
                    u.first_name,
                    u.last_name,
                    u.user_type AS owner_type,
                    jc.category_name
                FROM client_job_requests AS cjr
                JOIN users AS u ON cjr.client_id = u.user_id
                LEFT JOIN job_categories AS jc ON cjr.category_id = jc.category_id
                WHERE cjr.request_id = ? AND cjr.status = 'open'"; // แสดงเฉพาะงานที่ open

        $stmt = $condb->prepare($sql);
        if ($stmt === false) {
            error_log("SQL Prepare Error (client_job_request detail): " . $condb->error);
            $error_message = "เกิดข้อผิดพลาดทางเทคนิคในการดึงข้อมูลร้องขอจากผู้ว่าจ้าง";
        } else {
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $job_data = $result->fetch_assoc();
            } else {
                $error_message = "ไม่พบงานร้องขอจากผู้ว่าจ้างนี้ หรือสถานะไม่เป็น 'open'";
            }
            $stmt->close();
        }

    } else {
        $error_message = "ประเภทงานที่ระบุไม่ถูกต้อง";
    }
} else {
    $error_message = "ไม่พบ Job ID หรือประเภทงานที่ระบุ";
}

$condb->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดงาน: <?= $job_data ? htmlspecialchars($job_data['title']) : 'ไม่พบงาน' ?> | PixelLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />

    <style>
        /* CSS จาก index.php ที่ปรับใช้สำหรับ job_detail.php */
        * {
            font-family: 'Kanit', sans-serif;
            font-style: normal;
            font-weight: 400; /* สามารถปรับ weight ได้ตามที่ Kanit มี */
        }

        body {
            /* ใช้ background จาก job_detail.php เดิม เพื่อคง gradient สีฟ้า */
            background: linear-gradient(135deg, #e0f2fe, #bbdefb); /* Light blue gradient */
            color: #333; /* ใช้สีตัวอักษรเดิมของ job_detail.php */
            min-height: 100vh;
            overflow-x: hidden; /* เพิ่มเพื่อให้เหมือน index.php */
        }

        .navbar {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05); /* เพิ่มเส้นขอบล่างเหมือน index.php */
        }

        /* --- ปุ่มหลัก (Primary Button) เหมือนกับ index.php --- */
        .btn-primary {
            background: linear-gradient(45deg, #0a5f97 0%, #0d96d2 100%); /* Deep Blue to Sky Blue */
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(13, 150, 210, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #0d96d2 0%, #0a5f97 100%); /* Invert gradient on hover */
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 150, 210, 0.5);
        }

        /* --- CSS สำหรับ Card และองค์ประกอบอื่นๆ ของ job_detail.php เดิม --- */
        .job-detail-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 1.5rem; /* rounded-3xl */
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        /* .btn-action ที่ถูกใช้ในปุ่ม "ยื่นข้อเสนอ" / "ติดต่อผู้ประกาศ" */
        /* ถ้าต้องการให้ใช้ style เหมือน btn-primary ก็สามารถลบ class นี้ออก และเปลี่ยนใน HTML */
        /* แต่ถ้าต้องการให้ต่างกัน ให้คงไว้ */
        .btn-action {
            background: linear-gradient(45deg, #a8c0ff, #3f2b96);
            color: white;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        

        .badge-info {
            background-color: #e0f2fe; /* blue-100 */
            color: #2563eb; /* blue-600 */
        }
        .badge-status {
            font-size: 0.8em;
            font-weight: 600;
            padding: 0.25em 0.6em;
            border-radius: 0.375rem;
        }
        .status-active { background-color: #d1fae5; color: #059669; } /* green-100, green-600 */
        .status-open { background-color: #dbeafe; color: #1d4ed8; } /* blue-100, blue-700 */
        .status-completed { background-color: #e5e7eb; color: #4b5563; } /* gray-200, gray-600 */
        .status-assigned { background-color: #fef3c7; color: #d97706; } /* yellow-100, yellow-700 */
        .status-cancelled { background-color: #fee2e2; color: #ef4444; } /* red-100, red-500 */

        /* Responsive adjustments จาก index.php ที่อาจเป็นประโยชน์ */
        @media (max-width: 768px) {
            /* คืนค่า padding ของปุ่มบนมือถือให้เหมือน job_detail.php (เก่า) */
            .navbar .px-5 { /* สำหรับปุ่มเข้าสู่ระบบและสมัครสมาชิก */
                padding-left: 1.25rem; /* px-5 */
                padding-right: 1.25rem; /* px-5 */
            }

            .navbar .py-2 { /* สำหรับปุ่มเข้าสู่ระบบและสมัครสมาชิก */
                padding-top: 0.5rem; /* py-2 */
                padding-bottom: 0.5rem; /* py-2 */
            }
        }
        @media (max-width: 480px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            /* ถ้ามี h2 ใน job_detail.php ที่ต้องการปรับขนาดตาม responsive */
            /* h2 { font-size: 1.5rem; } */
        }
    </style>
</head>
<body class="flex flex-col">

    <nav class="navbar p-4 shadow-md sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <a href="designer/main.php" class="transition duration-300 hover:opacity-80">
                <img src="dist/img/logo.png" alt="PixelLink Logo" class="h-12">
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

                    <a href="logout.php" class="
                        bg-red-500 text-white
                        px-3 py-1.5 sm:px-5 sm:py-2
                        rounded-lg font-medium
                        shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300
                        focus:outline-none focus:ring-2 focus:ring-red-300
                    ">
                        <i class="fas fa-sign-out-alt mr-1"></i> ออกจากระบบ
                    </a>
                <?php else : ?>
                    <a href="login.php"
                        class="px-5 py-2 rounded-lg font-semibold border-2 border-transparent hover:border-blue-500 hover:text-blue-500 transition duration-300">เข้าสู่ระบบ</a>
                    <a href="register.php"
                        class="btn-primary px-5 py-2 rounded-lg font-semibold shadow-lg">สมัครสมาชิก</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="flex-grow flex items-center justify-center py-10 px-4">
        <?php if (!empty($error_message)) : ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg shadow-md text-center max-w-md mx-auto">
                <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error_message) ?></span>
                <p class="mt-3 text-sm">หากคุณต้องการค้นหางาน ท่านสามารถกลับไปที่ <a href="index.php#job-postings" class="font-semibold text-blue-700 hover:underline">หน้าหลัก</a> เพื่อค้นหา</p>
            </div>
        <?php elseif ($job_data) : ?>
            <div class="job-detail-card w-full max-w-4xl p-8 md:p-12 text-left">
                <div class="flex items-center justify-between mb-6 border-b pb-4">
                    <div>
                        <h1 class="text-4xl font-extrabold text-gray-900 mb-2"><?= htmlspecialchars($job_data['title']) ?></h1>
                        <p class="text-lg text-gray-600">
                            <span class="badge-info px-3 py-1 rounded-full text-sm font-semibold">
                                <i class="fas <?= $job_type === 'posting' ? 'fa-pen-nib' : 'fa-lightbulb' ?> mr-1"></i>
                                <?= $job_type === 'posting' ? 'ประกาศรับงาน' : 'งานร้องขอ' ?>
                            </span>
                            <span class="ml-3 text-gray-500">โดย: <a href="profile.php?id=<?= $job_data['owner_id'] ?>" class="font-semibold text-blue-700 hover:underline"><?= htmlspecialchars($job_data['first_name'] . ' ' . $job_data['last_name']) ?></a></span>
                        </p>
                    </div>
                    <span class="badge-status
                        <?php
                            $status_class = '';
                            switch ($job_data['status']) {
                                case 'active': $status_class = 'status-active'; break;
                                case 'open': $status_class = 'status-open'; break;
                                case 'completed': $status_class = 'status-completed'; break;
                                case 'assigned': $status_class = 'status-assigned'; break;
                                case 'cancelled': $status_class = 'status-cancelled'; break;
                                default: $status_class = 'bg-gray-200 text-gray-700'; break;
                            }
                            echo $status_class;
                        ?>">
                        <?= ucfirst(str_replace('_', ' ', $job_data['status'])) ?>
                    </span>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-3">รายละเอียดงาน</h2>
                    <p class="text-lg text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($job_data['description'])) ?></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 mb-8 border-b pb-8">
                    <div>
                        <p class="text-gray-700 text-lg mb-2">
                            <i class="fas fa-tag text-blue-500 mr-2"></i>
                            **หมวดหมู่:** <span class="font-semibold"><?= htmlspecialchars($job_data['category_name'] ?? 'ไม่ระบุ') ?></span>
                        </p>
                        <p class="text-gray-700 text-lg mb-2">
                            <i class="fas fa-calendar-alt text-gray-500 mr-2"></i>
                            **ประกาศเมื่อ:** <span class="font-semibold"><?= date('d M Y', strtotime($job_data['posted_date'])) ?></span>
                        </p>
                    </div>
                    <div>
                        <?php if ($job_type === 'posting') : ?>
                            <p class="text-gray-700 text-lg mb-2">
                                <i class="fas fa-hand-holding-usd text-green-600 mr-2"></i>
                                **ช่วงราคา:** <span class="font-semibold"><?= htmlspecialchars($job_data['price_range'] ?? 'สอบถาม') ?></span>
                            </p>
                        <?php elseif ($job_type === 'request') : ?>
                            <p class="text-gray-700 text-lg mb-2">
                                <i class="fas fa-coins text-purple-600 mr-2"></i>
                                **งบประมาณ:** <span class="font-semibold"><?= htmlspecialchars($job_data['budget'] ?? 'ไม่ระบุ') ?></span>
                            </p>
                            <?php if (!empty($job_data['deadline'])) : ?>
                                <p class="text-gray-700 text-lg mb-2">
                                    <i class="fas fa-hourglass-half text-orange-500 mr-2"></i>
                                    **กำหนดส่ง:** <span class="font-semibold"><?= date('d M Y', strtotime($job_data['deadline'])) ?></span>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-200 text-center">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">การดำเนินการ</h2>
                    <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                        <?php if (isset($_SESSION['user_id'])) : ?>
                            <?php if ($job_type === 'request' && $_SESSION['user_type'] === 'designer') : ?>
                                <a href="apply_job.php?request_id=<?= $job_data['id'] ?>" class="btn-action px-8 py-4 rounded-full font-bold shadow-md flex items-center justify-center">
                                    <i class="fas fa-paper-plane mr-2"></i> ยื่นข้อเสนอ
                                </a>
                            <?php elseif ($job_type === 'posting' && $_SESSION['user_type'] === 'client') : ?>
                                <a href="messages.php?to_user=<?= $job_data['owner_id'] ?>" class="btn-action px-8 py-4 rounded-full font-bold shadow-md flex items-center justify-center">
                                    <i class="fas fa-comment-dots mr-2"></i> ติดต่อผู้ประกาศ
                                </a>
                            <?php else : ?>
                                <p class="text-lg text-gray-600">คุณต้องเป็น
                                    <?php if ($job_type === 'request') echo 'นักออกแบบ'; else echo 'ผู้ว่าจ้าง'; ?>
                                    เพื่อดำเนินการกับงานนี้
                                </p>
                            <?php endif; ?>
                            <a href="messages.php?to_user=<?= $job_data['owner_id'] ?>" class="btn-action bg-blue-500 hover:bg-blue-600 px-8 py-4 rounded-full font-bold shadow-md flex items-center justify-center">
                                <i class="fas fa-comments mr-2"></i> ส่งข้อความถึง <?= htmlspecialchars($job_data['first_name']) ?>
                            </a>
                        <?php else : ?>
                            <p class="text-lg text-gray-600">
                                <a href="login.php" class="font-semibold text-blue-700 hover:underline">เข้าสู่ระบบ</a> เพื่อยื่นข้อเสนอ หรือติดต่อผู้ประกาศ/ผู้ร้องขอ
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-800 text-gray-300 py-8 mt-auto">
        <div class="container mx-auto px-6 text-center">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <a href="index.php" class="text-2xl font-bold text-white mb-4 md:mb-0">PixelLink</a>
                <div class="flex space-x-6">
                    <a href="#" class="hover:text-white transition duration-300">เกี่ยวกับเรา</a>
                    <a href="#" class="hover:text-white transition duration-300">ติดต่อเรา</a>
                    <a href="#" class="hover:text-white transition duration-300">เงื่อนไขการใช้งาน</a>
                    <a href="#" class="hover:text-white transition duration-300">นโยบายความเป็นส่วนตัว</a>
                </div>
            </div>
            <hr class="border-gray-700 my-6">
            <p>&copy; <?php echo date('Y'); ?> PixelLink. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>