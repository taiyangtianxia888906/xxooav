<?php
require 'header.php';

$message = '';

// 删除单条
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ?");
    $stmt->execute([intval($_GET['delete'])]);
    header("Location: ads.php");
    exit;
}

// 批量操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected_ids']) && isset($_POST['batch_action'])) {
    $ids = array_map('intval', $_POST['selected_ids']);
    switch ($_POST['batch_action']) {
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM ads WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
            $stmt->execute($ids);
            $message = "已删除 " . $stmt->rowCount() . " 个广告。";
            break;
        case 'update_category':
            $catId = !empty($_POST['batch_category_id']) ? intval($_POST['batch_category_id']) : null;
            $stmt = $pdo->prepare("UPDATE ads SET category_id = ? WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
            $stmt->execute(array_merge([$catId], $ids));
            $message = "已更新 " . count($ids) . " 个广告的分类。";
            break;
        case 'update_trigger':
            $trigger = $_POST['batch_trigger_event'] ?? '';
            $stmt = $pdo->prepare("UPDATE ads SET trigger_event = ? WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
            $stmt->execute(array_merge([$trigger], $ids));
            $message = "已更新 " . count($ids) . " 个广告的触发事件。";
            break;
        case 'update_remark':
            $remark = $_POST['batch_remark'] ?? '';
            $stmt = $pdo->prepare("UPDATE ads SET remark = ? WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
            $stmt->execute(array_merge([$remark], $ids));
            $message = "已更新 " . count($ids) . " 个广告的备注。";
            break;
        case 'update_link':
            $linkUrl = $_POST['batch_link_url'] ?? '';
            $stmt = $pdo->prepare("UPDATE ads SET link_url = ? WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
            $stmt->execute(array_merge([$linkUrl], $ids));
            $message = "已更新 " . count($ids) . " 个广告的跳转链接。";
            break;
        case 'update_duration':
            $duration = intval($_POST['batch_duration'] ?? 15);
            $stmt = $pdo->prepare("UPDATE ads SET popup_duration = ? WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
            $stmt->execute(array_merge([$duration], $ids));
            $message = "已更新 " . count($ids) . " 个广告的显示时长。";
            break;
    }
}

// 常规提交（新增/编辑）
$uploadDir = dirname(__DIR__) . '/uploads/ads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['batch_action'])) {
    $id = $_POST['id'] ?? null;
    $type = $_POST['type'] ?? 'banner';
    $title = trim($_POST['title'] ?? '');
    $linkUrl = trim($_POST['link_url'] ?? '');
    $sort = intval($_POST['sort'] ?? 0);
    $categoryId = ($type === 'native' && !empty($_POST['category_id'])) ? intval($_POST['category_id']) : null;
    $triggerEvent = trim($_POST['trigger_event'] ?? '');
    $triggerValue = trim($_POST['trigger_value'] ?? '');
    $popupDuration = intval($_POST['popup_duration'] ?? 15);
    $remark = trim($_POST['remark'] ?? '');

    $imageUrl = trim($_POST['image_url'] ?? '');
    if (!empty($_FILES['ad_image']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['ad_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $newName = 'uploads/ads/' . uniqid('ad_', true) . '.' . $ext;
            $destPath = dirname(__DIR__) . '/' . $newName;
            if (move_uploaded_file($_FILES['ad_image']['tmp_name'], $destPath)) {
                $imageUrl = '/' . $newName;
            } else {
                $message = '图片上传失败，请检查 uploads/ads 目录权限。';
            }
        } else {
            $message = '不支持的图片格式，仅允许 jpg、png、gif、webp。';
        }
    }

    if ($type === 'popup' && $imageUrl === '') {
        $message = '弹窗广告需要提供图片';
    }

    if (empty($message)) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE ads SET type=?, title=?, image_url=?, link_url=?, sort=?, category_id=?, trigger_event=?, trigger_value=?, popup_duration=?, remark=? WHERE id=?");
            $stmt->execute([$type, $title, $imageUrl, $linkUrl, $sort, $categoryId, $triggerEvent, $triggerValue, $popupDuration, $remark, intval($id)]);
            $message = '广告已更新。';
        } else {
            $stmt = $pdo->prepare("INSERT INTO ads (type, title, image_url, link_url, sort, category_id, trigger_event, trigger_value, popup_duration, remark) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$type, $title, $imageUrl, $linkUrl, $sort, $categoryId, $triggerEvent, $triggerValue, $popupDuration, $remark]);
            $message = '广告已添加。';
        }
    }
}

// 获取所有广告，按类型分组
$allAds = $pdo->query("SELECT a.*, c.name AS cat_name FROM ads a LEFT JOIN categories c ON a.category_id = c.id ORDER BY a.sort, a.id")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY sort")->fetchAll();

// 分组
$groupedAds = [];
$typeNames = [
    'banner' => '横幅广告',
    'icon' => '图标广告',
    'text' => '文字广告',
    'native' => '原生广告',
    'adwall' => '广告墙轮播',
    'popup' => '弹窗广告',
];
foreach ($allAds as $ad) {
    $t = $ad['type'];
    if (!isset($groupedAds[$t])) $groupedAds[$t] = [];
    $groupedAds[$t][] = $ad;
}
// 排序分组
$orderedTypes = ['banner','icon','text','native','adwall','popup'];
$finalGroups = [];
foreach ($orderedTypes as $t) {
    if (isset($groupedAds[$t])) {
        $finalGroups[$t] = $groupedAds[$t];
    }
}
// 可能还有未在列表中的类型
foreach ($groupedAds as $t => $ads) {
    if (!in_array($t, $orderedTypes)) $finalGroups[$t] = $ads;
}
?>

<h2 class="page-title">广告管理</h2>

<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<button class="btn btn-primary" onclick="showForm()">+ 新增广告</button>

<!-- 全局折叠按钮 -->
<button class="btn btn-sm" id="globalToggleBtn" onclick="globalToggle()" style="background:#555; color:#333; margin-left:10px;">📂 全部折叠</button>

<!-- 新增/编辑表单 -->
<div id="formBox" style="display:none; margin:20px 0;">
    <div class="card">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" id="aid">

            <label style="color:#b0b0b0;">广告类型</label>
            <select class="form-inp" name="type" id="atype" onchange="toggleType()">
                <option value="banner">横幅 banner (480×100)</option>
                <option value="icon">小图标 icon (52×52)</option>
                <option value="text">文字标签 text</option>
                <option value="native">原生广告 native (插入视频网格)</option>
                <option value="adwall">广告墙轮播 (全屏图片)</option>
                <option value="popup">弹窗广告 (居中弹出)</option>
            </select>

            <div id="popupSettings" style="display:none;">
                <label style="color:#b0b0b0;">触发事件</label>
                <select class="form-inp" name="trigger_event" id="atrigger">
                    <option value="">每次点击/操作触发</option>
                    <option value="category_click">用户点击分类</option>
                    <option value="tag_click">用户点击标签</option>
                    <option value="video_progress">视频播放进度到达</option>
                    <option value="video_seek">用户快进视频</option>
                    <option value="search_click">用户搜索</option>
                </select>

                <label style="color:#b0b0b0;">触发参数（例如秒数）</label>
                <input class="form-inp" name="trigger_value" id="atriggervalue" placeholder="如 180 表示视频播放到180秒时弹出">

                <label style="color:#b0b0b0;">显示时长（秒）</label>
                <input class="form-inp" name="popup_duration" id="apopupduration" value="15" type="number">
            </div>

            <label style="color:#b0b0b0;">标题（用于识别）</label>
            <input class="form-inp" name="title" id="atitle" placeholder="例如：xx娱乐">

            <label style="color:#b0b0b0;">备注说明</label>
            <textarea class="form-inp" name="remark" id="aremark" rows="2" placeholder="例如：分类点击触发广告-3分钟弹出"></textarea>

            <label style="color:#b0b0b0;">上传图片（GIF/PNG/JPG）</label>
            <input type="file" name="ad_image" accept="image/*" style="width:100%; padding:10px; margin-bottom:14px; background:#fff; border:1px solid #d9d9d9; color:#333; border-radius:4px;">

            <label style="color:#b0b0b0;">或手动填写图片URL</label>
            <input class="form-inp" name="image_url" id="aimg" placeholder="http://...">

            <label style="color:#b0b0b0;">跳转链接（目标网址）</label>
            <input class="form-inp" name="link_url" id="alink" placeholder="http://...">

            <div id="catGroup" style="display:none;">
                <label style="color:#b0b0b0;">绑定视频分类（仅原生广告）</label>
                <select class="form-inp" name="category_id" id="acat">
                    <option value="">-- 全局（推荐区域） --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label style="color:#b0b0b0;">排序</label>
            <input class="form-inp" name="sort" id="asort" value="0" type="number">

            <div style="display:flex; gap:8px; margin-top:10px;">
                <button type="submit" class="btn btn-primary">保存</button>
                <button type="button" class="btn btn-sm" style="background:#555; color:#333;" onclick="hideForm()">取消</button>
            </div>
        </form>
    </div>
</div>

<!-- 分组折叠列表 -->
<div class="card" style="margin-top:20px;">
    <form id="batchEditForm" method="post">
        <?php foreach ($finalGroups as $type => $adsInGroup): 
            $typeLabel = $typeNames[$type] ?? $type;
            $groupId = 'group_' . $type;
        ?>
        <div style="margin-bottom:10px; border:1px solid #333; border-radius:4px;">
            <div onclick="toggleGroup('<?= $groupId ?>')" style="cursor:pointer; padding:12px 16px; background:#fff; display:flex; justify-content:space-between; align-items:center; border-radius:4px 4px 0 0;">
                <strong style="color:#007ecc;"><?= htmlspecialchars($typeLabel) ?> (<?= count($adsInGroup) ?>)</strong>
                <span id="<?= $groupId ?>_icon" style="color:#007ecc;">▶</span>
            </div>
            <div id="<?= $groupId ?>" style="display:none;">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="group-check" data-group="<?= $groupId ?>" onclick="toggleGroupCheck(this)"></th>
                            <th>ID</th>
                            <th>预览</th>
                            <th>标题</th>
                            <th>触发事件</th>
                            <th>参数</th>
                            <th>备注</th>
                            <th>分类</th>
                            <th>跳转链接</th>
                            <th>时长(秒)</th>
                            <th>排序</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adsInGroup as $ad): ?>
                        <tr>
                            <td><input type="checkbox" name="selected_ids[]" value="<?= $ad['id'] ?>" class="group-cb-<?= $groupId ?>"></td>
                            <td><?= $ad['id'] ?></td>
                            <td>
                                <?php if (!empty($ad['image_url']) && in_array($ad['type'], ['banner','icon','native','adwall','popup'])): ?>
                                    <img src="<?= htmlspecialchars($ad['image_url']) ?>" style="max-width:60px; max-height:30px; border-radius:4px;">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($ad['title'] ?? '') ?></td>
                            <td><?= htmlspecialchars($ad['trigger_event'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($ad['trigger_value'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($ad['remark'] ?? '') ?></td>
                            <td><?= htmlspecialchars($ad['cat_name'] ?? '全局') ?></td>
                            <td style="max-width:100px; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($ad['link_url'] ?? '') ?></td>
                            <td><?= $ad['popup_duration'] ?></td>
                            <td><?= $ad['sort'] ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" onclick="editAd(<?= $ad['id'] ?>, '<?= $ad['type'] ?>', '<?= addslashes($ad['title'] ?? '') ?>', '<?= addslashes($ad['image_url'] ?? '') ?>', '<?= addslashes($ad['link_url'] ?? '') ?>', '<?= $ad['category_id'] ?? '' ?>', <?= $ad['sort'] ?>, '<?= addslashes($ad['trigger_event'] ?? '') ?>', '<?= addslashes($ad['trigger_value'] ?? '') ?>', '<?= $ad['popup_duration'] ?? 15 ?>', '<?= addslashes($ad['remark'] ?? '') ?>')">编辑</button>
                                <a href="?delete=<?= $ad['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- 全局批量操作栏 -->
        <div style="margin-top:15px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <select id="batchAction" name="batch_action" style="padding:8px; background:#fff; border:1px solid #d9d9d9; color:#333; border-radius:4px;">
                <option value="">-- 批量操作 --</option>
                <option value="delete">删除选中</option>
                <option value="update_category">修改分类</option>
                <option value="update_trigger">修改触发事件</option>
                <option value="update_link">修改跳转链接</option>
                <option value="update_remark">修改备注</option>
                <option value="update_duration">修改广告时间(秒)</option>
            </select>
            <span id="batchCategoryPicker" style="display:none;">
                <select name="batch_category_id" style="padding:8px; background:#fff; border:1px solid #d9d9d9; color:#333; border-radius:4px;">
                    <option value="">-- 选择分类 --</option>
                    <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                </select>
            </span>
            <span id="batchTriggerPicker" style="display:none;">
                <select name="batch_trigger_event" style="padding:8px; background:#fff; border:1px solid #d9d9d9; color:#333; border-radius:4px;">
                    <option value="">无（常规广告）</option>
                    <option value="category_click">用户点击分类</option>
                    <option value="tag_click">用户点击标签</option>
                    <option value="video_progress">视频播放进度</option>
                    <option value="video_seek">用户快进视频</option>
                    <option value="search_click">用户搜索</option>
                </select>
            </span>
            <span id="batchLinkInput" style="display:none;">
                <input type="text" name="batch_link_url" placeholder="新跳转链接" style="padding:8px; background:#fff; border:1px solid #d9d9d9; color:#333; border-radius:4px; width:250px;">
            </span>
            <span id="batchRemarkInput" style="display:none;">
                <input type="text" name="batch_remark" placeholder="新备注内容" style="padding:8px; background:#fff; border:1px solid #d9d9d9; color:#333; border-radius:4px; width:250px;">
            </span>
            <span id="batchDurationInput" style="display:none;">
                <input type="number" name="batch_duration" placeholder="秒数" value="15" style="padding:8px; background:#fff; border:1px solid #d9d9d9; color:#333; border-radius:4px; width:100px;">
            </span>
            <button type="submit" class="btn btn-primary" onclick="return confirm('确认执行批量操作？')">执行</button>
        </div>
    </form>
</div>

<script>
// 全局折叠状态，默认全部折叠
var allCollapsed = true;
var allGroupIds = [<?php foreach ($finalGroups as $t=>$v) echo "'group_$t',"; ?>];

function showForm() { document.getElementById('formBox').style.display='block'; clearForm(); toggleType(); }
function hideForm() { document.getElementById('formBox').style.display='none'; }
function clearForm() {
    document.getElementById('aid').value = '';
    document.getElementById('atype').value = 'banner';
    document.getElementById('atitle').value = '';
    document.getElementById('aimg').value = '';
    document.getElementById('alink').value = '';
    document.getElementById('acat').value = '';
    document.getElementById('asort').value = '0';
    document.getElementById('atrigger').value = '';
    document.getElementById('atriggervalue').value = '';
    document.getElementById('apopupduration').value = '15';
    document.getElementById('aremark').value = '';
}
function toggleType() {
    var type = document.getElementById('atype').value;
    document.getElementById('catGroup').style.display = (type === 'native') ? 'block' : 'none';
    document.getElementById('popupSettings').style.display = (type === 'popup' || type === 'adwall') ? 'block' : 'none';
}
function editAd(id, type, title, img, link, catId, sort, trigger, triggervalue, popupduration, remark) {
    document.getElementById('aid').value = id;
    document.getElementById('atype').value = type;
    document.getElementById('atitle').value = title;
    document.getElementById('aimg').value = (img.startsWith('/uploads/') ? '' : img);
    document.getElementById('alink').value = link;
    document.getElementById('acat').value = catId;
    document.getElementById('asort').value = sort;
    document.getElementById('atrigger').value = trigger;
    document.getElementById('atriggervalue').value = triggervalue;
    document.getElementById('apopupduration').value = popupduration;
    document.getElementById('aremark').value = remark;
    document.getElementById('formBox').style.display = 'block';
    toggleType();
}
// 折叠单个分组
function toggleGroup(groupId) {
    var div = document.getElementById(groupId);
    var icon = document.getElementById(groupId + '_icon');
    if (div.style.display === 'none') {
        div.style.display = 'block';
        icon.textContent = '▼';
    } else {
        div.style.display = 'none';
        icon.textContent = '▶';
    }
}
// 全局折叠/展开
function globalToggle() {
    allCollapsed = !allCollapsed;
    var btn = document.getElementById('globalToggleBtn');
    for (var i = 0; i < allGroupIds.length; i++) {
        var div = document.getElementById(allGroupIds[i]);
        var icon = document.getElementById(allGroupIds[i] + '_icon');
        if (!div) continue;
        if (allCollapsed) {
            div.style.display = 'none';
            if (icon) icon.textContent = '▶';
        } else {
            div.style.display = 'block';
            if (icon) icon.textContent = '▼';
        }
    }
    btn.textContent = allCollapsed ? '📂 全部展开' : '📂 全部折叠';
}
// 分组全选
function toggleGroupCheck(checkbox) {
    var groupId = checkbox.getAttribute('data-group');
    var cbs = document.querySelectorAll('.group-cb-' + groupId);
    for (var i = 0; i < cbs.length; i++) {
        cbs[i].checked = checkbox.checked;
    }
}
// 批量操作显示
document.getElementById('batchAction').addEventListener('change', function() {
    var val = this.value;
    document.getElementById('batchCategoryPicker').style.display = val === 'update_category' ? 'inline' : 'none';
    document.getElementById('batchTriggerPicker').style.display = val === 'update_trigger' ? 'inline' : 'none';
    document.getElementById('batchLinkInput').style.display = val === 'update_link' ? 'inline' : 'none';
    document.getElementById('batchRemarkInput').style.display = val === 'update_remark' ? 'inline' : 'none';
    document.getElementById('batchDurationInput').style.display = val === 'update_duration' ? 'inline' : 'none';
});
</script>
<?php require 'footer.php'; ?>
