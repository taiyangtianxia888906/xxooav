<?php
/**
 * Telegram 推送模块
 * 包含视频和图集推送函数
 */

// 读取配置
$settings = $pdo->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$token   = $settings["telegram_bot_token"] ?? "";
$channel = $settings["telegram_channel_id"] ?? "";
$group   = $settings["telegram_group_id"] ?? "";

/**
 * 推送视频到频道和群组
 */
function sendTelegramVideoNotify($video) {
    global $token, $channel, $group;
    // 防止5秒内重复推送同一视频
    static $lastPush = [];
    $vid = $video["id"] ?? 0;
    if (isset($lastPush[$vid]) && time() - $lastPush[$vid] < 5) return;
    $lastPush[$vid] = time();
    if (empty($token) || (empty($channel) && empty($group))) return;

    $api = "https://api.telegram.org/bot{$token}/";
    $title = $video['title'] ?? '无标题';
    $cover = $video['cover'] ?? '';
    if ($cover && strpos($cover, 'http') !== 0) {
        $cover = $domains = ['fakakuai.com', 'fakakuai.shop']; $domain = $domains[array_rand($domains)]; 'https://'.$domain . '/' . ltrim($cover, '/');
    }
    $url = $domains = ['fakakuai.com', 'fakakuai.shop']; $domain = $domains[array_rand($domains)]; 'https://'.$domain . '/video.php?id=' . $video['id'];

    // 消息模板，用占位符 {_LINK_} 标记链接位置
    $siteUrl = $domains = ['fakakuai.com', 'fakakuai.shop']; $domain = $domains[array_rand($domains)]; 'https://'.$domain . '/';
    $text = "🎬 *新视频发布*\n\n"
          . "*标题：* {$title}\n"
          . "*分类：* " . ($video['cat_name'] ?? '无') . "\n"
          . "*标签：* " . ($video['tags'] ?: '无') . "\n\n"
          . "👉 [立即观看]({_LINK_})\n\n"
          . "更多免费精彩内容，点击链接进入 [xxoo免费成人网站]({$siteUrl})";

    $targets = array_filter([$channel, $group]);
    foreach ($targets as $chatId) {
        // 根据目标生成带 from 参数的完整链接
        $fromParam = ($chatId == $channel) ? "from=telegram_channel" : (($chatId == $group) ? "from=telegram_group" : "");
        $finalUrl = $url;
        if ($fromParam) {
            $finalUrl .= (strpos($url, '?') === false ? '?' : '&') . $fromParam;
        }
        $finalText = str_replace('{_LINK_}', $finalUrl, $text);

        if ($cover && filter_var($cover, FILTER_VALIDATE_URL)) {
            @file_get_contents($api . "sendPhoto?" . http_build_query([
                'chat_id'    => $chatId,
                'photo'      => $cover,
                'caption'    => $finalText,
                'parse_mode' => 'Markdown'
            ]));
        } else {
            @file_get_contents($api . "sendMessage?" . http_build_query([
                'chat_id'    => $chatId,
                'text'       => $finalText,
                'parse_mode' => 'Markdown'
            ]));
        }
    }
}

/**
 * 推送图集到频道和群组
 */
function sendTelegramImageNotify($image) {
    global $token, $channel, $group;
    if (empty($token) || (empty($channel) && empty($group))) return;

    $api = "https://api.telegram.org/bot{$token}/";
    $title = $image['title'] ?? '无标题';
    $imgUrl = $image['image_url'];
    if (strpos($imgUrl, 'http') !== 0) {
        $imgUrl = $domains = ['fakakuai.com', 'fakakuai.shop']; $domain = $domains[array_rand($domains)]; 'https://'.$domain . '/' . ltrim($imgUrl, '/');
    }

    $siteUrl = $domains = ['fakakuai.com', 'fakakuai.shop']; $domain = $domains[array_rand($domains)]; 'https://'.$domain . '/';
    $text = "🖼️ *新图集发布*\n\n"
          . "*标题：* {$title}\n"
          . "*分类：* " . ($image['cat_name'] ?? '无') . "\n"
          . "*标签：* " . ($image['tags'] ?: '无') . "\n\n"
          . "👉 [立即查看]({$imgUrl})\n\n"
          . "更多免费精彩内容，点击链接进入 [xxoo免费成人网站]({$siteUrl})";

    $targets = array_filter([$channel, $group]);
    foreach ($targets as $chatId) {
        @file_get_contents($api . "sendPhoto?" . http_build_query([
            'chat_id'    => $chatId,
            'photo'      => $imgUrl,
            'caption'    => $text,
            'parse_mode' => 'Markdown'
        ]));
    }
}
