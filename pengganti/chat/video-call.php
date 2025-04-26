<?php
// /chat/video-call.php
// Halaman untuk melakukan video call antara guru dan sekolah

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

// Ambil parameter
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;

// Generate room ID unik
if ($conversation_id > 0) {
    // Gunakan conversation_id sebagai dasar room ID
    $room_id = 'gs_' . md5('conversation_' . $conversation_id . '_' . date('Ymd'));
} elseif ($teacher_id > 0 && $school_id > 0) {
    // Gunakan kombinasi teacher_id dan school_id
    $room_id = 'gs_' . md5('teacher_' . $teacher_id . '_school_' . $school_id . '_' . date('Ymd'));
} else {
    // Redirect jika tidak ada parameter yang valid
    set_flash_message('error', 'Parameter tidak valid untuk video call.');
    header('Location: /chat/inbox.php');
    exit;
}

// Verifikasi akses ke conversation jika ada conversation_id
if ($conversation_id > 0) {
    $query = "SELECT c.*, 
              (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND (sender_id = ? OR receiver_id = ?)) as message_count
              FROM conversations c 
              WHERE c.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("iii", $_SESSION['user_id'], $_SESSION['user_id'], $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0 || $result->fetch_assoc()['message_count'] === 0) {
        set_flash_message('error', 'Anda tidak memiliki akses ke percakapan ini.');
        header('Location: /chat/inbox.php');
        exit;
    }
}

// Jika ada teacher_id, verifikasi bahwa pengguna adalah sekolah
if ($teacher_id > 0 && $_SESSION['role'] !== 'school') {
    set_flash_message('error', 'Hanya sekolah yang dapat memulai video call dengan guru.');
    header('Location: /dashboard.php');
    exit;
}

// Jika ada school_id, verifikasi bahwa pengguna adalah guru
if ($school_id > 0 && $_SESSION['role'] !== 'teacher') {
    set_flash_message('error', 'Hanya guru yang dapat memulai video call dengan sekolah.');
    header('Location: /dashboard.php');
    exit;
}

// Ambil informasi lawan bicara
$other_name = '';
$other_role = '';
$other_id = 0;

if ($_SESSION['role'] === 'teacher' && $school_id > 0) {
    // Ambil informasi sekolah
    $query = "SELECT sp.*, u.id as user_id, u.email 
              FROM school_profiles sp
              JOIN users u ON sp.user_id = u.id
              WHERE sp.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $school = $result->fetch_assoc();
        $other_name = $school['school_name'];
        $other_role = 'school';
        $other_id = $school['user_id'];
    }
} elseif ($_SESSION['role'] === 'school' && $teacher_id > 0) {
    // Ambil informasi guru
    $query = "SELECT tp.*, u.id as user_id, u.email 
              FROM teacher_profiles tp
              JOIN users u ON tp.user_id = u.id
              WHERE tp.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $teacher = $result->fetch_assoc();
        $other_name = $teacher['full_name'];
        $other_role = 'teacher';
        $other_id = $teacher['user_id'];
    }
} elseif ($conversation_id > 0) {
    // Ambil informasi lawan bicara dari percakapan
    $query = "SELECT DISTINCT m.sender_id
              FROM messages m
              WHERE m.conversation_id = ? AND m.sender_id != ?
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $conversation_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $other_user_id = $row['sender_id'];
        
        // Ambil detail lawan bicara
        $query = "SELECT u.id, u.role, u.email, 
                 CASE 
                     WHEN u.role = 'teacher' THEN tp.full_name
                     WHEN u.role = 'school' THEN sp.school_name
                     ELSE 'Unknown'
                 END as name,
                 CASE 
                     WHEN u.role = 'teacher' THEN tp.id
                     WHEN u.role = 'school' THEN sp.id
                     ELSE 0
                 END as profile_id
                 FROM users u
                 LEFT JOIN teacher_profiles tp ON u.id = tp.user_id AND u.role = 'teacher'
                 LEFT JOIN school_profiles sp ON u.id = sp.user_id AND u.role = 'school'
                 WHERE u.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $other_user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        
        if ($user_result->num_rows > 0) {
            $user = $user_result->fetch_assoc();
            $other_name = $user['name'];
            $other_role = $user['role'];
            $other_id = $user['id'];
            
            // Set teacher_id atau school_id untuk URL
            if ($other_role === 'teacher') {
                $teacher_id = $user['profile_id'];
            } elseif ($other_role === 'school') {
                $school_id = $user['profile_id'];
            }
        }
    }
}

