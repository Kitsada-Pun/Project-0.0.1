<?php
// ================================================================= //
// ========== ส่วน PHP เดิมของคุณทั้งหมด ถูกรวมไว้ที่นี่แล้ว ========== //
// ================================================================= //
session_start();
date_default_timezone_set('Asia/Bangkok');

// --- ตั้งค่าการแสดงผล Error สำหรับ Development (ลบออกเมื่อขึ้น Production) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'designer') {
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

// ดึงข้อมูลผู้ใช้ปัจจุบัน (Designer)
$designer_id = $_SESSION['user_id'];
$designer_name = ''; // กำหนดค่าเริ่มต้นให้เป็นสตริงว่างเปล่า
// เพิ่มการตรวจสอบและดึงชื่อผู้ใช้ให้ครบถ้วนและมั่นใจว่ามีค่า
if (isset($_SESSION['user_id'])) {
    $designer_name = $_SESSION['username'] ?? $_SESSION['full_name'] ?? '';
    if (empty($designer_name)) {
        $sql_designer_info = "SELECT first_name, last_name, username FROM users WHERE user_id = ?";
        $stmt_designer_info = $condb->prepare($sql_designer_info);
        if ($stmt_designer_info) {
            $stmt_designer_info->bind_param("i", $designer_id);
            $stmt_designer_info->execute();
            $result_designer_info = $stmt_designer_info->get_result();
            if ($result_designer_info->num_rows === 1) {
                $info = $result_designer_info->fetch_assoc();
                $designer_name = trim($info['first_name'] . ' ' . $info['last_name']);
                if (empty($designer_name)) {
                    $designer_name = $info['username'];
                }
                $_SESSION['first_name'] = $info['first_name'];
                $_SESSION['last_name'] = $info['last_name'];
                $_SESSION['username'] = $info['username'];
                $_SESSION['full_name'] = $designer_name;
            }
            $stmt_designer_info->close();
        } else {
            error_log("SQL Prepare Error (designer info fetch): " . $condb->error);
        }
    }
}


$success_message = '';
$error_message = '';
$categories = [];

// ดึงหมวดหมู่งานจากฐานข้อมูล
$sql_categories = "SELECT category_id, category_name FROM job_categories ORDER BY category_name";
$result_categories = $condb->query($sql_categories);
if ($result_categories) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
} else {
    error_log("SQL Error fetching categories: " . $condb->error);
}

// ================================================================= //
// ======== ใช้ Logic การจัดการ Transaction เพื่อความเสถียร ======== //
// ================================================================= //
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // เริ่มต้น Transaction
    $condb->begin_transaction();
    $upload_path = null; // กำหนดค่าเริ่มต้น

    try {
        $title = $condb->real_escape_string($_POST['title']);
        $description = $condb->real_escape_string($_POST['description']);
        $price_range = $condb->real_escape_string($_POST['price_range']);
        $category_id = (int) $_POST['category'];
        $status = 'active';
        $main_image_id = NULL;

        if (empty($title) || empty($description) || empty($price_range) || empty($category_id)) {
            throw new Exception('กรุณากรอกข้อมูลหลักให้ครบถ้วน');
        }

        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['main_image']['tmp_name'];
            $file_name = $_FILES['main_image']['name'];
            $file_size = $_FILES['main_image']['size'];
            $file_type = $_FILES['main_image']['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_ext, $allowed_ext)) {
                throw new Exception('อนุญาตเฉพาะไฟล์รูปภาพ (JPG, JPEG, PNG, GIF) เท่านั้น');
            }
            if ($file_size > $max_file_size) {
                throw new Exception('ขนาดไฟล์รูปภาพต้องไม่เกิน 5MB');
            }

            $new_file_name = uniqid('job_img_') . '.' . $file_ext;
            $upload_dir = '../uploads/job_images/';
            $upload_path = $upload_dir . $new_file_name;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($file_tmp_name, $upload_path)) {
                $sql_insert_file = "INSERT INTO uploaded_files (file_path, file_type, file_size, uploader_id, uploaded_by_user_id, uploaded_date, contract_id) VALUES (?, ?, ?, ?, ?, NOW(), NULL)";
                $stmt_insert_file = $condb->prepare($sql_insert_file);
                if ($stmt_insert_file === false) {
                    throw new Exception("Error preparing file insert: " . $condb->error);
                }
                $stmt_insert_file->bind_param("ssiii", $upload_path, $file_type, $file_size, $designer_id, $designer_id);

                if (!$stmt_insert_file->execute()) {
                    throw new Exception("Error saving file data: " . $stmt_insert_file->error);
                }

                $main_image_id = $condb->insert_id;
                $stmt_insert_file->close();
            } else {
                throw new Exception('ไม่สามารถอัปโหลดไฟล์รูปภาพได้');
            }
        }

        $sql_insert = "INSERT INTO job_postings (designer_id, title, description, category_id, price_range, posted_date, status, main_image_id) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)";
        $stmt_insert = $condb->prepare($sql_insert);
        if ($stmt_insert === false) {
            throw new Exception("Error preparing job post insert: " . $condb->error);
        }
        $stmt_insert->bind_param("ississi", $designer_id, $title, $description, $category_id, $price_range, $status, $main_image_id);
        if (!$stmt_insert->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูลประกาศงาน: " . $stmt_insert->error);
        }
        $stmt_insert->close();

        $condb->commit();
        $success_message = 'โพสต์ประกาศงานของคุณสำเร็จแล้ว!';
    } catch (Exception $e) {
        $condb->rollback();
        $error_message = $e->getMessage();
        error_log("Job Post Creation Failed: " . $e->getMessage());
        if ($upload_path && file_exists($upload_path)) {
            unlink($upload_path);
        }
    }
}

