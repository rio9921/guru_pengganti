<?php
// /chat/view.php
// Halaman untuk melihat dan mengirim pesan

// Mulai sesi
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    // Redirect ke halaman login
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Include file konfigurasi dan database
require_once '../config/config.php';
require_once '../config/database.php';

// Include file fungsi
require_once '../includes/functions.php';

// Proses parameter URL
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;

// Cek jika tidak ada conversation_id, coba buat percakapan baru
if ($conversation_id == 0) {
    // Jika peran guru dan ada school_id, atau peran sekolah dan ada teacher_id
    if (($_SESSION['role'] == 'teacher' && $school_id > 0) || ($_SESSION['role'] == 'school' && $teacher_id > 0)) {
        // Cari user_id dari teacher_id atau school_id
        if ($_SESSION['role'] == 'teacher' && $school_id > 0) {
            $query = "SELECT user_id FROM school_profiles WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $school_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $receiver_user_id = $row['user_id'];
                
                // Ambil teacher_id dari pengguna saat ini
                $query = "SELECT id FROM teacher_profiles WHERE user_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $teacher_id = $row['id'];
                }
            }
        } elseif ($_SESSION['role'] == 'school' && $teacher_id > 0) {
            $query = "SELECT user_id FROM teacher_profiles WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $teacher_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $receiver_user_id = $row['user_id'];
                
                // Ambil school_id dari pengguna saat ini
                $query = "SELECT id FROM school_profiles WHERE user_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $school_id = $row['id'];
                }
            }
        }
        
        if (isset($receiver_user_id)) {
            // Cek apakah sudah ada percakapan sebelumnya
            $query = "SELECT c.id 
                      FROM conversations c 
                      JOIN messages m ON c.id = m.conversation_id 
                      WHERE (
                          (m.sender_id = ? AND EXISTS (SELECT 1 FROM messages WHERE conversation_id = c.id AND sender_id = ?)) 
                          OR 
                          (m.sender_id = ? AND EXISTS (SELECT 1 FROM messages WHERE conversation_id = c.id AND sender_id = ?))
                      )";
            
            if ($assignment_id > 0) {
                $query .= " AND c.assignment_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("iiiii", $_SESSION['user_id'], $receiver_user_id, $receiver_user_id, $_SESSION['user_id'], $assignment_id);
            } else {
                $query .= " AND c.assignment_id IS NULL";
                $stmt = $db->prepare($query);
                $stmt->bind_param("iiii", $_SESSION['user_id'], $receiver_user_id, $receiver_user_id, $_SESSION['user_id']);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Gunakan percakapan yang sudah ada
                $row = $result->fetch_assoc();
                $conversation_id = $row['id'];
            } else {
                // Buat percakapan baru
                if ($assignment_id > 0) {
                    $query = "INSERT INTO conversations (assignment_id) VALUES (?)";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("i", $assignment_id);
                } else {
                    $query = "INSERT INTO conversations (assignment_id) VALUES (NULL)";
                    $stmt = $db->prepare($query);
                }
                
                if ($stmt->execute()) {
                    $conversation_id = $db->insert_id;
                }
            }
        }
    }
    
    // Jika masih tidak ada conversation_id, redirect ke inbox
    if ($conversation_id == 0) {
        header('Location: /chat/inbox.php');
        exit;
    }
}

// Ambil detail percakapan
$query = "SELECT c.*, 
          a.title as assignment_title, 
          a.subject as assignment_subject, 
          a.school_id,
          s.school_name
          FROM conversations c
          LEFT JOIN assignments a ON c.assignment_id = a.id
          LEFT JOIN school_profiles s ON a.school_id = s.id
          WHERE c.id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$conversation_result = $stmt->get_result();

if ($conversation_result->num_rows === 0) {
    // Percakapan tidak ditemukan
    set_flash_message('error', 'Percakapan tidak ditemukan.');
    header('Location: /chat/inbox.php');
    exit;
}

$conversation = $conversation_result->fetch_assoc();

