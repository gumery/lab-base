<?php

namespace Gini\Controller\CGI;

class User extends \Gini\Controller\CGI\Rest\Base
{
    // 获取登录信息接口
    public function getSession()
    {
        $session_id = session_id();

        list($loginURL, $logoutURL) = self::getLoginURL();
        $data = [
            'session_id' => $session_id,
            'url'=> ['login_in'=> $loginURL, 'login_out'=> $logoutURL]
        ];
	if (!_G('ME')->id || !_G('GROUP')->id) {
		$code = 499;
	} else {
		$code = 200;
	}
        $response = $this->response($code, null, $data);
        return \Gini\IoC::construct('\Gini\CGI\Response\Json', $response);
    }
}
