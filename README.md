好的，我将根据您项目的典型结构（PHP + 视频/小说/图片 + 后台管理）编写一份**完整的技术说明文档**。您可以将以下内容保存为 `README.md` 或单独的项目文档。

---

# xxooav 项目完整说明文档

## 1. 项目概述

**xxooav** 是一个基于 PHP + MySQL 的内容管理系统，专门用于管理视频、小说、图片等多媒体内容，并集成了广告管理、推广员系统、Telegram 机器人、二步验证等功能。适用于搭建内容分享站点或流量变现平台。

**核心定位**  
- 多媒体内容聚合展示（视频、小说、图片）
- 后台全功能管理（内容、用户、广告、统计）
- 推广员系统（支持二步验证、独立后台）
- 广告触发机制（点击、展示、弹窗等）

**技术环境要求**
- Web 服务器：Apache 2.4+ / Nginx
- PHP 7.4 – 8.2（推荐 7.4）
- MySQL 5.7+ 或 MariaDB 10.3+
- PHP 扩展：PDO、GD、cURL、json、session、fileinfo
- 服务器内存：推荐 1GB 以上（处理视频上传需更多）

---

## 2. 目录结构

```
xxooav/
├── admin/                 # 后台管理模块
│   ├── ads.php            # 广告管理
│   ├── advertisers.php    # 广告主管理
│   ├── auth.php           # 后台权限验证
│   ├── categories.php     # 分类管理（视频/小说/图片）
│   ├── chunk_upload.php   # 分片上传处理
│   ├── footer.php / header.php
│   ├── images.php         # 图片管理
│   ├── image_categories.php
│   ├── index.php          # 后台仪表盘
│   ├── login.php / logout.php
│   ├── novels.php / novel_categories.php
│   ├── promoters.php      # 推广员管理
│   ├── promoter_stats.php # 推广员统计
│   ├── settings.php       # 站点设置
│   ├── stats.php          # 全局统计
│   ├── tags.php           # 标签管理
│   ├── upload.php         # 媒体上传
│   ├── user_analysis.php  # 用户分析
│   └── videos.php         # 视频管理
├── inc/                   # 公共函数库
│   ├── GoogleAuthenticator.php   # 二步验证类
│   ├── footer.php / header.php   # 页面公共部分
│   ├── functions.php      # 全局函数
│   ├── meta_tags.php      # SEO 元标签
│   ├── pagination.php     # 分页函数
│   ├── share_modal.php    # 分享弹窗
│   ├── source_tracking.php # 来源追踪
│   └── telegram.php       # Telegram 机器人方法
├── promoter/              # 推广员前台
│   ├── change_password.php
│   ├── index.php          # 推广员仪表盘
│   ├── login.php / logout.php
│   ├── setup_2fa.php      # 二步验证设置
│   └── user_detail.php    # 用户明细
├── assets/                # 静态资源
│   ├── js/close_ad.js
│   └── share.js
├── uploads/               # 用户/管理员上传的文件（图片、视频）
│   └── (内容被 .gitignore 忽略，不进入版本库)
├── 根目录文件
│   ├── index.php          # 网站首页
│   ├── home.php           # 主页（可能用于动态）
│   ├── video.php          # 视频详情页
│   ├── novels.php         # 小说列表页
│   ├── novel.php          # 小说详情页
│   ├── images.php         # 图片列表页
│   ├── category.php       # 分类页
│   ├── search.php         # 搜索页
│   ├── s.php              # 短链或跳转
│   ├── share.php          # 分享页
│   ├── stay.php           # 停留页（广告）
│   ├── adwall.php         # 广告墙
│   ├── adclick.php        # 广告点击处理
│   ├── close_ad.php       # 关闭广告
│   ├── config.php         # 数据库及站点配置（重要）
│   ├── style.css          # 全局样式
│   ├── manifest.json      # PWA 清单
│   ├── xxoo_bot.php       # Telegram 机器人入口
│   ├── update_video_duration.php  # 视频时长更新脚本
│   └── verify.php         # 验证逻辑
└── .user.ini              # PHP 配置（如上传大小限制）
```

---

## 3. 安装与部署步骤

### 3.1 准备服务器环境

