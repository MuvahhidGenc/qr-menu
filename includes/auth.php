<?php
// Session başlatma kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin girişi kontrolü
function isLoggedIn() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role_slug'])) {
        return false;
    }
    
    return true;
}
// Admin yetkisi kontrolü
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    return isset($_SESSION['role_slug']) && 
           in_array($_SESSION['role_slug'], ['super-admin', 'admin']);
}

// Süper Admin yetkisi kontrolü - tüm sayfalara erişim için
function isSuperAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    return isset($_SESSION['role_slug']) && $_SESSION['role_slug'] === 'super-admin';
}

// Rol kontrolü - mevcut yapıyı koruyarak güncellendi
function checkRole($allowedRoles = []) {
    // Süper admin her sayfaya erişebilir
    if (isSuperAdmin()) {
        return true;
    }
    
    // Diğer roller için kontrol
    if (!isset($_SESSION['role_slug']) || 
        (!empty($allowedRoles) && !in_array($_SESSION['role_slug'], $allowedRoles))) {
        header('Location: index.php');
        exit;
    }
    
    return true;
}

function checkPermission($requiredRole = null) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }

    // Süper admin her şeye erişebilir
    if (isSuperAdmin()) {
        return true;
    }

    // Belirli bir rol gerekiyorsa kontrol et
    if ($requiredRole && $_SESSION['role_slug'] !== $requiredRole) {
        header('Location: index.php');
        exit();
    }

    return true;
}

// Mevcut şifreleme fonksiyonları
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Kullanıcının belirli bir yetkiye sahip olup olmadığını kontrol eder
 * @param string $permission "module.permission" formatında (örn: "users.view")
 * @return bool
 */
function hasPermission($permission) {
    // Süper admin her zaman tüm yetkilere sahiptir
    if (isSuperAdmin()) {
        return true;
    }

    // Oturum kontrolü
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role_id'])) {
        return false;
    }

    try {
        $db = new Database();
        
        // Kullanıcının rolünü al
        $role = $db->query("SELECT permissions FROM roles WHERE id = ?", 
            [$_SESSION['user']['role_id']])->fetch();

        if (!$role) {
            return false;
        }

        $permissions = json_decode($role['permissions'], true);
        
        if (!$permissions) {
            return false;
        }

        // "module.permission" formatını parçala
        list($module, $perm) = explode('.', $permission);

        // Modül seviyesinde tam yetki kontrolü
        if (isset($permissions[$module]) && $permissions[$module] === true) {
            return true;
        }

        // Özel yetki kontrolü
        return isset($permissions[$module][$perm]) && $permissions[$module][$perm] === true;

    } catch (Exception $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Kullanıcının bir modüle tam erişimi olup olmadığını kontrol eder
 * @param string $module Modül adı
 * @return bool
 */
function hasModuleAccess($module) {
    // Süper admin her zaman tüm modüllere erişebilir
    if (isSuperAdmin()) {
        return true;
    }

    // Oturum kontrolü
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role_id'])) {
        return false;
    }

    try {
        $db = new Database();
        
        // Kullanıcının rolünü al
        $role = $db->query("SELECT permissions FROM roles WHERE id = ?", 
            [$_SESSION['user']['role_id']])->fetch();

        if (!$role) {
            return false;
        }

        $permissions = json_decode($role['permissions'], true);
        
        if (!$permissions) {
            return false;
        }

        // Modül seviyesinde yetki kontrolü
        return isset($permissions[$module]) && 
               ($permissions[$module] === true || !empty($permissions[$module]));

    } catch (Exception $e) {
        error_log("Module access check error: " . $e->getMessage());
        return false;
    }
}