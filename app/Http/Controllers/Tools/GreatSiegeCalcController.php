<?php

namespace App\Http\Controllers\Tools;


use App\Util\BasicFunctions;
use App\World;
use Illuminate\Routing\Controller as BaseController;

class GreatSiegeCalcController extends BaseController
{

    public function index($server, $world){
        BasicFunctions::local();
        $server = Server::getAndCheckServerByCode($server);
        $worldData = World::getAndCheckWorld($server, $world);
        
        abort_if($worldData->config == null || $worldData->units == null || $worldData->win_condition != 9, 404, __("ui.errors.404.toolNotAvail.greatSiegeCalc"));

        $unitConfig = $worldData->unitConfig();
        $config = simplexml_load_string($worldData->config);
        
        return view('tools.greatSiegeCalc', compact('worldData', 'server', 'unitConfig', 'config'));
    }

}
