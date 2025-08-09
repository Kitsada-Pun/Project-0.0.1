<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

// --- ตรวจสอบสิทธิ์ Client ---
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
if ($condb->connect_error) { die("Connection failed: " . $condb->connect_error); }
$condb->set_charset("utf8mb4");

$client_id = $_SESSION['user_id'];
$client_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Client';

// --- ดึงข้อมูลนักออกแบบแนะนำ (สุ่มมา 8 คน) ---
$featured_designers = [];
$sql_designers = "SELECT 
                    u.user_id, 
                    u.first_name, 
                    u.last_name, 
                    p.skills, 
                    p.profile_picture_url 
                  FROM users u
                  JOIN profiles p ON u.user_id = p.user_id
                  WHERE u.user_type = 'designer' AND u.is_approved = 1
                  ORDER BY RAND()
                  LIMIT 8"; // เพิ่มจำนวนที่แสดงผล
$result_designers = $condb->query($sql_designers);
if ($result_designers) {
    $featured_designers = $result_designers->fetch_all(MYSQLI_ASSOC);
}

$condb->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PixelLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f8fafc; }
        .btn-primary { background: linear-gradient(45deg, #0a5f97 0%, #0d96d2 100%); color: white; transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(13, 150, 210, 0.4); }
        .btn-danger { background-color: #ef4444; color: white; transition: all 0.3s ease; }
        .btn-danger:hover { background-color: #dc2626; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4); }
        .text-gradient { background: linear-gradient(45deg, #0a5f97, #0d96d2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <nav class="bg-white/90 backdrop-blur-sm p-4 shadow-md sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <a href="main.php"><img src="../dist/img/logo.png" alt="PixelLink Logo" class="h-12 transition-transform hover:scale-105"></a>
            <div class="space-x-4 flex items-center">
                <span class="font-medium text-slate-700">สวัสดี, <?= htmlspecialchars($client_name) ?>!</span>
                <a href="../logout.php" class="btn-danger text-white px-5 py-2 rounded-lg font-medium shadow-md">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow">
        <header class="bg-gradient-to-r from-blue-50 to-indigo-50 py-16">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-slate-800">ค้นหานักออกแบบมืออาชีพ</h1>
                <p class="mt-4 text-lg text-slate-600">เริ่มต้นโปรเจกต์ของคุณกับฟรีแลนซ์มากฝีมือได้ที่นี่</p>
                
                <div class="mt-8">
                    <a href="../job_listings.php?type=designers" class="inline-block bg-white px-10 py-4 rounded-xl shadow-lg hover:shadow-2xl transition-shadow text-left">
                        <div class="flex items-center space-x-6">
                             <div class="bg-green-100 p-4 rounded-full">
                                <i class="fas fa-search fa-2x text-green-600"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-slate-800">ค้นหานักออกแบบ</h2>
                                <p class="text-slate-500 mt-1">ดูโปรไฟล์และผลงานของนักออกแบบทั้งหมด</p>
                            </div>
                        </div>
                    </a>
                </div>

            </div>
        </header>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <div>
                <h2 class="text-3xl font-bold text-slate-800 mb-6 text-center">นักออกแบบแนะนำ</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <?php foreach ($featured_designers as $designer): ?>
                    <a href="../designer/view_profile.php?user_id=<?= $designer['user_id'] ?>" class="block bg-white rounded-xl shadow-lg hover:shadow-2xl transition-shadow text-center p-6">
                        <?php $profile_pic = !empty($designer['profile_picture_url']) && file_exists(str_replace('../','',$designer['profile_picture_url'])) ? str_replace('../','',$designer['profile_picture_url']) : '../dist/img/default_profile.png'; ?>
                        <img src="<?= htmlspecialchars($profile_pic) ?>" class="w-24 h-24 rounded-full object-cover mx-auto mb-4 border-4 border-white shadow-md">
                        <div>
                            <p class="font-semibold text-lg text-slate-800"><?= htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']) ?></p>
                            <p class="text-sm text-slate-500 line-clamp-2 mt-1"><?= htmlspecialchars($designer['skills']) ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </main>

    <footer class="bg-slate-800 text-slate-400 py-6 mt-auto">
        <div class="container mx-auto px-6 text-center">
            <p class="text-sm">&copy; <?= date('Y'); ?> PixelLink. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>