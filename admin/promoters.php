<?php
// 纯数据接口（不加载 header.php，但需要数据库连接）
require __DIR__ . "/../config.php";

// 导出 CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $stmt = $pdo->query("SELECT p.id, p.username, p.view_key, p.parent_id, pp.username as parent_name, p.created_at 
                         FROM promoters p LEFT JOIN promoters pp ON p.parent_id = pp.id ORDER BY p.id DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=promoters_list.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', '用户名', '邀请码', '上级', '创建时间']);
    foreach ($rows as $row) {
        fputcsv($output, [$row['id'], $row['username'], $row['view_key'], $row['parent_name'], $row['created_at']]);
    }
    fclose($output);
    exit;
}

// 获取详情 JSON
if (isset($_GET['detail_id'])) {
    $detail_id = intval($_GET['detail_id']);
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            (SELECT COUNT(*) FROM shares WHERE view_key = p.view_key) as shares,
            (SELECT COUNT(*) FROM ad_clicks WHERE share_code = p.view_key) as ad_clicks,
            (SELECT COUNT(*) FROM visit_logs WHERE share_code = p.view_key) as visits,
            (SELECT COUNT(*) FROM video_views WHERE video_id IN (SELECT item_id FROM shares WHERE view_key = p.view_key AND type='video')) as video_views,
            (SELECT COUNT(*) FROM promoters WHERE parent_id = p.id) as children_count
        FROM promoters p WHERE p.id = ?
    ");
    $stmt->execute([$detail_id]);
    $detail = $stmt->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($detail ?: ['error' => '推广员不存在']);
    exit;
}

