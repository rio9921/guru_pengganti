<?php
// /locations/check-in.php
// Form untuk check-in kehadiran guru

// Mulai sesi
session_start();

// Cek apakah pengguna sudah login dan adalah guru
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    // Redirect ke halaman login
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Include file konfigurasi dan koneksi database
require_once '../config/config.php';
require_once '../config/database.php';

// Include file fungsi
require_once '../includes/functions.php';
require_once '../includes/location-functions.php';

// Get teacher profile
$user_id = $_SESSION['user_id'];
$teacher_profile = get_teacher_profile_by_user_id($user_id);

if (!$teacher_profile) {
    // Redirect ke halaman profil untuk melengkapi profil
    header('Location: /teachers/profile.php?incomplete=1');
    exit;
}

$teacher_id = $teacher_profile['id'];

// Default values
$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$check_in_type = isset($_GET['type']) && $_GET['type'] === 'out' ? 'out' : 'in'; // Default is check-in
$success_message = '';
$error_message = '';

// Load assignment details if assignment_id is provided
$assignment = null;
if ($assignment_id > 0) {
    $query = "SELECT a.*, s.school_name, s.address as school_address, s.location_lat, s.location_lng
              FROM assignments a
              JOIN school_profiles s ON a.school_id = s.id
              JOIN applications app ON a.id = app.assignment_id
              WHERE a.id = ? AND app.teacher_id = ? AND app.status = 'accepted' AND a.status = 'in_progress'";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $assignment_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $assignment = $result->fetch_assoc();
    } else {
        $error_message = "Penugasan tidak ditemukan atau Anda tidak memiliki akses untuk melakukan check-in.";
    }
}

