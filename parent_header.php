<?php
if (!isset($conn) && file_exists('koneksi.php')) {
    include 'koneksi.php';
}

$parent_id = isset($_SESSION['parent_id']) ? $_SESSION['parent_id'] : 0;

// Ambil daftar anak dari orang tua yang login
$children_q = $conn->prepare("SELECT id, name, avatar_base FROM users WHERE parent_id = ? AND role = 'student'");
$children_q->bind_param("i", $parent_id);
$children_q->execute();
$children_res = $children_q->get_result();

$children = [];
while ($c = $children_res->fetch_assoc()) {
    $children[] = $c;
}
$children_q->close();

// Tentukan anak yang sedang dipilih (prioritas dari GET, lalu session, lalu anak pertama)
if (isset($_GET['child_id'])) {
    $selected_child_id = intval($_GET['child_id']);
    $_SESSION['selected_child_id'] = $selected_child_id;
} else {
    $selected_child_id = isset($_SESSION['selected_child_id']) ? $_SESSION['selected_child_id'] : ($children[0]['id'] ?? 0);
}

// Ambil detail data anak aktif
$active_child = null;
foreach ($children as $child) {
    if ($child['id'] == $selected_child_id) {
        $active_child = $child;
        break;
    }
}
if (!$active_child && !empty($children)) {
    $active_child = $children[0];
    $selected_child_id = $active_child['id'];
    $_SESSION['selected_child_id'] = $selected_child_id;
}

// Ambil status virtual debt anak aktif untuk status Angel/Zombie
$w_q = $conn->prepare("SELECT virtual_debt FROM virtual_wallets WHERE user_id = ?");
$w_q->bind_param("i", $selected_child_id);
$w_q->execute();
$w_res = $w_q->get_result()->fetch_assoc();
$w_q->close();

$h_debt = $w_res['virtual_debt'] ?? 0;
$h_is_zombie = ($h_debt > 0);
?>

<!-- Header Konsisten Orang Tua -->
<div class="p-5 bg-white rounded-b-3xl shadow-sm space-y-3">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-[11px] text-slate-400 font-semibold tracking-wide">Portal Orang Tua x JEDA</p>
            <h1 class="text-base font-bold text-slate-800">Monitoring Anak 👨‍👩‍👦</h1>
        </div>
        <!-- Dropdown Pilih Anak -->
        <form method="GET" action="">
            <select name="child_id" onchange="this.form.submit()" class="bg-blue-50 border border-blue-200 text-xs font-bold text-blue-700 rounded-xl px-3 py-2 focus:outline-none">
                <?php foreach ($children as $ch): ?>
                    <option value="<?= $ch['id']; ?>" <?= ($ch['id'] == $selected_child_id) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($ch['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Badge Status Anak -->
    <div>
        <?php if ($h_is_zombie): ?>
            <span class="inline-flex items-center gap-1 text-[11px] px-3 py-1 rounded-full font-bold bg-rose-100 text-rose-600 border border-rose-200">
                <span>🧟‍♂️</span> Status: Zombie Mode (Membutuhkan Perhatian)
            </span>
        <?php else: ?>
            <span class="inline-flex items-center gap-1 text-[11px] px-3 py-1 rounded-full font-bold bg-emerald-100 text-emerald-600 border border-emerald-200">
                <span>👼</span> Status: Angel Mode (Disiplin & Hemat)
            </span>
        <?php endif; ?>
    </div>
</div>