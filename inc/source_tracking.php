<?php
$fromParam = $_GET["from"] ?? "";
$shareCode = $_GET["code"] ?? $_GET["ref"] ?? "";
if ($fromParam == "" && isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], "t.me") !== false) {
    $fromParam = "telegram";
}
$GLOBALS["_from_channel"] = $fromParam;
$GLOBALS["_share_code"] = $shareCode;