// Get active assignments for this teacher
$active_assignments = [];
$query = "SELECT a.*, s.school_name, s.address as school_address
          FROM assignments a
          JOIN school_profiles s ON a.school_id = s.id
          JOIN applications app ON a.id = app.assignment_id
          WHERE app.teacher_id = ? AND app.status = 'accepted' AND a.status = 'in_progress'
          ORDER BY a.start_date ASC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $active_assignments[] = $row;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
    $submitted_type = isset($_POST['check_type']) ? $_POST['check_type'] : 'in';
    $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : 0;
    $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : 0;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Validate inputs
    if ($submitted_assignment_id <= 0) {
        $error_message = "Pilih penugasan yang valid.";
    } elseif ($lat == 0 || $lng == 0) {
        $error_message = "Gagal mendapatkan lokasi Anda. Pastikan Anda mengizinkan akses lokasi.";
    } else {
        // Check if there's an existing attendance record for today
        $today = date('Y-m-d');
        $query = "SELECT * FROM attendance WHERE assignment_id = ? AND teacher_id = ? AND date = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iis", $submitted_assignment_id, $teacher_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Get the school location for this assignment
        $query2 = "SELECT s.location_lat, s.location_lng 
                  FROM assignments a
                  JOIN school_profiles s ON a.school_id = s.id
                  WHERE a.id = ?";
        $stmt2 = $db->prepare($query2);
        $stmt2->bind_param("i", $submitted_assignment_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $school_location = $result2->fetch_assoc();
        
        // Calculate distance between teacher and school
        $distance = 0;
        if ($school_location && $school_location['location_lat'] && $school_location['location_lng']) {
            $distance = calculate_distance(
                $lat, 
                $lng, 
                $school_location['location_lat'], 
                $school_location['location_lng']
            );
        }
        
        // Determine attendance status based on location
        $status = 'present';
        if ($distance > 1) { // If more than 1 km away, mark as 'late' or special case
            $status = 'late';
        }
        
        if ($result->num_rows > 0) {
            // There's an existing record, update it
            $attendance = $result->fetch_assoc();
            
            if ($submitted_type === 'in' && !empty($attendance['check_in_time'])) {
                $error_message = "Anda sudah melakukan check-in hari ini.";
            } elseif ($submitted_type === 'out' && !empty($attendance['check_out_time'])) {
                $error_message = "Anda sudah melakukan check-out hari ini.";
            } elseif ($submitted_type === 'out' && empty($attendance['check_in_time'])) {
                $error_message = "Anda harus melakukan check-in terlebih dahulu sebelum check-out.";
            } else {
                // Update attendance record
                if ($submitted_type === 'in') {
                    $query = "UPDATE attendance SET 
                              check_in_time = CURRENT_TIME(),
                              check_in_location_lat = ?,
                              check_in_location_lng = ?,
                              status = ?,
                              notes = CONCAT(notes, '\n', ?)
                              WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("ddssi", $lat, $lng, $status, $notes, $attendance['id']);
                } else {
                    $query = "UPDATE attendance SET 
                              check_out_time = CURRENT_TIME(),
                              check_out_location_lat = ?,
                              check_out_location_lng = ?,
                              notes = CONCAT(notes, '\n', ?)
                              WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("ddsi", $lat, $lng, $notes, $attendance['id']);
                }
                
                if ($stmt->execute()) {
                    $success_message = $submitted_type === 'in' ? 
                                       "Check-in berhasil dicatat!" : 
                                       "Check-out berhasil dicatat!";
                    
                    // Send notification to school
                    $query = "SELECT s.user_id, s.school_name, a.title 
                             FROM assignments a 
                             JOIN school_profiles s ON a.school_id = s.id 
                             WHERE a.id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("i", $submitted_assignment_id);
                    $stmt->execute();
                    $notification_result = $stmt->get_result();
                    
                    if ($notification_result->num_rows > 0) {
                        $notification_row = $notification_result->fetch_assoc();
                        $school_user_id = $notification_row['user_id'];
                        $school_name = $notification_row['school_name'];
                        $assignment_title = $notification_row['title'];
                        
                        $notification_title = $submitted_type === 'in' ? 
                                           "Guru Melakukan Check-in" : 
                                           "Guru Melakukan Check-out";
                                           
                        $notification_message = $submitted_type === 'in' ? 
                                             "Guru {$teacher_profile['full_name']} telah melakukan check-in untuk penugasan \"{$assignment_title}\"." :
                                             "Guru {$teacher_profile['full_name']} telah melakukan check-out untuk penugasan \"{$assignment_title}\".";
                        
                        create_notification($school_user_id, $notification_title, $notification_message, 'attendance', $attendance['id']);
                    }
                } else {
                    $error_message = "Terjadi kesalahan saat mencatat kehadiran: " . $db->error;
                }
            }
        } else {
            // No existing record, create a new one
            if ($submitted_type === 'out') {
                $error_message = "Anda harus melakukan check-in terlebih dahulu sebelum check-out.";
            } else {
                // Create new attendance record
                $query = "INSERT INTO attendance 
                          (assignment_id, teacher_id, date, check_in_time, check_in_location_lat, check_in_location_lng, status, notes) 
                          VALUES (?, ?, ?, CURRENT_TIME(), ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->bind_param("iisddss", $submitted_assignment_id, $teacher_id, $today, $lat, $lng, $status, $notes);
                
                if ($stmt->execute()) {
                    $attendance_id = $db->insert_id;
                    $success_message = "Check-in berhasil dicatat!";
                    
                    // Send notification to school
                    $query = "SELECT s.user_id, s.school_name, a.title 
                             FROM assignments a 
                             JOIN school_profiles s ON a.school_id = s.id 
                             WHERE a.id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("i", $submitted_assignment_id);
                    $stmt->execute();
                    $notification_result = $stmt->get_result();
                    
                    if ($notification_result->num_rows > 0) {
                        $notification_row = $notification_result->fetch_assoc();
                        $school_user_id = $notification_row['user_id'];
                        $school_name = $notification_row['school_name'];
                        $assignment_title = $notification_row['title'];
                        
                        $notification_title = "Guru Melakukan Check-in";
                        $notification_message = "Guru {$teacher_profile['full_name']} telah melakukan check-in untuk penugasan \"{$assignment_title}\".";
                        
                        create_notification($school_user_id, $notification_title, $notification_message, 'attendance', $attendance_id);
                    }
                } else {
                    $error_message = "Terjadi kesalahan saat mencatat kehadiran: " . $db->error;
                }
            }
        }
    }
}

// Set page title
$page_title = $check_in_type === 'in' ? 'Check-in Kehadiran' : 'Check-out Kehadiran';

// Include header
include('../templates/header.php');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0"><?php echo $check_in_type === 'in' ? 'Check-in Kehadiran' : 'Check-out Kehadiran'; ?></h4>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($active_assignments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h5>Tidak Ada Penugasan Aktif</h5>
                        <p class="text-muted">
                            Anda tidak memiliki penugasan aktif saat ini.
                            Silakan cek halaman Penugasan untuk melihat penugasan yang tersedia.
                        </p>
                        <a href="/teachers/assignments.php" class="btn btn-primary">Lihat Penugasan</a>
                    </div>
                    <?php else: ?>
                    
                    <form method="post" id="attendance-form">
                        <input type="hidden" name="lat" id="lat" value="">
                        <input type="hidden" name="lng" id="lng" value="">
                        <input type="hidden" name="check_type" value="<?php echo $check_in_type; ?>">
                        
                        <div class="mb-3">
                            <label for="assignment_id" class="form-label">Pilih Penugasan</label>
                            <select class="form-select" id="assignment_id" name="assignment_id" required>
                                <option value="">-- Pilih Penugasan --</option>
                                <?php foreach ($active_assignments as $active): ?>
                                <option value="<?php echo $active['id']; ?>" <?php echo ($assignment_id == $active['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($active['title']); ?> - <?php echo htmlspecialchars($active['school_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Pilih penugasan yang sedang Anda laksanakan.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Lokasi Anda</label>
                            <div id="map" style="height: 300px; width: 100%;" class="border rounded mb-2"></div>
                            <div class="form-text">
                                <span id="location-status">Menunggu lokasi...</span>
                                <button type="button" id="refresh-location" class="btn btn-sm btn-outline-secondary ms-2">
                                    <i class="fas fa-sync-alt"></i> Perbarui Lokasi
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($assignment): ?>
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Informasi Lokasi Sekolah</h6>
                                <p class="mb-0"><strong>Alamat:</strong> <?php echo htmlspecialchars($assignment['school_address']); ?></p>
                                <?php if ($assignment['location_lat'] && $assignment['location_lng']): ?>
                                <div id="distance-info"></div>
                                <?php else: ?>
                                <p class="mb-0 text-warning">Lokasi GPS sekolah tidak tersedia.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan (opsional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Tambahkan informasi tambahan jika diperlukan, misalnya alasan terlambat."></textarea>
                        </div>
                        
                        <div class="alert alert-warning" id="location-warning" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Perhatian! Anda berada cukup jauh dari lokasi sekolah. Pastikan Anda berada di lokasi yang benar.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <i class="fas fa-<?php echo $check_in_type === 'in' ? 'sign-in-alt' : 'sign-out-alt'; ?>"></i> 
                                <?php echo $check_in_type === 'in' ? 'Check-in Sekarang' : 'Check-out Sekarang'; ?>
                            </button>
                            <a href="/teachers/attendance.php" class="btn btn-outline-secondary">
                                <i class="fas fa-history"></i> Riwayat Kehadiran
                            </a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let map, marker, schoolMarker;
let schoolLat = <?php echo ($assignment && $assignment['location_lat']) ? $assignment['location_lat'] : 'null'; ?>;
let schoolLng = <?php echo ($assignment && $assignment['location_lng']) ? $assignment['location_lng'] : 'null'; ?>;

function initMap() {
    // Default location (Indonesia)
    const defaultLocation = { lat: -0.789275, lng: 113.921327 };
    
    map = new google.maps.Map(document.getElementById("map"), {
        center: defaultLocation,
        zoom: 15,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: false,
        streetViewControl: false
    });
    
    // Create marker for teacher
    marker = new google.maps.Marker({
        position: defaultLocation,
        map: map,
        title: "Lokasi Anda",
        icon: {
            url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
        }
    });
    
    // Create marker for school if coordinates are available
    if (schoolLat && schoolLng) {
        const schoolLocation = { lat: schoolLat, lng: schoolLng };
        schoolMarker = new google.maps.Marker({
            position: schoolLocation,
            map: map,
            title: "Lokasi Sekolah",
            icon: {
                url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
            }
        });
    }
    
    // Get current location
    getCurrentLocation();
}

function getCurrentLocation() {
    const locationStatus = document.getElementById('location-status');
    const submitBtn = document.getElementById('submit-btn');
    
    locationStatus.textContent = "Mendapatkan lokasi Anda...";
    submitBtn.disabled = true;
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                // Update map and marker
                map.setCenter(pos);
                marker.setPosition(pos);
                
                // Set form values
                document.getElementById('lat').value = pos.lat;
                document.getElementById('lng').value = pos.lng;
                
                // Show location status
                locationStatus.textContent = "Lokasi ditemukan: " + pos.lat.toFixed(6) + ", " + pos.lng.toFixed(6);
                submitBtn.disabled = false;
                
                // Calculate distance to school if school coordinates are available
                if (schoolLat && schoolLng) {
                    const distanceInKm = calculateDistance(pos.lat, pos.lng, schoolLat, schoolLng);
                    const distanceInfo = document.getElementById('distance-info');
                    
                    if (distanceInfo) {
                        distanceInfo.innerHTML = "<strong>Jarak dari sekolah:</strong> " + distanceInKm.toFixed(2) + " km";
                    }
                    
                    // Show warning if distance is too far
                    const locationWarning = document.getElementById('location-warning');
                    if (distanceInKm > 1) {
                        locationWarning.style.display = 'block';
                    } else {
                        locationWarning.style.display = 'none';
                    }
                }
            },
            function(error) {
                // Handle location error
                let errorMessage = "Gagal mendapatkan lokasi.";
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = "Akses lokasi ditolak. Mohon izinkan akses lokasi di pengaturan browser Anda.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = "Informasi lokasi tidak tersedia.";
                        break;
                    case error.TIMEOUT:
                        errorMessage = "Waktu permintaan lokasi habis.";
                        break;
                }
                
                locationStatus.textContent = errorMessage;
                locationStatus.classList.add('text-danger');
                submitBtn.disabled = true;
            },
            { enableHighAccuracy: true }
        );
    } else {
        locationStatus.textContent = "Browser Anda tidak mendukung geolokasi.";
        locationStatus.classList.add('text-danger');
        submitBtn.disabled = true;
    }
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Radius of the earth in km
    const dLat = deg2rad(lat2 - lat1);
    const dLon = deg2rad(lon2 - lon1);
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
        Math.sin(dLon/2) * Math.sin(dLon/2); 
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
    const d = R * c; // Distance in km
    return d;
}

function deg2rad(deg) {
    return deg * (Math.PI/180);
}

document.addEventListener('DOMContentLoaded', function() {
    // Refresh location button
    const refreshBtn = document.getElementById('refresh-location');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            getCurrentLocation();
        });
    }
    
    // Assignment selection change
    const assignmentSelect = document.getElementById('assignment_id');
    if (assignmentSelect) {
        assignmentSelect.addEventListener('change', function() {
            const assignmentId = this.value;
            if (assignmentId) {
                window.location.href = 'check-in.php?assignment_id=' + assignmentId + '&type=<?php echo $check_in_type; ?>';
            }
        });
    }
});
</script>

<!-- Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap" async defer></script>

<?php
// Include footer
include('../templates/footer.php');
?>