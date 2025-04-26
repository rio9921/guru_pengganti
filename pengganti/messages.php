<?php
/**
 * GuruSinergi - Messages Page
 * 
 * Halaman untuk menampilkan daftar percakapan dan chatting
 */

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Include file auth functions
require_once 'includes/auth-functions.php';

// Cek login user
check_access('all');

// Inisialisasi variabel
$current_user = get_app_current_user();
$receiver_id = isset($_GET['user']) ? intval($_GET['user']) : 0;
$assignment_id = isset($_GET['assignment']) ? intval($_GET['assignment']) : 0;

// Handle pencarian
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Handler for sending message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message = sanitize($_POST['message']);
    $receiver = intval($_POST['receiver_id']);
    $assignment = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : null;
    
    if (empty($message)) {
        set_error_message('Pesan tidak boleh kosong.');
    } else {
        $conn = db_connect();
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, assignment_id, message, is_read)
            VALUES (?, ?, ?, ?, 0)
        ");
        
        if ($stmt->execute([$current_user['id'], $receiver, $assignment, $message])) {
            // Refresh halaman dengan parameter yang sama
            $redirect_url = 'messages.php?';
            if ($receiver > 0) {
                $redirect_url .= 'user=' . $receiver . '&';
            }
            if ($assignment > 0) {
                $redirect_url .= 'assignment=' . $assignment;
            }
            redirect(url($redirect_url));
        } else {
            set_error_message('Gagal mengirim pesan. Silakan coba lagi.');
        }
    }
}

// Ambil daftar percakapan
$conn = db_connect();

// SQL untuk mendapatkan daftar percakapan unik
$conversations_sql = "
    SELECT 
        CASE
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END as other_user_id,
        MAX(m.created_at) as last_message_time,
        COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = ? THEN 1 END) as unread_count,
        MAX(CASE WHEN m.assignment_id IS NOT NULL THEN m.assignment_id ELSE NULL END) as assignment_id
    FROM messages m
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY other_user_id
    ORDER BY last_message_time DESC
";

