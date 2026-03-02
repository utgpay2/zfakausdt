<?php
/**
 * 188Pay ZFAKA 一键安装脚本
 *
 * 使用方法：
 * 1. 将插件文件上传到 ZFAKA 目录
 * 2. 将此文件放到 ZFAKA 根目录（与 public/ 同级）
 * 3. 在浏览器中访问 http://你的域名/install_188pay.php
 * 4. 安装完成后删除此文件
 */

// 读取 ZFAKA 配置文件获取数据库信息
$iniFile = __DIR__ . '/conf/application.ini';
if (!file_exists($iniFile)) {
    // 尝试 Docker 映射路径
    $iniFile = '/var/www/zfaka/conf/application.ini';
}
if (!file_exists($iniFile)) {
    die(renderPage('error', '找不到 ZFAKA 配置文件 (conf/application.ini)，请确认此脚本放在 ZFAKA 根目录下。'));
}

$ini = parse_ini_file($iniFile, true);
// ZFAKA 使用 [product : common] 段
$section = isset($ini['product : common']) ? $ini['product : common'] : (isset($ini['product']) ? $ini['product'] : null);
if (!$section) {
    die(renderPage('error', '无法解析数据库配置，请检查 conf/application.ini 文件。'));
}

$host = isset($section['WRITE_HOST']) ? $section['WRITE_HOST'] : (isset($section['READ_HOST']) ? $section['READ_HOST'] : 'localhost');
$port = isset($section['WRITE_PORT']) ? $section['WRITE_PORT'] : (isset($section['READ_PORT']) ? $section['READ_PORT'] : 3306);
$user = isset($section['WRITE_USER']) ? $section['WRITE_USER'] : (isset($section['READ_USER']) ? $section['READ_USER'] : '');
$pass = isset($section['WRITE_PSWD']) ? $section['WRITE_PSWD'] : (isset($section['READ_PSWD']) ? $section['READ_PSWD'] : '');
$db   = isset($section['Default']) ? $section['Default'] : 'zfaka';

// 连接数据库
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(renderPage('error', '数据库连接失败: ' . $e->getMessage()));
}

// 检查插件文件是否已上传
$pluginFile = __DIR__ . '/application/library/Pay/epay188/epay188.php';
if (!file_exists($pluginFile)) {
    // 尝试 Docker 映射路径
    $pluginFile = '/var/www/zfaka/application/library/Pay/epay188/epay188.php';
}
$pluginExists = file_exists($pluginFile);

// 检查是否已存在记录
$stmt = $pdo->prepare("SELECT COUNT(*) FROM t_payment WHERE alias = 'epay188'");
$stmt->execute();
$exists = $stmt->fetchColumn() > 0;

$messages = [];
$status = 'success';

if ($exists) {
    $messages[] = '支付渠道记录已存在，无需重复添加。';
} else {
    // 插入记录
    try {
        $sql = "INSERT INTO `t_payment`
            (`payment`, `payname`, `payimage`, `alias`, `sign_type`,
             `app_id`, `app_secret`, `ali_public_key`, `rsa_private_key`,
             `configure3`, `configure4`, `overtime`, `active`)
            VALUES
            ('188Pay USDT', 'USDT(TRC20)', '/res/images/pay/188pay.png', 'epay188', 'MD5',
             '', '', '', '',
             'https://api2.188pay.top', 'usdt', 600, 0)";
        $pdo->exec($sql);
        $messages[] = '支付渠道记录添加成功！';
    } catch (PDOException $e) {
        $status = 'error';
        $messages[] = '数据库插入失败: ' . $e->getMessage();
    }
}

// 检查插件文件
if ($pluginExists) {
    $messages[] = '插件文件已就位 (epay188.php)。';
} else {
    $status = 'warning';
    $messages[] = '未检测到插件文件，请确认已上传 application/library/Pay/epay188/epay188.php';
}

// 检查模板文件
$tplFile = __DIR__ . '/application/modules/Goadmin/views/payment/tpl/epay188.html';
if (!file_exists($tplFile)) {
    $tplFile = '/var/www/zfaka/application/modules/Goadmin/views/payment/tpl/epay188.html';
}
if (file_exists($tplFile)) {
    $messages[] = '后台模板文件已就位 (epay188.html)。';
} else {
    if ($status === 'success') $status = 'warning';
    $messages[] = '未检测到后台模板文件，请确认已上传 application/modules/Goadmin/views/payment/tpl/epay188.html';
}

// 检查图标
$iconFile = __DIR__ . '/public/res/images/pay/188pay.png';
if (!file_exists($iconFile)) {
    $iconFile = '/var/www/zfaka/public/res/images/pay/188pay.png';
}
if (file_exists($iconFile)) {
    $messages[] = '支付图标已就位 (188pay.png)。';
}

$messages[] = '';
if ($status !== 'error') {
    $messages[] = '下一步：登录 ZFAKA 管理后台 → 设置中心 → 支付设置 → 找到 188Pay USDT → 编辑 → 填入商户ID、密钥、网关地址 → 激活。';
    $messages[] = '安装完成后请删除此文件 (install_188pay.php) 以确保安全。';
}

echo renderPage($status, implode("\n", $messages));

// ─── 渲染页面 ───
function renderPage($status, $message) {
    $colors = [
        'success' => ['bg' => '#d1fae5', 'border' => '#059669', 'icon' => '✅', 'title' => '安装成功'],
        'warning' => ['bg' => '#fef3c7', 'border' => '#d97706', 'icon' => '⚠️', 'title' => '部分完成'],
        'error'   => ['bg' => '#fee2e2', 'border' => '#dc2626', 'icon' => '❌', 'title' => '安装失败'],
    ];
    $c = $colors[$status] ?? $colors['error'];
    $lines = nl2br(htmlspecialchars($message));

    return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>188Pay ZFAKA 插件安装</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
  .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); max-width: 560px; width: 100%; overflow: hidden; }
  .card-header { background: linear-gradient(135deg, #7c3aed, #6366f1); color: #fff; padding: 24px 28px; }
  .card-header h1 { font-size: 1.3rem; font-weight: 700; margin-bottom: 4px; }
  .card-header p { font-size: .85rem; opacity: .85; }
  .card-body { padding: 24px 28px; }
  .status-box { background: {$c['bg']}; border-left: 4px solid {$c['border']}; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; }
  .status-title { font-size: 1rem; font-weight: 700; margin-bottom: 8px; color: {$c['border']}; }
  .status-msg { font-size: .88rem; color: #333; line-height: 1.8; }
  .footer { text-align: center; padding: 16px; font-size: .78rem; color: #999; border-top: 1px solid #eee; }
  .footer a { color: #7c3aed; text-decoration: none; }
</style>
</head>
<body>
<div class="card">
  <div class="card-header">
    <h1>188Pay ZFAKA 插件安装</h1>
    <p>USDT (TRC20) / TRX 加密货币支付</p>
  </div>
  <div class="card-body">
    <div class="status-box">
      <div class="status-title">{$c['icon']} {$c['title']}</div>
      <div class="status-msg">{$lines}</div>
    </div>
  </div>
  <div class="footer">
    <a href="https://www.188pay.top" target="_blank">188Pay</a> ·
    <a href="https://t.me/token188" target="_blank">Telegram @token188</a> ·
    <a href="https://github.com/utgpay2/zfakausdt" target="_blank">GitHub</a>
  </div>
</div>
</body>
</html>
HTML;
}
