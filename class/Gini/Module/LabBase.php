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
}

