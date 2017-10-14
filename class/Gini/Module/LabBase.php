<?php

namespace Gini\Module;

class LabBase
{
    public static function setup()
    {
    }

    public static function getFEUrl($path, $force = false)
    {
        $feURL = \Gini\Config::get('app.lab-fe-url');
        if (!$feURL || $feURL=='${LAB_FE_URL}') {
            return;
        }

        $mui = parse_url($_SERVER['HTTP_HOST']);
        $myHost = $mui['host'];
        $uri = parse_url($feURL);
        $toHost = $uri['host'];
        if ($myHost==$toHost) return;

        if ($force) return "{$feURL}/{$path}";

        $me = _G('ME');
        if ($me->id) {
            $clientID = \Gini\Config::get('app.lab-fe-app-client-id');
            $gapperToken = \Gini\Gapper\Client::getLoginToken($clientID);
            if ($gapperToken) {
                $group = _G('GROUP');
                $url = \Gini\URI::url($feURL."/gapper/client/login", [
                    'gapper-token'=> $gapperToken,
                    'gapper-group'=> $group->id,
                    'redirect'=> "{$feURL}/{$path}"
                ]);
            }
        } else {
            $url = "{$feURL}/{$path}";

        }

        return $url;
    }
}

