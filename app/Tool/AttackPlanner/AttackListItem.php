<?php
/**
 * Created by IntelliJ IDEA.
 * User: crams
 * Date: 18.08.2019
 * Time: 16:10
 */

namespace App\Tool\AttackPlanner;


use App\CustomModel;
use App\Util\BasicFunctions;
use App\Village;
use App\Tool\AttackPlanner\AttackList as AttackList;
use Illuminate\Http\Request;

class AttackListItem extends CustomModel
{
    protected $fillable = [
        'attack_list_id',
        'type',
        'start_village_id',
        'target_village_id',
        'slowest_unit',
        'note',
        'send_time',
        'arrival_time',
        'spear',
        'sword',
        'axe',
        'archer',
        'spy',
        'light',
        'marcher',
        'heavy',
        'ram',
        'catapult',
        'knight',
        'snob',
    ];

    protected $dates = [
        'send_time',
        'arrival_time',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
    protected $cache = [
        'attack_list_id',
    ];

    public static $units = ['spear', 'sword', 'axe', 'archer', 'spy', 'light', 'marcher', 'heavy', 'ram', 'catapult', 'knight', 'snob'];

    /**
     * @return AttackList
     */
    public function list(){
        return $this->belongsTo('App\Tool\AttackPlanner\AttackList', 'attack_list_id');
    }

    /**
     * @return Village
     */
    public function start_village(){
        $world = $this->list->world;
        $dbName = BasicFunctions::getDatabaseName($world->server->code, $world->name);

        return $this->mybelongsTo('App\Village', 'start_village_id', 'villageID', $dbName.'.village_latest');
    }

    /**
     * @return Village
     */
    public function target_village(){
        $world = $this->list->world;
        $dbName = BasicFunctions::getDatabaseName($world->server->code, $world->name);

        return $this->mybelongsTo('App\Village', 'target_village_id', 'villageID', $dbName.'.village_latest');
    }

    public function unitIDToName(){
        return AttackListItem::$units[$this->slowest_unit];
    }

    public static function unitNameToID($input){
        return array_search($input, self::$units);
    }

    public function typeIDToName(){
        return static::statTypeIDToName($this->type);
    }
    
    public static function statTypeIDToName($type) {
        switch ($type) {
            case '8': return __('tool.attackPlanner.attack');
            case '11': return __('tool.attackPlanner.conquest');
            case '14': return __('tool.attackPlanner.fake');
            case '45': return __('tool.attackPlanner.wallbreaker');
            case '0': return __('tool.attackPlanner.support');
            case '1': return __('tool.attackPlanner.standSupport');
            case '7': return __('tool.attackPlanner.fastSupport');
            case '46': return __('tool.attackPlanner.fakeSupport');
            case '30': return __('ui.buildings.main');
            case '31': return __('ui.buildings.barracks');
            case '32': return __('ui.buildings.stable');
            case '33': return __('ui.buildings.garage');
            case '34': return __('ui.buildings.church');
            case '35': return __('ui.buildings.snob');
            case '36': return __('ui.buildings.smith');
            case '37': return __('ui.buildings.place');
            case '38': return __('ui.buildings.statue');
            case '39': return __('ui.buildings.market');
            case '40': return __('ui.buildings.wood');
            case '41': return __('ui.buildings.stone');
            case '42': return __('ui.buildings.iron');
            case '43': return __('ui.buildings.farm');
            case '44': return __('ui.buildings.storage');
        }
    }

    public function unitIDToNameOutput(){
        switch ($this->slowest_unit) {
            case '0': return __('ui.unit.spear');
            case '1': return __('ui.unit.sword');
            case '2': return __('ui.unit.axe');
            case '3': return __('ui.unit.archer');
            case '4': return __('ui.unit.spy');
            case '5': return __('ui.unit.light');
            case '6': return __('ui.unit.marcher');
            case '7': return __('ui.unit.heavy');
            case '8': return __('ui.unit.ram');
            case '9': return __('ui.unit.catapult');
            case '10': return __('ui.unit.knight');
            case '11': return __('ui.unit.snob');
        }
    }

    public function attackerName(){
        if($this->start_village == null) return ucfirst(__('ui.player.deleted'));
        if($this->start_village->owner == 0) return ucfirst(__('ui.player.barbarian'));
        if($this->start_village->playerLatest == null) return ucfirst(__('ui.player.deleted'));
        return BasicFunctions::decodeName($this->start_village->playerLatest->name);
    }

    public function attackerID(){
        if($this->start_village == null) return ucfirst(__('ui.player.deleted'));
        if($this->start_village->owner == 0) return ucfirst(__('ui.player.barbarian'));
        if($this->start_village->playerLatest == null) return ucfirst(__('ui.player.deleted'));
        return BasicFunctions::decodeName($this->start_village->playerLatest->playerID);
    }

    public function defenderName(){
        if($this->target_village == null) return ucfirst(__('ui.player.deleted'));
        if($this->target_village->owner == 0) return ucfirst(__('ui.player.barbarian'));
        if($this->target_village->playerLatest == null) return ucfirst(__('ui.player.deleted'));
        return BasicFunctions::decodeName($this->target_village->playerLatest->name);
    }

    public function defenderID(){
        if($this->target_village == null) return ucfirst(__('ui.player.deleted'));
        if($this->target_village->owner == 0) return ucfirst(__('ui.player.barbarian'));
        if($this->target_village->playerLatest == null) return ucfirst(__('ui.player.deleted'));
        return BasicFunctions::decodeName($this->target_village->playerLatest->playerID);
    }

    public function calcSend(){
        $unitConfig = $this->list->world->unitConfig();
        $dist = $this->calcDistance();
        $unit = self::$units[$this->slowest_unit];
        $runningTime = round(((float)$unitConfig->$unit->speed * 60) * $dist);
        return $this->arrival_time->subSeconds($runningTime);
    }

    public function calcArrival(){
        $unitConfig = $this->list->world->unitConfig();
        $dist = $this->calcDistance();
        $unit = self::$units[$this->slowest_unit];
        $runningTime = round(((float)$unitConfig->$unit->speed * 60) * $dist);
        return $this->send_time->addSeconds($runningTime);
    }

    public function calcDistance(){
        return sqrt(pow($this->start_village->x - $this->target_village->x, 2) + pow($this->start_village->y - $this->target_village->y, 2));
    }

    public function setVillageID($xStart, $yStart, $xTarget, $yTarget){
        $err = [];
        $this->start_village_id = $this->getVillageID($xStart, $yStart);
        if ($this->start_village_id === null){
            $err[] = __('tool.attackPlanner.villageNotExistStart');
        }
        
        $this->target_village_id = $this->getVillageID($xTarget, $yTarget);
        if ($this->target_village_id === null){
            $err[] = __('tool.attackPlanner.villageNotExistTarget');
        }
        return $err;
    }

    private function getVillageID($x, $y){
        $villageModel = new Village();
        $villageModel->setTable(BasicFunctions::getDatabaseName($this->list->world->server->code, $this->list->world->name).'.village_latest');
        $village = $villageModel->where(['x' => $x, 'y' => $y])->first();
        return isset($village->villageID)? $village->villageID : null;
    }
    
    public function setUnits(Request $data, $forceAllow) {
        $err = [];
        foreach (self::$units as $unit){
            if(!$forceAllow && !isset($data->checkboxes[$unit])) continue;
            
            if ($data->{$unit} == null){
                $this->{$unit} = 0;
            }else{
                $value = $data->{$unit};
                if ($value <= 2147483648){
                    $this->{$unit} = intval($value);
                }else{
                    $err[] = __('ui.unit.'.$unit).' '.__('tool.attackPlanner.errorUnitCount');
                }
            }
        }
        return $err;
    }
    
    
    public function setUnitsArr(array $data) {
        $err = [];
        foreach (self::$units as $unit){
            if (!isset($data[$unit]) || $data[$unit] == null){
                $this[$unit] = 0;
            } else {
                $value = $data[$unit];
                if ($value <= 2147483648){
                    $this[$unit] = intval($value);
                }else{
                    $err[] = __('ui.unit.'.$unit).' '.__('tool.attackPlanner.errorUnitCount');
                }
            }
        }
        return $err;
    }
    
    public function verifyTime() {
        if($this->send_time->year <= 1970) {
            return [ __('tool.attackPlanner.sendtimeToSoon') ];
        }
        if($this->send_time->year > 2037) {
            return [ __('tool.attackPlanner.sendtimeToLate') ];
        }
        if($this->arrival_time->year <= 1970) {
            return [ __('tool.attackPlanner.arrivetimeToSoon') ];
        }
        if($this->arrival_time->year > 2037) {
            return [ __('tool.attackPlanner.arrivetimeToLate') ];
        }
        return [];
    }
    
    public static function errJsonReturn($err) {
        $msg = "";
        foreach($err as $e) {
            $msg .= $e . "<br>";
        }
        
        return \Response::json(array(
            'data' => 'error',
            'title' => __('tool.attackPlanner.errorTitle'),
            'msg' => $msg,
        ));
    }
}
