<?php

namespace Pay\Token188;

use \Payment\Client\Charge;
use \Payment\Common\PayException;
use \Payment\Client\Notify;
use \Payment\Config;

use \Pay\Token188\callback;

class Token188
{
	private $paymethod ="Token188";

	protected function getSDK($payconfig) {
	    $config = [
			'token188_mchid' => $payconfig['app_id'],
			'token188_key' => $payconfig['app_secret'],
			'token188_url' => $payconfig['configure3'],
		];
		return new Token188SDK($config);
	}

	//处理请求
	public function pay($payconfig,$params)
	{
	    
		$token188 = $this->getSDK($payconfig);

		try {
		    $res = $token188->pay([
		        'trade_no'      => $params['orderid'],
                'total_fee'     => $params['money'],
                'notify_url'    => $params['weburl'] . '/product/notify/?paymethod='.$this->paymethod,
            ]);
			$data = [
			    'type'           => 1,
			    'subjump'        => 0,
			    'subjumpurl'     => $res,
			    'url'            => $res,
			    'paymethod'      => $this->paymethod,
			    'qr'             => $params['qrserver'] . urlencode($res),
			    'payname'        => $payconfig['payname'],
			    'overtime'       => $payconfig['overtime'],
			    'money'          => $params['money'],
			];
			return [
			    'code' => 1,
			    'msg'  => 'success',
			    'data' => $data,
			];
		} catch (\Exception $e) {
			return [
			    'code' => 1000,
			    'msg'  => $e->getMessage(),
			    'data' => '',
            ];
		}
	}

	public function notify(array $payconfig)
	{
	    $token188 = $this->getSDK($payconfig);

	    $inputString = file_get_contents('php://input', 'r');
        $inputStripped = str_replace(array("\r", "\n", "\t", "\v"), '', $inputString);
        $params = json_decode($inputStripped, true); //convert JSON into array
        file_put_contents(YEWU_FILE, CUR_DATETIME.'-'.$inputString.PHP_EOL, FILE_APPEND);

        if (!$token188->verify($params)) {
			return 'error|Notify: auth fail';
		}

		$orderid = $params['outTradeNo'];
		$order = (\Helper::load('order'))->Where(['orderid'=>$orderid])->SelectOne();
		if (empty($order)) {
		    return 'error|Notify: fail to load order';
		}

		// 业务处理
		$data = (new \Pay\notify())->run([
		    'paymethod' => $this->paymethod,
		    'tradeid'   => $params['tradeNo'],
		    'paymoney'  => $order['money'],
		    'orderid'   => $orderid,
		]);
		if ($data['code']>1) {
			return 'error|Notify: '.$data['msg'];
		}
        
		return 'success';
	}
}
