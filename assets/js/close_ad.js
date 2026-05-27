(function() {
    function addCloseButtons() {
        document.querySelectorAll('.ad-hot-item').forEach(function(adLink) {
            if (adLink.parentNode.querySelector('.close-ad')) return;
            var match = adLink.href.match(/[?&]id=(\d+)/);
            if (!match) return;
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
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var id = this.getAttribute('data-id');
                if (id) {
                    fetch('/close_ad.php?id=' + id).then(function() {
                        wrapper.style.display = 'none';
                    }).catch(function() {});
                }
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addCloseButtons);
    } else {
        addCloseButtons();
    }
})();
