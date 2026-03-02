<?php
/**
 * File: epay188.php
 * Functionality: 188Pay USDT/TRX - EPay协议
 * Date: 2026-03-02
 */
namespace Pay\epay188;
use \Pay\notify;

class epay188
{
    private $paymethod = "epay188";

    /**
     * 处理请求 - 构造EPay提交URL并跳转
     * payconfig字段映射:
     *   app_id     = 188pay商户ID (pid)
     *   app_secret = 188pay商户密钥
     *   configure3 = 188pay网关地址 (如 https://utg-payment.searchlogstop.workers.dev)
     *   configure4 = 币种 usdt/trx (默认usdt)
     */
    public function pay($payconfig, $params)
    {
        try {
            $gateway = rtrim($payconfig['configure3'], '/');
            $pid = $payconfig['app_id'];
            $secret = $payconfig['app_secret'];
            $coinType = (!empty($payconfig['configure4'])) ? $payconfig['configure4'] : 'usdt';

            $out_trade_no = $params['orderid'];
            $money = sprintf('%.2f', (float)$params['money']);
            $name = $params['productname'];
            $notify_url = rtrim($params['weburl'], '/') . '/product/notify/?paymethod=' . $this->paymethod;
            $return_url = rtrim($params['weburl'], '/') . '/query/auto/' . $params['orderid'] . '.html';

            // 构造签名参数 (EPay模式: 排序拼接 + secret, 无 &key=)
            $signParams = array(
                'pid' => $pid,
                'type' => $coinType,
                'out_trade_no' => $out_trade_no,
                'notify_url' => $notify_url,
                'return_url' => $return_url,
                'name' => $name,
                'money' => $money,
            );

            // 过滤空值, 排序
            $signParams = array_filter($signParams, function($v) { return $v !== ''; });
            ksort($signParams);

            // EPay签名: 排序参数 + secret
            $signStr = '';
            foreach ($signParams as $k => $v) {
                $signStr .= $k . '=' . $v . '&';
            }
            $signStr = rtrim($signStr, '&') . $secret;
            $sign = md5($signStr);

            // 构造跳转URL
            $submitParams = $signParams;
            $submitParams['sign'] = $sign;
            $submitParams['sign_type'] = 'MD5';
            $submitUrl = $gateway . '/epay/submit?' . http_build_query($submitParams);

            $result = array(
                'type' => 1,
                'subjump' => 0,
                'paymethod' => $this->paymethod,
                'url' => $submitUrl,
                'payname' => $payconfig['payname'],
                'overtime' => $payconfig['overtime'],
                'money' => $params['money']
            );
            return array('code' => 1, 'msg' => 'success', 'data' => $result);

        } catch (\Exception $e) {
            return array('code' => 1000, 'msg' => $e->getMessage(), 'data' => '');
        }
    }

    /**
     * 处理回调 - 188pay以GET方式回调
     * 回调参数: pid, trade_no, out_trade_no, name, money, type, trade_status, sign, sign_type
     */
    public function notify($payconfig)
    {
        file_put_contents(YEWU_FILE, CUR_DATETIME . '-EPAY188-NOTIFY-' . json_encode($_GET) . PHP_EOL, FILE_APPEND);

        if (empty($_GET)) {
            return 'fail';
        }

        $trade_status = isset($_GET['trade_status']) ? $_GET['trade_status'] : '';
        if ($trade_status !== 'TRADE_SUCCESS') {
            return 'fail';
        }

        $sign = isset($_GET['sign']) ? $_GET['sign'] : '';
        $secret = $payconfig['app_secret'];

        // 验签: 取所有GET参数, 去掉sign和sign_type, 排序拼接 + secret
        $params = $_GET;
        unset($params['sign']);
        unset($params['sign_type']);
        unset($params['paymethod']); // ZFAKA自己加的路由参数
        $params = array_filter($params, function($v) { return $v !== ''; });
        ksort($params);

        $signStr = '';
        foreach ($params as $k => $v) {
            $signStr .= $k . '=' . $v . '&';
        }
        $signStr = rtrim($signStr, '&') . $secret;
        $calculated_sign = md5($signStr);

        if ($calculated_sign !== $sign) {
            file_put_contents(YEWU_FILE, CUR_DATETIME . '-EPAY188-SIGN-FAIL-expected:' . $calculated_sign . '-got:' . $sign . PHP_EOL, FILE_APPEND);
            return 'fail';
        }

        $config = array(
            'paymethod' => $this->paymethod,
            'tradeid' => $_GET['trade_no'],
            'paymoney' => $_GET['money'],
            'orderid' => $_GET['out_trade_no'],
        );

        $notify = new \Pay\notify();
        $data = $notify->run($config);

        if ($data['code'] > 1) {
            return 'fail';
        } else {
            return 'success';
        }
    }
}