// Jika tidak dapat menemukan informasi lawan bicara, redirect
if (empty($other_name)) {
    set_flash_message('error', 'Tidak dapat memulai video call. Informasi lawan bicara tidak ditemukan.');
    header('Location: /chat/inbox.php');
    exit;
}

// Kirim notifikasi ke lawan bicara
$notification_title = "Panggilan Video";
$notification_message = $_SESSION['role'] === 'teacher' ? 
                       "Guru " . get_user_name($_SESSION['user_id']) . " ingin melakukan panggilan video dengan Anda." :
                       get_user_name($_SESSION['user_id']) . " ingin melakukan panggilan video dengan Anda.";

create_notification($other_id, $notification_title, $notification_message, 'video_call', $conversation_id);

// Set judul halaman
$page_title = 'Video Call dengan ' . $other_name;

// Include header
include('../templates/header.php');
?>

<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <h1 class="h3">Video Call</h1>
            <p class="text-muted">
                Panggilan dengan <strong><?php echo htmlspecialchars($other_name); ?></strong>
                <?php if ($_SESSION['role'] === 'teacher' && $other_role === 'school'): ?>
                <span class="badge bg-primary">Sekolah</span>
                <?php elseif ($_SESSION['role'] === 'school' && $other_role === 'teacher'): ?>
                <span class="badge bg-info">Guru</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="<?php echo $conversation_id ? '/chat/view.php?conversation_id=' . $conversation_id : '/chat/inbox.php'; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Chat
                </a>
                <button id="endCall" class="btn btn-danger">
                    <i class="fas fa-phone-slash"></i> Akhiri Panggilan
                </button>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-9">
            <div class="card">
                <div class="card-body p-0">
                    <div id="videoContainer" class="position-relative">
                        <!-- Video utama (lawan bicara) -->
                        <div id="remoteVideo" class="w-100 bg-dark">
                            <video id="remoteVideoElement" autoplay playsinline class="w-100"></video>
                            <div id="waitingMessage" class="text-center text-white position-absolute top-50 start-50 translate-middle">
                                <div class="spinner-border text-light mb-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <h5>Menunggu <?php echo htmlspecialchars($other_name); ?> bergabung...</h5>
                                <p class="mb-0">Pastikan Anda telah memberikan akses kamera dan mikrofon</p>
                            </div>
                        </div>
                        
                        <!-- Video sendiri (kecil) -->
                        <div id="localVideo" class="position-absolute bottom-0 end-0 m-3" style="width: 200px; height: 150px; border-radius: 8px; overflow: hidden;">
                            <video id="localVideoElement" autoplay playsinline muted class="w-100 h-100 bg-secondary" style="object-fit: cover;"></video>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer bg-dark">
                    <div class="d-flex justify-content-center">
                        <div class="btn-group">
                            <button id="toggleAudio" class="btn btn-light rounded-circle mx-2" title="Matikan Mikrofon">
                                <i class="fas fa-microphone"></i>
                            </button>
                            <button id="toggleVideo" class="btn btn-light rounded-circle mx-2" title="Matikan Kamera">
                                <i class="fas fa-video"></i>
                            </button>
                            <button id="shareScreen" class="btn btn-light rounded-circle mx-2" title="Bagikan Layar">
                                <i class="fas fa-desktop"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Chat</h5>
                </div>
                <div class="card-body p-0">
                    <div id="chatMessages" class="p-3" style="height: 300px; overflow-y: auto;">
                        <div class="text-center text-muted my-5">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>Belum ada pesan</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="input-group">
                        <input type="text" id="chatInput" class="form-control" placeholder="Ketik pesan...">
                        <button id="sendMessage" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Informasi</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Status Koneksi</h6>
                        <p id="connectionStatus" class="mb-0">
                            <span class="badge bg-warning">Menunggu Koneksi</span>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Kualitas Video</h6>
                        <div class="progress">
                            <div id="videoQuality" class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <h6>Tips</h6>
                        <ul class="small mb-0">
                            <li>Gunakan headphone untuk kualitas audio yang lebih baik</li>
                            <li>Pastikan koneksi internet Anda stabil</li>
                            <li>Berada di ruangan yang cukup terang</li>
                            <li>Jangan ragu untuk me-refresh halaman jika mengalami masalah</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript untuk WebRTC dan Video Call -->
