<?php
require __DIR__ . "/config.php";
require 'inc/functions.php';
session_start();

// 如果已通过广告墙，直接跳转
if (!empty($_SESSION['adwall_passed'])) {
    $target = $_GET['ref'] ?? 'home.php';
    header("Location: $target");
    exit;
}

// 获取所有广告墙广告
$ads = $pdo->query("SELECT * FROM ads WHERE type = 'adwall' ORDER BY sort, id")->fetchAll();
if (empty($ads)) {
    $_SESSION['adwall_passed'] = true;
    $target = $_GET['ref'] ?? 'home.php';
    header("Location: $target");
    exit;
}

$adData = [];
foreach ($ads as $ad) {
    $adData[] = [
        'image' => $ad['image_url'] ?? '',
        'link' => 'adclick.php?id=' . $ad['id'] . '&url=' . urlencode($ad['link_url'] ?? ''),
    ];
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>精彩推荐</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #000; overflow: hidden; font-family: Arial, sans-serif; }
        .adwall-container { width: 100vw; height: 100vh; position: relative; }
        .slide {
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            background: #000; position: absolute; top: 0; left: 0;
        }
        .slide img { max-width: 100%; max-height: 100%; object-fit: contain; cursor: pointer; }
        .skip-btn {
            position: fixed; top: 15px; right: 15px; z-index: 10;
            background: rgba(0,0,0,0.6); color: #fff; border: 2px solid #fff;
            border-radius: 50%; width: 40px; height: 40px; display: flex;
            align-items: center; justify-content: center; font-size: 20px;
            cursor: pointer; transition: 0.3s;
        }
        .skip-btn:hover { background: #f00; }
        .finish-btn {
            display: none; position: fixed; bottom: 40px; left: 50%;
            transform: translateX(-50%); z-index: 10;
            background: #f90; color: #000; border: none; padding: 12px 30px;
            border-radius: 30px; font-size: 18px; cursor: pointer; font-weight: bold;
        }
        .finish-btn.show { display: block; }
        .indicators {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 8px; z-index: 10;
        }
        .indicator {
            width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5);
            transition: 0.3s;
        }
        .indicator.active { background: #f90; width: 16px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="adwall-container" id="adwall">
        <div class="skip-btn" id="skipBtn" title="跳过本张">✕</div>
        <button class="finish-btn" id="finishBtn">进入网站</button>
        <div class="indicators" id="indicators"></div>
    </div>

    <script>
        var ads = <?= json_encode($adData) ?>;
        if (ads.length === 0) {
            window.location.href = '<?= $_GET['ref'] ?? 'home.php' ?>';
        }

        var currentIndex = 0;
        var skipped = new Array(ads.length).fill(false); // 记录是否已跳过
        var intervalId = null;
        var slideDuration = 5000; // 每张自动播放 5 秒

        var container = document.getElementById('adwall');
        var indicatorsDiv = document.getElementById('indicators');
        var skipBtn = document.getElementById('skipBtn');
        var finishBtn = document.getElementById('finishBtn');
        var slides = [];

        // 创建幻灯片
        ads.forEach(function(ad, index) {
            var slide = document.createElement('div');
            slide.className = 'slide';
            slide.style.display = index === 0 ? 'flex' : 'none';
            var img = document.createElement('img');
            img.src = ad.image;
            img.alt = '广告';
            img.onclick = function() {
                if (ad.link) window.open(ad.link, '_blank');
            };
            slide.appendChild(img);
            container.insertBefore(slide, document.getElementById('skipBtn'));
            slides.push(slide);

            // 指示器
            var dot = document.createElement('span');
            dot.className = 'indicator' + (index === 0 ? ' active' : '');
            indicatorsDiv.appendChild(dot);
        });

        // 更新幻灯片显示
        function showSlide(index) {
            slides.forEach(function(s, i) {
                s.style.display = i === index ? 'flex' : 'none';
                indicatorsDiv.children[i].classList.toggle('active', i === index);
            });
        }

        // 检查是否所有广告都已跳过
        function checkAllSkipped() {
            if (skipped.every(Boolean)) {
                // 全部跳过，停止自动播放，显示进入按钮
                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
                finishBtn.classList.add('show');
                skipBtn.style.display = 'none'; // 隐藏跳过按钮
                document.querySelector('.indicators').style.display = 'none';
            }
        }

        // 跳过当前（标记已跳过，跳到下一张）
        function skipCurrent() {
            skipped[currentIndex] = true;
            if (skipped.every(Boolean)) {
                // 全部标记完，隐藏幻灯片，显示进入按钮
                slides[currentIndex].style.display = 'none';
                checkAllSkipped();
                return;
            }
            // 还有未跳过的，切到下一张未跳过的
            var next = (currentIndex + 1) % ads.length;
            // 如果下一张已跳过，继续找
            while (skipped[next] && next !== currentIndex) {
                next = (next + 1) % ads.length;
            }
            if (next === currentIndex && skipped[currentIndex]) {
                checkAllSkipped();
                return;
            }
            slides[currentIndex].style.display = 'none';
            currentIndex = next;
            slides[currentIndex].style.display = 'flex';
            indicatorsDiv.children[currentIndex].classList.add('active');
            // 重置自动播放计时器
            resetTimer();
        }

        // 自动下一张（标记当前已跳过，逻辑同跳过）
        function autoNext() {
            skipped[currentIndex] = true;
            if (skipped.every(Boolean)) {
                slides[currentIndex].style.display = 'none';
                checkAllSkipped();
                return;
            }
            var next = (currentIndex + 1) % ads.length;
            while (skipped[next] && next !== currentIndex) {
                next = (next + 1) % ads.length;
            }
            if (next === currentIndex && skipped[currentIndex]) {
                checkAllSkipped();
                return;
            }
            slides[currentIndex].style.display = 'none';
            currentIndex = next;
            slides[currentIndex].style.display = 'flex';
            indicatorsDiv.children[currentIndex].classList.add('active');
            resetTimer();
        }

        function resetTimer() {
            if (intervalId) clearInterval(intervalId);
            intervalId = setInterval(autoNext, slideDuration);
        }

        // 启动自动播放
        resetTimer();

        // 跳过按钮
        skipBtn.addEventListener('click', function() {
            skipCurrent();
        });

        // 进入网站按钮
        finishBtn.addEventListener('click', function() {
            fetch('set_adwall_passed.php', { method: 'POST' })
                .then(function() {
                    window.location.href = '<?= $_GET['ref'] ?? 'home.php' ?>';
                })
                .catch(function() {
                    window.location.href = '<?= $_GET['ref'] ?? 'home.php' ?>';
                });
        });

    </script>
</body>
</html>
