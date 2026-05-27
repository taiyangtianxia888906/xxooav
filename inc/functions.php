<?php
// 公共函数库 - 版本：2024-05-26 全链路修复版

/**
 * 解析UserAgent，返回设备类型和详细描述
 */
function parseUA($ua) {
    $device = "未知"; $os = ""; $browser = "Unknown";
    if (preg_match("/Mobile|Android|iPhone|iPad|iPod/i", $ua)) {
        $device = "手机";
        if (preg_match("/iPhone|iPad|iPod/i", $ua)) { $device = "苹果设备"; }
        elseif (preg_match("/Android/i", $ua)) { $device = "安卓手机"; }
    } elseif (preg_match("/Tablet|iPad/i", $ua)) {
        $device = "平板";
    } elseif (preg_match("/Windows|Macintosh|Linux|CrOS/i", $ua)) {
        $device = "电脑";
    }
    if (preg_match("/Windows NT 10.0/i", $ua)) $os = "Windows 10";
    elseif (preg_match("/Windows NT 6.3/i", $ua)) $os = "Windows 8.1";
    elseif (preg_match("/Mac OS X/i", $ua)) $os = "macOS";
    elseif (preg_match("/Linux/i", $ua)) $os = "Linux";
    if (preg_match("/Edg\/([0-9.]+)/i", $ua, $m)) $browser = "Edge " . $m[1];
    elseif (preg_match("/Chrome\/([0-9.]+)/i", $ua, $m)) $browser = "Chrome " . $m[1];
    elseif (preg_match("/Firefox\/([0-9.]+)/i", $ua, $m)) $browser = "Firefox " . $m[1];
    elseif (preg_match("/Safari\/([0-9.]+)/i", $ua, $m)) $browser = "Safari " . $m[1];
    return [
        "device_type" => ($device == "电脑" ? "desktop" : ($device == "手机" || $device == "安卓手机" || $device == "苹果设备" ? "mobile" : "tablet")),
        "device_detail" => $device . ($os ? " " . $os : "") . " " . $browser,
        "os" => $os,
        "browser" => $browser
    ];
}

/**
 * 记录访问日志
 */
function logVisit() {
    global $pdo;
    if (!$pdo) return false;
    $ip = $_SERVER["REMOTE_ADDR"] ?? "";
    $ua = $_SERVER["HTTP_USER_AGENT"] ?? "";
    $referer = $_SERVER["HTTP_REFERER"] ?? "";
    $page = $_SERVER["REQUEST_URI"] ?? "";
    $visitorId = md5($ip . "_" . $ua);
    $uaInfo = parseUA($ua);
    $deviceType = $uaInfo["device_type"];
    $country = $region = $city = "";
    try {
        $ipData = @file_get_contents("http://ip-api.com/json/{$ip}?lang=zh-CN&fields=country,regionName,city");
        if ($ipData) {
            $geo = json_decode($ipData, true);
            $country = $geo["country"] ?? "";
            $region = $geo["regionName"] ?? "";
            $city = $geo["city"] ?? "";
        }
    } catch (Exception $e) {}
    $stmt = $pdo->prepare("INSERT INTO visit_logs (visitor_id, ip, page, entry_page, user_agent, referer, device_type, country, region, city, visited_at, stay_seconds, referer_code, share_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0, ?, ?)");
    $stmt->execute([$visitorId, $ip, $page, $page, $ua, $referer, $deviceType, $country, $region, $city, $GLOBALS["_from_channel"] ?? "", $GLOBALS["_share_code"] ?? ""]);
    return $pdo->lastInsertId();
}

/**
 * 记录视频观看
 */
