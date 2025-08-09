<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

// --- ตรวจสอบสิทธิ์ ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: ../login.php");
    exit();
}

// --- การเชื่อมต่อฐานข้อมูล ---
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "pixellink";
$condb = new mysqli($servername, $username, $password, $dbname);
if ($condb->connect_error) { die("Connection Failed: " . $condb->connect_error); }
$condb->set_charset("utf8mb4");

$user_id = $_SESSION['user_id'];
$error_message = '';
$client_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Client';

// --- Logic การอัปเดตข้อมูล ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับข้อมูลจากฟอร์ม
    $first_name = $condb->real_escape_string($_POST['first_name']);
    $last_name = $condb->real_escape_string($_POST['last_name']);
    $email = $condb->real_escape_string($_POST['email']);
    $phone_number = $condb->real_escape_string($_POST['phone_number']);

    // อัปเดตตาราง users
    $sql_update_user = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ? WHERE user_id = ?";
    $stmt_user = $condb->prepare($sql_update_user);
    $stmt_user->bind_param("ssssi", $first_name, $last_name, $email, $phone_number, $user_id);
    
    if ($stmt_user->execute()) {
        $_SESSION['full_name'] = trim($first_name . ' ' . $last_name);
        header("Location: main.php?update=success");
        exit();
    } else {
        $error_message = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $stmt_user->error;
    }
    $stmt_user->close();
}

// --- ดึงข้อมูลโปรไฟล์ปัจจุบันมาแสดงในฟอร์ม ---
$sql_fetch = "SELECT first_name, last_name, email, phone_number FROM users WHERE user_id = ?";
$stmt_fetch = $condb->prepare($sql_fetch);
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$profile_data = $stmt_fetch->get_result()->fetch_assoc();
$stmt_fetch->close();

if (!$profile_data) {
    $error_message = "ไม่พบข้อมูลโปรไฟล์ของคุณ";
}
$condb->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขโปรไฟล์ | PixelLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Kanit', sans-serif; background-image: url('../dist/img/cover.png'); background-size: cover; background-position: center; background-attachment: fixed; }
    </style>
</head>
<body class="text-slate-800 antialiased">
    <nav class="bg-white/90 backdrop-blur-sm p-4 shadow-md sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <a href="main.php"><img src="../dist/img/logo.png" alt="PixelLink Logo" class="h-12 transition-transform hover:scale-105"></a>
        </div>
    </nav>

    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="mx-auto max-w-2xl">
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-md" role="alert">
                    <p class="font-bold">เกิดข้อผิดพลาด</p>
                    <p><?= htmlspecialchars($error_message) ?></p>
                </div>
            <?php elseif ($profile_data): ?>
                <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl border border-slate-200/75">
                    <div class="p-8 sm:p-12">
                        <div class="text-center">
                            <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent">แก้ไขข้อมูลส่วนตัว</h1>
                            <p class="mt-2 text-sm text-slate-500">อัปเดตข้อมูลของคุณให้เป็นปัจจุบัน</p>
                        </div>

                        <form action="edit_profile.php" method="POST" class="mt-10 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="first_name" class="block text-gray-700 text-lg font-semibold mb-2">ชื่อจริง:</label>
                                    <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($profile_data['first_name']) ?>" class="block w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50" required>
                                </div>
                                <div>
                                    <label for="last_name" class="block text-gray-700 text-lg font-semibold mb-2">นามสกุล:</label>
                                    <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($profile_data['last_name']) ?>" class="block w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50" required>
                                </div>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-gray-700 text-lg font-semibold mb-2">อีเมล:</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($profile_data['email']) ?>" class="block w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50" required>
                            </div>
                            
                            <div>
                                <label for="phone_number" class="block text-gray-700 text-lg font-semibold mb-2">เบอร์โทรศัพท์:</label>
                                <input type="text" id="phone_number" name="phone_number" value="<?= htmlspecialchars($profile_data['phone_number']) ?>" class="block w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50">
                            </div>

                            <div class="flex justify-end pt-4 space-x-4">
                                <a href="main.php" class="bg-slate-200 hover:bg-slate-300 text-slate-800 px-8 py-3 rounded-lg font-semibold transition-colors">ยกเลิก</a>
                                <button type="submit" class="bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-8 py-3 rounded-lg font-semibold text-lg shadow-lg hover:shadow-xl hover:scale-105 transition-all">
                                    <i class="fas fa-save mr-2"></i> บันทึกการเปลี่ยนแปลง
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>