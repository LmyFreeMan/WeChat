<?php

error_reporting(E_ERROR); 

ini_set("display_errors","Off");

$openid = $_POST['openid'];
$total_fee = $_POST['total_fee'];

//参数配置
$mch_id = '1512000501';
$mch_key = '1234567890987654321abcdefghijklm';
$appid = 'wx01c675d8e1f68fc5';
$secret = 'f9d421d0c91b8c238c1f0369b75326d0';


if (is_null($openid) || is_null($total_fee)) {
    print_r('数据不合法');
    return false;
} else {
    WxPay($openid, $total_fee, '');
}

function WxPay($openid, $total_fee, $body)
{
    if (!$openid) {
        print_r('openid不能为空');
    }
    if ($total_fee < 0.01) {
        print_r('付款金额最低0.01');
    }
    if (!$total_fee) {
        print_r('付款金额不能为空');
    }
    $body = '商品充值';
    $out_trade_no = date("YmdHis");
    $chars = '0123456789';
    $max = strlen($chars) - 1;
    PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
    for ($i = 0; $i < 4; $i++) {
        $out_trade_no .= $chars[mt_rand(0, $max)];
    }

    $GLOBALS['out_trade_no'] = $out_trade_no;
    $GLOBALS['openid'] = $openid;
    $GLOBALS['body'] = $body;
//        统一下单接口
    $res = weixinapp();
    print_r(json_encode($res));
//    return $res;
}


//统一下单接口
function unifiedorder()
{
    $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    $parameters = array(
        'appid' => $GLOBALS['appid'], //小程序ID
        'mch_id' => $GLOBALS['mch_id'], //商户号
        'nonce_str' => createNoncestr(), //随机字符串
        'body' => $GLOBALS['body'],//商品描述
        'out_trade_no' => $GLOBALS['out_trade_no'],//商户订单号
        'total_fee' => floatval($GLOBALS['total_fee'] * 100),//总金额 单位 分
        'spbill_create_ip' => $_SERVER['REMOTE_ADDR'], //终端IP
        'notify_url' => 'http://www.weixin.qq.com/wxpay/pay.php', //通知地址  确保外网能正常访问
        'openid' => $GLOBALS['openid'], //用户id
        'trade_type' => 'JSAPI'//交易类型
    );
    //统一下单签名
    $parameters['sign'] = getSign($parameters);
    $xmlData = arrayToXml($parameters);
    $xml = postXmlCurl($xmlData, $url, 60);
    $return = xmlToArray($xml);
    return $return;
}

function postXmlCurl($xml, $url, $second = 30)
{
    $ch = curl_init();
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $second);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //严格校验
    //设置header
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    //要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    //post提交方式
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);
    set_time_limit(0);
    //运行curl
    $data = curl_exec($ch);
    //返回结果
    if ($data) {
        curl_close($ch);
        return $data;
    } else {
        $error = curl_errno($ch);
        curl_close($ch);
        print_r('curl出错');
    }
}

//数组转换成xml
function arrayToXml($arr)
{
    $xml = "<root>";
    foreach ($arr as $key => $val) {
        if (is_array($val)) {
            $xml .= "<" . $key . ">" . arrayToXml($val) . "</" . $key . ">";
        } else {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        }
    }
    $xml .= "</root>";
    return $xml;
}

//xml转换成数组
function xmlToArray($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    $val = json_decode(json_encode($xmlstring), true);
    return $val;
}

//微信小程序接口
function weixinapp()
{
    //统一下单接口
    $unifiedorder = unifiedorder();
    $parameters = array(
        'appId' => $GLOBALS['appid'], //小程序ID
        'timeStamp' => '' . time() . '', //时间戳
        'nonceStr' => createNoncestr(), //随机串
        'package' => 'prepay_id=' . $unifiedorder['prepay_id'], //数据包
        'signType' => 'MD5'//签名方式
    );
    //签名
    $parameters['paySign'] = getSign($parameters);

    return $parameters;
}

//作用：产生随机字符串，不长于32位
function createNoncestr($length = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

//作用：生成签名
function getSign($Obj)
{
    foreach ($Obj as $k => $v) {
        $Parameters[$k] = $v;
    }
    //签名步骤一：按字典序排序参数
    ksort($Parameters);
    $String = formatBizQueryParaMap($Parameters, false);
    //签名步骤二：在string后加入KEY
    $String = $String . "&key=" . $GLOBALS['mch_key'];
    //签名步骤三：MD5加密
    $String = md5($String);
    //签名步骤四：所有字符转为大写
    $result_ = strtoupper($String);
    return $result_;
}

///作用：格式化参数，签名过程需要使用
function formatBizQueryParaMap($paraMap, $urlencode)
{
    $buff = "";
    ksort($paraMap);
    foreach ($paraMap as $k => $v) {
        if ($urlencode) {
            $v = urlencode($v);
        }
        $buff .= $k . "=" . $v . "&";
    }
    if (strlen($buff) > 0) {
        $reqPar = substr($buff, 0, strlen($buff) - 1);
    }
    return $reqPar;
}
?>