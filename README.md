# ZFAKA 发卡平台 - 188Pay 支付插件

> 让 ZFAKA 发卡系统支持 USDT (TRC20) / TRX 加密货币，以及支付宝、微信等法币通道收款，资金直达您自己的钱包，无中间商。

[![188Pay](https://img.shields.io/badge/platform-188pay.top-brightgreen.svg)](https://www.188pay.top)
[![Telegram](https://img.shields.io/badge/telegram-token188-blue.svg?logo=telegram)](https://t.me/token188pay)

---

## 特点

- **EPay 协议对接** — 标准易支付协议，稳定可靠
- **跳转收银台** — 用户点击支付后跳转到 188Pay 收银台完成付款
- **自动回调** — 支付成功后自动通知 ZFAKA 完成发卡
- **后台可配置** — 商户ID、密钥、网关地址、支付类型均可在 ZFAKA 后台直接配置
- **多通道支持** — 加密货币（USDT TRC20 / TRX）+ 法币（支付宝 / 微信等）
- **零手续费** — 收款直达您自己的钱包地址

---

## 前置条件

1. 已安装 [ZFAKA](https://github.com/ZFAKA/ZFAKA) 发卡系统（v1.4.x）
2. 已注册 [188Pay 商户账号](https://www.188pay.top/login) 并完成相应通道配置

---

## 安装步骤

### 第 1 步：上传插件文件

将本仓库中的文件上传到 ZFAKA 安装目录，覆盖合并即可：

```
zfaka/                              ← 你的 ZFAKA 根目录
├── application/
│   ├── library/Pay/
│   │   ├── epay188/
│   │   │   └── epay188.php          ← USDT/TRX 通道核心文件
│   │   └── epay188alipay/
│   │       └── epay188alipay.php    ← 支付宝法币通道核心文件
│   └── modules/Goadmin/views/payment/tpl/
│       ├── epay188.html             ← USDT 后台配置模板
│       └── epay188alipay.html       ← 支付宝后台配置模板
├── public/res/images/pay/
│   └── 188pay.png                  ← 支付图标（可选）
└── install_188pay.php              ← 一键安装脚本（安装后删除）
```

> **⚠️ 命名约束：** 插件目录、PHP 类名、数据库 alias **不能包含下划线**。
> Yaf 框架的自动加载器遵循 PSR-0 规范，会把类名里的 `_` 当成目录分隔符。
> 比如 alias 写成 `epay188_alipay`，Yaf 会去找 `Pay/epay188/alipay/epay188/alipay.php` → 找不到 → 500。
> 所以支付宝通道用 `epay188alipay`（无下划线），USDT 通道用 `epay188`。

**命令行操作（SSH）：**

```bash
cd /你的zfaka目录

# 克隆插件
git clone https://github.com/utgpay2/zfakausdt.git /tmp/zfakausdt

# 复制插件文件
cp -r /tmp/zfakausdt/application/* application/
cp -r /tmp/zfakausdt/public/* public/

# 复制一键安装脚本到 ZFAKA 根目录
cp /tmp/zfakausdt/install_188pay.php .

# 设置权限
chmod 755 application/library/Pay/epay188/epay188.php
```

### 第 2 步：一键安装（浏览器访问）

在浏览器中访问安装脚本，自动完成数据库配置：

```
http://你的域名/install_188pay.php
```

脚本会自动读取 ZFAKA 的数据库配置，添加支付渠道记录，并检查插件文件是否已就位。

> **安装完成后请立即删除 `install_188pay.php` 以确保安全！**

<details>
<summary>💡 手动安装（备选方案）</summary>

如果一键安装脚本无法使用，也可以手动在数据库中执行 SQL：

```sql
INSERT INTO `t_payment`
(`payment`, `payname`, `payimage`, `alias`, `sign_type`,
 `app_id`, `app_secret`, `ali_public_key`, `rsa_private_key`,
 `configure3`, `configure4`, `overtime`, `active`)
VALUES
('188Pay', '188Pay', '/res/images/pay/188pay.png', 'epay188', 'MD5',
 '', '', '', '',
 'https://api2.188pay.top', 'usdt', 600, 0);
```

也可以直接执行仓库中的 `install.sql` 文件。

</details>

### 第 3 步：后台配置

安装脚本会自动插入**两条**支付渠道记录，分别用于 USDT 和支付宝。

1. 登录 ZFAKA 管理后台 → **支付设置**
2. 看到两条 188Pay 渠道：
   - **epay188**（USDT/TRX 加密货币）
   - **epay188alipay**（支付宝法币）
3. 只想用其中一种就只激活那一条；两种都用就两条都激活
4. 每条都需要填入相同的：

| 配置项 | 说明 |
|--------|------|
| **商户ID** | 在 [188Pay 商户平台](https://www.188pay.top) → API 密钥页面获取 |
| **商户密钥** | 同上，获取 Secret Key |
| **网关地址** | 默认 `https://api2.188pay.top`，一般无需修改 |
| **支付类型** | USDT 通道填 `usdt` / `trx`；支付宝通道填 `alipay` |
| **超时(秒)** | 建议 `600`（10 分钟） |
| **是否激活** | 设为 **激活** |

**支付类型可选值：**

| 填写值 | 说明 |
|--------|------|
| `usdt` | USDT (TRC20) 加密货币 |
| `trx` | TRX 加密货币 |
| `alipay` | 支付宝（法币） |
| `wechat` | 微信支付（法币） |

> **大小写不敏感**，填 `alipay` 或 `Alipay` 效果相同。

5. 点击 **确认修改**

### 第 4 步：测试支付

1. 在 ZFAKA 前台创建一个测试商品（价格设为 0.10）
2. 下单后选择对应支付方式
3. 页面应跳转到 188Pay 收银台
4. 完成支付后 ZFAKA 自动发卡

---

## 文件说明

```
├── application/
│   ├── library/Pay/
│   │   ├── epay188/
│   │   │   └── epay188.php          # USDT/TRX 通道（创建订单 + 回调验签）
│   │   └── epay188alipay/
│   │       └── epay188alipay.php    # 支付宝法币通道（创建订单 + 回调验签）
│   └── modules/Goadmin/views/payment/tpl/
│       ├── epay188.html             # USDT 后台支付配置表单模板
│       └── epay188alipay.html       # 支付宝后台支付配置表单模板
├── public/res/images/pay/
│   └── 188pay.png                  # 支付按钮图标
├── install_188pay.php              # 一键安装脚本（浏览器访问自动配置数据库）
├── install.sql                     # 数据库初始化 SQL（手动安装备选）
└── README.md                       # 本文档
```

---

## 技术细节

### 支付流程

```
用户下单 → ZFAKA 生成签名 → 302 跳转 188Pay 收银台
    → 用户在收银台完成付款（USDT/TRX 或 支付宝/微信）
    → 188Pay 确认到账
    → GET 回调 ZFAKA notify_url（附带签名）
    → ZFAKA 验签成功 → 自动发卡
```

### 签名算法（EPay 模式）

1. 将参数按 key 的 ASCII 码排序
2. 拼接为 `key1=value1&key2=value2` 格式
3. 末尾直接追加密钥（无分隔符）
4. MD5 取小写 32 位

```php
ksort($params);
$str = urldecode(http_build_query($params));
$sign = md5($str . $secretKey);
```

---

## 常见问题

### Q: 点击支付后 404？

检查后台 **网关地址** 是否已填写（如 `https://api2.188pay.top`）。如果为空，跳转 URL 会指向 ZFAKA 自身而非 188Pay。

### Q: 回调失败？

1. 确认 ZFAKA 的 `weburl` 配置正确（后台 → 配置中心），且末尾不要带 `/`
2. 确认回调地址 `http://你的域名/product/notify/?paymethod=epay188` 能外网访问
3. 查看 ZFAKA 日志：`log/yewu/` 目录下的日志文件

### Q: 如何同时支持多种支付方式？

仓库已经提供两个插件副本（`epay188` + `epay188alipay`），安装脚本默认会插入两条记录。后台把两条都激活就行。

如果还要加更多通道（比如微信），复制 `epay188alipay` 目录改名（不要含下划线），同时插入一条 `t_payment` 记录，`alias` 写新名字。

### Q: 后台编辑页 500 / 前台点支付按钮 500？

最常见原因：alias 含下划线。Yaf 自动加载器会把类名里的 `_` 当成目录分隔符（PSR-0 遗留规范），导致找不到插件 PHP 文件。所以 alias 必须用 `epay188alipay` 而不是 `epay188_alipay`。

检查 ZFAKA 的 PHP 错误日志：`log/php/{当天日期}.log`，会看到类似：

```
Yaf\Loader::autoload(): Failed opening script
  application/library/Pay/epay188/alipay/epay188/alipay.php
```

如果看到这种「斜杠拆开」的路径，就是 alias 含下划线导致的。

### Q: 后台编辑页看不到「网关地址」「支付类型」字段？

`install_188pay.php` 会自动读取 ZFAKA 的 `application/init.php` 检测 `ADMIN_DIR`，并把模板复制到正确目录。如果没生效，手动复制：

```bash
# 假设 ADMIN_DIR=Befree
mkdir -p application/modules/Befree/views/payment/tpl/
cp application/modules/Goadmin/views/payment/tpl/epay188*.html \
   application/modules/Befree/views/payment/tpl/
# 清模板缓存
rm -f temp/payment.json
```

---

## 相关链接

- [188Pay 官网](https://www.188pay.top)
- [188Pay API 文档](https://github.com/anonymitypay/usdtpayapi)
- [ZFAKA 发卡系统](https://github.com/ZFAKA/ZFAKA)
- Telegram：[@token188](https://t.me/token188)
- 频道：[@token188pay](https://t.me/token188pay)
- 公开群：[加入公开群](https://t.me/+dyDaHNvyQcY2MDBh)
