@extends('layouts.temp')

@section('titel', "Collection")

@section('content')
    <div id="toast-content" style="position: absolute; top: 60px; right: 10px; z-index: 100;"></div>

    <div class="row justify-content-center">
        <!-- Titel für Tablet | PC -->
        <div class="col-12 p-lg-5 mx-auto my-1 text-center d-none d-lg-block">
            <h1 class="font-weight-normal">Datensammlung: </h1>
        </div>
        <div class="card col-lg-10 mb-4">
            <form id="collect-form" class="m-3" action="" method="POST">
                <div class="form-group">
                    <textarea class="w-100" name="data" style="height: 300px"></textarea>
                </div>
                <div class="form-group">
                    <select id="select-world">
                    @foreach ($worlds as $world)
                        <option value="{{ $world->server->code . $world->name }}">
                            {{ $world->displayName() }} ({{ $world->server->code . $world->name }})
                        </option>
                    @endforeach
                    </select>
                    <input type="submit">
                </div>
                @csrf
            </form>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h2>Letze daten:<p class="float-right" style="font-size: 0.9rem;">
                    <a href="{{ route("tools.collectDataStats") }}">Gesamtstatistiken (nur für admins)</a>
                </p></h2>
                <table class="table" id="latest-table">
                    <colgroup>
                        <col style="width: 100px">
                        <col style="width: 120px">
                        <col style="width: 50px">
                        <col style="width: 80px">
                        <col style="width: 80px">
                        <col style="width: 60px">
                        <col style="width: 60px">
                        <col style="width: 60px">
                        <col style="width: 100px">
                    </colgroup>
                    <thead>
                    <tr>
                        <th>Welt</th>
                        <th>Gebäude</th>
                        <th>Stufe</th>
                        <th>Bauzeit</th>
                        <th>HG Stufe</th>
                        <th>Holz</th>
                        <th>Lehm</th>
                        <th>Eisen</th>
                        <th>Bauernhof</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($latestData as $data)
                        <tr>
                            <th>{{ $data->world->displayName() }} ({{ $data->world->server->code . $data->world->name }})</th>
                            <td>{{ ucfirst(__("ui.buildings." . $data->building)) }}</td>
                            <td>{{ $data->level }}</td>
                            <td>{{ $data->buildtime }}</td>
                            <td>{{ $data->mainLevel }}</td>
                            <td>{{ $data->wood }}</td>
                            <td>{{ $data->clay }}</td>
                            <td>{{ $data->iron }}</td>
                            <td>{{ $data->pop }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    var url = "{{ route('index') }}/tools/datacollectionHQ/post/";
    
    $(function () {
        $('#select-world').change(function() {
            $('#collect-form')[0].action = url + $('#select-world').val() + "?debug=1";
        });
        
        $('#select-world').trigger("change");
        
        $('#collect-form').submit(function(e) {
            e.preventDefault();
            axios.post($('#collect-form')[0].action, $('#collect-form').serialize())
                .then((response) => {
                    var data = response.data;
                    createToast(data, "Daten erhalten");
                })
                .catch((error) => {

                });
        });
    });
    
    function createToast(data, title) {
        var int = Math.floor((Math.random() * 1000) + 1);
        $('#toast-content').append('<div class="toast toast'+int+'" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">\n' +
            '            <div class="toast-header">\n' +
            '                <div class="mr-2"><i class="fas fa-sync"></i></div>\n' +
            '                <strong class="mr-auto">' + title + '</strong>\n' +
            '                <small class="text-muted">{{__('global.now')}}</small>\n' +
            '                <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">\n' +
            '                    <span aria-hidden="true">&times;</span>\n' +
            '                </button>\n' +
            '            </div>\n' +
            '            <div class="toast-body">\n' +
            data +
            '            </div>\n' +
            '        </div>');
        $('.toast'+int).toast('show');
    }
</script>
@endsection

@section('style')
<style>
    #latest-table thead th { text-align: center }
    #latest-table thead th:first-child { text-align: left }
    #latest-table tbody td { text-align: center }
    #latest-table tbody td:first-child { text-align: left }
    
    #data-table thead th { text-align: center }
    #data-table thead th:first-child { text-align: left }
    #data-table tbody td { text-align: center }
    #data-table tbody td:first-child { text-align: left }
</style>
@endsection