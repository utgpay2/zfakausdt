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

// 检测 ADMIN_DIR (ZFAKA 安装时由用户自定义, 不一定是 Goadmin)
$baseDir = file_exists(__DIR__ . '/application/init.php') ? __DIR__ : '/var/www/zfaka';
$initFile = $baseDir . '/application/init.php';
$adminDir = 'Goadmin';
if (file_exists($initFile)) {
    $initContent = file_get_contents($initFile);
    if (preg_match("/define\s*\(\s*['\"]ADMIN_DIR['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $initContent, $m)) {
        $adminDir = $m[1];
    }
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

// 检查插件文件是否已上传（USDT/TRX + 支付宝法币 两条插件路径）
$pluginUsdt = $baseDir . '/application/library/Pay/epay188/epay188.php';
$pluginAlipay = $baseDir . '/application/library/Pay/epay188alipay/epay188alipay.php';
$pluginUsdtExists = file_exists($pluginUsdt);
$pluginAlipayExists = file_exists($pluginAlipay);

$messages = [];
$status = 'success';

// 通道定义：alias / payname / configure4 / payimage
// 注意: alias 不能含下划线, Yaf 自动加载器会把 _ 当成目录分隔符 (PSR-0 规范)
$channels = [
    'epay188'      => ['payname' => '188Pay USDT/TRX', 'configure4' => 'usdt',   'payimage' => '/res/images/pay/188pay.png'],
    'epay188alipay'=> ['payname' => '188Pay 支付宝',   'configure4' => 'alipay', 'payimage' => '/res/images/pay/188pay.png'],
];

foreach ($channels as $alias => $cfg) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM t_payment WHERE alias = ?");
    $stmt->execute([$alias]);
    if ($stmt->fetchColumn() > 0) {
        $messages[] = "支付渠道 {$alias} 记录已存在，跳过。";
        continue;
    }
    try {
        $sql = "INSERT INTO `t_payment`
            (`payment`, `payname`, `payimage`, `alias`, `sign_type`,
             `app_id`, `app_secret`, `ali_public_key`, `rsa_private_key`,
             `configure3`, `configure4`, `overtime`, `active`)
            VALUES
            ('188Pay', ?, ?, ?, 'MD5',
             '', '', '', '',
             'https://api2.188pay.top', ?, 600, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cfg['payname'], $cfg['payimage'], $alias, $cfg['configure4']]);
        $messages[] = "支付渠道 {$alias} 记录添加成功（{$cfg['payname']}）！";
    } catch (PDOException $e) {
        $status = 'error';
        $messages[] = "数据库插入 {$alias} 失败: " . $e->getMessage();
    }
}

// 检查插件文件
if ($pluginUsdtExists) {
    $messages[] = '插件文件已就位：epay188 (USDT/TRX 通道)。';
} else {
    $status = 'warning';
    $messages[] = '未检测到 USDT/TRX 插件文件 application/library/Pay/epay188/epay188.php';
}
if ($pluginAlipayExists) {
    $messages[] = '插件文件已就位：epay188alipay (支付宝法币通道)。';
} else {
    if ($status === 'success') $status = 'warning';
    $messages[] = '未检测到支付宝插件文件 application/library/Pay/epay188alipay/epay188alipay.php';
}

// 检查/部署后台模板文件
// ZFAKA 的 Payment 控制器按 ADMIN_DIR 找模板: application/modules/{ADMIN_DIR}/views/payment/tpl/{alias}.html
// 仓库默认打包到 Goadmin 目录, 如果 ADMIN_DIR 不是 Goadmin 就自动复制过去 (USDT + 支付宝两个模板)
$tplDstDir = $baseDir . '/application/modules/' . $adminDir . '/views/payment/tpl';
$tplNames = ['epay188.html', 'epay188alipay.html'];

if ($adminDir !== 'Goadmin') {
    if (!is_dir($tplDstDir)) {
        @mkdir($tplDstDir, 0755, true);
    }
    foreach ($tplNames as $tplName) {
        $src = $baseDir . '/application/modules/Goadmin/views/payment/tpl/' . $tplName;
        $dst = $tplDstDir . '/' . $tplName;
        if (file_exists($src) && !file_exists($dst)) {
            if (@copy($src, $dst)) {
                $messages[] = "检测到 ADMIN_DIR={$adminDir}，已自动复制模板 {$tplName}。";
            } else {
                if ($status === 'success') $status = 'warning';
                $messages[] = "复制 {$tplName} 失败，请手动执行：cp {$src} {$dst}";
            }
        }
    }
}

foreach ($tplNames as $tplName) {
    if (file_exists($tplDstDir . '/' . $tplName)) {
        $messages[] = "后台模板已就位：{$adminDir}/views/payment/tpl/{$tplName}";
    } else {
        if ($status === 'success') $status = 'warning';
        $messages[] = "未检测到后台模板 application/modules/{$adminDir}/views/payment/tpl/{$tplName}";
    }
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
    $messages[] = '下一步：登录 ZFAKA 管理后台 → 设置中心 → 支付设置，会看到两条 188Pay 渠道：';
    $messages[] = '  • epay188 (USDT/TRX 加密货币)';
    $messages[] = '  • epay188alipay (支付宝法币)';
    $messages[] = '只用其中一种就只激活一条，两种都用就两条都激活。每条都需要填入：商户ID、密钥、网关地址 (https://api2.188pay.top)、支付类型 (usdt/trx 或 alipay)。';
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
    <p>USDT / TRX 加密货币 · 支付宝法币</p>
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
