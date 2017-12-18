<?php

namespace Gini\Module;

class LabBase
{
    public static function setup()
    {
    }

    public static function getFEUrl($path, $justPath = false, $withLoginInfo=false)
    {
        $feURL = \Gini\Config::get('app.lab-fe-url');
        if (!$feURL || $feURL=='${LAB_FE_URL}') {
            return $path;
        }

        $url = rtrim("{$feURL}/{$path}", '/');
        if (!$justPath) {
            if ($withLoginInfo) {
                $me = _G('ME');
                if ($me->id) {
                    $clientID = \Gini\Config::get('app.lab-fe-app-client-id');
                    $gapperToken = \Gini\Gapper\Client::getLoginToken($clientID);
                    if ($gapperToken) {
			$app = \Gini\Gapper\Client::getInfo($clientID);
			$feHost = parse_url($feURL)['host'];
			$appHost = parse_url($app['url'])['host'];
			$tmpURL = $feHost==$appHost ? $app['url'] : $feURL;
                        $group = _G('GROUP');
                        $url = \Gini\URI::url($tmpURL."/gapper/client/login", [
                            'gapper-token'=> $gapperToken,
                            'gapper-group'=> $group->id,
                            'redirect'=> $url
                        ]);
                    }
                }
            }
            return $url;
        }
        $uri = parse_url($url);
        return $uri['path'];
    }

    public static function getRedirectUrl($path, $clientID=null)
    {
        $app = \Gini\Gapper\Client::getInfo();
        $clientID = $clientID?:\Gini\Gapper\Client::getId();
        $to = \Gini\Gapper\Client::getInfo($clientID);
        $url = $to['url'] . "/" . ltrim($path, '/');

        $result = "gapper/client/go/{$clientID}";
        $group = _G('GROUP');
        if ($group->id) {
            $result .= "/{$group->id}";
        }

/*
        if ($justPath) {
            return "/".$result . '?' . http_build_query([
                'redirect'=> $url
            ]);
        }
*/
        $feURL = \Gini\Config::get('app.lab-fe-url');
	$feHost = parse_url($feURL)['host'];
	$appHost = parse_url($app['url'])['host'];
	$tmpURL = $feHost==$appHost ? $app['url'] : $feURL;

        return \Gini\URI::url("{$tmpURL}/{$result}", [
            'redirect'=> $url
        ]);
    }
}