<script src="https://unpkg.com/peerjs@1.4.7/dist/peerjs.min.js"></script>
<script>
    // Variabel konfigurasi
    const roomId = '<?php echo $room_id; ?>';
    const userId = '<?php echo $_SESSION['user_id']; ?>';
    const userName = '<?php echo get_user_name($_SESSION['user_id']); ?>';
    const otherName = '<?php echo $other_name; ?>';
    let localStream = null;
    let remoteStream = null;
    let peer = null;
    let currentCall = null;
    let screenSharingStream = null;
    let isAudioEnabled = true;
    let isVideoEnabled = true;
    let isScreenSharing = false;
    
    // Tombol-tombol
    const toggleAudioBtn = document.getElementById('toggleAudio');
    const toggleVideoBtn = document.getElementById('toggleVideo');
    const shareScreenBtn = document.getElementById('shareScreen');
    const endCallBtn = document.getElementById('endCall');
    const sendMessageBtn = document.getElementById('sendMessage');
    const chatInput = document.getElementById('chatInput');
    const chatMessages = document.getElementById('chatMessages');
    const connectionStatus = document.getElementById('connectionStatus');
    const videoQuality = document.getElementById('videoQuality');
    const waitingMessage = document.getElementById('waitingMessage');
    
    // Inisialisasi
    async function initialize() {
        try {
            // Dapatkan akses ke media
            localStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });
            
            // Tampilkan video lokal
            const localVideoElement = document.getElementById('localVideoElement');
            localVideoElement.srcObject = localStream;
            
            // Inisialisasi Peer
            peer = new Peer('gs_user_' + userId, {
                debug: 3,
                config: {
                    'iceServers': [
                        { urls: 'stun:stun.l.google.com:19302' },
                        { urls: 'stun:stun1.l.google.com:19302' }
                    ]
                }
            });
            
            // Event saat peer terhubung ke server
            peer.on('open', (id) => {
                console.log('Connected to PeerJS server with ID:', id);
                connectionStatus.innerHTML = '<span class="badge bg-info">Menunggu Lawan Bicara</span>';
                joinRoom();
            });
            
            // Event saat ada panggilan masuk
            peer.on('call', (call) => {
                console.log('Incoming call from:', call.peer);
                connectionStatus.innerHTML = '<span class="badge bg-success">Terhubung</span>';
                
                // Jawab panggilan dan kirim stream lokal
                call.answer(localStream);
                handleCall(call);
                currentCall = call;
                waitingMessage.style.display = 'none';
            });
            
            // Event saat terjadi kesalahan
            peer.on('error', (err) => {
                console.error('PeerJS error:', err);
                connectionStatus.innerHTML = '<span class="badge bg-danger">Error: ' + err.type + '</span>';
                
                // Tampilkan pesan error
                showErrorMessage('Terjadi kesalahan koneksi: ' + err.type);
            });
            
            // Event untuk chat
            peer.on('connection', (conn) => {
                conn.on('data', (data) => {
                    // Tampilkan pesan chat
                    if (data.type === 'chat') {
                        addChatMessage(data.sender, data.message, false);
                    }
                });
            });
            
        } catch (err) {
            console.error('Error accessing media devices:', err);
            
            // Tampilkan pesan error
            showErrorMessage('Tidak dapat mengakses kamera atau mikrofon. Pastikan Anda telah memberikan izin akses.');
        }
    }
    
    // Fungsi untuk bergabung ke room
    function joinRoom() {
        // Buat data connection untuk chat
        const dataConnection = peer.connect('gs_user_' + (userId === '<?php echo $_SESSION['user_id']; ?>' ? '<?php echo $other_id; ?>' : '<?php echo $_SESSION['user_id']; ?>'));
        
        dataConnection.on('open', () => {
            console.log('Data connection established');
            
            // Kirim pesan selamat datang
            sendMessageBtn.addEventListener('click', () => {
                const message = chatInput.value.trim();
                if (message) {
                    dataConnection.send({
                        type: 'chat',
                        sender: userName,
                        message: message
                    });
                    
                    // Tampilkan pesan sendiri
                    addChatMessage(userName, message, true);
                    chatInput.value = '';
                }
            });
            
            // Kirim pesan dengan Enter
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendMessageBtn.click();
                }
            });
        });
        
        // Panggil peer lain di room yang sama
        const remotePeerId = 'gs_user_' + (userId === '<?php echo $_SESSION['user_id']; ?>' ? '<?php echo $other_id; ?>' : '<?php echo $_SESSION['user_id']; ?>');
        console.log('Calling peer:', remotePeerId);
        
        const call = peer.call(remotePeerId, localStream);
        handleCall(call);
        currentCall = call;
    }
    
    // Fungsi untuk menangani panggilan
    function handleCall(call) {
        call.on('stream', (stream) => {
            console.log('Received remote stream');
            remoteStream = stream;
            
            // Tampilkan video remote
            const remoteVideoElement = document.getElementById('remoteVideoElement');
            remoteVideoElement.srcObject = stream;
            
            // Sembunyikan pesan menunggu
            waitingMessage.style.display = 'none';
            
            // Update status koneksi
            connectionStatus.innerHTML = '<span class="badge bg-success">Terhubung</span>';
            
            // Monitor kualitas video
            monitorVideoQuality();
        });
        
        call.on('close', () => {
            console.log('Call closed');
            connectionStatus.innerHTML = '<span class="badge bg-danger">Panggilan Berakhir</span>';
            
            // Tampilkan pesan panggilan berakhir
            showErrorMessage('Panggilan telah berakhir.');
            
            // Redirect ke halaman sebelumnya setelah beberapa detik
            setTimeout(() => {
                window.location.href = '<?php echo $conversation_id ? "/chat/view.php?conversation_id={$conversation_id}" : "/chat/inbox.php"; ?>';
            }, 3000);
        });
    }
    
    // Fungsi untuk mengakhiri panggilan
    function endCall() {
        if (currentCall) {
            currentCall.close();
        }
        
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
        }
        
        if (screenSharingStream) {
            screenSharingStream.getTracks().forEach(track => track.stop());
        }
        
        // Redirect ke halaman sebelumnya
        window.location.href = '<?php echo $conversation_id ? "/chat/view.php?conversation_id={$conversation_id}" : "/chat/inbox.php"; ?>';
    }
    
    // Fungsi untuk mematikan/menghidupkan audio
    function toggleAudio() {
        if (localStream) {
            const audioTracks = localStream.getAudioTracks();
            if (audioTracks.length > 0) {
                isAudioEnabled = !isAudioEnabled;
                audioTracks[0].enabled = isAudioEnabled;
                
                toggleAudioBtn.innerHTML = isAudioEnabled ? 
                    '<i class="fas fa-microphone"></i>' : 
                    '<i class="fas fa-microphone-slash"></i>';
                
                toggleAudioBtn.classList.toggle('btn-light', isAudioEnabled);
                toggleAudioBtn.classList.toggle('btn-danger', !isAudioEnabled);
                
                toggleAudioBtn.title = isAudioEnabled ? 'Matikan Mikrofon' : 'Hidupkan Mikrofon';
            }
        }
    }
    
    // Fungsi untuk mematikan/menghidupkan video
    function toggleVideo() {
        if (localStream) {
            const videoTracks = localStream.getVideoTracks();
            if (videoTracks.length > 0) {
                isVideoEnabled = !isVideoEnabled;
                videoTracks[0].enabled = isVideoEnabled;
                
                toggleVideoBtn.innerHTML = isVideoEnabled ? 
                    '<i class="fas fa-video"></i>' : 
                    '<i class="fas fa-video-slash"></i>';
                
                toggleVideoBtn.classList.toggle('btn-light', isVideoEnabled);
                toggleVideoBtn.classList.toggle('btn-danger', !isVideoEnabled);
                
                toggleVideoBtn.title = isVideoEnabled ? 'Matikan Kamera' : 'Hidupkan Kamera';
                
                // Tampilkan/sembunyikan video lokal
                document.getElementById('localVideoElement').style.display = isVideoEnabled ? 'block' : 'none';
            }
        }
    }
    
    // Fungsi untuk berbagi layar
    async function shareScreen() {
        try {
            if (!isScreenSharing) {
                screenSharingStream = await navigator.mediaDevices.getDisplayMedia({
                    video: true,
                    audio: false
                });
                
                // Ganti video track di panggilan yang sedang berlangsung
                if (currentCall && screenSharingStream) {
                    const videoTrack = screenSharingStream.getVideoTracks()[0];
                    const sender = currentCall.peerConnection.getSenders().find(s => s.track.kind === 'video');
                    
                    if (sender) {
                        sender.replaceTrack(videoTrack);
                    }
                    
                    // Tampilkan video screen sharing di video lokal
                    document.getElementById('localVideoElement').srcObject = screenSharingStream;
                    
                    // Update status screen sharing
                    isScreenSharing = true;
                    shareScreenBtn.innerHTML = '<i class="fas fa-stop"></i>';
                    shareScreenBtn.classList.remove('btn-light');
                    shareScreenBtn.classList.add('btn-warning');
                    shareScreenBtn.title = 'Hentikan Berbagi Layar';
                    
                    // Event saat screen sharing berakhir
                    videoTrack.onended = () => {
                        stopScreenSharing();
                    };
                }
            } else {
                stopScreenSharing();
            }
        } catch (err) {
            console.error('Error sharing screen:', err);
            alert('Tidak dapat berbagi layar: ' + err.message);
        }
    }
    
    // Fungsi untuk menghentikan berbagi layar
    function stopScreenSharing() {
        if (screenSharingStream) {
            screenSharingStream.getTracks().forEach(track => track.stop());
            screenSharingStream = null;
            
            // Kembalikan video track dari kamera
            if (currentCall && localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                if (videoTrack) {
                    const sender = currentCall.peerConnection.getSenders().find(s => s.track.kind === 'video');
                    if (sender) {
                        sender.replaceTrack(videoTrack);
                    }
                    
                    // Kembalikan video lokal ke kamera
                    document.getElementById('localVideoElement').srcObject = localStream;
                }
            }
            
            // Update status screen sharing
            isScreenSharing = false;
            shareScreenBtn.innerHTML = '<i class="fas fa-desktop"></i>';
            shareScreenBtn.classList.remove('btn-warning');
            shareScreenBtn.classList.add('btn-light');
            shareScreenBtn.title = 'Bagikan Layar';
        }
    }
    
    // Fungsi untuk menampilkan pesan chat
    function addChatMessage(sender, message, isSelf) {
        // Hapus pesan kosong jika ada
        const emptyMessage = chatMessages.querySelector('.text-center.text-muted.my-5');
        if (emptyMessage) {
            chatMessages.removeChild(emptyMessage);
        }
        
        const messageElement = document.createElement('div');
        messageElement.className = 'mb-3';
        
        const messageContent = document.createElement('div');
        messageContent.className = isSelf ? 
            'bg-primary text-white p-2 rounded float-end' : 
            'bg-light p-2 rounded float-start';
        messageContent.style.maxWidth = '80%';
        messageContent.style.wordWrap = 'break-word';
        
        const senderElement = document.createElement('div');
        senderElement.className = 'small ' + (isSelf ? 'text-white-50' : 'text-muted');
        senderElement.textContent = sender;
        
        const textElement = document.createElement('div');
        textElement.textContent = message;
        
        messageContent.appendChild(senderElement);
        messageContent.appendChild(textElement);
        messageElement.appendChild(messageContent);
        
        const clearfix = document.createElement('div');
        clearfix.className = 'clearfix';
        messageElement.appendChild(clearfix);
        
        chatMessages.appendChild(messageElement);
        
        // Scroll ke bawah
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Fungsi untuk memantau kualitas video
    function monitorVideoQuality() {
        if (currentCall && currentCall.peerConnection) {
            const intervalId = setInterval(() => {
                if (currentCall && currentCall.peerConnection) {
                    // Ambil statistik koneksi
                    currentCall.peerConnection.getStats().then(stats => {
                        stats.forEach(report => {
                            if (report.type === 'inbound-rtp' && report.kind === 'video') {
                                // Hitung packet loss
                                const packetsLost = report.packetsLost || 0;
                                const packetsReceived = report.packetsReceived || 0;
                                const totalPackets = packetsLost + packetsReceived;
                                
                                const lossRate = totalPackets > 0 ? (packetsLost / totalPackets) * 100 : 0;
                                
                                // Update indikator kualitas video
                                const quality = 100 - lossRate;
                                videoQuality.style.width = quality + '%';
                                
                                if (quality > 80) {
                                    videoQuality.className = 'progress-bar bg-success';
                                } else if (quality > 50) {
                                    videoQuality.className = 'progress-bar bg-warning';
                                } else {
                                    videoQuality.className = 'progress-bar bg-danger';
                                }
                            }
                        });
                    });
                } else {
                    clearInterval(intervalId);
                }
            }, 2000);
        }
    }
    
    // Fungsi untuk menampilkan pesan error
    function showErrorMessage(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger position-absolute top-50 start-50 translate-middle text-center';
        errorDiv.style.zIndex = '1000';
        errorDiv.style.maxWidth = '80%';
        
        const errorIcon = document.createElement('i');
        errorIcon.className = 'fas fa-exclamation-circle fa-3x mb-3';
        
        const errorText = document.createElement('h5');
        errorText.textContent = message;
        
        errorDiv.appendChild(errorIcon);
        errorDiv.appendChild(errorText);
        
        document.getElementById('videoContainer').appendChild(errorDiv);
    }
    
    // Event listeners
    document.addEventListener('DOMContentLoaded', () => {
        initialize();
        
        toggleAudioBtn.addEventListener('click', toggleAudio);
        toggleVideoBtn.addEventListener('click', toggleVideo);
        shareScreenBtn.addEventListener('click', shareScreen);
        endCallBtn.addEventListener('click', endCall);
        
        // Tambahkan event sebelum meninggalkan halaman
        window.addEventListener('beforeunload', () => {
            endCall();
        });
    });
</script>

<?php
// Include footer
include('../templates/footer.php');
?>