function logVideoView($videoId) {
    global $pdo;
    $ip = $_SERVER["REMOTE_ADDR"] ?? "";
    $ua = $_SERVER["HTTP_USER_AGENT"] ?? "";
    $uaInfo = function_exists("parseUA") ? parseUA($ua) : [];
    $deviceType = $uaInfo["device_type"] ?? "unknown";
    $videoTitle = "";
    try {
        $stmt = $pdo->prepare("SELECT title FROM videos WHERE id = ?");
        $stmt->execute([$videoId]);
        $videoTitle = $stmt->fetchColumn() ?: "";
    } catch (Exception $e) {}
    $country = $region = $city = "";
    try {
        $ipData = @file_get_contents("http://ip-api.com/json/{$ip}?lang=zh-CN&fields=country,regionName,city");
        if ($ipData) {
            $geo = json_decode($ipData, true);
            $country = $geo["country"] ?? "";
            $region = $geo["regionName"] ?? "";
            $city = $geo["city"] ?? "";
        }
    } catch (Exception $e) {}
    try {
        $stmt = $pdo->prepare("INSERT INTO video_views (video_id, video_title, ip, user_agent, device_type, country, region, city, viewed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$videoId, $videoTitle, $ip, $ua, $deviceType, $country, $region, $city]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 记录广告点击
 */
function logAdClick($adId, $adTitle = '', $adType = '', $position = '', $linkUrl = '', $shareCode = '') {
    global $pdo;
    $ip = $_SERVER["REMOTE_ADDR"] ?? "";
    $ua = $_SERVER["HTTP_USER_AGENT"] ?? "";
    $referer = $_SERVER["HTTP_REFERER"] ?? "";
    $uaInfo = function_exists("parseUA") ? parseUA($ua) : [];
    $deviceType = $uaInfo["device_type"] ?? "unknown";
    $country = $region = $city = "";
    try {
        $ipData = @file_get_contents("http://ip-api.com/json/{$ip}?lang=zh-CN&fields=country,regionName,city");
        if ($ipData) {
            $geo = json_decode($ipData, true);
            $country = $geo["country"] ?? "";
            $region = $geo["regionName"] ?? "";
            $city = $geo["city"] ?? "";
        }
    } catch (Exception $e) {}
    try {
        $stmt = $pdo->prepare("INSERT INTO ad_clicks (ad_id, ad_title, ad_type, position, link_url, ip, country, region, city, user_agent, device_type, referer, share_code, clicked_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$adId, $adTitle, $adType, $position, $linkUrl, $ip, $country, $region, $city, $ua, $deviceType, $referer, $shareCode]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 获取客户端真实IP
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * 获取IP地理位置
 */
function getIpLocation($ip) {
    $geo = ['country' => null, 'regionName' => null, 'city' => null];
    try {
        $data = @file_get_contents("http://ip-api.com/json/{$ip}?lang=zh-CN&fields=country,regionName,city");
        if ($data) {
            $result = json_decode($data, true);
            $geo['country'] = $result['country'] ?? null;
            $geo['regionName'] = $result['regionName'] ?? null;
            $geo['city'] = $result['city'] ?? null;
        }
    } catch (Exception $e) {}
    return $geo;
}

/**
 * TOTP 相关函数（谷歌验证器）
 */
function base32_decode($str) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $str = strtoupper(str_replace(' ', '', $str));
    $buffer = 0;
    $bits = 0;
    $output = '';
    for ($i = 0; $i < strlen($str); $i++) {
        $buffer = ($buffer << 5) | strpos($alphabet, $str[$i]);
        $bits += 5;
        if ($bits >= 8) {
            $bits -= 8;
            $output .= chr(($buffer >> $bits) & 0xFF);
        }
    }
    return $output;
}

function generateTOTP($secret, $time = null) {
    $time = $time ?? floor(time() / 30);
    $key = base32_decode($secret);
    $time = pack('J', $time);
    $hash = hash_hmac('SHA1', $time, $key, true);
    $offset = ord($hash[19]) & 0x0F;
    $code = ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);
    return str_pad($code % 1000000, 6, '0', STR_PAD_LEFT);
}

function verifyTOTP($secret, $code) {
    $code = trim($code);
    if (!preg_match('/^\d{6}$/', $code)) return false;
    $time = floor(time() / 30);
    for ($i = -1; $i <= 1; $i++) {
        if (hash_equals(generateTOTP($secret, $time + $i), $code)) {
            return true;
        }
    }
    return false;
}

if (!function_exists('generateCover')) {
    function generateCover($videoPath) {
        $ffmpeg = trim(shell_exec('which ffmpeg') ?? '');
        if (!$ffmpeg) return null;
        $duration = floatval(trim(shell_exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($videoPath) . " 2>&1") ?? '0'));
        if ($duration <= 0) return null;
        $minSeek = min(240, (int)($duration / 2));
        $maxSeek = min(360, (int)($duration - 1));
        $seek = ($minSeek >= $maxSeek) ? (int)($duration / 2) : rand($minSeek, $maxSeek);
        $coverDir = dirname(__DIR__) . '/uploads/covers';
        if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
        $coverName = 'uploads/covers/' . uniqid('cover_', true) . '.jpg';
        $dest = dirname(__DIR__) . '/' . $coverName;
        exec("ffmpeg -ss {$seek} -i " . escapeshellarg($videoPath) . " -vframes 1 -q:v 2 -y " . escapeshellarg($dest) . " 2>&1", $out, $ret);
        return ($ret === 0 && file_exists($dest)) ? $coverName : null;
    }
}

if (!function_exists('generateSmartCover')) {
    function generateSmartCover($videoPath) {
        $cover = generateCover($videoPath);
        if ($cover) return $cover;
        $ffmpeg = trim(shell_exec('which ffmpeg') ?? '');
        if (!$ffmpeg) return null;
        $coverDir = dirname(__DIR__) . '/uploads/covers';
        if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
        $coverName = 'uploads/covers/' . uniqid('smart_', true) . '.jpg';
        $dest = dirname(__DIR__) . '/' . $coverName;
        exec("$ffmpeg -ss 5 -i " . escapeshellarg($videoPath) . " -vframes 1 -q:v 2 -y " . escapeshellarg($dest) . " 2>&1", $out, $ret);
        return ($ret === 0 && file_exists($dest)) ? $coverName : null;
    }
}

if (!function_exists('trimVideo')) {
    function trimVideo($videoPath, $keepSeconds) {
        $ffmpeg = trim(shell_exec('which ffmpeg') ?? '');
        if (!$ffmpeg) return;
        $tmpPath = $videoPath . '.trim.mp4';
        $cmd = "$ffmpeg -i " . escapeshellarg($videoPath) . " -t {$keepSeconds} -c copy -y " . escapeshellarg($tmpPath) . " 2>&1";
        exec($cmd, $out, $ret);
        if ($ret === 0 && file_exists($tmpPath)) {
            unlink($videoPath);
            rename($tmpPath, $videoPath);
        }
    }
}
