<?php

namespace App\Http\Controllers;

use App\Ally;
use App\Changelog;
use App\News;
use App\Player;
use App\Server;
use App\Util\BasicFunctions;
use App\World;


class ContentController extends Controller
{
    public function index(){
        BasicFunctions::local();
        $serverArray = Server::getServer();
        $news = News::all();
        return view('content.index', compact('serverArray', 'news'));
    }

    /*
     * https://ds-ultimate.de/de
     * */
    public function server($server){
        BasicFunctions::local();
        World::existServer($server);
        $worldsArray = World::worldsCollection($server);
        return view('content.server', compact('worldsArray', 'server'));
    }

    /*
     * https://ds-ultimate.de/de/164
     * */
    public function world($server, $world){
        BasicFunctions::local();
        World::existWorld($server, $world);

        $playerArray = Player::top10Player($server, $world);
        $allyArray = Ally::top10Ally($server, $world);
        $worldData = World::getWorld($server, $world);

        return view('content.world', compact('playerArray', 'allyArray', 'worldData', 'server'));

    }

    /*
     * https://ds-ultimate.de/de/164/allys
     * */
    public function allys($server, $world){
        BasicFunctions::local();
        World::existWorld($server, $world);

        $worldData = World::getWorld($server, $world);
        
        return view('content.worldAlly', compact('worldData', 'server'));
    }

    /*
     * https://ds-ultimate.de/de/164/players
     * */
    public function players($server, $world){
        BasicFunctions::local();
        World::existWorld($server, $world);

        $worldData = World::getWorld($server, $world);

        return view('content.worldPlayer', compact('worldData', 'server'));
    }
    
    public function conquer($server, $world, $type){
        BasicFunctions::local();
        World::existWorld($server, $world);

        $worldData = World::getWorld($server, $world);

        switch($type) {
            case "all":
                $typeName = ucfirst(__('ui.conquer.all'));
                break;
            default:
                abort(404, "Unknown type");
        }
        
        $allHighlight = ['s', 'i', 'b', 'd'];
        if(\Auth::check()) {
            $profile = \Auth::user()->profile;
            $userHighlight = explode(":", $profile->conquerHightlight_World);
        } else {
            $userHighlight = $allHighlight;
        }
        
        $who = $worldData->displayName();
        $routeDatatableAPI = route('api.worldConquer', [$worldData->server->code, $worldData->name, $type]);
        $routeHighlightSaving = route('user.saveConquerHighlighting', ['world']);
        
        return view('content.conquer', compact('server', 'worldData', 'typeName',
                'who', 'routeDatatableAPI', 'routeHighlightSaving',
                'allHighlight', 'userHighlight'));
    }

    public function sitemap() {
        $servers = array();
        $serverArray = Server::getServer();
        
        foreach($serverArray as $server) {
            $worldsArray = World::worldsCollection($server->code);
            $servers[$server->code] = collect();
            
            if($worldsArray->get('world') != null && count($worldsArray->get('world')) > 0) {
                $servers[$server->code] = $servers[$server->code]->merge($worldsArray->get('world'));
            }
            if($worldsArray->get('speed') != null && count($worldsArray->get('speed')) > 0) {
                $servers[$server->code] = $servers[$server->code]->merge($worldsArray->get('speed'));
            }
            if($worldsArray->get('casual') != null && count($worldsArray->get('casual')) > 0) {
                $servers[$server->code] = $servers[$server->code]->merge($worldsArray->get('casual'));
            }
            if($worldsArray->get('classic') != null && count($worldsArray->get('classic')) > 0) {
                $servers[$server->code] =  $servers[$server->code]->merge($worldsArray->get('classic'));
            }
        }
        
        return response()->view('sitemap', compact('servers'))->header('Content-Type', 'text/xml');
    }

    public function changelog(){
        $changelogModel = new Changelog();

        $changelogs = $changelogModel->orderBy('created_at', 'DESC')->get();

        return view('content.changelog', compact('changelogs'));
    }
}
