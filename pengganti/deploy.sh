#!/bin/bash
# GuruSinergi - Script Deployment
# 
# Script untuk melakukan deployment platform GuruSinergi ke server
# Pastikan script ini dijalankan dari root direktori proyek
# 
# Penggunaan: ./deploy.sh [environment]
# environment: production (default) atau staging

set -e  # Exit pada error

# Konfigurasi
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
ENVIRONMENT=${1:-production}  # Default ke production jika tidak disebutkan

# Konfigurasi berdasarkan environment
if [ "$ENVIRONMENT" = "production" ]; then
    REMOTE_USER="gurusinergi"
    REMOTE_HOST="pengganti.gurusinergi.com"
    REMOTE_PATH="/home/gurusinergi/public_html"
    REMOTE_BACKUP_PATH="/home/gurusinergi/backups"
    DB_NAME="u532109326_guru_pengganti"
    DB_USER="u532109326_gurupengganti"
    DB_HOST="localhost"
    # Catatan: Password database tidak disimpan di script
elif [ "$ENVIRONMENT" = "staging" ]; then
    REMOTE_USER="staging"
    REMOTE_HOST="staging.gurusinergi.com"
    REMOTE_PATH="/home/staging/public_html"
    REMOTE_BACKUP_PATH="/home/staging/backups"
    DB_NAME="u532109326_staging"
    DB_USER="u532109326_staging"
    DB_HOST="localhost"
    # Catatan: Password database tidak disimpan di script
else
    echo "Environment tidak valid. Gunakan 'production' atau 'staging'."
    exit 1
fi

# Fungsi untuk bantuan
show_help() {
    echo "GuruSinergi Deployment Script"
    echo "-----------------------------"
    echo "Penggunaan: ./deploy.sh [environment] [opsi]"
    echo ""
    echo "Environment:"
    echo "  production (default)  - Deploy ke server produksi"
    echo "  staging               - Deploy ke server staging"
    echo ""
    echo "Opsi:"
    echo "  --help, -h            - Tampilkan bantuan ini"
    echo "  --skip-backup         - Lewati proses backup"
    echo "  --skip-db             - Lewati update database"
    echo "  --full                - Lakukan full deployment termasuk reset database"
    echo ""
    echo "Contoh:"
    echo "  ./deploy.sh staging --skip-backup"
    echo "  ./deploy.sh production --full"
    exit 0
}

# Parse opsi tambahan
SKIP_BACKUP=false
SKIP_DB=false
FULL_DEPLOY=false

for arg in "$@"; do
    case $arg in
        --help|-h)
            show_help
            ;;
        --skip-backup)
            SKIP_BACKUP=true
            ;;
        --skip-db)
            SKIP_DB=true
            ;;
        --full)
            FULL_DEPLOY=true
            ;;
    esac
done

echo "=== GuruSinergi Deployment Tool ==="
echo "Environment: $ENVIRONMENT"
echo "Timestamp: $TIMESTAMP"
echo "=================================="

# 1. Persiapan lokal
echo "[1/6] Persiapan lokal..."

# Membuat direktori build sementara
BUILD_DIR="build_$TIMESTAMP"
mkdir -p $BUILD_DIR

# Mengkopi file-file yang diperlukan ke direktori build
echo "  - Menyalin file-file proyek..."
cp -R config includes templates assets api *.php $BUILD_DIR/

# Membuat direktori uploads jika belum ada
mkdir -p $BUILD_DIR/uploads/guru
mkdir -p $BUILD_DIR/uploads/sekolah
mkdir -p $BUILD_DIR/uploads/profile
mkdir -p $BUILD_DIR/uploads/materials
mkdir -p $BUILD_DIR/logs

# Membuat file .htaccess
cat > $BUILD_DIR/.htaccess << 'EOF'
# GuruSinergi - Konfigurasi Apache

# Aktifkan rewrite engine
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Jangan rewrite file dan direktori yang ada
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    # Arahkan semua permintaan ke index.php
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

# Batasi akses ke file konfigurasi
<FilesMatch "^(config\.php|database\.php)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Lindungi direktori
<DirectoryMatch "^/(config|includes)/">
    Order Allow,Deny
    Deny from all
</DirectoryMatch>

# Mengatur timezone
php_value date.timezone "Asia/Jakarta"

# Mengatur batas upload
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300

# Keamanan
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "same-origin"
</IfModule>
EOF

# Modifikasi config.php untuk environment target
if [ "$ENVIRONMENT" = "production" ]; then
    sed -i "s/'site_url' => 'https:\/\/pengganti\.gurusinergi\.com'/'site_url' => 'https:\/\/pengganti\.gurusinergi\.com'/g" $BUILD_DIR/config/config.php
