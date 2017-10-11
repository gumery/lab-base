<?php

namespace Gini\Controller\CGI;

class User extends \Gini\Controller\CGI\Rest\Base
{
    // 获取登录信息接口
    public function getSession()
    {
        $response = $this->response(400);
        $me = _G('ME');
        $group = _G('GROUP');
        $session_id = session_id();

        if (!$group->id || !$me->id || !$session_id) {
            $response = $this->response(499);
            goto response;
        }

        $data = [
            'session_id' => $session_id
        ];

        $response = $this->response(200, null, $data);

        response:
        return \Gini\IoC::construct('\Gini\CGI\Response\Json', $response);
    }
}