$stmt = $conn->prepare($conversations_sql);
$stmt->execute([$current_user['id'], $current_user['id'], $current_user['id'], $current_user['id']]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil detail pengguna untuk setiap percakapan
foreach ($conversations as &$conversation) {
    $user_id = $conversation['other_user_id'];
    
    $stmt = $conn->prepare("SELECT id, full_name, user_type, profile_image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $conversation['user'] = $user;
        
        // Ambil pesan terakhir
        $stmt = $conn->prepare("
            SELECT message, created_at, sender_id 
            FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$current_user['id'], $user_id, $user_id, $current_user['id']]);
        $last_message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($last_message) {
            $conversation['last_message'] = $last_message;
        }
        
        // Jika ada assignment, ambil detail penugasan
        if ($conversation['assignment_id']) {
            $stmt = $conn->prepare("SELECT id, judul FROM assignments WHERE id = ?");
            $stmt->execute([$conversation['assignment_id']]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($assignment) {
                $conversation['assignment'] = $assignment;
            }
        }
    }
}

// Jika percakapan dipilih, ambil pesan
$messages = [];
$receiver = null;
$assignment = null;

if ($receiver_id > 0) {
    // Ambil detail receiver
    $stmt = $conn->prepare("SELECT id, full_name, user_type, profile_image FROM users WHERE id = ?");
    $stmt->execute([$receiver_id]);
    $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($receiver) {
        // Ambil pesan antara user saat ini dan receiver
        $stmt = $conn->prepare("
            SELECT m.*, u_sender.full_name as sender_name, u_receiver.full_name as receiver_name
            FROM messages m
            JOIN users u_sender ON m.sender_id = u_sender.id
            JOIN users u_receiver ON m.receiver_id = u_receiver.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
            " . ($assignment_id > 0 ? "AND m.assignment_id = ?" : "") . "
            ORDER BY m.created_at ASC
        ");
        
        if ($assignment_id > 0) {
            $stmt->execute([$current_user['id'], $receiver_id, $receiver_id, $current_user['id'], $assignment_id]);
            
            // Ambil detail assignment
            $stmt_assignment = $conn->prepare("
                SELECT a.*, s.full_name as sekolah_name, g.full_name as guru_name
                FROM assignments a
                JOIN users s ON a.sekolah_id = s.id
                LEFT JOIN users g ON a.guru_id = g.id
                WHERE a.id = ?
            ");
            $stmt_assignment->execute([$assignment_id]);
            $assignment = $stmt_assignment->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt->execute([$current_user['id'], $receiver_id, $receiver_id, $current_user['id']]);
        }
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tandai pesan yang diterima sebagai telah dibaca
        $stmt = $conn->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
        ");
        $stmt->execute([$receiver_id, $current_user['id']]);
    }
}

// Set variabel untuk page title
$page_title = 'Pesan';

// Include header
include_once 'templates/header.php';
?>

<div class="messages-container">
    <div class="row">
        <!-- Daftar Kontak -->
        <div class="col-md-4 col-lg-3">
            <div class="contacts-panel">
                <div class="panel-header">
                    <h2>Percakapan</h2>
                    <div class="search-wrapper">
                        <form action="" method="get" class="search-form">
                            <input type="text" name="search" placeholder="Cari nama..." value="<?php echo $search; ?>" class="form-control">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="contacts-list">
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <p>Belum ada percakapan</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conversation): ?>
                            <?php
                            // Skip jika ada pencarian dan tidak cocok dengan nama
                            if (!empty($search) && stripos($conversation['user']['full_name'], $search) === false) {
                                continue;
                            }
                            
                            $is_active = $receiver_id == $conversation['other_user_id'];
                            $has_assignment = isset($conversation['assignment']);
                            $is_same_assignment = $has_assignment && $assignment_id == $conversation['assignment_id'];
                            
                            // Tentukan URL untuk percakapan
                            $conversation_url = 'messages.php?user=' . $conversation['other_user_id'];
                            if ($has_assignment) {
                                $conversation_url .= '&assignment=' . $conversation['assignment_id'];
                            }
                            ?>
                            
                            <a href="<?php echo url($conversation_url); ?>" class="contact-item <?php echo $is_active && ($is_same_assignment || !$has_assignment) ? 'active' : ''; ?>">
                                <div class="contact-avatar">
                                    <?php if (!empty($conversation['user']['profile_image'])): ?>
                                        <img src="<?php echo $conversation['user']['profile_image']; ?>" alt="<?php echo $conversation['user']['full_name']; ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <?php echo substr($conversation['user']['full_name'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($conversation['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $conversation['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-name"><?php echo $conversation['user']['full_name']; ?></div>
                                    <?php if ($has_assignment): ?>
                                        <div class="contact-context">
                                            <span class="context-badge">Penugasan</span>
                                            <span class="context-title"><?php echo $conversation['assignment']['judul']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($conversation['last_message'])): ?>
                                        <div class="contact-last-message">
                                            <?php 
                                            $is_own_message = $conversation['last_message']['sender_id'] == $current_user['id'];
                                            echo $is_own_message ? 'Anda: ' : '';
                                            echo (strlen($conversation['last_message']['message']) > 30) 
                                                ? substr($conversation['last_message']['message'], 0, 30) . '...' 
                                                : $conversation['last_message']['message']; 
                                            ?>
                                        </div>
                                        <div class="contact-time">
                                            <?php echo format_time_ago($conversation['last_message']['created_at']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Area Chat -->
        <div class="col-md-8 col-lg-9">
            <div class="chat-panel">
                <?php if ($receiver): ?>
                    <div class="chat-header">
                        <div class="chat-user-info">
                            <div class="user-avatar">
                                <?php if (!empty($receiver['profile_image'])): ?>
                                    <img src="<?php echo $receiver['profile_image']; ?>" alt="<?php echo $receiver['full_name']; ?>">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <?php echo substr($receiver['full_name'], 0, 1); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="user-details">
                                <div class="user-name"><?php echo $receiver['full_name']; ?></div>
                                <div class="user-status">
                                    <?php echo $receiver['user_type'] == 'guru' ? 'Guru' : ($receiver['user_type'] == 'sekolah' ? 'Sekolah' : 'Admin'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($assignment): ?>
                            <div class="chat-context">
                                <div class="context-badge">Penugasan</div>
                                <div class="context-title"><?php echo $assignment['judul']; ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-body" id="chat-body">
                        <?php if (empty($messages)): ?>
                            <div class="empty-chat-state">
                                <div class="empty-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <p>Belum ada pesan. Kirim pesan untuk memulai percakapan.</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            $current_date = '';
                            foreach ($messages as $message): 
                                $message_date = date('Y-m-d', strtotime($message['created_at']));
                                $is_own_message = $message['sender_id'] == $current_user['id'];
                                
                                // Tampilkan tanggal jika berbeda dari pesan sebelumnya
                                if ($current_date != $message_date):
                                    $current_date = $message_date;
                                    $date_display = '';
                                    
                                    $today = date('Y-m-d');
                                    $yesterday = date('Y-m-d', strtotime('-1 day'));
                                    
                                    if ($message_date == $today) {
                                        $date_display = 'Hari ini';
                                    } elseif ($message_date == $yesterday) {
                                        $date_display = 'Kemarin';
                                    } else {
                                        $date_display = date('d F Y', strtotime($message_date));
                                    }
                            ?>
                                <div class="chat-date-separator">
                                    <span><?php echo $date_display; ?></span>
                                </div>
                            <?php endif; ?>
                            
                                <div class="message-item <?php echo $is_own_message ? 'own-message' : 'other-message'; ?>">
                                    <div class="message-content">
                                        <div class="message-text"><?php echo $message['message']; ?></div>
                                        <div class="message-time">
                                            <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                            <?php if ($is_own_message && $message['is_read']): ?>
                                                <span class="read-status">
                                                    <i class="fas fa-check-double"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-footer">
                        <form method="post" action="" class="message-form">
                            <input type="hidden" name="receiver_id" value="<?php echo $receiver['id']; ?>">
                            <?php if ($assignment): ?>
                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="input-group">
                                <textarea name="message" class="form-control message-input" placeholder="Ketik pesan..." required></textarea>
                                <button type="submit" name="send_message" class="btn btn-primary send-button">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-chat-placeholder">
                        <div class="empty-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Selamat datang di Pesan</h3>
                        <p>Pilih percakapan dari daftar di sebelah kiri untuk mulai chat.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto scroll to bottom of chat
    const chatBody = document.getElementById('chat-body');
    if (chatBody) {
        chatBody.scrollTop = chatBody.scrollHeight;
    }
    
    // Auto resize textarea
    const messageInput = document.querySelector('.message-input');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
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
        return floor($time_difference / 60) . ' menit';
    } elseif ($time_difference < 86400) {
        return floor($time_difference / 3600) . ' jam';
    } elseif ($time_difference < 2592000) {
        return floor($time_difference / 86400) . ' hari';
    } elseif ($time_difference < 31536000) {
        return floor($time_difference / 2592000) . ' bulan';
    } else {
        return floor($time_difference / 31536000) . ' tahun';
    }
}

// Include footer
include_once 'templates/footer.php';
?>