elif [ "$ENVIRONMENT" = "staging" ]; then
    sed -i "s/'site_url' => 'https:\/\/pengganti\.gurusinergi\.com'/'site_url' => 'https:\/\/staging\.gurusinergi\.com'/g" $BUILD_DIR/config/config.php
fi

# Membuat archive
echo "  - Membuat archive deployment..."
tar -czf "${BUILD_DIR}.tar.gz" $BUILD_DIR

echo "  ✓ Persiapan lokal selesai"

# 2. Backup server (jika diperlukan)
if [ "$SKIP_BACKUP" = false ]; then
    echo "[2/6] Melakukan backup server..."
    
    ssh $REMOTE_USER@$REMOTE_HOST "mkdir -p $REMOTE_BACKUP_PATH"
    
    # Backup file
    echo "  - Backup file-file website..."
    ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -czf $REMOTE_BACKUP_PATH/files_$TIMESTAMP.tar.gz ."
    
    # Backup database
    if [ "$SKIP_DB" = false ]; then
        echo "  - Backup database..."
        echo "    Masukkan password database untuk $DB_USER:"
        ssh $REMOTE_USER@$REMOTE_HOST "mysqldump -h $DB_HOST -u $DB_USER -p $DB_NAME > $REMOTE_BACKUP_PATH/db_$TIMESTAMP.sql"
    else
        echo "  - Melewati backup database..."
    fi
    
    echo "  ✓ Backup selesai: $REMOTE_BACKUP_PATH/files_$TIMESTAMP.tar.gz"
    if [ "$SKIP_DB" = false ]; then
        echo "    Database: $REMOTE_BACKUP_PATH/db_$TIMESTAMP.sql"
    fi
else
    echo "[2/6] Melewati proses backup..."
fi

# 3. Upload file ke server
echo "[3/6] Mengupload file ke server..."
scp "${BUILD_DIR}.tar.gz" $REMOTE_USER@$REMOTE_HOST:/tmp/

echo "  ✓ Upload selesai"

# 4. Deploy di server
echo "[4/6] Melakukan deployment di server..."

# Extrak file dan pindahkan ke direktori target
ssh $REMOTE_USER@$REMOTE_HOST "
    cd /tmp && 
    tar -xzf ${BUILD_DIR}.tar.gz && 
    
    # Jika full deploy, hapus semua file lama
    if [ \"$FULL_DEPLOY\" = true ]; then
        echo '  - Menghapus semua file lama...' && 
        rm -rf $REMOTE_PATH/* $REMOTE_PATH/.[^.]* 2>/dev/null || true
    else
        # Pastikan directory uploads dipertahankan
        echo '  - Mempertahankan direktori uploads...' && 
        mkdir -p $REMOTE_PATH/uploads
    fi && 
    
    # Pindahkan file baru
    echo '  - Memindahkan file baru...' && 
    cp -a /tmp/$BUILD_DIR/. $REMOTE_PATH/ && 
    
    # Set permission
    echo '  - Mengatur permission...' && 
    find $REMOTE_PATH -type f -exec chmod 644 {} \; && 
    find $REMOTE_PATH -type d -exec chmod 755 {} \; && 
    chmod -R 775 $REMOTE_PATH/uploads && 
    chmod -R 775 $REMOTE_PATH/logs && 
    
    # Bersihkan file temporary
    echo '  - Membersihkan file temporary...' && 
    rm -rf /tmp/${BUILD_DIR}* 
"

echo "  ✓ Deployment file selesai"

# 5. Update database (jika diperlukan)
if [ "$SKIP_DB" = false ]; then
    echo "[5/6] Mengupdate database..."
    
    if [ "$FULL_DEPLOY" = true ]; then
        echo "  - Melakukan setup database lengkap..."
        echo "  - PERHATIAN: Ini akan menghapus semua data dan membuat ulang struktur database!"
        echo "    Lanjutkan? (y/n)"
        read confirm
        
        if [ "$confirm" = "y" ]; then
            echo "    Masukkan password database untuk $DB_USER:"
            ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && php -r \"require 'config/config.php'; require 'config/database.php'; setup_database();\""
            echo "  ✓ Database berhasil disetup ulang"
        else
            echo "  - Setup database dibatalkan"
        fi
    else
        echo "  - Tidak ada perubahan skema database, melewati update..."
    fi
else
    echo "[5/6] Melewati update database..."
fi

# 6. Bersihkan
echo "[6/6] Membersihkan file lokal..."
rm -rf $BUILD_DIR "${BUILD_DIR}.tar.gz"

echo "  ✓ Pembersihan selesai"

echo ""
echo "=== Deployment Selesai ==="
echo "GuruSinergi telah berhasil di-deploy ke $ENVIRONMENT"
echo "URL: https://$REMOTE_HOST"
echo "=========================="