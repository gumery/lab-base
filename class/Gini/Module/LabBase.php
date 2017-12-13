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

    public static function getRedirectUrl($path, $clientID=null)
    {
	$app = \Gini\Gapper\Client::getInfo();
	$url = $app['url'] . "/" . ltrim($path, '/');
        $clientID = $clientID?:\Gini\Gapper\Client::getId();
        $me = _G('ME');
        $result = "gapper/client/go/{$clientID}";
        if ($me->id) {
		$result .= "/{$me->id}";
                $group = _G('GROUP');
		if ($group->id) {
			$result .= "/{$group->id}";
		}
        }

        return \Gini\URI::url("{$app['url']}/{$result}", [
		'redirect'=> $url
	]);
    }
}

