@extends('layouts.app')

@section('titel', ucfirst(__('ui.titel.player')).': '.\App\Util\BasicFunctions::decodeName($playerTopData->name))

@section('content')
    <div class="row justify-content-center">
        <!-- Titel für Tablet | PC -->
        <div class="p-lg-3 mx-auto my-1 text-center d-none d-lg-block">
            <h1 class="font-weight-normal">{{ ucfirst(__('ui.titel.player')).': '.\App\Util\BasicFunctions::decodeName($playerTopData->name) }}</h1>
        </div>
        <!-- ENDE Titel für Tablet | PC -->
        <!-- Titel für Mobile Geräte -->
        <div class="p-lg-3 mx-auto my-1 text-center d-lg-none truncate">
            <h1 class="font-weight-normal">
                {{ ucfirst(__('ui.titel.player')).': ' }}
            </h1>
            <h4>
                {{ \App\Util\BasicFunctions::decodeName($playerTopData->name) }}
            </h4>
        </div>
        <!-- ENDE Titel für Tablet | PC -->
        <!-- Informationen -->
        <div class="col-12">
            <div class="card">
                <div class="card-body tab-content">
                    <div class="row">
                        <div class="col-12">
                            <h4 class="card-title">{{ucfirst(__('ui.tabletitel.info'))}}</h4>
                        </div>
                        <div class="col-12 mt-3">
                            <h5 class="card-subtitle">{{__('ui.tabletitel.general')}}</h5>
                            <table id="data_top1" class="table table-bordered no-wrap w-100">
                                <thead>
                                <tr>
                                    <th class="all">{{ ucfirst(__('ui.table.rank')) }}</th>
                                    <th class="all">{{ ucfirst(__('ui.table.name')) }}</th>
                                    <th class="desktop">{{ ucfirst(__('ui.table.ally')) }}</th>
                                    <th class="desktop">{{ ucfirst(__('ui.table.points')) }}</th>
                                    <th class="desktop">{{ ucfirst(__('ui.table.villages')) }}</th>
                                    <th class="desktop">{{ ucfirst(__('ui.table.avgVillage')) }}</th>
                                    <th class="desktop">{{ ucfirst(__('ui.table.conquer')) }}</th>
                                    <th class="desktop">{{ ucfirst(__('ui.table.allyChanges')) }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <th>
                                        {{ \App\Util\BasicFunctions::numberConv($playerTopData->rank_top) }}
                                        <br><span class="small float-right">{{ $playerTopData->getDate("rank") }}</span>
                                    </th>
                                    <td>{{ \App\Util\BasicFunctions::decodeName($playerTopData->name) }}</td>
                                    <td>{!! ($playerTopData->ally_id != 0 && $playerTopData->allyTop !== null)?\App\Util\BasicFunctions::linkAlly($worldData, $playerTopData->ally_id, \App\Util\BasicFunctions::outputName($playerTopData->allyTop->tag)) : '-' !!}</td>
                                    <td>
                                        {{ \App\Util\BasicFunctions::numberConv($playerTopData->points_top) }}
                                        <br><span class="small float-right">{{ $playerTopData->getDate("points") }}</span>
                                    </td>
                                    <td>
                                        {{ \App\Util\BasicFunctions::numberConv($playerTopData->village_count_top) }}
                                        <br><span class="small float-right">{{ $playerTopData->getDate("village_count") }}</span>
                                    </td>
                                    <td>{{ ($playerTopData->village_count_top != 0)?\App\Util\BasicFunctions::numberConv($playerTopData->points_top/$playerTopData->village_count_top): '-' }}</td>
                                    <td>{!! \App\Util\BasicFunctions::linkWinLoose($worldData, $playerTopData->playerID, $conquer, 'playerConquer') !!}</td>
                                    <td>{!! \App\Util\BasicFunctions::linkWinLoose($worldData, $playerTopData->playerID, $allyChanges, 'playerAllyChanges') !!}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-12 mt-3">
                            <h5 class="card-subtitle">{{__('ui.tabletitel.bashStats')}}</h5>
                            <table id="data_top2" class="table table-bordered no-wrap w-100" style="border: 1px solid #b1b1b1">
                                <thead>
                                <tr>
                                    <th class="all" style="border-bottom:1px solid #dee2e6" colspan="3" width="50%">{{ ucfirst(__('ui.tabletitel.bashStats')) }} - {{__('ui.table.bashGes') }}</th>
                                    <th class="desktop" style="border-bottom:1px solid #dee2e6; border-left: 1px solid #b1b1b1" colspan="3">{{ ucfirst(__('ui.tabletitel.bashStats')) }} - {{__('ui.table.bashOff') }}</th>
                                </tr>
                                <tr>
                                    <th>{{ ucfirst(__('ui.table.rank')) }}</th>
                                    <th>{{ ucfirst(__('ui.table.points')) }}</th>
                                    <th>{{ ucfirst(__('ui.table.bashPointsRatio')) }}</th>
                                    <th style="border-left: 1px solid #b1b1b1">{{ ucfirst(__('ui.table.rank')) }}</th>
                                    <th>{{ ucfirst(__('ui.table.points')) }}</th>
                                    <th>{{ ucfirst(__('ui.table.bashPointsRatio')) }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>
                                        {{ \App\Util\BasicFunctions::numberConv($playerTopData->gesBashRank_top) }}
                                        <br><span class="small float-right">{{ $playerTopData->getDate("gesBashRank") }}</span>
                                    </td>
                                    <td>
                                        {{ \App\Util\BasicFunctions::numberConv($playerTopData->gesBash_top) }}
                                        <br><span class="small float-right">{{ $playerTopData->getDate("gesBash") }}</span>
                                    </td>
                                    <td>{{ ($playerTopData->points_top != 0)?\App\Util\BasicFunctions::numberConv(($playerTopData->gesBash_top/$playerTopData->points_top)*100): ('-') }}%</td>
                                    <td style="border-left: 1px solid #b1b1b1">
                                        {{ \App\Util\BasicFunctions::numberConv($playerTopData->offBashRank_top) }}
                                        <br><span class="small float-right">{{ $playerTopData->getDate("offBashRank") }}</span>
                                    </td>
                                    <td>
                                        {{ \App\Util\BasicFunctions::numberConv($playerTopData->offBash_top) }}
                                        <br><span class="small float-right">{{ $playerTopData->getDate("offBash") }}</span>
                                    </td>
                                    <td>{{ ($playerTopData->points_top != 0)?\App\Util\BasicFunctions::numberConv(($playerTopData->offBash_top/$playerTopData->points_top)*100): ('-') }}%</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-12 mt-3">
                            <table id="data_top3" class="table table-bordered no-wrap w-100" style="border: 1px solid #b1b1b1">
                                <thead>
                                <tr>
                                    <th class="all" style="border-bottom:1px solid #dee2e6" colspan="3" width="50%">{{ ucfirst(__('ui.tabletitel.bashStats')) }} - {{__('ui.table.bashDeff') }}</th>
                                    <th class="desktop" style="border-bottom:1px solid #dee2e6; border-left: 1px solid #b1b1b1" colspan="3">{{ ucfirst(__('ui.tabletitel.bashStats')) }} - {{__('ui.table.supDeff') }}</th>
                                </tr>
                                <tr>
                                    <th>{{ ucfirst(__('ui.table.rank')) }}</th>
                                    <th>{{ ucfirst(__('ui.table.points')) }}</th>
                                    <th>{{ ucfirst(__('ui.table.bashPointsRatio')) }}</th>
                                    <th style="border-left: 1px solid #b1b1b1">{{ ucfirst(__('ui.table.rank')) }}</th>
                                    <th>{{ ucfirst(__('ui.table.points')) }}</th>
                                    <th>{{ ucfirst(__('ui.table.bashPointsRatio')) }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>
                                        {{ \App\Util\BasicFunctions::numberConv($playerTopData->defBashRank_top) }}
                                        <br><span class="small float-right">{{ $playerTopData->getDate("defBashRank") }}</span>
                                    </td>
                                    <td>
                                        {{ \App\Util\BasicFunctions::numberConv($playerTopData->defBash_top) }}
                                        <br><span class="small float-right">{{ $playerTopData->getDate("defBash") }}</span>
                                    </td>
                                    <td>{{ ($playerTopData->points_top != 0)?\App\Util\BasicFunctions::numberConv(($playerTopData->defBash_top/$playerTopData->points_top)*100): ('-') }}%</td>
                                    <td style="border-left: 1px solid #b1b1b1">{{ \App\Util\BasicFunctions::numberConv($playerTopData->supBashRank_top) }}</td>
                                    <td>
                                        {{ \App\Util\BasicFunctions::numberConv($playerTopData->supBash_top) }}
                                        <br><span class="small float-right">{{ $playerTopData->getDate("supBashRank") }}</span>
                                    </td>
                                    <td>{{ ($playerTopData->points_top != 0)?\App\Util\BasicFunctions::numberConv(($playerTopData->supBash_top/$playerTopData->points_top)*100): ('-') }}%</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        @isset($playerOtherServers)
                            <div class="col-12 mt-3 mb-3">
                                <h4 class="card-title">{{ __('ui.otherWorldsPlayer')}}</h4>
                                @foreach($playerOtherServers->getWorlds() as $worldModel)
                                    <div class="otherworld d-inline-block mt-1 position-relative" data-worldid="{{ $worldModel->id }}">
                                        {!! \App\Util\BasicFunctions::linkPlayer($worldModel, $playerTopData->playerID, \App\Util\BasicFunctions::escape($worldModel->shortName()), 'btn btn-primary btn-sm' . (($worldModel->name == $worldData->name)?(' active'):('')), true) !!}
                                        <div class="otherworld-popup popover fade bs-popover-bottom d-none" style="top: 100%">
                                            <div class="arrow m-0" style="left: calc(50% - 0.5rem)"></div>
                                            <div class="popover-body text-nowrap">
                                                <h1><i class="fas fa-spinner fa-spin"></i></h1>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endisset
                        <div class="col">
                            <a href="javascript:void(0)" class="text-secondary font-weight-bold" onclick="$('#signatureContent').toggle()">{{ ucfirst(__('ui.signature')) }}</a>
                        </div>
                    </div>
                    <div id="signatureContent" class="input-group mt-2 float-right" style="display: none;">
                        <div class="input-group-prepend">
                            <a class="btn btn-primary" target="_blank" href="{{ route('api.signature', [$server, $worldData->name, 'player', $playerTopData->playerID]) }}">{{ __('ui.sigPreview') }}</a>
                        </div>
                        <input id="signature" type="text" class="form-control" value="[url={{ route('player', [$server, $worldData->name, $playerTopData->playerID]) }}][img]{{ route('api.signature', [$server, $worldData->name, 'player', $playerTopData->playerID]) }}[/img][/url]" aria-label="Recipient's username" aria-describedby="basic-addon2">
                        <div class="input-group-append">
                            <span class="input-group-text" style="cursor:pointer" id="basic-addon2" onclick="copy('signature')"><i class="far fa-copy"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ENDE Informationen -->
    </div>
@endsection

@push('js')
    <script>
        $(document).ready( function () {
            $('#data_top1').DataTable({
                dom: 't',
                ordering: false,
                paging: false,
                responsive: true,

                keys: true, //enable KeyTable extension
            });

            $('#data_top2').DataTable({
                dom: 't',
                ordering: false,
                paging: false,
                responsive: true,

                keys: true, //enable KeyTable extension
            });

            $('#data_top3').DataTable({
                dom: 't',
                ordering: false,
                paging: false,
                responsive: true,

                keys: true, //enable KeyTable extension
            });
            
            @isset($playerOtherServers)
                $(".otherworld").hover(function(e) {
                    if(e.type == "mouseenter") {
                        $('.otherworld-popup', this).removeClass("d-none").addClass("show");
                        //popover-body
                        if(! $('.otherworld-popup', this).hasClass("data-loaded")) {
                            $('.otherworld-popup', this).addClass("data-loaded");
                            var url = "{{ route('api.worldPopup', ['worldId', $playerTopData->playerID]) }}";
                            axios.get(url.replace("worldId", $(this).data("worldid")), {
                            })
                            .then((response) => {
                                $('.popover-body', this).html(response.data);
                                var lOffset = ($(this).width() - $('.otherworld-popup', this).width()) / 2;
                                $('.otherworld-popup', this)[0].style.left = lOffset + "px";
                            })
                            .catch((error) => {
                                $('.popover-body', this).html("-");
                            });
                        }
                    } else {
                        $('.otherworld-popup', this).addClass("d-none").removeClass("show");
                    }
                })
            @endisset
        });
    </script>
@endpush
