<?php
require_once __DIR__ . '/inc/functions.php';

define('BOT_TOKEN', '8510594916:AAGQWyZ8gCVgYABzm4JACrV6ebWPr7mA0uo');

$input = file_get_contents('php://input');
$update = json_decode($input, true);
if (!$update) exit;

$message = $update['message'] ?? null;
if (!$message) exit;

$chatId = $message['chat']['id'];
$text   = trim($message['text'] ?? '');

// 如果用户从落地页链接进来（/start geturl），或直接发送任意消息，都返回最新短链接
if (stripos($text, '/start') === 0 || !empty($text)) {
    $reply = "👉 最新地址：" . getRandomShortUrl() . "\n\n此地址随机变化，请保存本机器人以便随时获取最新入口。";
} else {
    $reply = "欢迎！发送任意消息即可获取最新地址。";
}

sendToTelegram($chatId, $reply);

function getRandomShortUrl(): string {
    $domains = ['fakakuai.com', 'fakakuai.shop'];
    $domain  = $domains[array_rand($domains)];
    $prefix  = substr(md5(uniqid(mt_rand(), true)), 0, rand(5, 8));
    return "https://{$prefix}.{$domain}?from=tg";
}

function sendToTelegram(int $chatId, string $text): void {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage?" . http_build_query([
        'chat_id' => $chatId,
        'text'    => $text,
    ]);
    @file_get_contents($url);
}
