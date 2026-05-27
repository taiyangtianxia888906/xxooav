<div id="shareModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#1e1e1e; border-radius:8px; padding:20px; width:90%; max-width:420px; text-align:center;">
        <h4 style="color:#f90; margin-bottom:10px;">📤 分享 <span id="shareContentTitle"></span></h4>
        <div style="margin-bottom:10px;">
            <label style="color:#ccc; font-size:13px;">🔑 推广邀请码（可选）</label>
            <div style="display:flex; gap:8px; margin-top:4px;">
                <input type="text" id="shareViewKeyInput" placeholder="输入邀请码" style="flex:1; padding:8px; background:#171717; border:1px solid #444; color:#fff; border-radius:4px;">
                <button type="button" id="verifyKeyBtn" style="padding:8px 14px; background:#f90; color:#000; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">🔍 验证</button>
            </div>
            <div id="keyVerifyResult" style="margin-top:6px; font-size:13px; color:#f90; display:none;"></div>
        </div>
        <p id="shareLink" style="color:#fff; word-break:break-all; margin-bottom:10px;"></p>
        <p style="color:#ccc; font-size:13px;" id="shareDescription">更多免费精彩内容，点击链接或扫码观看</p>
        <img id="shareQr" src="" crossorigin="anonymous" style="max-width:200px; display:block; margin:10px auto; background:#fff; padding:5px; border-radius:4px;">
        <div style="display:flex; gap:10px; justify-content:center; margin-top:10px; flex-wrap:wrap;">
            <button class="btn btn-sm btn-primary" onclick="copyShareContent()">📋 复制分享内容</button>
            <button class="btn btn-sm" style="background:#f90; color:#000;" onclick="shareUniversal()">🔗 一键转发</button>
            <button class="btn btn-sm" style="background:#555; color:#fff;" onclick="saveQrCode()">💾 保存二维码</button>
            <button class="btn btn-sm" style="background:#555; color:#fff;" onclick="closeShareModal()">关闭</button>
        </div>
    </div>
</div>
<script>
(function(){
    if (window.__shareModalFixed) return;
    window.__shareModalFixed = true;

    let verifiedKey = '';
    let verifiedPromoterId = '';

    const verifyBtn = document.getElementById('verifyKeyBtn');
    const keyInput = document.getElementById('shareViewKeyInput');
    const resultDiv = document.getElementById('keyVerifyResult');

    verifyBtn.addEventListener('click', function() {
        const key = keyInput.value.trim();
        if (!key) {
            resultDiv.style.display = 'block';
            resultDiv.textContent = '❌ 请输入邀请码';
            resultDiv.style.color = '#ff6b6b';
            return;
        }
        fetch('share.php?check_key=' + encodeURIComponent(key))
            .then(r => r.json())
            .then(data => {
                resultDiv.style.display = 'block';
                if (data.valid) {
                    verifiedKey = key;
                    verifiedPromoterId = data.promoter_id;
                    resultDiv.textContent = '✅ 已绑定推广员：' + data.promoter_name;
                    resultDiv.style.color = '#4caf50';
                    keyInput.disabled = true;
                    verifyBtn.disabled = true;
                    verifyBtn.textContent = '✓ 已验证';
                } else {
                    verifiedKey = '';
                    verifiedPromoterId = '';
                    resultDiv.textContent = '❌ 邀请码无效，请检查';
                    resultDiv.style.color = '#ff6b6b';
                }
            })
            .catch(() => {
                resultDiv.style.display = 'block';
                resultDiv.textContent = '⚠️ 验证失败，请稍后重试';
                resultDiv.style.color = '#ff6b6b';
                verifiedKey = '';
                verifiedPromoterId = '';
            });
    });

    window.closeShareModal = function() {
        document.getElementById('shareModal').style.display = 'none';
    };

    window.openShareModal = function(type, itemId, contentTitle) {
        if (window._sharingInProgress) return;
        window._sharingInProgress = true;
        setTimeout(function(){ window._sharingInProgress = false; }, 2000);
        // 注意：不要重置 verifiedKey，保留已验证的值
        // 如果还没有验证，则要求用户先验证
        if (!verifiedKey) {
            alert('请先输入邀请码并点击验证');
            window._sharingInProgress = false;
            return;
        }
        let title = contentTitle || document.title || '分享内容';
        document.getElementById('shareContentTitle').textContent = title ? ' - ' + title : '';

        let code = verifiedKey;
        let viewKey = verifiedKey;
        let url = `share.php?type=${encodeURIComponent(type)}&id=${encodeURIComponent(itemId)}&code=${encodeURIComponent(code)}&title=${encodeURIComponent(title)}&view_key=${encodeURIComponent(viewKey)}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.share_url) {
                    document.getElementById('shareLink').textContent = data.share_url;
                    document.getElementById('shareDescription').textContent = title + '，更多免费精彩内容，点击链接或扫码观看';
                    const qrImg = document.getElementById('shareQr');
                    qrImg.src = data.qr_url;
                    qrImg.setAttribute('data-qr', data.qr_url);
                    document.getElementById('shareModal').style.display = 'flex';
                } else {
                    alert("生成分享链接失败: " + (data.error || "未知错误"));
                }
            })
            .catch(err => { console.error(err); alert("请求失败，请稍后重试"); });
    };

    window.copyShareContent = function() {
        const titleElem = document.getElementById('shareContentTitle');
        const title = titleElem ? titleElem.textContent.replace(' - ', '') : '分享内容';
        const link = document.getElementById('shareLink').textContent || '';
        const content = `📤 分享 - ${title}\n\n👉 ${link}\n\n${title}，更多免费精彩内容，点击链接或扫码观看`;
        navigator.clipboard.writeText(content).then(() => alert("已复制，可粘贴到任意聊天窗口"));
    };

    window.shareUniversal = function() {
        const title = document.getElementById('shareContentTitle').textContent.replace(' - ', '') || '分享内容';
        const link = document.getElementById('shareLink').textContent || '';
        if (navigator.share) {
            navigator.share({ title, text: title + "，更多精彩内容点击链接观看", url: link }).catch(()=>{});
        } else {
            copyShareContent();
            alert("已复制分享内容到剪贴板，可粘贴到微信/QQ等发送。");
        }
    };

    window.saveQrCode = function() {
        const qrImg = document.getElementById('shareQr');
        const qrUrl = qrImg.getAttribute('data-qr') || qrImg.src;
        const title = document.getElementById('shareContentTitle').textContent.replace(' - ', '') || 'content';
        const a = document.createElement('a');
        a.href = qrUrl;
        a.download = 'share_' + title + '.png';
        a.click();
    };

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.share-btn');
        if (!btn) return;
        const type = btn.getAttribute('data-type');
        const id = btn.getAttribute('data-id');
        const titleElem = document.querySelector('h2') || document.querySelector('.video-title');
        const title = titleElem ? titleElem.innerText : document.title;
        if (type && id) window.openShareModal(type, id, title);
    });
})();
</script>
