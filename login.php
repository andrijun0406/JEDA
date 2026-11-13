<?php
session_start();
include 'koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_user = trim($_POST['username']);
    $input_pass = trim($_POST['password']);
    $role = $_POST['role']; // 'student' atau 'parent'

    if (!empty($input_user) && !empty($input_pass)) {
        if ($role == 'student') {
            $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE (name = ? OR username = ?) AND password = ?");
            $stmt->bind_param("sss", $input_user, $input_user, $input_pass);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['role'] = 'student';
                header("Location: index.php");
                exit();
            } else {
                $error = "Username/Nama atau Password siswa salah!";
            }
            $stmt->close();
        } elseif ($role == 'parent') {
            $stmt = $conn->prepare("SELECT id, name, role FROM parents WHERE (name = ? OR username = ?) AND password = ?");
            $stmt->bind_param("sss", $input_user, $input_user, $input_pass);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $_SESSION['parent_id'] = $row['id'];
                $_SESSION['role'] = 'parent';
                header("Location: parent_dashboard.php");
                exit();
            } else {
                $error = "Username/Nama atau Password orang tua salah!";
            }
            $stmt->close();
        }
    } else {
        $error = "Mohon isi username/nama dan kata sandi dengan lengkap.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEDA - Edukasi Finansial & Smart Parenting Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }</style>
</head>
<body class="flex justify-center items-center min-h-screen p-4">

    <!-- Container Utama Mobile App Friendly -->
    <div class="w-full max-w-[480px] bg-slate-50 min-h-[90vh] shadow-2xl rounded-[40px] flex flex-col justify-between relative overflow-hidden border border-slate-800">
        
        <!-- Header Branding: JEDA sebagai Sorotan Utama -->
        <div class="bg-gradient-to-br from-blue-600 via-indigo-700 to-slate-900 text-white p-6 rounded-b-[35px] shadow-md space-y-3 text-center">
            <div class="inline-flex w-14 h-14 bg-white/10 backdrop-blur-md rounded-2xl items-center justify-center text-2xl shadow-inner border border-white/20 mb-1">
                ⏳
            </div>
            <div>
                <h1 class="text-3xl font-black tracking-tight text-white mt-1">JEDA.</h1>
                <span class="text-[10px] uppercase tracking-widest text-blue-200 font-extrabold block">Pause the Impulse. Protect Your Future.</span>
            </div>
            <p class="text-xs text-blue-100 max-w-[340px] mx-auto leading-relaxed">
                Aplikasi untuk <b>membantu edukasi finansial ke siswa</b> dalam mengelola menabung dan disiplin, disempurnakan dengan fitur <i>parent monitoring</i>.
            </p>
        </div>

        <!-- Bagian Form Login -->
        <div class="p-6 flex-1 space-y-5">
            
            <?php if (!empty($error)): ?>
                <div class="bg-rose-100 border border-rose-200 text-rose-600 px-4 py-3 rounded-2xl text-xs font-bold text-center">
                    ⚠️ <?= $error; ?>
                </div>
            <?php endif; ?>

            <!-- Tab Switcher Role (Siswa vs Orang Tua) -->
            <div class="bg-slate-200/80 p-1.5 rounded-2xl grid grid-cols-2 gap-1">
                <button type="button" id="tabStudent" onclick="switchRole('student')" class="py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm bg-white text-blue-600">
                    👨‍🎓 Portal Siswa
                </button>
                <button type="button" id="tabParent" onclick="switchRole('parent')" class="py-2.5 rounded-xl text-xs font-bold transition-all text-slate-600 hover:text-slate-800">
                    👩‍👦 Portal Orang Tua
                </button>
            </div>

            <!-- Form Input Login -->
            <form action="login.php" method="POST" class="space-y-4">
                <input type="hidden" id="roleInput" name="role" value="student">
                
                <!-- Kotak Input Username / Name -->
                <div class="space-y-1.5">
                    <label id="labelUsername" class="text-[11px] text-slate-500 font-bold block px-1">Username / Nama Siswa</label>
                    <input type="text" id="usernameInput" name="username" placeholder="Contoh: aldo atau dika" required 
                        class="w-full bg-white border border-slate-200 text-xs rounded-2xl px-4 py-3.5 text-slate-700 focus:outline-none focus:border-blue-600 font-medium shadow-sm">
                </div>

                <!-- Kotak Input Password -->
                <div class="space-y-1.5">
                    <label class="text-[11px] text-slate-500 font-bold block px-1">Kata Sandi (Password)</label>
                    <input type="password" name="password" placeholder="Masukkan password (Contoh: 123)" required 
                        class="w-full bg-white border border-slate-200 text-xs rounded-2xl px-4 py-3.5 text-slate-700 focus:outline-none focus:border-blue-600 font-medium shadow-sm">
                </div>

                <!-- Tombol Submit -->
                <button type="submit" id="submitBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-3.5 rounded-2xl text-xs transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2 mt-2">
                    <span>Masuk ke Portal Siswa</span>
                    <span>→</span>
                </button>
            </form>

            <!-- Informasi Fitur Fokus Edukasi Finansial -->
            <div id="infoStudent" class="bg-blue-50/80 border border-blue-100 p-4 rounded-3xl space-y-2 text-[11px] text-blue-900">
                <div class="font-bold flex items-center gap-1.5">
                    <span>💡</span>
                    <span>Fokus Utama Edukasi Finansial Siswa:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-blue-800/80 pl-1">
                    <li><b>Angel vs Zombie Status:</b> Gamifikasi kedisiplinan dan manajemen utang virtual.</li>
                    <li><b>AI Saving Insights:</b> Panduan nominal menabung harian untuk mencapai impian.</li>
                    <li><b>Pojok Impian & Alokasi:</b> Belajar mencicil barang impian secara mandiri.</li>
                </ul>
            </div>

            <div id="infoParent" class="hidden bg-indigo-50/80 border border-indigo-100 p-4 rounded-3xl space-y-2 text-[11px] text-indigo-900">
                <div class="font-bold flex items-center gap-1.5">
                    <span>🛡️</span>
                    <span>Fitur Pendukung (Parent Monitoring):</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-indigo-800/80 pl-1">
                    <li><b>Multi-Child Monitor:</b> Pemantauan progres finansial anak dalam satu genggaman.</li>
                    <li><b>AI Recurring Baseline:</b> Analisis siklus restock kebutuhan harian anak.</li>
                    <li><b>Apresiasi & Top-Up:</b> Kirim bonus apresiasi (terkunci otomatis jika anak Zombie).</li>
                </ul>
            </div>

        </div>

        <!-- Footer -->
        <div class="p-4 text-center bg-white border-t border-slate-100">
            <p class="text-[10px] text-slate-400 font-medium">JEDA. Financial Education © 2026 • BSI Flash x Hackathon</p>
        </div>

    </div>

    <!-- Script Tab Switcher -->
    <script>
        function switchRole(role) {
            const tabStudent = document.getElementById('tabStudent');
            const tabParent = document.getElementById('tabParent');
            const roleInput = document.getElementById('roleInput');
            const labelUsername = document.getElementById('labelUsername');
            const usernameInput = document.getElementById('usernameInput');
            const submitBtn = document.getElementById('submitBtn');
            const infoStudent = document.getElementById('infoStudent');
            const infoParent = document.getElementById('infoParent');

            if (role === 'student') {
                tabStudent.className = "py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm bg-white text-blue-600";
                tabParent.className = "py-2.5 rounded-xl text-xs font-bold transition-all text-slate-600 hover:text-slate-800";
                roleInput.value = 'student';
                labelUsername.innerText = 'Username / Nama Siswa';
                usernameInput.placeholder = 'Contoh: aldo, dika, atau budi';
                submitBtn.innerHTML = '<span>Masuk ke Portal Siswa</span> <span>→</span>';
                infoStudent.classList.remove('hidden');
                infoParent.classList.add('hidden');
            } else {
                tabParent.className = "py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm bg-white text-indigo-600";
                tabStudent.className = "py-2.5 rounded-xl text-xs font-bold transition-all text-slate-600 hover:text-slate-800";
                roleInput.value = 'parent';
                labelUsername.innerText = 'Username / Nama Orang Tua';
                usernameInput.placeholder = 'Contoh: ortu_hendra';
                submitBtn.innerHTML = '<span>Masuk ke Portal Orang Tua</span> <span>→</span>';
                infoParent.classList.remove('hidden');
                infoStudent.classList.add('hidden');
            }
        }
    </script>
</body>
</html>