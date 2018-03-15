<?php

namespace Gini\Controller\CGI;

class User extends \Gini\Controller\CGI\Rest\Base
{
    // 获取登录信息接口
    public function getSession()
    {
        // 获取 登录 登出 url
        list($loginURL, $logoutURL) = self::getLoginURL();

        // 获取 用户 信息
        $userInfo = self::_getUserInfo();

        $data = [
            'session_id' => session_id(),
            'url'        => [
                'login'  => $loginURL,
                'logout' => $logoutURL
            ],
            'user'       => [
                'name'         => $userInfo['name'],
                'icon_type'    => $userInfo['iconType'],
                'icon_content' => $userInfo['iconContent']
            ]
        ];

        $code = $this->isLogin() ? 200 : 499;
        $response = $this->response($code, null, $data);
        return \Gini\IoC::construct('\Gini\CGI\Response\Json', $response);
    }

    // 获取用户信息
    private static function _getUserInfo()
    {
        $userInfo = [];

        $me = _G('ME');
        if (!$me->id) {
            return $userInfo;
        }

        // 用户头像
        $icon = $me->icon();
        if (parse_url($icon)['scheme'] == 'initials') {
            $iconContent    = $me -> initials;
            $iconType       = 'text';
        } else {
            $iconContent = $me -> icon(72);
            $iconType    = 'img';
        }

        $userInfo['iconType']       = $iconType;
        $userInfo['iconContent']    = $iconContent;
        $userInfo['name']           = $me->name;

        return $userInfo;
    }
}
