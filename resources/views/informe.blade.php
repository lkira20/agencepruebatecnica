@extends('layouts.front')

@section('styles')
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">

    <style>
        .ui-datepicker-calendar {
            display: none;
        }
    </style>
@endsection

@section('content')
    <div class="container card shadow">
        <div class="card-body">
            <a class="btn btn-primary float-end" href="{{ URL::previous() }}">Volver a consultar</a>
            <h1 style="font-size: 1.5rem;" class="mt-5 text-center">{{ $titulo }}</h1>

            @foreach ($datosFormateados as $co_usuario => $datos)
                <div class="table-responsive">
                    <table class="table table-striped mt-4">
                        <tr class="table-dark">
                            <th colspan="5">
                                <h3 class="ml-2">{{ $co_usuario }}</h3>
                            </th>
                        </tr>
                        <tr>
                            <th>Periodo</th>
                            <th>Ganancia neta</th>
                            <th>Costo fijo</th>
                            <th>Comisión</th>
                            <th>Lucro</th>
                        </tr>
                        @php
                            $totalReceitaLiquida = 0;
                            $totalCustoFixo = 0;
                            $totalComision = 0;
                            $totalLucro = 0;
                        @endphp
                        @foreach ($datos as $mes => $valores)
                            <tr>
                                <th>{{ $mes }}</th>
                                <td>{{ precioFormato($valores['gananciasNetas']) }}</td>
                                <td>{{ precioFormato($valores['costo_fijo']) }}</td>
                                <td>{{ precioFormato($valores['comision']) }}</td>
                                <td>
                                    @php 
                                        $lucro = $valores['gananciasNetas'] - ($valores['costo_fijo'] + $valores['comision']);
                                        $totalLucro += $lucro;
                                    @endphp
                                    {{ precioFormato($lucro) }}
                                </td>
                            </tr>
                            @php
                                $totalReceitaLiquida += $valores['gananciasNetas'];
                                $totalCustoFixo += $valores['costo_fijo'];
                                $totalComision += $valores['comision'];
                            @endphp
                        @endforeach
                        <tr class="table-dark">
                            <td>Saldo</td>
                            <td>{{ precioFormato($totalReceitaLiquida) }}</td>
                            <td>{{ precioFormato($totalCustoFixo) }}</td>
                            <td>{{ precioFormato($totalComision) }}</td>
                            <td>{{ precioFormato($totalLucro) }}</td>
                        </tr>
                    </table>
                </div>
            @endforeach

        </div>
    </div>
@endsection

@section('scripts')
@endsection
