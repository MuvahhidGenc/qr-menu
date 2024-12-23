<?php
function secureUpload($file) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    $newName = uniqid() . '.' . $ext;
    $path = '../uploads/' . $newName;
    
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $newName;
    }
    return false;
}