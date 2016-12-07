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

    private static function getValue($value)
    {
        if (!is_array($value)) return trim($value);
        return array_map(function($v) {
            return trim($v);
        }, $value);
    }

    public static function validate($form)
    {
        $errors = [];
        $campus = self::getValue(@$form['campus']);
        $building = self::getValue(@$form['building']);
        $room = self::getValue(@$form['room']);
        list($cErrors, $campus, $campusName) = self::validateCampus($campus);
        list($bErrors, $building, $buildingName) = self::validateBuilding($building, $campus);
        list($rErrors, $room, $roomName) = self::validateRoom($room, $building);
        $errors = array_merge($cErrors, $bErrors, $rErrors);
		
        return [
            $errors, 
            $campus, $campusName, 
            $building, $buildingName, 
            $room, $roomName,
        ];
    }
	
    private static function validateSets()
    {
        $errors = [];
        $codes = [];
        $names = [];

        $args = func_get_args();
        if (count($args)<2) return;
        $type = array_shift($args);
        $method = "validate{$type}";
        list($sets, $pas) = $args;
        $pas = (array)$pas;
        foreach ($sets as $k=>$v) {
            list($tError, $code, $name) = call_user_func_array([self, $method], [$v, $pas[$k]]);
            if (!empty($tError) && $tError[$type]) {
                $errors[(string)$type][(string)$k] = $tError[$type];
            }
            $codes[$k] = $code;
            $names[$k] = $name;
        }

        return [
            $errors,
            $codes,
            $names,
        ];
    }

	
    private static function validateCampus($campus)
    {	   
        if (is_array($campus)) return self::validateSets('campus', $campus);
        $errors = [];
        try{
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
                        return false;
                    } 
                }, T('请选择校区'))
                ->done();
        } catch (\Gini\CGI\Validator\Exception $e) {
            $errors = (array)$validator->errors();
        }

        return [
            $errors,
            $campus,
            $campusName,
        ];
    }
	
    private static function validateBuilding($building, $campus)
    {	
        if (is_array($building)) return self::validateSets('building', $building, $campus);
        $errors = [];
        try{
	        $validator = new \Gini\CGI\Validator();
            $validator
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
                        return false;
                    }
                }, T('请选择楼宇'))
                ->done();
        } catch (\Gini\CGI\Validator\Exception $e) {
             $errors = (array)$validator->errors();
        } 
        return [
            $errors,
            $building,
            $buildingName,
        ];
    }

    private static function validateRoom($room, $building)
    {	
        if (is_array($room)) return self::validateSets('room', $room, $building);
        $errors = [];
        try{
	        $validator = new \Gini\CGI\Validator();
    	    $validator
    	        ->validate('room', function() use($building, $room, &$roomName) {
                    try {
                        $rooms = \Gini\Gapper\Auth\Gateway::getRooms(['building'=>$building]);
                        if (empty($rooms)) {
                            return true;
                        }
                        foreach ($rooms as $rid => $r) {
                            if ($rid==$room) {
                                $roomName = $r['name'];
                                return true;
                            }
              	        }
                    } 
                    catch (\Exception $e) {
                        return false;
                    }
                }, T('请选择房间'))
                ->done();
        } catch (\Gini\CGI\Validator\Exception $e) {
            $errors = (array)$validator->errors();
        }

        return [
            $errors,
            $room,
            $roomName,
        ];
    }
}
