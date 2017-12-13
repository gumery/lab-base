<?php

namespace Gini\Module;

class LabBase
{
    public static function setup()
    {
    }

    public static function getFEUrl($path, $justPath = false)
    {
        $feURL = \Gini\Config::get('app.lab-fe-url');
        if (!$feURL || $feURL=='${LAB_FE_URL}') {
            return $path;
        }

        $url = rtrim("{$feURL}/{$path}", '/');
	if (!$justPath) return $url;
	$uri = parse_url($url);
	return $uri['path'];
    }

    public static function getRedirectUrl($path)
    {
	$app = \Gini\Gapper\Client::getInfo();
	$url = $app['url'] . "/" . $path;
        $me = _G('ME');
        if ($me->id) {
            $gapperToken = \Gini\Gapper\Client::getLoginToken(\Gini\Gapper\Client::getId());
            if ($gapperToken) {
                $group = _G('GROUP');
                $url = \Gini\URI::url($app['url']."/gapper/client/login", [
                    'gapper-token'=> $gapperToken,
                    'gapper-group'=> $group->id,
                    'redirect'=> $url
                ]);
            }
        }

        return $url;
    }
}

