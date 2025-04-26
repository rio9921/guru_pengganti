<?php
/**
 * GuruSinergi - Notifications Page
 * 
 * Halaman untuk melihat semua notifikasi pengguna
 */

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Include file auth functions
require_once 'includes/auth-functions.php';

// Include file notification functions
require_once 'includes/notification-functions.php';

// Cek login user
check_access('all');

// Inisialisasi variabel
$current_user = get_app_current_user();
$action = isset($_GET['action']) ? $_GET['action'] : '';
$notification_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle aksi
if ($action === 'mark-read' && $notification_id > 0) {
    if (mark_notification_as_read($notification_id, $current_user['id'])) {
        set_success_message('Notifikasi ditandai sebagai telah dibaca.');
    } else {
        set_error_message('Gagal menandai notifikasi sebagai telah dibaca.');
    }
    redirect(url('notifications.php'));
}

if ($action === 'mark-all-read') {
    if (mark_all_notifications_as_read($current_user['id'])) {
        set_success_message('Semua notifikasi ditandai sebagai telah dibaca.');
    } else {
        set_error_message('Gagal menandai semua notifikasi sebagai telah dibaca.');
    }
    redirect(url('notifications.php'));
}

if ($action === 'delete' && $notification_id > 0) {
    if (delete_notification($notification_id, $current_user['id'])) {
        set_success_message('Notifikasi berhasil dihapus.');
    } else {
        set_error_message('Gagal menghapus notifikasi.');
    }
    redirect(url('notifications.php'));
}

if ($action === 'delete-all') {
    if (delete_all_notifications($current_user['id'])) {
        set_success_message('Semua notifikasi berhasil dihapus.');
    } else {
        set_error_message('Gagal menghapus semua notifikasi.');
    }
    redirect(url('notifications.php'));
}

// Paginasi
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filter berdasarkan status dibaca/belum
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$status_filter = "";
$params = [$current_user['id']];

if ($filter === 'unread') {
    $status_filter = "AND is_read = 0";
} elseif ($filter === 'read') {
    $status_filter = "AND is_read = 1";
}

// Ambil notifikasi dari database
$conn = db_connect();
$stmt = $conn->prepare("
    SELECT id, title, message, is_read, link, created_at
    FROM notifications
    WHERE user_id = ? $status_filter
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$current_user['id'], $limit, $offset]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total notifikasi untuk paginasi
$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM notifications
    WHERE user_id = ? $status_filter
");
$stmt->execute([$current_user['id']]);
$count = $stmt->fetch(PDO::FETCH_ASSOC);
$total_pages = ceil($count['total'] / $limit);

// Set variabel untuk page title
$page_title = 'Notifikasi';

// Include header
include_once 'templates/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Notifikasi</h1>
            <p class="page-description">Melihat semua notifikasi yang Anda terima</p>
        </div>
        <div class="header-actions">
            <?php if (count($notifications) > 0): ?>
                <a href="<?php echo url('notifications.php?action=mark-all-read'); ?>" class="btn btn-outline">
                    <i class="fas fa-check-double"></i> Tandai Semua Dibaca
                </a>
                <a href="<?php echo url('notifications.php?action=delete-all'); ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus semua notifikasi?');">
                    <i class="fas fa-trash"></i> Hapus Semua
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="card-title">Daftar Notifikasi</h2>
            <div class="filter-wrapper">
                <select id="filter-notifications" class="form-select">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Semua</option>
                    <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Belum Dibaca</option>
                    <option value="read" <?php echo $filter === 'read' ? 'selected' : ''; ?>>Sudah Dibaca</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5">
                <div class="empty-state-icon">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <h3>Tidak Ada Notifikasi</h3>
                <p class="text-muted">Anda belum memiliki notifikasi untuk ditampilkan.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-header">
                                <h3 class="notification-title"><?php echo $notification['title']; ?></h3>
                                <div class="notification-time"><?php echo format_time_ago($notification['created_at']); ?></div>
                            </div>
                            <div class="notification-message"><?php echo $notification['message']; ?></div>
                            <div class="notification-actions">
                                <?php if (!empty($notification['link'])): ?>
                                    <a href="<?php echo url($notification['link']); ?>" class="btn btn-sm btn-primary">Lihat Detail</a>
                                <?php endif; ?>
                                
                                <?php if (!$notification['is_read']): ?>
                                    <a href="<?php echo url('notifications.php?action=mark-read&id=' . $notification['id']); ?>" class="btn btn-sm btn-outline">Tandai Dibaca</a>
                                <?php endif; ?>
                                
                                <a href="<?php echo url('notifications.php?action=delete&id=' . $notification['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus notifikasi ini?');">Hapus</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination-wrapper mt-4">
                    <nav aria-label="Pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo url('notifications.php?page=' . ($page - 1) . '&filter=' . $filter); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo url('notifications.php?page=' . $i . '&filter=' . $filter); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo url('notifications.php?page=' . ($page + 1) . '&filter=' . $filter); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterSelect = document.getElementById('filter-notifications');
    
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            window.location.href = '<?php echo url('notifications.php?filter='); ?>' + this.value;
        });
    }
});
</script>

<?php
// Helper function untuk format waktu "waktu yang lalu"
function format_time_ago($datetime) {
    $time = strtotime($datetime);
    $time_difference = time() - $time;
    
    if ($time_difference < 60) {
        return 'Baru saja';
    } elseif ($time_difference < 3600) {
        return floor($time_difference / 60) . ' menit yang lalu';
    } elseif ($time_difference < 86400) {
        return floor($time_difference / 3600) . ' jam yang lalu';
    } elseif ($time_difference < 2592000) {
        return floor($time_difference / 86400) . ' hari yang lalu';
    } elseif ($time_difference < 31536000) {
        return floor($time_difference / 2592000) . ' bulan yang lalu';
    } else {
        return floor($time_difference / 31536000) . ' tahun yang lalu';
    }
}

// Include footer
include_once 'templates/footer.php';
?>