确保 PHP 已安装所需扩展：
```bash
# 以 CentOS / OpenCloudOS 为例
yum install php php-mysqlnd php-gd php-json php-curl php-fileinfo
```

修改 `upload_max_filesize` 和 `post_max_size` 以支持大视频上传（例如 2GB）。

### 3.2 克隆代码（或直接复制文件）

```bash
git clone https://github.com/taiyangtianxia888906/xxooav.git
cd xxooav
```

### 3.3 创建数据库并导入结构

该项目未提供 `.sql` 文件（可能通过 `admin/settings.php` 或安装向导创建）。您需要手动创建数据库，并参考以下表结构（根据代码反推）：

- `videos` – 视频表（id, title, file_path, cover, category, views, create_time）
- `novels` – 小说表（id, title, content, category, views）
- `images` – 图片表（id, title, file_path, category）
- `categories` – 分类表（id, name, type）
- `ads` – 广告表（id, code, position, clicks, views）
- `advertisers` – 广告主表
- `promoters` – 推广员表（id, username, password, 2fa_secret, balance, commission）
- `promoter_clicks` – 推广点击记录
- `settings` – 站点配置（键值对）
- `tags` – 标签表
- `telegram_users` – Telegram 用户绑定

您可以通过后台 `admin/settings.php` 或手动创建。**建议执行**：登录后台后检查是否自动检测表结构，若无，则需要自行编写 SQL。

### 3.4 配置数据库连接

编辑 `config.php`，填写数据库信息：

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'xxooav_db');
define('BASE_URL', 'https://yourdomain.com');
```

### 3.5 设置目录权限

```bash
chmod 755 uploads
chmod 644 config.php
```

确保 `uploads/` 目录可写（用于上传视频/图片）。

### 3.6 Web 服务器配置

**Apache** – 使用 `.htaccess`（如果支持）或设置 `DocumentRoot` 指向项目根目录。  
**Nginx** – 配置示例：

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /www/wwwroot/xxoo.tytdwpt.vip;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3.7 访问网站

- 前台：`https://yourdomain.com`
- 后台：`https://yourdomain.com/admin`（默认账号密码可能需要从数据库设置或见下文）

---

## 4. 功能详解

### 4.1 前台功能

| 页面              | 功能描述                                                                 |
|-------------------|--------------------------------------------------------------------------|
| `index.php`       | 首页，展示最新视频、小说、图片、推荐内容。                                |
| `video.php?id=X`  | 视频详情页，播放视频（可能是 `video.js` 或直接 HTML5），显示广告、点赞、分享。 |
| `novel.php?id=X`  | 小说详情页，显示章节内容，支持长文本分页。                                |
| `novels.php`      | 小说列表，按分类、热门、最新排序。                                       |
| `images.php`      | 图片列表，瀑布流展示。                                                   |
| `search.php`      | 全局搜索（标题、标签）。                                                 |
| `category.php`    | 分类筛选页。                                                             |
| `share.php`       | 内容分享页，生成带推广参数的链接。                                       |
| `adwall.php`      | 广告墙，用户点击广告后获得积分或解锁内容。                               |
| `stay.php`        | 强制停留页面，用于展示广告（需倒计时）。                                 |
| `close_ad.php`    | 关闭广告弹窗。                                                           |
| `xxoo_bot.php`    | Telegram 机器人 Webhook，处理机器人命令（如查询推广收益、最新内容）。    |

### 4.2 后台管理（/admin）

后台登录后可以：

- **内容管理**：添加/编辑/删除视频、小说、图片；设置分类、标签、封面。
- **广告管理**：添加广告代码（Google AdSense、自定义图片等），设置投放位置（首页、列表页、内容页顶部/底部/侧边栏），统计点击/展示。
- **推广员系统**：查看推广员列表、佣金、提现申请；生成推广链接统计。
- **站点设置**：修改站点名称、关键词、描述；配置 Telegram Bot Token；开启/关闭注册、验证码。
- **统计分析**：PV/UV、广告点击率、内容热度排行、用户地理位置（通过 IP 库）。
- **用户分析**：查看在线用户、登录日志、推广来源。
- **上传管理**：支持分片大文件上传（`chunk_upload.php`），视频格式自动获取时长。

### 4.3 推广员系统（/promoter）

