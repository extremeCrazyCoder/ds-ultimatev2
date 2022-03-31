<?php

namespace App\Http\Controllers;

use App\Ally;
use App\Changelog;
use App\Conquer;
use App\News;
use App\Player;
use App\Server;
use App\Util\BasicFunctions;
use App\World;
use Illuminate\Support\Facades\App;


class ContentController extends Controller
{
    public function index(){
        BasicFunctions::local();
        $serverArray = Server::getServer();
        $news = News::orderBy('order')->get();
        return view('content.index', compact('serverArray', 'news'));
    }

    /*
     * https://ds-ultimate.de/de
     * */
    public function server($server){
        BasicFunctions::local();
        World::existServer($server);
        $worldsArray = World::worldsCollection($server, ['speed' => 'special', 'casual' => 'special', 'classic' => 'special']);
        usort($worldsArray['world'], function($a, $b) {
            return -1*strcmp($a->name, $b->name);
        });
        $worldsArrays = World::worldsCollectionActiveSorter($worldsArray);
        $worldsActive = $worldsArrays['active'];
        $worldsInactive = $worldsArrays['inactive'];
        return view('content.server', compact('worldsActive', 'worldsInactive', 'server'));
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

        $who = $worldData->display_name;
        $routeDatatableAPI = route('api.worldConquer', [$worldData->server->code, $worldData->name, $type]);
        $routeHighlightSaving = route('user.saveConquerHighlighting', ['world']);

        return view('content.conquer', compact('server', 'worldData', 'typeName',
                'who', 'routeDatatableAPI', 'routeHighlightSaving',
                'allHighlight', 'userHighlight'));
    }

    public function conquereDaily($server, $world){
        BasicFunctions::local();
        World::existWorld($server, $world);

        $worldData = World::getWorld($server, $world);
        $conquer = new Conquer();
        $conquer->setTable(BasicFunctions::getDatabaseName($server, $world).'.conquer');
        $fistconquer = $conquer->first();

        return view('content.conquerDaily', compact('server', 'worldData', 'fistconquer'));
    }

    public function sitemap() {
        $servers = array();
        $serverArray = Server::getServer();

        foreach($serverArray as $server) {
            $worldsArray = World::worldsCollection($server->code);
            $servers[$server->code] = [];

            if(isset($worldsArray['world']) && count($worldsArray['world']) > 0) {
                $servers[$server->code] = array_merge($servers[$server->code], $worldsArray['world']);
            }
            if(isset($worldsArray['speed']) && count($worldsArray['speed']) > 0) {
                $servers[$server->code] = array_merge($servers[$server->code], $worldsArray['speed']);
            }
            if(isset($worldsArray['casual']) && count($worldsArray['casual']) > 0) {
                $servers[$server->code] = array_merge($servers[$server->code], $worldsArray['casual']);
            }
            if(isset($worldsArray['classic']) && count($worldsArray['classic']) > 0) {
                $servers[$server->code] =  array_merge($servers[$server->code], $worldsArray['classic']);
            }
        }

        return response()->view('sitemap', compact('servers'))->header('Content-Type', 'text/xml');
    }

    public function changelog(){
        BasicFunctions::local();
        $changelogModel = new Changelog();

        $changelogs = $changelogModel->orderBy('created_at', 'DESC')->get();

        BasicFunctions::changelogUpdate();

        $locale = in_array(App::getLocale(), config('dsUltimate.changelog_lang_key'))? App::getLocale() : 'en';

        return view('content.changelog', compact('changelogs', 'locale'));
    }
}
