<?php

namespace Gini\Module;

class LabBase {

    public static function headerTopMenu($e, $menu) {
        $me = _G('ME');
        if ($me->id) {

            $menu['settings']['@icon'] = 'gear';
            $menu['settings']['account']['@weight'] = 100;

            if (1 < count((array)\Gini\Gapper\Client::getGroups())) {
                $menu['settings']['account']['switch'] = [
                    '@url' => 'reset-group',
                    '@title' => T('切换分组'),
                ];
            }

            $menu['settings']['account']['logout'] = [
                '@url' => 'logout',
                '@title' => T('登出系统'),
                '@weight' => 100,
            ];
        }
    }

}