// Cek apakah pengguna adalah peserta percakapan ini
$query = "SELECT * FROM messages WHERE conversation_id = ? AND (sender_id = ? OR EXISTS (SELECT 1 FROM messages WHERE conversation_id = ? AND sender_id = ?))";
$stmt = $db->prepare($query);
$stmt->bind_param("iiii", $conversation_id, $_SESSION['user_id'], $conversation_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$is_participant = ($result->num_rows > 0);

// Jika ini adalah percakapan baru tanpa pesan, izinkan akses
if (!$is_participant) {
    $query = "SELECT COUNT(*) as count FROM messages WHERE conversation_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    
    $is_new_conversation = ($count_row['count'] === '0');
    
    if (!$is_new_conversation) {
        // Bukan percakapan baru dan pengguna bukan peserta
        set_flash_message('error', 'Anda tidak memiliki akses ke percakapan ini.');
        header('Location: /chat/inbox.php');
        exit;
    }
}

// Ambil data lawan bicara
$other_user_id = null;
$other_user_info = null;

// Jika ini adalah percakapan dengan guru
if ($_SESSION['role'] === 'school' && $teacher_id > 0) {
    $query = "SELECT tp.*, u.email
              FROM teacher_profiles tp
              JOIN users u ON tp.user_id = u.id
              WHERE tp.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $other_user_info = $result->fetch_assoc();
        $other_user_id = $other_user_info['user_id'];
    }
}
// Jika ini adalah percakapan dengan sekolah
elseif ($_SESSION['role'] === 'teacher' && $school_id > 0) {
    $query = "SELECT sp.*, u.email
              FROM school_profiles sp
              JOIN users u ON sp.user_id = u.id
              WHERE sp.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $other_user_info = $result->fetch_assoc();
        $other_user_id = $other_user_info['user_id'];
    }
}
// Jika sudah ada conversation_id, cari lawan bicara dari pesan
else {
    $query = "SELECT DISTINCT sender_id FROM messages WHERE conversation_id = ? AND sender_id != ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $conversation_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $other_user_id = $row['sender_id'];
        
        // Ambil detail lawan bicara
        $query = "SELECT role FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $other_user_id);
        $stmt->execute();
        $role_result = $stmt->get_result();
        $role_row = $role_result->fetch_assoc();
        
        if ($role_row['role'] === 'teacher') {
            $query = "SELECT tp.*, u.email
                      FROM teacher_profiles tp
                      JOIN users u ON tp.user_id = u.id
                      WHERE u.id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $other_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $other_user_info = $result->fetch_assoc();
                $teacher_id = $other_user_info['id'];
            }
        } elseif ($role_row['role'] === 'school') {
            $query = "SELECT sp.*, u.email
                      FROM school_profiles sp
                      JOIN users u ON sp.user_id = u.id
                      WHERE u.id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $other_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $other_user_info = $result->fetch_assoc();
                $school_id = $other_user_info['id'];
            }
        }
    }
}

// Jika tidak ada informasi lawan bicara, gunakan default
if (!$other_user_info) {
    $other_user_info = [
        'full_name' => 'Pengguna',
        'school_name' => 'Sekolah',
        'profile_picture' => null,
        'email' => 'user@example.com'
    ];
}

// Proses pengiriman pesan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message_text = trim($_POST['message']);
    
    if (!empty($message_text)) {
        $query = "INSERT INTO messages (conversation_id, sender_id, message) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iis", $conversation_id, $_SESSION['user_id'], $message_text);
        
        if ($stmt->execute()) {
            // Pesan berhasil terkirim
            
            // Tandai pesan lain sebagai dibaca
            $query = "UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ii", $conversation_id, $_SESSION['user_id']);
            $stmt->execute();
            
            // Redirect untuk mencegah pengiriman ulang saat refresh
            header('Location: /chat/view.php?conversation_id=' . $conversation_id);
            exit;
        }
    }
}

// Ambil pesan-pesan dalam percakapan
$query = "SELECT m.*, u.role
          FROM messages m
          JOIN users u ON m.sender_id = u.id
          WHERE m.conversation_id = ?
          ORDER BY m.created_at ASC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$messages_result = $stmt->get_result();

$messages = [];
while ($message = $messages_result->fetch_assoc()) {
    $messages[] = $message;
}

// Tandai semua pesan sebagai dibaca
$query = "UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0";
$stmt = $db->prepare($query);
$stmt->bind_param("ii", $conversation_id, $_SESSION['user_id']);
$stmt->execute();

// Siapkan judul halaman
$page_title = $_SESSION['role'] === 'teacher' ? 'Chat dengan ' . ($other_user_info['school_name'] ?? 'Sekolah') : 'Chat dengan ' . ($other_user_info['full_name'] ?? 'Guru');

