<?php

namespace Gini\Module;

class LabBase
{
    public static function setup()
    {
    }

    public static function getFEUrl($path)
    {
        $feURL = \Gini\Config::get('app.lab-fe-url');
        if (!$feURL || $feURL=='${LAB_FE_URL}') {
            return;
        }

        $me = _G('ME');
        if ($me->id) {
            $clientID = \Gini\Config::get('app.lab-fe-app-client-id');
            $gapperToken = \Gini\Gapper\Client::getLoginToken($clientID);
            if ($gapperToken) {
                $group = _G('GROUP');
                $url = \Gini\URI::url($feURL, [
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

