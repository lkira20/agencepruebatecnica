@extends('layouts.front')

@section('styles')
@endsection

@section('content')
    <div class="container mt-5 card shadow">
        <div class="card-body">
            <a class="btn btn-primary float-end" href="{{ URL::previous() }}">Volver a consultar</a>
            <h1>Gráfico</h1>
            <div id="container">

            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script>
        let tituloGrafica = @json($tituloGrafica);
        let mesesname = @json($mesesName);
        let coUsuarios = @json($formatoGraficaCosUsuario);

        // Data retrieved from https://www.ssb.no/energi-og-industri/olje-og-gass/statistikk/sal-av-petroleumsprodukt/artikler/auka-sal-av-petroleumsprodukt-til-vegtrafikk

        Highcharts.chart('container', {
            title: {
                text: tituloGrafica,
                align: 'left'
            },
            xAxis: {
                categories: mesesname
            },
            yAxis: {
                title: {
                    text: ''
                },
                max: 32000
            },
            tooltip: {
                valueSuffix: ''
            },
            plotOptions: {
                series: {
                    borderRadius: '25%'
                }
            },
            tooltip: {
                formatter: function() {
                    return '<b>' + this.y.toLocaleString('pt-BR', {
                        currency: 'BRL',
                        style: 'currency',
                    }) + '</b>';
                }
            },
            series: coUsuarios
        });
    </script>
@endsection
