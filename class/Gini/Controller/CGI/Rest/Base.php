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
        // 验证用户 是不是需要做缓存，还得考虑考虑
		$token = $_SERVER['HTTP_X_GINI_SESSION'];
        $this->_verify($token);

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

    private function _verify($token)
	{
		$conf = \Gini\Config::get('gapper.rpc');
        $rpc = self::getRPC();
        if ($rpc) {
			\Gini\Gapper\Client::loginByToken($token);
		}
	}

    private static $_RPC;
    public static function getRPC()
    {
        if (self::$_RPC) return self::$_RPC;

        $config = (array) \Gini\Config::get('gapper.rpc');
        $api = $config['url'];
        $client_id = $config['client_id'];
        $client_secret = $config['client_secret'];
        $cacheKey = "app#client#{$client_id}#session_id";
        $token = self::_cache($cacheKey);
        $rpc = \Gini\IoC::construct('\Gini\RPC', $api);
        if ($token) {
            $rpc->setHeader(['X-Gini-Session' => $token]);
        } else {
            $token = $rpc->gapper->app->authorize($client_id, $client_secret);
            if (!$token) {
                \Gini\Logger::of('gapper')->error('Your app was not registered in gapper server!');
            } else {
                self::_cache($cacheKey, $token, 600);
                self::$_RPC = $rpc;
            }
        }

        return $rpc;
    }

    // 缓存设置
    private static function _cache($key, $value=false, $ttl=300) {
        $cacher = \Gini\Cache::of('gapper');
        if (false === $value) {
            return $cacher->get($key);
        }
        $cacher->set($key, $value, $ttl);
    }

    // lab-* 应用 侧边栏顶边栏信息获取接口
    public function getHomeInfo()
    {
        $me = _G('ME');
        $group = _G('GROUP');

        // 获取显示信息
        $info = \Gini\Config::get('sidebar') ?: [];

        //是否登录
        $isLogin = (!$me->id || !$group->id) ? false : true;
        if ($isLogin) {
            // 获取当前应用的 gapper_id
            $currentID = \Gini\Gapper\Client::getId();

            // 获取 有二级菜单的 nav, 拿到所有有二级菜单的 gapper_id
            $subs = $info['subs'] ?: [];

            // 获取该课题组的所有应用
            $groupApps = (array) $group->getApps();
            // 设置侧边栏
            $bar = [];
            foreach ($groupApps as $clientID => $app) {
                // 设置 nav
                $bar = [
                    'icon'          => $app['font_icon'],
                    'title'         => $app['short_title'] ?: $app['title'],
                    'url'           => ($clientID === $currentID) ? '/' : $app['url'],
                    'is_selected'   => ($clientID === $currentID) ? true : false,
                    'sub'           => []
                ];

                // 如果该应用 有二级菜单 设置二级菜单
                if (array_key_exists($clientID, $subs)) {
                    $bar['sub'] = $subs[$clientID];
                }

                $sidebar[] = $bar;
            }
            // 侧边栏
            $data['sidebar'] = $sidebar ?: [];

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
            \Gini\Event::trigger('header.items', $items);
            $data['message'] = [
                'isShow' => $items['message'] ? true : false,
                'count'  => $items['message'] ?: 0
            ];

            $data['cart'] = [
                'isShow' => $items['cart'] ? true : false,
                'count'  => $items['cart'] ?: 0
            ];

            $data['help'] = [
                'isShow' => $items['help'] ? true : false,
                'url'  => $items['help'] ?: ''
            ];

            $data['set'] = $items['setMenu'];
        }

        // 登录状态
        $data['is_login'] = [
            'status' => $isLogin,
            'redirect' => true,
            'url'    => $isLogin ? '' : \Gini\URI::base() . 'gapper/client/login'
        ];

        // 商城信息
        $data['link_index'] = [
            'title' => $info['link']['title'],
            'url'   => $info['link']['url'],
        ];

        // 二维码是否显示
        $showQRCode = \Gini\Config::get('app.show_sidebar_qrcode');
        if ($showQRCode) {
            $data['qrcode_img'] = \Gini\URI::base() . 'assets/img/sidebar-code.png' ?: '';
        }

        // 客服电话是否显示
        $showSPhone = \Gini\Config::get('app.show_sidebar_service_phone');
        if ($showSPhone) {
            $data['tel_number'] = \Gini\Config::get('app.service_phone') ?: '';
        }

        $response = $this->response(200, null, $data);
        return \Gini\IoC::construct('\Gini\CGI\Response\JSON', $response);
    }
}
