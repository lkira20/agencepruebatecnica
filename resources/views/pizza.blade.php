@extends('layouts.front')

@section('styles')
@endsection

@section('content')
    <div class="container card mt-5 shadow">
        <div class="card-body">
            <a class="btn btn-primary float-end" href="{{ URL::previous() }}">Volver a consultar</a>
            <h1>Pizza</h1>
            <div id="container">
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script>
        let tituloGrafica = @json($tituloGrafica);
        let pizzaFormat = @json($pizzaFormat);

        Highcharts.chart('container', {
            chart: {
                type: 'pie'
            },
            title: {
                text: tituloGrafica
            },
            tooltip: {
                valueSuffix: '%'
            },
            plotOptions: {
                series: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: [{
                        enabled: true,
                        distance: 20
                    }, {
                        enabled: true,
                        distance: -40,
                        format: '{point.percentage:.1f}%',
                        style: {
                            fontSize: '1.2em',
                            textOutline: 'none',
                            opacity: 0.7
                        },
                        filter: {
                            operator: '>',
                            property: 'percentage',
                            value: 10
                        }
                    }]
                }
            },
            tooltip: {
                formatter: function() {
                    return '<b>% ' + this.y.toFixed(2)+'</b>';
                }
            },
            series: [{
                name: 'Percentage',
                colorByPoint: true,
                data: pizzaFormat
            }]
        });
    </script>
@endsection