// 获取下线树（递归）
if (isset($_GET['get_children_tree'])) {
    $id = intval($_GET['get_children_tree']);
    function getChildren($pdo, $parent_id) {
        $stmt = $pdo->prepare("SELECT id, username, view_key, (SELECT COUNT(*) FROM ad_clicks WHERE share_code = view_key) as ad_clicks FROM promoters WHERE parent_id = ?");
        $stmt->execute([$parent_id]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($children as &$child) {
            $child["children"] = getChildren($pdo, $child["id"]);
        }
        return $children;
    }
    $tree = getChildren($pdo, $id);
    header('Content-Type: application/json');
    echo json_encode($tree);
    exit;
}

// 之后才是正常的 HTML 输出（包含 header.php）
require 'header.php';

// 处理 POST 请求（添加、编辑、删除）
$message = '';
$message_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $view_key = trim($_POST['view_key'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $error = '';
        if (empty($username) || empty($password)) {
            $error = "用户名和密码不能为空";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM promoters WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) $error = "用户名已存在";
            elseif (!empty($view_key)) {
                $stmt = $pdo->prepare("SELECT id FROM promoters WHERE view_key = ?");
                $stmt->execute([$view_key]);
                if ($stmt->fetch()) $error = "邀请码已被使用";
            }
            if ($parent_id) {
                $stmt = $pdo->prepare("SELECT id FROM promoters WHERE id = ?");
                $stmt->execute([$parent_id]);
                if (!$stmt->fetch()) $error = "上级推广员不存在";
            }
        }
        if (empty($error)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if (empty($view_key)) $view_key = $username . '_' . substr(md5(uniqid()), 0, 4);
            $stmt = $pdo->prepare("INSERT INTO promoters (username, password, view_key, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$username, $hash, $view_key, $parent_id]);
            $new_id = $pdo->lastInsertId();
            if ($parent_id) {
                $stmt = $pdo->prepare("SELECT path FROM promoters WHERE id = ?");
                $stmt->execute([$parent_id]);
                $parent_path = $stmt->fetchColumn();
                $path = $parent_path . ',' . $new_id;
            } else {
                $path = $new_id;
            }
            $pdo->prepare("UPDATE promoters SET path = ? WHERE id = ?")->execute([$path, $new_id]);
            $message = "✅ 推广员已添加！邀请码：<code>$view_key</code><br>🔗 登录后台：<a href='/promoter/login.php' target='_blank'>/promoter/login.php</a>";
        } else {
            $message = "❌ $error";
            $message_type = 'danger';
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $view_key = trim($_POST['view_key'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $error = '';
        if ($id <= 0 || empty($username)) {
            $error = "参数错误";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM promoters WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) $error = "用户名已被其他推广员使用";
            elseif (!empty($view_key)) {
                $stmt = $pdo->prepare("SELECT id FROM promoters WHERE view_key = ? AND id != ?");
                $stmt->execute([$view_key, $id]);
                if ($stmt->fetch()) $error = "邀请码已被其他推广员使用";
            }
            if ($parent_id) {
                if ($parent_id == $id) $error = "不能选择自己作为上级";
                $stmt = $pdo->prepare("SELECT id FROM promoters WHERE id = ?");
                $stmt->execute([$parent_id]);
                if (!$stmt->fetch()) $error = "上级推广员不存在";
            }
        }
        if (empty($error)) {
            $sql = "UPDATE promoters SET username = ?, view_key = ?, parent_id = ?";
            $params = [$username, $view_key, $parent_id];
            if (!empty($password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= " WHERE id = ?";
            $params[] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ($parent_id) {
                $stmt = $pdo->prepare("SELECT path FROM promoters WHERE id = ?");
                $stmt->execute([$parent_id]);
                $parent_path = $stmt->fetchColumn();
                $path = $parent_path . ',' . $id;
            } else {
                $path = $id;
            }
            $pdo->prepare("UPDATE promoters SET path = ? WHERE id = ?")->execute([$path, $id]);
            $message = "✅ 推广员信息已更新";
        } else {
            $message = "❌ $error";
            $message_type = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("DELETE FROM promoters WHERE id = ?")->execute([$id]);
            $message = "已删除";
        }
    }
}

$all_promoters = $pdo->query("SELECT id, username FROM promoters ORDER BY id")->fetchAll();
$promoters = $pdo->query("SELECT p.*, pp.username as parent_name 
                          FROM promoters p 
                          LEFT JOIN promoters pp ON p.parent_id = pp.id 
                          ORDER BY p.id DESC")->fetchAll();
?>
<style>
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: #333; color: #333; margin: 5% auto; padding: 20px; border-radius: 8px; width: 550px; max-width: 90%; color: #fff; }
    .close, .close-edit, .close-stats { float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa; }
    .close:hover, .close-edit:hover, .close-stats:hover { color: #f90; }
    .stats-table td { padding: 6px 12px; }
</style>
<h2 class="page-title">👥 推广员管理（无限级下线）</h2>
<?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
<?php endif; ?>
<div class="card">
    <a href="?export=csv" class="btn btn-primary" style="margin-bottom:15px; background-color:#28a745;">📥 导出 CSV</a>
    <button id="addPromoterBtn" class="btn btn-primary" style="margin-bottom:15px;">➕ 添加推广员</button>
    <table class="table">
        <thead><tr><th>ID</th><th>用户名</th><th>邀请码</th><th>上级</th><th>创建时间</th><th>操作</th></tr></thead>
        <tbody>
            <?php foreach ($promoters as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['username']) ?></td>
                <td><code><?= htmlspecialchars($p['view_key'] ?? '') ?></code></td>
                <td><?= htmlspecialchars($p['parent_name'] ?? '无') ?></td>
                <td><?= $p['created_at'] ?></td>
                <td>
                    <button class="btn btn-sm btn-primary view-stats" data-id="<?= $p['id'] ?>">📊 数据详情</button>
                    <button class="btn btn-sm btn-warning edit-promoter" data-id="<?= $p['id'] ?>" data-username="<?= htmlspecialchars($p['username']) ?>" data-viewkey="<?= htmlspecialchars($p['view_key']) ?>" data-parent="<?= $p['parent_id'] ?>">✏️ 编辑</button>
                    <form method="post" style="display:inline;"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</button></form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 添加模态框 -->
<div id="addModal" class="modal"><div class="modal-content"><span class="close">&times;</span><h3>➕ 添加推广员</h3><form method="post"><input type="hidden" name="action" value="add"><div><label>账号</label><input type="text" name="username" class="form-inp" required></div><div><label>密码</label><input type="password" name="password" class="form-inp" required></div><div><label>邀请码（留空自动生成）</label><input type="text" name="view_key" class="form-inp" placeholder="可选"></div><div><label>上级推广员</label><select name="parent_id" class="form-inp"><option value="">无（顶级推广员）</option><?php foreach ($all_promoters as $opt): ?><option value="<?= $opt['id'] ?>"><?= htmlspecialchars($opt['username']) ?> (ID:<?= $opt['id'] ?>)</option><?php endforeach; ?></select></div><button type="submit" class="btn btn-primary">确认添加</button></form></div></div>

<!-- 编辑模态框 -->
<div id="editModal" class="modal"><div class="modal-content"><span class="close-edit">&times;</span><h3>✏️ 编辑推广员</h3><form method="post" id="editForm"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id"><div><label>账号</label><input type="text" name="username" id="edit_username" class="form-inp" required></div><div><label>新密码（留空则不修改）</label><input type="password" name="password" class="form-inp" placeholder="留空表示不修改密码"></div><div><label>邀请码</label><input type="text" name="view_key" id="edit_viewkey" class="form-inp" required></div><div><label>上级推广员</label><select name="parent_id" id="edit_parent" class="form-inp"><option value="">无（顶级推广员）</option><?php foreach ($all_promoters as $opt): ?><option value="<?= $opt['id'] ?>"><?= htmlspecialchars($opt['username']) ?> (ID:<?= $opt['id'] ?>)</option><?php endforeach; ?></select></div><div class="alert alert-info" style="margin-top:10px; background:#f0f0f0; color:#333; color:#fff;">🔗 推广员后台登录链接：<a href="/promoter/login.php" target="_blank" style="color:#f90;">/promoter/login.php</a></div><button type="submit" class="btn btn-primary">保存修改</button></form></div></div>

<!-- 数据详情模态框 -->
<div id="statsModal" class="modal"><div class="modal-content" style="width:650px;"><span class="close-stats">&times;</span><h3>📊 推广员数据详情 & 下线树</h3><div id="statsContent">加载中...</div><button class="btn btn-sm" onclick="window.location.href='/promoter/login.php'">🔐 推广员登录后台</button></div></div>

<script>
    var addModal = document.getElementById('addModal');
    var addBtn = document.getElementById('addPromoterBtn');
    var spanClose = document.getElementsByClassName('close')[0];
    addBtn.onclick = function() { addModal.style.display = 'block'; }
    spanClose.onclick = function() { addModal.style.display = 'none'; }

    var editModal = document.getElementById('editModal');
    var editBtns = document.getElementsByClassName('edit-promoter');
    var closeEdit = document.getElementsByClassName('close-edit')[0];
    for (var i = 0; i < editBtns.length; i++) {
        editBtns[i].onclick = function() {
            var id = this.getAttribute('data-id');
            var username = this.getAttribute('data-username');
            var viewkey = this.getAttribute('data-viewkey');
            var parent = this.getAttribute('data-parent');
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_viewkey').value = viewkey;
            var select = document.getElementById('edit_parent');
            for (var j = 0; j < select.options.length; j++) {
                if (select.options[j].value == parent) { select.selectedIndex = j; break; }
            }
            editModal.style.display = 'block';
        }
    }
    closeEdit.onclick = function() { editModal.style.display = 'none'; }

    var statsModal = document.getElementById('statsModal');
    var closeStats = document.getElementsByClassName('close-stats')[0];
    closeStats.onclick = function() { statsModal.style.display = 'none'; }

    window.onclick = function(event) {
        if (event.target == addModal) addModal.style.display = 'none';
        if (event.target == editModal) editModal.style.display = 'none';
        if (event.target == statsModal) statsModal.style.display = 'none';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    function renderTree(nodes) {
        if (!nodes || nodes.length === 0) return '无下级';
        var ul = '<ul style="list-style-type:none; padding-left:20px;">';
        for (var i = 0; i < nodes.length; i++) {
            var node = nodes[i];
            ul += '<li><strong>' + escapeHtml(node.username) + '</strong> (ID:' + node.id + ', 点击:' + node.ad_clicks + ')';
            if (node.children && node.children.length) ul += renderTree(node.children);
            ul += '</li>';
        }
        ul += '</ul>';
        return ul;
    }

    document.querySelectorAll('.view-stats').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            fetch('?detail_id=' + id).then(res => res.json()).then(data => {
                if (data.error) {
                    document.getElementById('statsContent').innerHTML = '<div class="alert alert-danger">' + escapeHtml(data.error) + '</div>';
                    statsModal.style.display = 'block';
                    return;
                }
                var html = '<table class="stats-table">' +
                    '<tr><td>用户名：</td><td>' + escapeHtml(data.username) + '</td></tr>' +
                    '<tr><td>邀请码：</td><td><code>' + escapeHtml(data.view_key) + '</code></td></tr>' +
                    '<tr><td>上级ID：</td><td>' + (data.parent_id || '无') + '</td></tr>' +
                    '<tr><td>总分享数：</td><td>' + (data.shares || 0) + '</td></tr>' +
                    '<tr><td>总广告点击：</td><td>' + (data.ad_clicks || 0) + '</td></tr>' +
                    '<tr><td>总访问量：</td><td>' + (data.visits || 0) + '</td></tr>' +
                    '<tr><td>视频观看次数：</td><td>' + (data.video_views || 0) + '</td></tr>' +
                    '<tr><td>下级数量：</td><td>' + (data.children_count || 0) + '</td></tr>' +
                    '<tr><td>注册时间：</td><td>' + data.created_at + '</td></tr>' +
                    '</table><br><h4>📌 下线推广员树形结构</h4><div id="childrenTree">加载中...</div>';
                document.getElementById('statsContent').innerHTML = html;
                statsModal.style.display = 'block';
                fetch('?get_children_tree=' + id).then(r => r.json()).then(tree => {
                    document.getElementById('childrenTree').innerHTML = renderTree(tree);
                }).catch(() => { document.getElementById('childrenTree').innerHTML = '加载失败'; });
            }).catch(() => { alert('加载失败'); });
        });
    });
</script>
<?php require 'footer.php'; ?>
