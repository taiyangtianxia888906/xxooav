<?php
session_start();
if (!isset($_SESSION['closed_ads'])) $_SESSION['closed_ads'] = [];
if (isset($_GET['id'])) {
    $adId = intval($_GET['id']);
    if (!in_array($adId, $_SESSION['closed_ads'])) $_SESSION['closed_ads'][] = $adId;
    echo 'ok';
}
