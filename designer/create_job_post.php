<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'designer') {
    // ถ้าไม่ได้ล็อกอินหรือไม่ใช่ designer ให้เปลี่ยนเส้นทางไปหน้า login
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

// จัดการการส่งฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $condb->real_escape_string($_POST['title']);
    $description = $condb->real_escape_string($_POST['description']);
    $price_range = $condb->real_escape_string($_POST['price_range']);
    $category_id = (int) $_POST['category'];
    $status = 'active'; // สถานะเริ่มต้นของประกาศงานคือ active
    $main_image_id = NULL; // กำหนดค่าเริ่มต้นเป็น NULL เผื่อไม่มีการอัปโหลดรูปภาพ

    // ตรวจสอบความถูกต้องของข้อมูลพื้นฐาน
    if (empty($title) || empty($description) || empty($price_range) || empty($category_id)) {
        $error_message = 'กรุณากรอกข้อมูลหลักให้ครบถ้วน';
    } else {
        // --- ส่วนจัดการการอัปโหลดรูปภาพ ---
        // ตรวจสอบว่า designer_id เป็นค่าที่ถูกต้องและมีอยู่ในฐานข้อมูล users ก่อนอัปโหลดไฟล์
        // การตรวจสอบนี้ช่วยป้องกัน Foreign Key Error uploaded_files_ibfk_2
        if (!isset($designer_id) || $designer_id <= 0) {
            $error_message = 'ไม่พบข้อมูลผู้ใช้งานที่ถูกต้อง กรุณาล็อกอินใหม่';
        } else {
            // โค้ดส่วนนี้จะรันเมื่อ designer_id มีค่าถูกต้องเท่านั้น
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == UPLOAD_ERR_OK) {
                $file_tmp_name = $_FILES['main_image']['tmp_name'];
                $file_name = $_FILES['main_image']['name'];
                $file_size = $_FILES['main_image']['size'];
                $file_type = $_FILES['main_image']['type'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                $max_file_size = 5 * 1024 * 1024; // 5MB

                if (!in_array($file_ext, $allowed_ext)) {
                    $error_message = 'อนุญาตเฉพาะไฟล์รูปภาพ (JPG, JPEG, PNG, GIF) เท่านั้น';
                } elseif ($file_size > $max_file_size) {
                    $error_message = 'ขนาดไฟล์รูปภาพต้องไม่เกิน 5MB';
                } else {
                    // สร้างชื่อไฟล์ที่ไม่ซ้ำกัน
                    $new_file_name = uniqid('job_img_') . '.' . $file_ext;
                    $upload_dir = '../uploads/job_images/'; // โฟลเดอร์สำหรับเก็บรูปภาพงาน (ต้องสร้างโฟลเดอร์นี้)
                    $upload_path = $upload_dir . $new_file_name;

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true); // สร้างโฟลเดอร์ถ้าไม่มี
                    }

                    if (move_uploaded_file($file_tmp_name, $upload_path)) {
                        // บันทึกข้อมูลไฟล์ลงในตาราง uploaded_files
                        // ตรวจสอบให้แน่ใจว่า `contract_id` ถูกระบุใน field list และ bind_param
                        $sql_insert_file = "INSERT INTO uploaded_files (file_path, file_type, file_size, uploader_id, uploaded_by_user_id, uploaded_date, contract_id)
                             VALUES (?, ?, ?, ?, ?, NOW(), ?)";
                        $stmt_insert_file = $condb->prepare($sql_insert_file);
                        if ($stmt_insert_file) {
                            $contract_id_for_file = NULL; // กำหนดค่าเป็น NULL อย่างชัดเจน
                            // 'ssiisi' -> string (path), string (type), integer (size), integer (user_id), datetime (NOW()), integer (contract_id, can be NULL)
                            $stmt_insert_file->bind_param("ssiiii", $upload_path, $file_type, $file_size, $designer_id, $designer_id, $contract_id_for_file);

                            if ($stmt_insert_file->execute()) { // <<--- บรรทัด 121
                                $main_image_id = $condb->insert_id; // ได้รับ ID ของไฟล์ที่เพิ่งอัปโหลด
                            } else {
                                error_log("SQL Execute Error (insert file): " . $stmt_insert_file->error);
                                $error_message = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลไฟล์: ' . $stmt_insert_file->error;
                                unlink($upload_path);
                            }
                            $stmt_insert_file->close();
                        } else {
                            error_log("SQL Prepare Error (insert file): " . $condb->error);
                            $error_message = 'เกิดข้อผิดพลาดในการเตรียมคำสั่งไฟล์: ' . $condb->error;
                            unlink($upload_path);
                        }
                    } else {
                        $error_message = 'ไม่สามารถอัปโหลดไฟล์รูปภาพได้';
                    }
                }
            } elseif (isset($_FILES['main_image']) && $_FILES['main_image']['error'] != UPLOAD_ERR_NO_FILE) {
                $error_message = 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ: รหัสข้อผิดพลาด ' . $_FILES['main_image']['error'];
            }
        }


        // หากไม่มีข้อผิดพลาดจากการอัปโหลดรูปภาพ (หรือไม่มีการอัปโหลดเลย) ให้บันทึกโพสต์งาน
        if (empty($error_message)) {
            $sql_insert = "INSERT INTO job_postings (designer_id, title, description, category_id, price_range, posted_date, status, main_image_id)
                           VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)";

            $stmt_insert = $condb->prepare($sql_insert);
            if ($stmt_insert === false) {
                error_log("SQL Prepare Error (insert job post): " . $condb->error);
                $error_message = 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง: ' . $condb->error;
            } else {
                $stmt_insert->bind_param("ississi", $designer_id, $title, $description, $category_id, $price_range, $status, $main_image_id);

                if ($stmt_insert->execute()) {
                    $success_message = 'โพสต์ประกาศงานของคุณสำเร็จแล้ว!';
                    // Redirect ไปหน้า main.php หลังจากบันทึกสำเร็จ
                    // header("Location: main.php");
                    // exit();
                } else {
                    error_log("SQL Execute Error (insert job post): " . $stmt_insert->error);
                    $error_message = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $stmt_insert->error;
                }
                $stmt_insert->close();
            }
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

        /* Styles for form container */
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Input/Textarea focus styles */
        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #0d96d2;
            /* Accent blue */
            box-shadow: 0 0 0 3px rgba(13, 150, 210, 0.2);
        }

        /* Image preview styles */
        .image-preview-container {
            width: 100%;
            height: 200px;
            background-color: #e2e8f0;
            /* gray-200 */
            border: 2px dashed #94a3b8;
            /* slate-400 */
            border-radius: 0.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        .image-preview-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            /* Show entire image without cropping */
        }

        .image-preview-container .placeholder-text {
            color: #64748b;
            /* slate-600 */
            font-size: 1rem;
            text-align: center;
        }
    </style>
