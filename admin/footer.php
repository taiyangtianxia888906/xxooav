</div>
<script>
// 公共 JS 函数
function copyText(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => alert('已复制'));
    } else {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        try { document.execCommand('copy'); alert('已复制'); } catch(e) { alert('复制失败'); }
        document.body.removeChild(textarea);
    }
}
</script>
</body>
</html>