$condb->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างโพสต์งานของคุณ | PixelLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'kanit': ['Kanit', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            /* เพิ่ม background image และ properties */
            background-image: url('../dist/img/cover.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed; /* ให้พื้นหลังตรึงอยู่กับที่ */
        }

        main {
            flex-grow: 1;
        }

        /* นำ CSS เดิมมาใช้สำหรับ Navbar และ Footer โดยเฉพาะ */
        .navbar-original {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .pixellink-logo-footer {
            font-weight: 700;
            font-size: 2.25rem;
            /* ~text-4xl */
            background: linear-gradient(45deg, #0a5f97, #0d96d2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body class="text-slate-800 antialiased">

    <nav class="navbar-original p-4 shadow-md sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <a href="main.php" class="transition duration-300 hover:opacity-80">
                <img src="../dist/img/logo.png" alt="PixelLink Logo" class="h-12">
            </a>
            <div class="space-x-2 sm:space-x-4 flex items-center">
                <span class="text-gray-700 font-medium">สวัสดี, <?= htmlspecialchars($designer_name) ?>!</span>
                <a href="view_profile.php?user_id=<?php echo $_SESSION['user_id']; ?>" class="bg-blue-500 text-white px-3 py-1.5 sm:px-5 sm:py-2 rounded-lg font-medium shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <i class="fas fa-user-circle mr-1"></i> ดูโปรไฟล์
                </a>
                <a href="../logout.php" class="bg-red-500 text-white px-3 py-1.5 sm:px-5 sm:py-2 rounded-lg font-medium shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-red-300">
                    <i class="fas fa-sign-out-alt mr-1"></i> ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="mx-auto max-w-2xl">
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl border border-slate-200/75">
                <div class="p-8 sm:p-12">
                    <div class="text-center">
                        <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent">สร้างโพสต์งานของคุณ</h1>
                        <p class="mt-2 text-sm text-slate-500">ประกาศบริการและผลงานของคุณ เพื่อให้ผู้ว่าจ้างที่ใช่ติดต่อคุณได้ง่ายขึ้น</p>
                    </div>

                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data" class="mt-10 space-y-6">

                        <div>
                            <label for="title" class="block text-gray-700 text-lg font-semibold mb-2">ชื่องาน/บริการ:</label>
                            <input type="text" id="title" name="title" placeholder="เช่น ออกแบบโลโก้, รับวาดภาพประกอบ"
                                   class="block w-full p-3 rounded-lg border-gray-400 shadow-md transition duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50"
                                   required>
                        </div>

                        <div>
                            <label for="description" class="block text-gray-700 text-lg font-semibold mb-2">รายละเอียด:</label>
                            <textarea id="description" name="description" rows="6"
                                      placeholder="อธิบายเกี่ยวกับบริการของคุณให้ชัดเจนและน่าสนใจ"
                                      class="block w-full p-3 rounded-lg border-gray-400 shadow-md transition duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50"
                                      required></textarea>
                        </div>

                        <div>
                            <label for="category" class="block text-gray-700 text-lg font-semibold mb-2">หมวดหมู่:</label>
                            <select id="category" name="category"
                                    class="block w-full p-3 rounded-lg border-gray-400 shadow-md transition duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50"
                                    required>
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['category_id']) ?>">
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="price_range" class="block text-gray-700 text-lg font-semibold mb-2">ช่วงราคา (โดยประมาณ):</label>
                            <input type="text" id="price_range" name="price_range"
                                   placeholder="เช่น 1,500 - 3,000 บาท, เริ่มต้น 500 บาท"
                                   class="block w-full p-3 rounded-lg border-gray-400 shadow-md transition duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50"
                                   required>
                        </div>

                        <div>
                             <label for="main_image" class="block text-gray-700 text-lg font-semibold mb-2">แนบภาพประกอบ (หลัก):</label>
                             <div id="imagePreviewContainer" class="mt-2 flex justify-center items-center rounded-xl border-2 border-dashed border-gray-300 px-6 pt-8 pb-10 cursor-pointer hover:border-blue-400 transition-colors duration-200 min-h-[200px]">
                                <div class="text-center" id="placeholderContent">
                                    <i class="fa-solid fa-image text-4xl text-gray-300"></i>
                                    <div class="mt-4 flex text-sm leading-6 text-slate-600 justify-center">
                                        <p class="relative font-semibold text-blue-600">
                                            <span>อัปโหลดไฟล์</span>
                                        </p>
                                        <p class="pl-1">หรือลากมาวาง</p>
                                    </div>
                                    <p class="text-xs leading-5 text-slate-500">PNG, JPG, GIF ขนาดไม่เกิน 5MB</p>
                                </div>
                                <img id="imagePreview" src="#" alt="Image Preview" class="hidden max-h-48 rounded-lg">
                             </div>
                             <input type="file" id="main_image" name="main_image" accept="image/*" class="sr-only">
                        </div>

                        <div class="flex justify-center pt-4">
                             <button type="submit" class="bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-8 py-3 rounded-lg font-semibold text-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                <i class="fas fa-paper-plane mr-2"></i> โพสต์ประกาศงาน
                             </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8 mt-auto">
        <div class="container mx-auto px-4 md:px-6 text-center">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <a href="main.php" class="pixellink-logo-footer mb-4 md:mb-0 transition duration-300 hover:opacity-80">Pixel<b>Link</b></a>
                <div class="flex flex-wrap justify-center space-x-2 md:space-x-6 text-sm md:text-base">
                    <a href="#" class="hover:text-white transition duration-300 mb-2 md:mb-0 font-light">เกี่ยวกับเรา</a>
                    <a href="#" class="hover:text-white transition duration-300 mb-2 md:mb-0 font-light">ติดต่อเรา</a>
                    <a href="#" class="hover:text-white transition duration-300 mb-2 md:mb-0 font-light">เงื่อนไขการใช้งาน</a>
                    <a href="#" class="hover:text-white transition duration-300 mb-2 md:mb-0 font-light">นโยบายความเป็นส่วนตัว</a>
                </div>
            </div>
            <hr class="border-gray-700 my-6">
            <p class="text-xs md:text-sm font-light">&copy; <?php echo date('Y'); ?> PixelLink. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // === SweetAlert2 Popups ===
            <?php if (!empty($success_message)): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: '<?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>',
                    showConfirmButton: false,
                    timer: 2500
                }).then(() => {
                    window.location.href = 'main.php';
                });
            <?php elseif (!empty($error_message)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: '<?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>',
                    confirmButtonText: 'รับทราบ'
                });
            <?php endif; ?>

            // === Image Preview & Drop Zone Logic ===
            const fileInput = document.getElementById('main_image');
            const imagePreview = document.getElementById('imagePreview');
            const imagePreviewContainer = document.getElementById('imagePreviewContainer');
            const placeholderContent = document.getElementById('placeholderContent');

            function handleFiles(files) {
                const file = files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                        placeholderContent.classList.add('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            }

            fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
            imagePreviewContainer.addEventListener('click', () => fileInput.click());
            imagePreviewContainer.addEventListener('dragover', (e) => { e.preventDefault(); imagePreviewContainer.classList.add('border-blue-400', 'bg-slate-50'); });
            imagePreviewContainer.addEventListener('dragleave', (e) => { e.preventDefault(); imagePreviewContainer.classList.remove('border-blue-400', 'bg-slate-50'); });
            imagePreviewContainer.addEventListener('drop', (e) => {
                e.preventDefault();
                imagePreviewContainer.classList.remove('border-blue-400', 'bg-slate-50');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    handleFiles(files);
                }
            });
        });
    </script>
</body>
</html>