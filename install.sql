-- 188Pay ZFAKA 支付插件 - 数据库初始化
-- 在 ZFAKA 数据库中执行此 SQL 即可添加 188Pay 支付渠道
-- 注意: alias 不能含下划线, Yaf 自动加载器会把 _ 当成目录分隔符 (PSR-0)

-- USDT/TRX 加密货币通道
INSERT INTO `t_payment`
(`payment`, `payname`, `payimage`, `alias`, `sign_type`,
 `app_id`, `app_secret`, `ali_public_key`, `rsa_private_key`,
 `configure3`, `configure4`, `overtime`, `active`)
VALUES
('188Pay', '188Pay USDT/TRX', '/res/images/pay/188pay.png', 'epay188', 'MD5',
 '', '', '', '',
 'https://api2.188pay.top', 'usdt', 600, 0);

-- 支付宝法币通道
INSERT INTO `t_payment`
(`payment`, `payname`, `payimage`, `alias`, `sign_type`,
 `app_id`, `app_secret`, `ali_public_key`, `rsa_private_key`,
 `configure3`, `configure4`, `overtime`, `active`)
VALUES
('188Pay', '188Pay 支付宝', '/res/images/pay/188pay.png', 'epay188alipay', 'MD5',
 '', '', '', '',
 'https://api2.188pay.top', 'alipay', 600, 0);
