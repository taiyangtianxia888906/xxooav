<?php
if (!isset($page) || !isset($pages) || $pages <= 1) return;
$query = $_GET;
unset($query['page']);
$base = basename($_SERVER['PHP_SELF']) . '?' . http_build_query($query);
?>
<div style="margin-top:15px; display:flex; justify-content:center; gap:6px;">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="<?= $base ?>&page=<?= $i ?>" style="padding:5px 10px; background:<?= $i==$page?'#007ecc':'#e0e0e0' ?>; color:<?= $i==$page?'#fff':'#333' ?>; border-radius:4px; text-decoration:none;"><?= $i ?></a>
  <?php endfor; ?>
</div>
