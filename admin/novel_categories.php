<?php
require 'header.php';

// 删除
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM novel_categories WHERE id = ?");
    $stmt->execute([intval($_GET['delete'])]);
    header("Location: novel_categories.php");
    exit;
}

// 新增/编辑
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $sort = intval($_POST['sort'] ?? 0);
    if ($name !== '') {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE novel_categories SET name = ?, sort = ? WHERE id = ?");
            $stmt->execute([$name, $sort, intval($id)]);
            $message = "分类已更新。";
        } else {
            $stmt = $pdo->prepare("INSERT INTO novel_categories (name, sort) VALUES (?, ?)");
            $stmt->execute([$name, $sort]);
            $message = "分类已添加。";
        }
    }
}

$categories = $pdo->query("SELECT * FROM novel_categories ORDER BY sort, id")->fetchAll();
?>

<h2 class="page-title">小说分类管理</h2>

<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<button class="btn btn-primary" onclick="showForm()">+ 新增分类</button>

<div id="formBox" style="display:none; margin:20px 0;">
    <div class="card">
        <form method="post">
            <input type="hidden" name="id" id="ncid">
            <label style="color:#b0b0b0;">分类名称</label>
            <input class="form-inp" name="name" id="ncname" required>
            <label style="color:#b0b0b0;">排序数字</label>
            <input class="form-inp" name="sort" id="ncsort" value="0" type="number">
            <div style="display:flex; gap:8px; margin-top:10px;">
                <button type="submit" class="btn btn-primary">保存</button>
                <button type="button" class="btn btn-sm" style="background:#555; color:#333;" onclick="hideForm()">取消</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <table>
        <thead><tr><th>ID</th><th>名称</th><th>排序</th><th>操作</th></tr></thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= $cat['sort'] ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editCat(<?= $cat['id'] ?>, '<?= addslashes($cat['name']) ?>', <?= $cat['sort'] ?>)">编辑</button>
                    <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function showForm() { document.getElementById('formBox').style.display='block'; clearForm(); }
function hideForm() { document.getElementById('formBox').style.display='none'; }
function clearForm() { document.getElementById('ncid').value = ''; document.getElementById('ncname').value = ''; document.getElementById('ncsort').value = '0'; }
function editCat(id, name, sort) {
    document.getElementById('ncid').value = id;
    document.getElementById('ncname').value = name;
    document.getElementById('ncsort').value = sort;
    document.getElementById('formBox').style.display = 'block';
}
</script>

<?php require 'footer.php'; ?>