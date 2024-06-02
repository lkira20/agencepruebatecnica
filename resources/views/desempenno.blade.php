@extends('layouts.front')

@section('styles')
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
@endsection

@section('content')
    <div class="container card shadow mt-2">
        <div class="card-body">
            <h1>Calculos Por consultor</h1>

            <form action="{{ route('consultar') }}" method="POST">
                @csrf
                <div class="container py-2 mt-3">
                    <h5>Periodo</h5>
                    <div class="row">
                        <div class="col-md-6 col-lg-2">
                            <label for="fechaInicio">Fecha inicio :</label>
                            <input name="fechaInicio" type="date" class="form-control" id="fechaInicio" class="date-picker"
                                placeholder="Fecha inicio" />
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="fechaFin">Fecha fin :</label>
                            <input name="fechaFin" type="date" class="form-control" id="fechaFin" class="date-picker"
                                placeholder="Fecha fin" />
                        </div>
                        <div class="col-md-6 col-lg-6">
                            <label for="">Seleccione el tipo de resultado a obtener:</label>
                            <div class="row">
                                <div class="col-4">
                                    <div class="d-grid gap-2">
                                        <input class="btn-check" type="radio" name="tipo" id="informe"
                                            value="informe">
                                        <label class="btn btn-outline-primary" for="informe">
                                            Informe
                                        </label>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="d-grid gap-2">
                                        <input class="btn-check" type="radio" name="tipo" id="grafico"
                                            value="grafico">
                                        <label class="btn btn-outline-success" for="grafico">
                                            Gráfico
                                        </label>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="d-grid gap-2">
                                        <input class="btn-check" type="radio" name="tipo" id="pizza"
                                            value="pizza">
                                        <label class="btn btn-outline-danger" for="pizza">
                                            Pizza
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="">Acción:</label>
                            <div class="d-grid gap-2">
                                <button class="btn btn-secondary" type="submit">Consultar</button>
                            </div>
                        </div>
                    </div>

                </div>

                <br>
                <h6>Seleccione los consultores a los cuales desee obtener resultados:</h6>

                <div class="row">
                    <div class="col-9">
                        <table class="table table-striped">
                            <thead>
                                <th class="w-75">Consultores</th>
                                <th>Seleccionar</th>
                            </thead>
                            <tbody>
                                @foreach ($usuarios as $usuario)
                                    <tr class="">
                                        <td class="w-75">{{ $usuario->no_usuario }}</td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input checkConsultor" type="checkbox"
                                                    value="{{ $usuario->co_usuario }}" name="co_usuario[]" id="flexCheckDefault">
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="col-3">
                        <h6>Seleccionados:</h6>
                        <ul class="list-group mt-3" id="listSelected">
                        </ul>
                    </div>
                </div>
            </form>
        </div>

    </div>
    </div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.js"></script>
    <script>
        let checkConsultoresAll = document.querySelectorAll('.checkConsultor');
        let listUserSelected = [];

        checkConsultoresAll.forEach(element => {
            element.addEventListener('change', function(e) {
                let valor = e.target.value;
                if (e.target.checked) {
                    listUserSelected.push(valor)
                } else {
                    let index = listUserSelected.indexOf(valor);
                    listUserSelected.splice(index, 1)
                }

                PintarList(listUserSelected);
            })
        });

        function PintarList(list) {
            let listSelected = document.querySelector('#listSelected');
            let listhml = '';
            list.forEach(function(val) {
                listhml += '<li class="list-group-item">' + val + '</li>';
            })

            listSelected.innerHTML = listhml;
        }
    </script>
@endsection
