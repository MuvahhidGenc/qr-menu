<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role_id']);
}

function isSuperAdmin() {
    return isLoggedIn() && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
}

function loginAdmin($username, $password) {
    try {
        $db = new Database();
        
        // Debug için
        error_log("Login attempt for username: " . $username);
        
        // Kullanıcıyı ve rolünü al
        $admin = $db->query("
            SELECT a.*, r.permissions, r.slug as role_slug
            FROM admins a 
            LEFT JOIN roles r ON a.role_id = r.id 
            WHERE a.username = ? 
            AND a.status = 1
        ", [$username])->fetch();

        // Debug için
        error_log("Query result: " . print_r($admin, true));

        if ($admin && password_verify($password, $admin['password'])) {
            // Session'ı temizle ve yeniden başlat
            session_regenerate_id(true);
            $_SESSION = array();

            // Temel bilgileri session'a kaydet
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role_id'] = $admin['role_id'];
            $_SESSION['role_slug'] = $admin['role_slug'];
            
            // Süper admin için tüm yetkileri ver
            if ($admin['role_id'] == 1 || $admin['role_slug'] == 'super-admin') {
                $_SESSION['permissions'] = ['*' => true];
            } else {
                $_SESSION['permissions'] = json_decode($admin['permissions'], true) ?? [];
            }

            // Debug için
            error_log("Login successful. Session data: " . print_r($_SESSION, true));
            
            return true;
        }
        
        // Debug için
        error_log("Login failed for username: " . $username);
        return false;

    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        return false;
    }
}

function hasPermission($permission) {
    // Süper admin her zaman tüm yetkilere sahiptir
    if (isSuperAdmin() || isset($_SESSION['permissions']['*'])) {
        return true;
    }

    if (!isLoggedIn() || !isset($_SESSION['permissions'])) {
        return false;
    }

    list($module, $perm) = explode('.', $permission);
    
    // Modül seviyesinde tam yetki kontrolü
    if (isset($_SESSION['permissions'][$module]) && $_SESSION['permissions'][$module] === true) {
        return true;
    }

    // Özel yetki kontrolü
    return isset($_SESSION['permissions'][$module][$perm]) && 
           $_SESSION['permissions'][$module][$perm] === true;
}

function logout() {
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
}