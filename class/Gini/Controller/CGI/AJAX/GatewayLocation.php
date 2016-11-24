<?php

namespace Gini\Controller\CGI\AJAX;

class GatewayLocation extends \Gini\Controller\CGI
{
    private static $multiKey = null;
    public function actionGetSubBuildings()
    {
        $form = $this->form('get');
        $code = $form['value'];
		self::$multiKey = $form['multiKey'];
        return \Gini\IoC::construct('\Gini\CGI\Response\JSON', (string)self::getLocationBuilding($code));
    }

    public function actionGetSubRooms()
    {
        $form = $this->form('get');
        $code = $form['value'];
		self::$multiKey = $form['multiKey'];
        return \Gini\IoC::construct('\Gini\CGI\Response\JSON', (string)self::getLocationRoom($code));
    }

    public static function getLocationCampus($campusCode, $buildingCode, $roomCode, array $form=[], $errors=null, $multiKey=null)
    {
        self::$multiKey = $multiKey;
        try {
            $campuses = \Gini\Gapper\Auth\Gateway::getCampuses();
            if (empty($campuses)) {
                throw new \Exception();
            }
        }
        catch (\Exception $e) {
            return V('gateway-location/edit-campus-warn');
        }

        $cid = (!is_null(self::$multiKey) ? $form['campus'][$multiKey] : $form['campus']) ?: (@$campusCode ?: current($campuses)['code']);
        return V('gateway-location/edit-campus', [
            'selected'=> $cid,
            'multiKey'=> self::$multiKey,
            'campuses'=> $campuses,
            'building'=> (string)self::getLocationBuilding($cid, $buildingCode, $roomCode, $form, $errors),
            'errors'=> $errors,
        ]);
    }

    public static function getLocationBuilding($campusCode, $buildingCode=null, $roomCode=null, array $form=[], $errors=null)
    {
        try {
            $buildings = \Gini\Gapper\Auth\Gateway::getBuildings(['campus'=>$campusCode]);
            if (empty($buildings)) {
                throw new \Exception();
            }
        }
        catch (\Exception $e) {
            return V('gateway-location/edit-building-warn');
        }

        $bid = (!is_null(self::$multiKey) ? $form['building'][$multiKey] : $form['building']) ?: ($buildingCode?:current($buildings)['code']);
        return V('gateway-location/edit-building', [
            'selected'=> $bid,
            'multiKey'=> self::$multiKey,
            'buildings'=> $buildings,
            'room'=> (string)self::getLocationRoom($bid, $roomCode, $form, $errors),
            'errors'=> $errors,
        ]);
    }

    public static function getLocationRoom($buildingCode, $roomCode=null, array $form=[], $errors=null)
    {
        try {
            $rooms = \Gini\Gapper\Auth\Gateway::getRooms(['building'=>$buildingCode]);
        }
        catch (\Exception $e) {
            return V('gateway-location/edit-room-warn');
        }
        $rid = (!is_null(self::$multiKey) ? $form['room'][$multiKey] : $form['room']) ?: ($roomCode?:current($rooms)['name']);
        return V('gateway-location/edit-room', [
            'selected'=> $rid,
            'multiKey'=> self::$multiKey,
            'rooms'=> $rooms,
            'errors'=> $errors,
        ]);
    }

    public static function validate($form)
    {
        $errors = [];
        $campus = trim($form['campus']);
        $building = trim($form['building']);
        $room = trim($form['room']);
        try {
            $validator = new \Gini\CGI\Validator();
            $validator
                ->validate('campus', function() use($campus, &$campusName) {
                    try {
                        $campuses = \Gini\Gapper\Auth\Gateway::getCampuses();
                        if (empty($campuses)) {
                            throw new \Exception();
                        }
                        foreach ($campuses as $c) {
                            if ($c['code']==$campus) {
                                $campusName = $c['name'];
                                return true;
                            }
                        }
                    }
                    catch (\Exception $e) {
                    }
                }, T('请选择校区'))
                ->validate('building', function() use($campus, $building, &$buildingName) {
                    try {
                        $buildings = \Gini\Gapper\Auth\Gateway::getBuildings(['campus'=>$campus]);
                        if (empty($buildings)) {
                            throw new \Exception();
                        }
                        foreach ($buildings as $b) {
                            if ($b['code']==$building) {
                                $buildingName = $b['name'];
                                return true;
                            }
                        }
                    }
                    catch (\Exception $e) {
                    }
                }, T('请选择楼宇'))
                ->validate('room', function() use($building, $room, &$roomName) {
                    try {
                        $rooms = \Gini\Gapper\Auth\Gateway::getRooms(['building'=>$building]);
                        if (empty($rooms)) {
                            $roomName = $room;
                            return true;
                        }
                        foreach ($rooms as $rid=>$r) {
                            if ($rid==$room) {
                                $roomName = $r['name'];
                                return true;
                            }
                        }
                    }
                    catch (\Exception $e) {
                        return false;
                    }
                    return true;
                }, T('请选择房间'))
                ->done();
        } catch (\Gini\CGI\Validator\Exception $e) {
            $errors = (array)$validator->errors();
        }
        return [
            $errors, 
            $campus, $campusName, 
            $building, $buildingName, 
            $room, $roomName,
        ];
    }

}
