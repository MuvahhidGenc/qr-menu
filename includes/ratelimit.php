<?php
function checkRateLimit($ip, $limit = 100, $minutes = 15) {
    $file = sys_get_temp_dir() . '/rate_' . md5($ip);
    $current = file_exists($file) ? file_get_contents($file) : 0;
    
    if($current > $limit) {
        header("HTTP/1.0 429 Too Many Requests");
        die("Rate limit exceeded");
    }
    
    file_put_contents($file, $current + 1);
}