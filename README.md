# ZFAKA 发卡平台 - 188Pay USDT/TRX 支付插件

> 让 ZFAKA 发卡系统支持 USDT (TRC20) / TRX 加密货币收款，资金直达您自己的钱包，无中间商。

[![188Pay](https://img.shields.io/badge/platform-188pay.top-brightgreen.svg)](https://www.188pay.top)
[![Telegram](https://img.shields.io/badge/telegram-token188-blue.svg?logo=telegram)](https://t.me/token188pay)

---

## 特点

- **EPay 协议对接** — 标准易支付协议，稳定可靠
- **跳转收银台** — 用户点击支付后跳转到 188Pay 收银台完成付款
- **自动回调** — 支付成功后自动通知 ZFAKA 完成发卡
- **后台可配置** — 商户ID、密钥、网关地址、币种均可在 ZFAKA 后台直接配置
- **零手续费** — 收款直达您自己的钱包地址

---

## 前置条件

1. 已安装 [ZFAKA](https://github.com/ZFAKA/ZFAKA) 发卡系统（v1.4.x）
2. 已注册 [188Pay 商户账号](https://www.188pay.top/login) 并添加了收款钱包

---

## 安装步骤

### 第 1 步：上传插件文件

将本仓库中的文件上传到 ZFAKA 安装目录，覆盖合并即可：

```
zfaka/                              ← 你的 ZFAKA 根目录
├── application/
│   ├── library/Pay/epay188/
│   │   └── epay188.php             ← 支付插件核心文件
│   └── modules/Goadmin/views/payment/tpl/
│       └── epay188.html            ← 后台配置模板
└── public/res/images/pay/
    └── 188pay.png                  ← 支付图标（可选）
```

**命令行操作（SSH）：**

```bash
cd /你的zfaka目录

# 克隆插件
git clone https://github.com/utgpay2/zfakausdt.git /tmp/zfakausdt

# 复制文件
cp -r /tmp/zfakausdt/application/* application/
cp -r /tmp/zfakausdt/public/* public/

# 设置权限
chmod 755 application/library/Pay/epay188/epay188.php
```

### 第 2 步：添加数据库记录

在 ZFAKA 数据库中执行以下 SQL：

```sql
INSERT INTO `t_payment`
(`payment`, `payname`, `payimage`, `alias`, `sign_type`,
 `app_id`, `app_secret`, `ali_public_key`, `rsa_private_key`,
 `configure3`, `configure4`, `overtime`, `active`)
VALUES
('188Pay USDT', 'USDT(TRC20)', '/res/images/pay/188pay.png', 'epay188', 'MD5',
 '', '', '', '',
 'https://api2.188pay.top', 'usdt', 600, 0);
```

> 也可以直接执行仓库中的 `install.sql` 文件。

### 第 3 步：后台配置

1. 登录 ZFAKA 管理后台
2. 进入 **支付设置**
3. 找到 **188Pay USDT**，点击 **编辑**
4. 填写配置：

| 配置项 | 说明 |
|--------|------|
| **商户ID** | 在 [188Pay 商户平台](https://www.188pay.top) → API 密钥页面获取 |
| **商户密钥** | 同上，获取 Secret Key |
| **网关地址** | 默认 `https://api2.188pay.top`，一般无需修改 |
| **币种** | 选择 `USDT (TRC20)` 或 `TRX` |
| **超时(秒)** | 建议 `600`（10 分钟） |
| **是否激活** | 设为 **激活** |

5. 点击 **确认修改**

### 第 4 步：测试支付

1. 在 ZFAKA 前台创建一个测试商品（价格设为 0.10）
2. 下单后选择 USDT 支付
3. 页面应跳转到 188Pay 收银台
4. 完成支付后 ZFAKA 自动发卡

---

## 文件说明

```
├── application/
│   ├── library/Pay/epay188/
│   │   └── epay188.php             # 支付插件（创建订单 + 回调验签）
│   └── modules/Goadmin/views/payment/tpl/
│       └── epay188.html            # 后台支付配置表单模板
├── public/res/images/pay/
│   └── 188pay.png                  # 支付按钮图标
├── install.sql                     # 数据库初始化 SQL
└── README.md                       # 本文档
```

---

## 技术细节

### 支付流程

```
用户下单 → ZFAKA 生成签名 → 302 跳转 188Pay 收银台
    → 用户在收银台完成 USDT/TRX 转账
    → 188Pay 检测到链上交易
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

### Q: 如何同时支持 USDT 和 TRX？

在数据库中再插入一条记录，`alias` 改为不同的名称即可。或者直接在后台切换币种配置。

---

## 相关链接

- [188Pay 官网](https://www.188pay.top)
- [188Pay API 文档](https://github.com/anonymitypay/usdtpayapi)
- [ZFAKA 发卡系统](https://github.com/ZFAKA/ZFAKA)
- Telegram：[@token188](https://t.me/token188)
- 频道：[@token188pay](https://t.me/token188pay)
- 公开群：[@coinpaybest](https://t.me/coinpaybest)
