<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltroRequest;
use App\Models\CaoCliente;
use App\Models\CaoFactura;
use App\Models\CaoUsuario;
use App\Traits\Calcular;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class DesempennoController extends Controller
{
    use Calcular;
    public function Consultor(Request $request)
    {
        //obtenemos el listado de consultores
        $usuarios = CaoUsuario::join('permissao_sistema', function ($join) {
            $join->on('cao_usuario.co_usuario', '=', 'permissao_sistema.co_usuario')
                ->where('co_sistema', 1)
                ->where('in_ativo', 'S')
                ->whereIn('co_tipo_usuario', [0, 1, 2]);
        })
            ->select('cao_usuario.co_usuario', 'no_usuario')
            ->get();

        return view('desempenno', compact('usuarios'));
    }

    public function postConsultar(FiltroRequest $request)
    {
        $usuarios = $request->co_usuario;
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        //guardamos la fecha para su posterior uso
        $cookieFechaInicio = Cookie::forever('fecha_inicio', $fechaInicio);
        $cookieFechaFin = Cookie::forever('Fecha_fin', $fechaFin);
        Cookie::queue($cookieFechaInicio);
        Cookie::queue($cookieFechaFin);

        switch ($request->tipo) {
            case 'informe':
                $titulo = 'Informe desde el '.Carbon::parse($fechaInicio)->format('d/m/Y') . ' hasta '.Carbon::parse($fechaFin)->format('d/m/Y');
                $facturas = $this->calcularinforme($usuarios, $fechaInicio, $fechaFin);
                //validacion en caso que no encuentre registro
                if ($facturas->count() == 0) {
                    return back()->with([
                        'swal' => [
                            'title' => 'Error',
                            'message' => 'No hay facturas registradas o no hay suficientes datos para hacer los calculos correspondientes.',
                            'icon' => 'error'
                        ]
                    ]);
                }

                $datosFormateados = [];
                //formateamos para hacer una tabla
                foreach ($facturas as $co_usuario => $data) {
                    foreach ($data as $annio => $listAnnios) {
                        foreach ($listAnnios as $mes => $listMes) {

                            $gananciasNetas = $listMes->sum('ganancias_neta');
                            $costo_fijo = $listMes->first()->costo_fijo; //obtenemos el primero porq es un monto invariable
                            $comision = $listMes->sum('comision');
                            $lucro = $listMes->sum('lucro');
                            $datosFormateados[$co_usuario][convertirNumerosAMeses($mes) . '-' . $annio] = ['gananciasNetas' => $gananciasNetas, 'costo_fijo' => $costo_fijo, 'comision' => $comision, 'lucro' => $lucro];
                        }
                    }
                }

                return view('informe', compact('datosFormateados', 'titulo'));

            case 'grafico':
                $facturas = $this->calcularBarra($usuarios, $fechaInicio, $fechaFin);
                if ($facturas->count() == 0) {
                    return back()->with([
                        'swal' => [
                            'title' => 'Error',
                            'message' => 'No hay facturas registradas o no hay suficientes datos para hacer los calculos correspondientes.',
                            'icon' => 'error'
                        ]
                    ]);
                }

                $tituloGrafica = "Rendimiento desde " . Carbon::parse($fechaInicio)->format('d/m/Y').' hasta ' .Carbon::parse($fechaInicio)->format('d/m/Y');
                $datosFormateados = [];
                $mesesName = [];
                $mesesNumero = [];
                $co_usuarios = [];

                //formatear las fechas barra horizontal y establecer cada co_usuario
                foreach ($facturas as $annio => $listAnnios) {
                    foreach ($listAnnios as $mes => $listMes) {
                        $mesTexto = convertirNumerosAMeses(intval($mes));
                        $key = $mesTexto . ' ' . $annio;
                        $mesesName[] = $key;
                        $mesesNumero[$mes] = 0;
                        foreach ($listMes as $co_usuario => $data) {
                            $co_usuarios[$co_usuario] = ['type' => 'column', 'name' => $co_usuario, 'data' => []];
                        }
                    }
                }

                //establecer la renta de cada uno de los co usuarios para q todos tengan la misma cantidad de meses
                foreach ($co_usuarios as $key => $co_usuario) {
                    $co_usuarios[$key]['data'] = $mesesNumero;
                }

                //establecer el contenido mensual de cada coo usuario y custo fixo
                $custoFixo = [];

                foreach ($facturas as $annio => $listAnnios) {
                    foreach ($listAnnios as $mes => $listMes) {
                        foreach ($listMes as $co_usuario => $data) {
                            $co_usuarios[$co_usuario]['data'][$mes] = $data->sum('ganancias_neta');
                            $custoFixo[$co_usuario] = $data->first()->costo_fijo;
                        }
                    }
                }

                //establecemos el custofixo
                $promedioCustoFix = 0;
                $totalCustoFix = 0;

                foreach ($custoFixo as $custo) {
                    $totalCustoFix += $custo;
                }

                $promedioCustoFix = $totalCustoFix / count($custoFixo);
                //establecemos el promedio mensual custo fixo
                foreach ($mesesNumero as $mes) {
                    $custoFixoMensual[] = $promedioCustoFix;
                }
                //
                $formatoGraficaCosUsuario = [];
                foreach ($co_usuarios as $co_usuario) {
                    $data = array_values($co_usuario['data']);
                    $co_usuario['data'] = $data;
                    $formatoGraficaCosUsuario[] = $co_usuario;
                }

                //agregamos el custo fixo como grafico de linea
                $formatoGraficaCosUsuario[] = ['type' => 'line', 'step' => 'center', 'name' => 'Custo Fixo Medio', 'data' => $custoFixoMensual];

                return view('grafico', compact('mesesName', 'formatoGraficaCosUsuario', 'tituloGrafica'));

            case 'pizza':
                $facturas = $this->calcularPizza($usuarios, $fechaInicio, $fechaFin);
                if ($facturas->count() == 0) {
                    return back()->with([
                        'swal' => [
                            'title' => 'Error',
                            'message' => 'No hay facturas registradas o no hay suficientes datos para hacer los calculos correspondientes.',
                            'icon' => 'error'
                        ]
                    ]);
                }

                $tituloGrafica = "participación en la recepción desde " . Carbon::parse($fechaInicio)->format('d/m/Y').' hasta ' .Carbon::parse($fechaInicio)->format('d/m/Y');
                $receitasFormat = [];
                $receitaTotal = 0;
                $countCosUsuario = count($facturas);
                //establecemos la receita total y la receta por usuario
                foreach ($facturas as $cos_usuario => $datos) {
                    $receitasFormat[$cos_usuario] = $datos->sum('ganancias_neta');
                    $receitaTotal += $datos->sum('ganancias_neta');
                }

                $pizzaFormat = [];
                //formateamos los datos para la grafica de pizza
                foreach ($receitasFormat as $cosUsuario => $receita) {
                    $data = ['name' => $cosUsuario, 'y' => ($receita * 100) / $receitaTotal];
                    $pizzaFormat[] = $data;
                }

                return view('pizza', compact('tituloGrafica', 'tituloGrafica', 'pizzaFormat'));

            default:
                return back()->with([
                    'swal' => [
                        'title' => 'Error',
                        'message' => 'EL tipo de consulta no pudo ser procesada, porfavor elija un tipo de dato a consultar.',
                        'icon' => 'error'
                    ]
                ]);
        }
    }


    public function cliente(Request $request)
    {
        $usuarios = CaoCliente::select('cao_usuario.co_usuario', 'no_usuario')
            ->get();

        return view('desempenno', compact('usuarios'));
    }

    public function informe(FiltroRequest $request)
    {
        $usuarios = explode(',', $request->co_usuario);
        $fechaInicioDividir = explode('-', $request->fechaInicio);
        $fechaFinDividir = explode('-', $request->fechaFin);

        $mesInicio = $fechaInicioDividir[0];
        $annioInicio = $fechaInicioDividir[1];

        $mesFin = $fechaFinDividir[0];
        $annioFin = $fechaFinDividir[1];

        $facturas = CaoFactura::join('cao_os', 'cao_fatura.co_os', 'cao_os.co_os')
            ->join('cao_salario', 'cao_os.co_usuario', 'cao_salario.co_usuario')
            ->whereIn('cao_os.co_usuario', $usuarios)
            ->where(function ($query) use ($mesInicio, $annioInicio) {
                $query->whereMonth('data_emissao', '>=', $mesInicio)
                    ->whereYear('data_emissao', '>=', $annioInicio);
            })
            ->where(function ($query) use ($mesFin, $annioFin) {
                $query->whereMonth('data_emissao', '<=', $mesFin)
                    ->whereYear('data_emissao', '<=', $annioFin);
            })

            ->select(
                'cao_os.co_usuario',
                DB::raw('MONTH(data_emissao) as mes'),
                DB::raw('YEAR(data_emissao) AS annio'),
                DB::raw('valor - (valor * (total_imp_inc /100)) as ganancias_neta'),
                'brut_salario as costo_fijo',
                DB::raw('(valor - (valor * (total_imp_inc/100))) * (comissao_cn/100) as comision'),
                DB::raw('(valor - (valor * (total_imp_inc/100))) - (brut_salario + ((valor - (valor * (total_imp_inc/100))) * (comissao_cn/100))) as lucro'),
            )
            ->get()
            ->groupBy(['co_usuario', 'annio', 'mes']);
        //validacion en caso que no encuentre registro
        if ($facturas->count() == 0) {
            return back()->with([
                'swal' => [
                    'title' => 'Error',
                    'message' => 'No hay facturas registradas o no hay suficientes datos para hacer los calculos correspondientes. Puede ser q halla q establecerle salario bruto al consultor.',
                    'icon' => 'error'
                ]
            ]);
        }
        $datosFormateados = [];

        foreach ($facturas as $co_usuario => $data) {
            foreach ($data as $annio => $listAnnios) {
                foreach ($listAnnios as $mes => $listMes) {

                    $gananciasNetas = $listMes->sum('ganancias_neta');
                    $costo_fijo = $listMes->first()->costo_fijo; //obtenemos el primero porq es un monto invariable
                    $comision = $listMes->sum('comision');
                    $lucro = $listMes->sum('lucro');
                    $datosFormateados[$co_usuario][$mes . '-' . $annio] = ['gananciasNetas' => $gananciasNetas, 'costo_fijo' => $costo_fijo, 'comision' => $comision, 'lucro' => $lucro];
                }
            }
        }

        return view('informe', compact('datosFormateados'));
    }

    public function grafico(FiltroRequest $request)
    {
        $usuarios = explode(',', $request->co_usuario);
        $fechaInicioDividir = explode('-', $request->fechaInicio);
        $fechaFinDividir = explode('-', $request->fechaFin);

        $mesInicio = $fechaInicioDividir[0];
        $annioInicio = $fechaInicioDividir[1];

        $mesFin = $fechaFinDividir[0];
        $annioFin = $fechaFinDividir[1];

        $tituloGrafica = "Performance comercio desde " . convertirNumerosAMeses(intval($mesInicio)) . ' del ' . $annioInicio . ' hasta ' . $mesFin . ' del ' . $annioFin;

        $facturas = CaoFactura::join('cao_os', 'cao_fatura.co_os', 'cao_os.co_os')
            ->join('cao_salario', 'cao_os.co_usuario', 'cao_salario.co_usuario')
            ->whereIn('cao_os.co_usuario', $usuarios)
            ->where(function ($query) use ($mesInicio, $annioInicio) {
                $query->whereMonth('data_emissao', '>=', $mesInicio)
                    ->whereYear('data_emissao', '>=', $annioInicio);
            })
            ->where(function ($query) use ($mesFin, $annioFin) {
                $query->whereMonth('data_emissao', '<=', $mesFin)
                    ->whereYear('data_emissao', '<=', $annioFin);
            })

            ->select(
                'cao_os.co_usuario',
                'data_emissao',
                DB::raw('MONTH(data_emissao) as mes'),
                DB::raw('YEAR(data_emissao) AS annio'),
                DB::raw('valor - (valor * (total_imp_inc /100)) as ganancias_neta'),
                'brut_salario as costo_fijo',
            )
            ->get()
            ->groupBy(['annio', 'mes', 'co_usuario']);

        //validacion en caso que no encuentre registro
        if ($facturas->count() == 0) {
            return back()->with([
                'swal' => [
                    'title' => 'Error',
                    'message' => 'No hay facturas registradas o no hay suficientes datos para hacer los calculos correspondientes. Puede ser q halla q establecerle salario bruto al consultor.',
                    'icon' => 'error'
                ]
            ]);
        }

        $datosFormateados = [];
        $mesesName = [];
        $mesesNumero = [];
        $co_usuarios = [];

        //formatear las fechas barra horizontal y establecer cada co_usuario
        foreach ($facturas as $annio => $listAnnios) {
            foreach ($listAnnios as $mes => $listMes) {
                $mesTexto = convertirNumerosAMeses(intval($mes));
                $key = $mesTexto . ' ' . $annio;
                $mesesName[] = $key;
                $mesesNumero[$mes] = 0;
                foreach ($listMes as $co_usuario => $data) {
                    $co_usuarios[$co_usuario] = ['type' => 'column', 'name' => $co_usuario, 'data' => []];
                }
            }
        }

        //establecer la renta de cada uno de los co usuarios para q todos tengan la misma cantidad de meses
        foreach ($co_usuarios as $key => $co_usuario) {
            $co_usuarios[$key]['data'] = $mesesNumero;
        }

        //establecer el contenido mensual de cada coo usuario y custo fixo
        $custoFixo = [];

        foreach ($facturas as $annio => $listAnnios) {
            foreach ($listAnnios as $mes => $listMes) {
                foreach ($listMes as $co_usuario => $data) {
                    $co_usuarios[$co_usuario]['data'][$mes] = $data->sum('ganancias_neta');
                    $custoFixo[$co_usuario] = $data->first()->costo_fijo;
                }
            }
        }

        //establecemos el custofixo
        $promedioCustoFix = 0;
        $totalCustoFix = 0;

        foreach ($custoFixo as $custo) {
            $totalCustoFix += $custo;
        }

        $promedioCustoFix = $totalCustoFix / count($custoFixo);
        //establecemos el promedio mensual custo fixo
        foreach ($mesesNumero as $mes) {
            $custoFixoMensual[] = $promedioCustoFix;
        }
        //
        $formatoGraficaCosUsuario = [];
        foreach ($co_usuarios as $co_usuario) {
            $data = array_values($co_usuario['data']);
            $co_usuario['data'] = $data;
            $formatoGraficaCosUsuario[] = $co_usuario;
        }

        //agregamos el custo fixo como grafico de linea
        $formatoGraficaCosUsuario[] = ['type' => 'line', 'step' => 'center', 'name' => 'Custo Fixo Medio', 'data' => $custoFixoMensual];

        return view('grafico', compact('mesesName', 'formatoGraficaCosUsuario', 'tituloGrafica'));
    }

    public function pizza(FiltroRequest $request)
    {
        $usuarios = explode(',', $request->co_usuario);
        $fechaInicioDividir = explode('-', $request->fechaInicio);
        $fechaFinDividir = explode('-', $request->fechaFin);

        $mesInicio = $fechaInicioDividir[0];
        $annioInicio = $fechaInicioDividir[1];

        $mesFin = $fechaFinDividir[0];
        $annioFin = $fechaFinDividir[1];

        $tituloGrafica = "participación en la recepción desde " . convertirNumerosAMeses(intval($mesInicio)) . ' del ' . $annioInicio . ' hasta ' . $mesFin . ' del ' . $annioFin;

        $facturas = CaoFactura::join('cao_os', 'cao_fatura.co_os', 'cao_os.co_os')
            ->join('cao_salario', 'cao_os.co_usuario', 'cao_salario.co_usuario')
            ->whereIn('cao_os.co_usuario', $usuarios)
            ->where(function ($query) use ($mesInicio, $annioInicio) {
                $query->whereMonth('data_emissao', '>=', $mesInicio)
                    ->whereYear('data_emissao', '>=', $annioInicio);
            })
            ->where(function ($query) use ($mesFin, $annioFin) {
                $query->whereMonth('data_emissao', '<=', $mesFin)
                    ->whereYear('data_emissao', '<=', $annioFin);
            })

            ->select(
                'cao_os.co_usuario',
                DB::raw('MONTH(data_emissao) as mes'),
                DB::raw('YEAR(data_emissao) AS annio'),
                DB::raw('valor - (valor * (total_imp_inc /100)) as ganancias_neta')
            )
            ->get()
            ->groupBy(['co_usuario']);

        //validacion en caso que no encuentre registro
        if ($facturas->count() == 0) {
            return back()->with([
                'swal' => [
                    'title' => 'Error',
                    'message' => 'No hay facturas registradas o no hay suficientes datos para hacer los calculos correspondientes.',
                    'icon' => 'error'
                ]
            ]);
        }

        $receitasFormat = [];
        $receitaTotal = 0;
        $countCosUsuario = count($facturas);
        foreach ($facturas as $cos_usuario => $datos) {
            $receitasFormat[$cos_usuario] = $datos->sum('ganancias_neta');
            $receitaTotal += $datos->sum('ganancias_neta');
        }

        $pizzaFormat = [];

        foreach ($receitasFormat as $cosUsuario => $receita) {
            $data = ['name' => $cosUsuario, 'y' => ($receita * 100) / $receitaTotal];
            $pizzaFormat[] = $data;
        }

        return view('pizza', compact('tituloGrafica', 'tituloGrafica', 'pizzaFormat'));
    }
}
