#  ZFAKA发卡USDT实时到账支付插件Token188版本 
### 网站配置
 - 下载SDK并解压到zfaka代码目录。
 - 进入帐户中心当前站点 修改成你自己的地址 不然有问题
 - 进入数据库并执行以下SQL语句
 ```
 INSERT INTO t_payment
(id, payment, payname, payimage, alias, sign_type,
app_id, app_secret, ali_public_key, rsa_private_key,
configure3, configure4, overtime, active)
VALUES
(NULL, 'Token188', 'USDT付款',
'/res/images/pay/token188.png', 'Token188', 'MD5',
'', '', '', '','https://api.token188.com/utg/pay/address', '', '0', '0');
```

### 进入管理界面，激活Token188 USDT支付
 - 设置中心 > 支付设置 > Token188 > 编辑
 - app_id	商户ID
 - 密钥	商户密钥
 - 是否激活	激活

 - 密钥, app_id  请到[TOKEN188](https://www.token188.com/) 官网注册获取.

### 产品介绍

 - [TOKEN188 USDT支付平台主页](https://www.token188.com)
 - [TOKEN188钱包](https://www.token188.com)（即将推出）
 - [商户平台](https://www.token188.com/manager)
### 特点
 - 使用您自己的USDT地址收款没有中间商
 - 五分钟完成对接
 - 没有任何支付手续费

## 安装流程
1. 注册[TOKEN188商户中心](https://www.token188.com/manager)
2. 在商户中心添加需要监听的地址
3. 根据使用的不同面板进行回调设置(回调地址可以不填)


## 有问题和合作可以小飞机联系我们
 - telegram：@token188
