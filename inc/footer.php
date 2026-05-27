<?php
// 只留这一行正确的（根目录配置文件，存在）
require __DIR__ . "/../config.php";
?>

</div> <!-- 关闭 max-w-6xl pb-20 -->
<footer class="bg-gray-900 border-t border-gray-800 py-6 mt-10">
    <div class="max-w-6xl mx-auto px-3 text-center text-gray-300 text-xs">
        <p>© 2026 xxoo 温馨提示：本网站内容仅适合18岁及以上用户浏览</p>
    </div>
</footer>
<button class="back-to-top" style="display:none;" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<?php include __DIR__ . "/share_modal.php"; ?>
<script src="assets/share.js"></script>
<?php include __DIR__ . "/trigger_ads.php"; ?>
<script src="/assets/js/close_ad.js"></script>
</body>
</html>
<!-- 全站底部版权提醒 -->
<div style="text-align:center; color:#888; font-size:0.8rem; padding:1rem 0; border-top:1px solid #333; margin-top:1rem;">
    © 2026 xxoo  温馨提示：本网站内容仅适合18岁及以上用户浏览，请勿向未成年人传播或展示相关内容。
</div>
<!-- 手动关闭广告功能 -->

<!-- 手动关闭广告功能 -->
<script>
(function() {
    function addCloseButtons() {
        var items = document.querySelectorAll('.ad-hot-item');
        for (var i = 0; i < items.length; i++) {
            var adLink = items[i];
            if (adLink.parentNode.querySelector('.close-ad')) continue;
            var match = adLink.href.match(/[?&]id=(\d+)/);
            if (!match) continue;
            var adId = match[1];
            var btn = document.createElement('button');
            btn.className = 'close-ad';
            btn.innerHTML = '✕';
            btn.setAttribute('data-id', adId);
            btn.style.position = 'absolute';
            btn.style.top = '-8px';
            btn.style.right = '-8px';
            btn.style.background = '#ff4444';
            btn.style.color = '#fff';
            btn.style.border = 'none';
            btn.style.borderRadius = '50%';
            btn.style.width = '22px';
            btn.style.height = '22px';
            btn.style.fontSize = '14px';
            btn.style.cursor = 'pointer';
            btn.style.zIndex = '10';
            var wrapper = adLink.parentNode;
            if (getComputedStyle(wrapper).position === 'static') wrapper.style.position = 'relative';
            wrapper.appendChild(btn);
            btn.onclick = (function(id, wrap) {
                return function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', '/close_ad.php?id=' + id, true);
                    xhr.send();
                    wrap.style.display = 'none';
                };
            })(adId, wrapper);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addCloseButtons);
    } else {
        addCloseButtons();
    }
})();
</script>
<script>
(function() {
    // 等待 DOM 加载完成
    function init() {
        var items = document.querySelectorAll('.ad-hot-item');
        for (var i = 0; i < items.length; i++) {
            var adLink = items[i];
            // 如果已经添加过关闭按钮，跳过
            if (adLink.parentNode.querySelector('.close-ad')) continue;
            // 获取广告ID（从 href 中提取）
            var match = adLink.href.match(/[?&]id=(\d+)/);
            if (!match) continue;
            var adId = match[1];
            // 创建关闭按钮
            var btn = document.createElement('button');
            btn.className = 'close-ad';
            btn.innerHTML = '✕';
            btn.setAttribute('data-id', adId);
            btn.style.position = 'absolute';
            btn.style.top = '-8px';
            btn.style.right = '-8px';
            btn.style.background = '#ff4444';
            btn.style.color = '#fff';
            btn.style.border = 'none';
            btn.style.borderRadius = '50%';
            btn.style.width = '22px';
            btn.style.height = '22px';
            btn.style.fontSize = '14px';
            btn.style.cursor = 'pointer';
            btn.style.zIndex = '9999';
            // 确保父容器相对定位
            var wrapper = adLink.parentNode;
            if (getComputedStyle(wrapper).position === 'static') {
                wrapper.style.position = 'relative';
            }
            wrapper.appendChild(btn);
            // 绑定点击事件
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var id = this.getAttribute('data-id');
                if (id) {
                    // 发送关闭请求
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', '/close_ad.php?id=' + id, true);
                    xhr.send();
                    // 隐藏广告容器
                    this.parentNode.style.display = 'none';
                }
            });
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
