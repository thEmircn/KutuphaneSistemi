<?php
class PermissionManager {
    private $pdo;
    private $user_permissions = null;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadUserPermissions();
    }
    
    private function loadUserPermissions() {
        if (!isset($_SESSION['admin_id'])) {
            return;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT r.izinler, r.rol_adi 
            FROM kullanicilar k 
            JOIN roller r ON k.rol_id = r.id 
            WHERE k.id = ? AND k.kullanici_tipi = 'admin' AND k.durum = 'aktif' AND r.durum = 'aktif'
        ");
        $stmt->execute([$_SESSION['admin_id']]);
        $result = $stmt->fetch();
        
        if ($result) {
            $this->user_permissions = json_decode($result['izinler'], true);
            $_SESSION['user_role'] = $result['rol_adi'];
        }
    }
    
    public function hasPermission($module, $action = 'read') {
        if (!$this->user_permissions) {
            return false;
        }
        
        // Super admin tüm yetkilere sahip
        if (isset($this->user_permissions['all']) && $this->user_permissions['all'] === true) {
            return true;
        }
        
        // Modül ve aksiyon kontrolü
        return isset($this->user_permissions[$module][$action]) && 
               $this->user_permissions[$module][$action] === true;
    }
    
    public function requirePermission($module, $action = 'read') {
        if (!$this->hasPermission($module, $action)) {
            header('HTTP/1.1 403 Forbidden');
            include 'includes/403.php';
            exit;
        }
    }
    
    public function logActivity($activity_type, $details = '', $affected_table = '', $affected_id = 0) {
        if (!isset($_SESSION['admin_id'])) {
            return;
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO personel_aktivite_log (personel_id, aktivite_turu, aktivite_detay, etkilenen_tablo, etkilenen_kayit_id, ip_adresi, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt->execute([
            $_SESSION['admin_id'],
            $activity_type,
            $details,
            $affected_table,
            $affected_id,
            $ip,
            $user_agent
        ]);
    }
    
    public function getUserRole() {
        return $_SESSION['user_role'] ?? 'Bilinmeyen';
    }
    
    public function canAccessPage($page) {
        $page_permissions = [
            'books.php' => ['books', 'read'],
            'members.php' => ['members', 'read'],
            'loans.php' => ['loans', 'read'],
            'reports.php' => ['reports', 'read'],
            'staff.php' => ['staff', 'read'],
            'settings.php' => ['system', 'update']
        ];
        
        if (isset($page_permissions[$page])) {
            return $this->hasPermission($page_permissions[$page][0], $page_permissions[$page][1]);
        }
        
        return true; // Tanımlanmamış sayfalar için izin ver
    }
}

// Global permission manager
$permission = new PermissionManager($pdo);
?>