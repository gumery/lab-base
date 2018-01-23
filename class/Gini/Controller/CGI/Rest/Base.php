<?php

namespace Gini\Controller\CGI\Rest;

class Base extends \Gini\Controller\REST
{
    protected $form;
    protected $method;

    private $messge = [
        '400' => 'Bad Request',
        '404' => 'Not Found',
        '500' => 'Internal Server Error',
        '200' => 'OK'
    ];

    function __preAction($action, &$params)
    {
        // 获取 form
        $this->method = strtolower($this->env['method']);
        switch ($this->method) {
            case 'get':
                $form = $this->form('get');
                break;
            case 'post':
                $form = $this->form('post');
                break;
            case 'patch':
            case 'delete':
            case 'put':
                if ($this->form('put')) {
                    $form = $this->form('put');
                } else {
                    $content = file_get_contents('php://input');
                    $form = json_decode($content, true);
                    if (!$form) {
                        $form = [];
                        parse_str($content, $form);
                    }
                }
                break;
        }

        $this->form = $form;
    }

    protected function response($code = 400, $msg = '', $data = [])
    {
        $response = [
            'code'  => $code,
            'msg'   => $msg ?: $this->messge[$code]
        ];
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }

        return $response;
    }

    // lab-* 应用 侧边栏顶边栏信息获取接口
    public function getHomeInfo()
    {
        $me = _G('ME');
        $group = _G('GROUP');

        // 获取显示信息
        $info = \Gini\Config::get('sidebar') ?: [];

        $appInfo = \Gini\Gapper\Client::getInfo();

        //是否登录
        $isLogin = (!$me->id || !$group->id) ? false : true;
        if ($isLogin) {
            // 侧边栏
            $data['sidebar'] = self::getSidebarLinks();

            // 获取 用户头像及相关信息
            $icon = $me->icon();
            if (parse_url($icon)['scheme'] == 'initials') {
                $iconContent    = $me -> initials;
                $iconType       = 'text';
            } else {
                $iconContent = $me -> icon(72);
                $iconType    = 'img';
            }
            $data['user'] = [
                'icon_content'  =>  $iconContent,
                'icon_type'     =>  $iconType,
                'name'          =>  $me->name,
                'group'         =>  $group->title
            ];


            // 获取顶部菜单可显示选项
            $items =  \Gini\Event::trigger('header.items');
            $data['message'] = [
                'isShow' => $items['message'] ? true : false,
                'count'  => $items['message'] ?: 0
            ];

            $data['bucket'] = [
                'count' => $items['bucket'] ?: 0
            ];

            $data['cart'] = [
                'isShow' => true,
                'count'  => $items['cart'] ?: 0,
                'add_customized_url' => "{$appInfo['url']}/cart/customized"
            ];

            $data['help'] = [
                'isShow' => $items['help'] ? true : false,
                'url'  => $items['help'] ?: ''
            ];

            $data['set'] = $items['setMenu'];

            // 获取定制的一些顶部元素
            $data['extra_item'] = \Gini\Event::trigger('header.node-item');
        }

        // 登录状态
        list($loginURL, $logoutURL) = self::getLoginURL();
        $data['is_login'] = [
            'status' => $isLogin,
            'redirect' => true,
            'url'    => $isLogin ? '' : $loginURL
        ];

        // 商城信息
        $data['link_index'] = [
            'title' => $info['link']['title'],
            'url'   => self::_getHomeURL() ?: $info['link']['url'],
        ];

        // 二维码是否显示
        $showQRCode = \Gini\Config::get('app.show_sidebar_qrcode');
        if ($showQRCode) {
            $data['qrcode_img'] = "{$appInfo['url']}/assets/img/sidebar-code.png";
        }

        // 客服电话是否显示
        $showSPhone = \Gini\Config::get('app.show_sidebar_service_phone');
        if ($showSPhone) {
            $data['tel_number'] = \Gini\Config::get('app.service_phone') ?: '';
        }

        $response = $this->response(200, null, $data);
        return \Gini\IoC::construct('\Gini\CGI\Response\JSON', $response);
    }

    protected static function getLoginURL()
    {
        $apps = self::getApps();
        foreach ($apps as $clientID=>$info) {
            if (($_GET['x-gini-current-module']==$info['module_name'])) {
                $realApp = \Gini\Gapper\Client::getInfo($clientID);
                break;
            }
        }
        if (!$realApp) $realApp = \Gini\Gapper\Client::getInfo();
        return [
            "{$realApp['url']}/gapper/client/login",
            \Gini\Module\LabBase::getFEUrl("logout"),
        ];
    }

    private static function getSidebarLinks()
    {
        $groupApps = self::getApps();
        $me = _G('ME');
        $group = _G('GROUP');
        $result = [];
        $info = \Gini\Config::get('sidebar') ?: [];
        $subs = $info['subs'] ?: [];
        foreach($subs as $client_id => $sub) {
            foreach($sub as $id => $item) {
                $subs[$client_id][$id]['url'] = self::_getFEURL($item['url'], $client_id);
                if (!$me->isAllowedTo('订单支付', 'order') && $item['title'] == '付款管理'){
                    unset($subs[$client_id][$id]);
                }
            }
        }
        foreach ($groupApps as $clientID => $app) {
            $shortURL = self::_getModuleURL($app['module_name'], $clientID);
            $result[] = [
                'icon'          => $app['font_icon'],
                'title'         => $app['short_title'] ?: $app['title'],
                'url'           => $shortURL ?: (($_GET['x-gini-current-module']==$app['module_name']) ? $app['url'] : "{$app['url']}/gapper/client/go/{$clientID}/{$group->id}"),
                'is_selected'   => ($_GET['x-gini-current-module']==$app['module_name']) ? true : false,
                'sub'           => @$subs[$clientID] ?: []
            ];
        }
        return $result;
    }

    private static $_apps = [];
    protected static function getApps()
    {
        if (!empty(self::$_apps)) return self::$_apps;
        $me = _G('ME');
        $group = _G('GROUP');
        $apps = (array) $group->getApps();
        $alloweds = \Gini\Config::get('sidebar.apps') ?: [];
        foreach ($alloweds as $clientID=>$actions) {
            $actions = (array) $actions;
            if (!isset($apps[$clientID])) continue;
            foreach ($actions as $action) {
                if ($me->isAllowedTo($action)) {
                    continue 2;
                }
            }
            unset($apps[$clientID]);
        }
        uasort($apps, function($a, $b) {
            $ra = $a['rate'];
            $rb = $b['rate'];
            if ($ra==$rb) return 0;
            return ($ra>$rb) ? -1 : 1;
        });

        self::$_apps = $apps;
        return $apps;
    }

    private static function _getHomeURL()
    {
        if (\Gini\Gapper\Client::getLoginStep() !== \Gini\Gapper\Client::STEP_DONE) return;
        $client_id = \Gini\Config::get('gapper.home_app_client');
        $app = \Gini\Gapper\Client::getInfo($client_id);
        if (!$app['url']) return;
        $username = \Gini\Gapper\Client::getUserName();
        if (!$username) return;
        $token = \Gini\Gapper\Client::getLoginToken($client_id, $username);
        if (!$token) return;
        $url = $app['url'];
        $confs = \Gini\Config::get('gapper.proxy');
        foreach ((array)$confs as $conf) {
            if ($url==$conf['url']) {
                $url = $conf['proxy'] ?: $url;
                break;
            }
        }
        $url = \Gini\URI::url($url, 'gapper-token='.$token);
        $group = _G('GROUP');
        if ($group->id) {
            $url = \Gini\URI::url($url, 'gapper-group='.$group->id);
        }
        return $url;
    }

    private static function _getFEURL($url, $clientID)
    {
        $uri = \parse_url($url);
        if (!isset($uri['host'])) return $url;
        $currentInfo = \Gini\Gapper\Client::getInfo();
        $currentURL = $currentInfo['url'];
        $currentURI = \parse_url($currentURL);
        if ($currentURI['host']==$uri['host']) return $url;
        $scheme = $uri['scheme'] ?: 'http';
        $group = _G('GROUP');
        $result = "/gapper/client/go/{$clientID}/{$group->id}";
        return \Gini\URI::url($result, [
            'redirect'=> $url ?: '/'
        ]);
    }

    private static function _getModuleURL($module, $clientID)
    {
        $mtps = (array) \Gini\Config::get('sidebar.module-to-path');
        if (isset($mtps[$module]) && $mtps[$module]) {
            //return '/'.\Gini\Module\LabBase::getFEUrl($mtps[$module]);
            return "{$mtps[$module]}";
        }
        return \Gini\Module\LabBase::getRedirectUrl('', $clientID);
    }
}
