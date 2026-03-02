-- 188Pay ZFAKA 支付插件 - 数据库初始化
-- 在 ZFAKA 数据库中执行此 SQL 即可添加 188Pay 支付渠道

INSERT INTO `t_payment`
(`payment`, `payname`, `payimage`, `alias`, `sign_type`,
 `app_id`, `app_secret`, `ali_public_key`, `rsa_private_key`,
 `configure3`, `configure4`, `overtime`, `active`)
VALUES
('188Pay USDT', 'USDT(TRC20)', '/res/images/pay/188pay.png', 'epay188', 'MD5',
 '', '', '', '',
 'https://api2.188pay.top', 'usdt', 600, 0);