推广员拥有独立子站：

- 登录（支持二步验证 Google Authenticator）
- 查看个人推广链接、点击量、收入
- 修改密码、绑定 Telegram 接收通知
- 查看自己推广带来的用户明细

### 4.4 Telegram 机器人

- 用户通过 Telegram 绑定账号后，可以查询最新内容、接收更新通知。
- 管理员可以通过机器人广播消息给所有订阅用户。
- 推广员可收到点击/收益提醒。

---

## 5. 配置说明（重点）

### 5.1 数据库配置 (`config.php`)

包含数据库连接常量，务必修改 `DB_PASS` 为强密码。

### 5.2 站点设置 (`admin/settings.php` 或数据库 `settings` 表)

可配置项目：
- `site_name` – 网站标题
- `site_keywords` – SEO 关键词
- `site_description` – 描述
- `telegram_bot_token` – Bot Token
- `telegram_channel_id` – 频道 ID
- `enable_promoter` – 是否开启推广员功能
- `ad_timeout` – 广告强制停留秒数
- `upload_max_size` – 上传文件大小限制（MB）

### 5.3 广告投放

广告位 ID（根据 `adwall.php` 和 `inc/trigger_ads.php` 可能预定义）：
- `home_top` – 首页顶部
- `home_bottom` – 首页底部
- `video_sidebar` – 视频详情右侧
- `video_before_content` – 视频播放前
- `novel_after_content` – 小说正文后
- `popup` – 弹窗广告

### 5.4 安全配置

- 后台路径建议通过 `.htaccess` 或 Nginx 限制 IP 访问。
- 开启二步验证（管理员/推广员）。
- 定期更新 `config.php` 权限为 `600`。
- 使用 HTTPS 防止中间人攻击。

---

## 6. 常见问题与故障排除

| 问题                                 | 解决方法                                                                 |
|--------------------------------------|--------------------------------------------------------------------------|
| 上传视频失败（大文件）               | 调整 PHP `upload_max_filesize`，并确保 `chunk_upload.php` 的分片设置合理。 |
| 后台无法登录（密码错误）             | 直接修改数据库 `admins` 表，密码使用 `password_hash()` 生成。            |
| 推广员二步验证无法扫描二维码         | 检查 `GoogleAuthenticator.php` 是否正常；确保服务器时间同步 NTP。       |
| Telegram 机器人无响应                | 检查 Webhook URL 是否正确（`https://域名/xxoo_bot.php`）。               |
| 首页加载慢（大量视频列表）           | 开启数据库缓存或添加 `LIMIT` 分页。                                      |
| git push 时内存不足（signal 9）      | 删除 `.git` 重新初始化，确保 `uploads/` 已加入 `.gitignore`。            |
| 广告不显示                           | 检查广告代码是否包含 `https://`；浏览器是否拦截弹窗。                    |

---

## 7. 开发与扩展

### 7.1 添加新内容类型

参考现有视频模块，复制 `video.php` 并修改 SQL 查询，同时在后台添加相应管理页面。

### 7.2 自定义广告位

在 `inc/trigger_ads.php` 中添加新位置的函数，并在模板中调用。

### 7.3 修改前端样式

主要样式位于 `style.css`，部分 JS 在 `assets/js/` 下。

### 7.4 开发规范

- 所有数据库操作使用 PDO 预处理（`inc/functions.php` 中应有 `db_query` 封装）。
- 敏感配置统一放在 `config.php`。
- 前台文件包含 `inc/header.php` 和 `inc/footer.php`。

---

## 8. 许可证与免责声明

- 本项目代码仅供学习交流，**严禁用于违法违规内容传播**。
- 使用者需自行承担因使用本项目而产生的任何法律责任。
- 未经作者许可，不得将本项目用于商业销售。

---

## 9. 联系方式与更新

- 维护者：吴学良 (GitHub: taiyangtianxia888906)
- 项目地址：https://github.com/taiyangtianxia888906/xxooav
- 更新日志：请关注 GitHub 提交记录。

---

**最后提醒**：由于您在实际部署时没有上传媒体文件（`uploads/` 被忽略），请通过后台或 FTP 自行上传内容，否则网站首页可能为空。建议先上传几个测试视频和小说，确保前台正常显示。
