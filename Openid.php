<?php
      
       $appid = "wx0864ef93138500ac";
        $secret = "7a8f47fa3d23565c8ac142ee0aa4abac";
	
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$secret."&js_code=".$_POST['code']."&grant_type=authorization_code";
 
       $postUrl = $url;
     
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);//运行curl

        curl_close($ch);
     //  var_dump($data);
       print_r($data);
	  