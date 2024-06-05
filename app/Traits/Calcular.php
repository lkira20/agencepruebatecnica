<?php

namespace App\Traits;

use App\Models\CaoFactura;
use Illuminate\Support\Facades\DB;

trait Calcular {
    public function calcularinforme($usuarios, $fechaInicio, $fechaFin)
    {
        return CaoFactura::join('cao_os', 'cao_fatura.co_os', 'cao_os.co_os')
        ->join('cao_salario', 'cao_os.co_usuario', 'cao_salario.co_usuario')
        ->whereIn('cao_os.co_usuario', $usuarios)
        ->where(function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereDate('data_emissao', '>=', $fechaInicio)
                ->whereDate('data_emissao', '<=', $fechaFin);
        })
        ->select(
            'cao_os.co_usuario',
            DB::raw('MONTH(data_emissao) as mes'),
            DB::raw('YEAR(data_emissao) AS annio'),
            DB::raw('valor - (valor * (total_imp_inc /100)) as ganancias_neta'),
            'brut_salario as costo_fijo',
            DB::raw('(valor - (valor * (total_imp_inc/100))) * (comissao_cn/100) as comision'),
        )
        ->get()
        ->groupBy(['co_usuario', 'annio', 'mes']);
    }

    public function calcularBarra($usuarios, $fechaInicio, $fechaFin)
    {
        return CaoFactura::join('cao_os', 'cao_fatura.co_os', 'cao_os.co_os')
        ->join('cao_salario', 'cao_os.co_usuario', 'cao_salario.co_usuario')
        ->whereIn('cao_os.co_usuario', $usuarios)
        ->where(function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereDate('data_emissao', '>=', $fechaInicio)
                ->whereDate('data_emissao', '<=', $fechaFin);
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
    }

    public function calcularPizza($usuarios, $fechaInicio, $fechaFin)
    {
        return CaoFactura::join('cao_os', 'cao_fatura.co_os', 'cao_os.co_os')
            ->join('cao_salario', 'cao_os.co_usuario', 'cao_salario.co_usuario')
            ->whereIn('cao_os.co_usuario', $usuarios)
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereDate('data_emissao', '>=', $fechaInicio)
                    ->whereDate('data_emissao', '<=', $fechaFin);
            })
            ->select(
                'cao_os.co_usuario',
                DB::raw('MONTH(data_emissao) as mes'),
                DB::raw('YEAR(data_emissao) AS annio'),
                DB::raw('valor - (valor * (total_imp_inc /100)) as ganancias_neta')
            )
            ->get()
            ->groupBy(['co_usuario']);
    }
}