// Include header
include('../templates/header.php');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Informasi</h5>
                        <a href="/chat/inbox.php" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($_SESSION['role'] === 'school' && isset($other_user_info['id'])): ?>
                    <!-- Info Guru -->
                    <div class="text-center mb-3">
                        <img src="<?php echo $other_user_info['profile_picture'] ?? '../assets/img/default-avatar.png'; ?>" 
                             alt="<?php echo htmlspecialchars($other_user_info['full_name']); ?>" 
                             class="rounded-circle img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    
                    <h5 class="text-center"><?php echo htmlspecialchars($other_user_info['full_name']); ?></h5>
                    
                    <div class="mb-3">
                        <small class="text-muted">Pendidikan:</small>
                        <p class="mb-1"><?php echo htmlspecialchars($other_user_info['education'] ?? '-'); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Keahlian:</small>
                        <p class="mb-1"><?php echo htmlspecialchars($other_user_info['subject_expertise'] ?? '-'); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Alamat:</small>
                        <p class="mb-1"><?php echo htmlspecialchars($other_user_info['address'] ?? '-'); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Email:</small>
                        <p class="mb-1"><?php echo htmlspecialchars($other_user_info['email'] ?? '-'); ?></p>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="/teachers/profile.php?id=<?php echo $teacher_id; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user"></i> Lihat Profil Lengkap
                        </a>
                        
                        <?php if ($conversation['assignment_id']): ?>
                        <a href="/assignments/detail.php?id=<?php echo $conversation['assignment_id']; ?>" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-clipboard-list"></i> Lihat Detail Penugasan
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php elseif ($_SESSION['role'] === 'teacher' && isset($other_user_info['id'])): ?>
                    <!-- Info Sekolah -->
                    <div class="text-center mb-3">
                        <img src="<?php echo get_school_logo($school_id) ?? '../assets/img/school-placeholder.png'; ?>" 
                             alt="<?php echo htmlspecialchars($other_user_info['school_name']); ?>" 
                             class="img-thumbnail" style="max-width: 100px;">
                    </div>
                    
                    <h5 class="text-center"><?php echo htmlspecialchars($other_user_info['school_name']); ?></h5>
                    
                    <div class="mb-3">
                        <small class="text-muted">Alamat:</small>
                        <p class="mb-1"><?php echo htmlspecialchars($other_user_info['address'] ?? '-'); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Kepala Sekolah:</small>
                        <p class="mb-1"><?php echo htmlspecialchars($other_user_info['principal_name'] ?? '-'); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Kontak:</small>
                        <p class="mb-1"><?php echo htmlspecialchars($other_user_info['contact_person'] ?? '-'); ?> (<?php echo htmlspecialchars($other_user_info['contact_phone'] ?? '-'); ?>)</p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Email:</small>
                        <p class="mb-1"><?php echo htmlspecialchars($other_user_info['email'] ?? '-'); ?></p>
                    </div>
                    
                    <?php if ($conversation['assignment_id']): ?>
                    <div class="d-grid">
                        <a href="/assignments/detail.php?id=<?php echo $conversation['assignment_id']; ?>" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-clipboard-list"></i> Lihat Detail Penugasan
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php endif; ?>
                </div>
                
                <?php if ($conversation['assignment_id']): ?>
                <div class="card-footer">
                    <div class="mb-2">
                        <small class="text-muted d-block">Penugasan:</small>
                        <span class="fw-bold"><?php echo htmlspecialchars($conversation['assignment_title'] ?? 'Penugasan'); ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Mata Pelajaran:</small>
                        <span><?php echo htmlspecialchars($conversation['assignment_subject'] ?? '-'); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo $page_title; ?></h5>
                </div>
                
                <div class="card-body p-0">
                    <!-- Chat Messages -->
                    <div class="chat-messages p-3" id="chatMessages" style="height: 400px; overflow-y: auto;">
                        <?php if (empty($messages)): ?>
                        <div class="text-center text-muted my-5">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>Belum ada pesan. Mulai percakapan sekarang!</p>
                        </div>
                        <?php else: ?>
                            <?php 
                            $last_date = null;
                            foreach ($messages as $message): 
                                $message_date = date('Y-m-d', strtotime($message['created_at']));
                                
                                // Tampilkan pemisah tanggal jika berbeda hari
                                if ($last_date !== $message_date):
                                    $formatted_date = format_date($message_date);
                                    $last_date = $message_date;
                            ?>
                            <div class="text-center mb-3">
                                <span class="badge bg-light text-dark px-3 py-2"><?php echo $formatted_date; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="message mb-3 <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'message-sent' : 'message-received'; ?>">
                                <div class="message-content <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'bg-primary text-white' : 'bg-light'; ?> p-3 rounded">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                    <div class="message-time mt-1">
                                        <small class="<?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'text-white-50' : 'text-muted'; ?>">
                                            <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                            <?php if ($message['sender_id'] == $_SESSION['user_id'] && $message['is_read']): ?>
                                            <i class="fas fa-check-double ms-1"></i>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Chat Input -->
                    <div class="chat-input p-3 border-top">
                        <form method="post" action="">
                            <div class="input-group">
                                <textarea class="form-control" id="message" name="message" rows="2" placeholder="Ketik pesan..." required></textarea>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .message-sent {
        display: flex;
        justify-content: flex-end;
    }
    
    .message-received {
        display: flex;
        justify-content: flex-start;
    }
    
    .message-content {
        max-width: 75%;
        word-wrap: break-word;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Scroll ke pesan terbaru
        var chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Auto resize textarea
        var messageInput = document.getElementById('message');
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
</script>

<?php
// Include footer
include('../templates/footer.php');
?>