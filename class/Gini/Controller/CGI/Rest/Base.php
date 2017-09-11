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
        // 验证用户
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
            'msg'   => $msg ?: $this->messge[$code],
            'list'  => $data
        ];

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

    public function getHomeInfo()
    {

    }
}
