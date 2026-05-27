<?php
require 'header.php';

// 删除
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM novels WHERE id = ?");
    $stmt->execute([intval($_GET['delete'])]);
    header("Location: novels.php");
    exit;
}

// 新增/编辑
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $categoryId = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    if ($title !== '' && $content !== '') {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE novels SET title = ?, content = ?, category_id = ? WHERE id = ?");
            $stmt->execute([$title, $content, $categoryId, intval($id)]);
            $message = "小说已更新。";
        } else {
            $stmt = $pdo->prepare("INSERT INTO novels (title, content, category_id) VALUES (?, ?, ?)");
            $stmt->execute([$title, $content, $categoryId]);
            $message = "小说已添加。";
        }
    }
}

// 分类筛选
$catFilter = intval($_GET['cat'] ?? 0);
$where = '';
$params = [];
if ($catFilter > 0) {
    $where = "WHERE n.category_id = ?";
    $params[] = $catFilter;
}

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM novels n $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT n.*, nc.name AS cat_name FROM novels n LEFT JOIN novel_categories nc ON n.category_id = nc.id $where ORDER BY n.id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$novels = $stmt->fetchAll();

// 小说分类列表
$categories = $pdo->query("SELECT id, name FROM novel_categories ORDER BY sort")->fetchAll();
?>

<h2 class="page-title">小说管理</h2>

<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- 分类筛选 -->
<div class="card" style="margin-bottom:16px;">
    <form method="get" style="display:flex; gap:8px; align-items:center;">
        <select name="cat" style="padding:8px; background:#fff; border:1px solid #d9d9d9; color:#333; border-radius:4px;">
            <option value="0">全部分类</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($catFilter === $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary">筛选</button>
        <?php if ($catFilter > 0): ?>
            <a href="novels.php" class="btn btn-sm" style="background:#555; color:#333;">清除</a>
        <?php endif; ?>
    </form>
</div>

<button class="btn btn-primary" onclick="showForm()">+ 新增小说</button>

<!-- 新增/编辑表单 -->
<div id="formBox" style="display:none; margin:20px 0;">
    <div class="card">
        <form method="post">
            <input type="hidden" name="id" id="nid">
            <label style="color:#b0b0b0;">标题</label>
            <input class="form-inp" name="title" id="ntitle" required>
            <label style="color:#b0b0b0;">内容</label>
            <textarea class="form-inp" name="content" id="ncontent" rows="12" style="resize:vertical;" required></textarea>
            <label style="color:#b0b0b0;">分类</label>
            <select class="form-inp" name="category_id" id="ncategory">
                <option value="">-- 选择分类 --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex; gap:8px; margin-top:10px;">
                <button type="submit" class="btn btn-primary">保存</button>
                <button type="button" class="btn btn-sm" style="background:#555; color:#333;" onclick="hideForm()">取消</button>
            </div>
        </form>
    </div>
</div>

<!-- 小说列表 -->
<div class="card" style="margin-top:20px;">
    <table>
        <thead>
            <tr><th>ID</th><th>标题</th><th>分类</th><th>时间</th><th>操作</th></tr>
        </thead>
        <tbody>
            <?php foreach ($novels as $n): ?>
            <tr>
                <td><?= $n['id'] ?></td>
                <td><?= htmlspecialchars($n['title']) ?></td>
                <td><?= htmlspecialchars($n['cat_name'] ?? '无') ?></td>
                <td><?= $n['created_at'] ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editNovel(<?= $n['id'] ?>, '<?= addslashes($n['title']) ?>', '<?= addslashes($n['content']) ?>', '<?= $n['category_id'] ?? '' ?>')">编辑</button>
                    <a href="?delete=<?= $n['id'] ?>&cat=<?= $catFilter ?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 分页 -->
<?php if ($totalPages > 1): ?>
<div style="display:flex; justify-content:center; gap:6px; margin-top:20px;">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?cat=<?= $catFilter ?>&page=<?= $i ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : '' ?>" style="background:<?= $i === $page ? '#f90' : '#555' ?>; color:#333;"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<script>
function showForm() { document.getElementById('formBox').style.display='block'; clearForm(); }
function hideForm() { document.getElementById('formBox').style.display='none'; }
function clearForm() {
    document.getElementById('nid').value = '';
    document.getElementById('ntitle').value = '';
    document.getElementById('ncontent').value = '';
    document.getElementById('ncategory').value = '';
}
function editNovel(id, title, content, catId) {
    document.getElementById('nid').value = id;
    document.getElementById('ntitle').value = title;
    document.getElementById('ncontent').value = content;
    document.getElementById('ncategory').value = catId;
    document.getElementById('formBox').style.display = 'block';
}
</script>

<?php require 'footer.php'; ?>