<?php
$stmt = $pdo->prepare("SELECT * FROM ads WHERE type = 'popup' ORDER BY sort");
$stmt->execute();
$triggerAds = $stmt->fetchAll();
if (empty($triggerAds)) return;
?>
<div id="triggerAdPopup" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:99999; align-items:center; justify-content:center;">
    <div style="position:relative; max-width:90vw; max-height:80vh;">
        <button id="triggerAdClose" style="position:absolute; top:5px; right:5px; z-index:1; background:rgba(0,0,0,0.5); color:#fff; border:none; font-size:24px; width:36px; height:36px; border-radius:50%; cursor:pointer;">✕</button>
        <a id="triggerAdLink" href="#" target="_blank">
            <img id="triggerAdImg" src="" style="max-width:100%; max-height:80vh; display:block; border-radius:8px;">
        </a>
        <div id="triggerAdTimer" style="position:absolute; bottom:10px; right:10px; background:rgba(0,0,0,0.6); color:#fff; padding:4px 10px; border-radius:4px; font-size:14px;"></div>
    </div>
</div>
<script>
(function() {
    var popupAds = <?= json_encode($triggerAds) ?>;
    var popupElement = document.getElementById('triggerAdPopup');
    var popupImg = document.getElementById('triggerAdImg');
    var popupLink = document.getElementById('triggerAdLink');
    var popupTimer = document.getElementById('triggerAdTimer');
    var popupClose = document.getElementById('triggerAdClose');
    var countdownInterval = null;
    var currentDuration = 15;
    var pendingCallback = null;   // 弹窗关闭后的回调函数

    function findAndShow(eventType, checkValue) {
        var ad = null;
        for (var i = 0; i < popupAds.length; i++) {
            var a = popupAds[i];
            if (a.trigger_event === eventType) {
                if (a.trigger_value) {
                    var triggerVal = parseFloat(a.trigger_value);
                    if (eventType === 'video_progress' && checkValue >= triggerVal) { ad = a; break; }
                } else { ad = a; break; }
            }
        }
        if (!ad) { for (var i = 0; i < popupAds.length; i++) { if (!popupAds[i].trigger_event) { ad = popupAds[i]; break; } } }
        if (!ad) return;
        popupImg.src = ad.image_url;
        popupLink.href = ad.link_url && ad.link_url !== '#' ? ad.link_url : 'javascript:void(0)';
        currentDuration = parseInt(ad.popup_duration) || 15;
        popupTimer.textContent = currentDuration + 's';
        popupElement.style.display = 'flex';
        clearInterval(countdownInterval);
        countdownInterval = setInterval(function() {
            currentDuration--;
            popupTimer.textContent = currentDuration + 's';
            if (currentDuration <= 0) { closePopup(); }
        }, 1000);
    }

    function closePopup() {
        popupElement.style.display = 'none';
        clearInterval(countdownInterval);
        if (pendingCallback) {
            var cb = pendingCallback;
            pendingCallback = null;
            cb();
        }
    }

    popupClose.addEventListener('click', closePopup);
    popupElement.addEventListener('click', function(e) { if (e.target === popupElement) closePopup(); });

    // 点击分类、标签、搜索按钮时，阻止默认跳转，弹窗关闭后再执行原动作
    document.addEventListener('click', function(e) {
        var catLink = e.target.closest('a.menu-item[href*="category.php?cat="]');
        if (catLink) {
            e.preventDefault();
            var targetUrl = catLink.href;
            findAndShow('category_click');
            pendingCallback = function() { window.location.href = targetUrl; };
            return;
        }
        var tagLink = e.target.closest('a.menu-item-sub');
        if (tagLink) {
            e.preventDefault();
            var targetUrl = tagLink.href;
            findAndShow('tag_click');
            pendingCallback = function() { window.location.href = targetUrl; };
            return;
        }
        var searchBtn = e.target.closest('.search-btn');
        if (searchBtn) {
            e.preventDefault();
            findAndShow('search_click');
            // 搜索按钮提交表单
            var form = searchBtn.closest('form');
            if (form) {
                pendingCallback = function() { form.submit(); };
            }
            return;
        }
    });

    if (document.querySelector('video')) {
        var video = document.querySelector('video');
        video.addEventListener('timeupdate', function() { findAndShow('video_progress', video.currentTime); });
        video.addEventListener('seeked', function() { findAndShow('video_seek'); });
    }
})();
</script>