</head>

<body class="flex flex-col min-h-screen">

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

    <main class="flex-grow container mx-auto px-4 py-8 md:py-12 flex items-center justify-center">
        <div class="form-container w-full max-w-2xl p-8 md:p-10">
            <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center text-gradient">สร้างโพสต์งานของคุณ</h2>
            <p class="text-center text-gray-600 mb-8">ประกาศบริการหรือผลงานของคุณ เพื่อให้ผู้ว่าจ้างเห็นและติดต่อคุณได้
            </p>

            <?php if ($success_message): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: '<?= $success_message ?>',
                        showConfirmButton: false,
                        timer: 2500
                    }).then(() => {
                        window.location.href = 'main.php'; // Redirect after success
                    });
                </script>
            <?php elseif ($error_message): ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด!',
                        text: '<?= $error_message ?>',
                        confirmButtonText: 'รับทราบ'
                    });
                </script>
            <?php endif; ?>

            <form action="create_job_post.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="title" class="block text-gray-700 text-lg font-semibold mb-2">ชื่องาน/บริการ:</label>
                    <input type="text" id="title" name="title" placeholder="เช่น ออกแบบโลโก้, รับวาดภาพประกอบ"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <div>
                    <label for="description" class="block text-gray-700 text-lg font-semibold mb-2">รายละเอียด:</label>
                    <textarea id="description" name="description" rows="6"
                        placeholder="อธิบายเกี่ยวกับบริการของคุณให้ชัดเจนและน่าสนใจ"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        required></textarea>
                </div>

                <div>
                    <label for="category" class="block text-gray-700 text-lg font-semibold mb-2">หมวดหมู่:</label>
                    <select id="category" name="category"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
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
                    <label for="price_range" class="block text-gray-700 text-lg font-semibold mb-2">ช่วงราคา
                        (โดยประมาณ):</label>
                    <input type="text" id="price_range" name="price_range"
                        placeholder="เช่น 1,500 - 3,000 บาท, เริ่มต้น 500 บาท"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <div>
                    <label for="main_image" class="block text-gray-700 text-lg font-semibold mb-2">แนบภาพประกอบ
                        (หลัก):</label>
                    <div class="image-preview-container mb-4" id="imagePreviewContainer">
                        <span class="placeholder-text"><i
                                class="fas fa-camera text-4xl mb-2"></i><br>คลิกหรือลากรูปภาพมาวางที่นี่</span>
                        <img id="imagePreview" src="#" alt="Image Preview" class="hidden">
                    </div>
                    <input type="file" id="main_image" name="main_image" accept="image/*"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    <p class="text-sm text-gray-500 mt-2">รองรับ JPG, PNG, GIF. สูงสุด 5MB.</p>
                </div>

                <div class="flex justify-center">
                    <button type="submit" class="
                        btn-primary
                        px-8 py-3 rounded-lg font-semibold text-xl
                        shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300
                        focus:outline-none focus:ring-4 focus:ring-blue-300
                    ">
                        <i class="fas fa-paper-plane mr-2"></i> โพสต์ประกาศงาน
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8 mt-auto">
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
        // Image preview script
        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.getElementById('main_image');
            const imagePreview = document.getElementById('imagePreview');
            const imagePreviewContainer = document.getElementById('imagePreviewContainer');
            const placeholderText = imagePreviewContainer.querySelector('.placeholder-text');

            fileInput.addEventListener('change', function (event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                        placeholderText.classList.add('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.src = '#';
                    imagePreview.classList.add('hidden');
                    placeholderText.classList.remove('hidden');
                }
            });

            // Handle drag and drop for image preview (optional)
            imagePreviewContainer.addEventListener('dragover', (e) => {
                e.preventDefault();
                imagePreviewContainer.classList.add('border-blue-500', 'bg-blue-50');
            });

            imagePreviewContainer.addEventListener('dragleave', (e) => {
                e.preventDefault();
                imagePreviewContainer.classList.remove('border-blue-500', 'bg-blue-50');
            });

            imagePreviewContainer.addEventListener('drop', (e) => {
                e.preventDefault();
                imagePreviewContainer.classList.remove('border-blue-500', 'bg-blue-50');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files; // Assign the dropped files to the input
                    fileInput.dispatchEvent(new Event('change')); // Trigger change event
                }
            });

            // Click to open file input
            imagePreviewContainer.addEventListener('click', () => {
                fileInput.click();
            });
        });
    </script>
</body>

</html>