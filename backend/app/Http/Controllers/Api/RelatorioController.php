<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debito;
use App\Models\Pagamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    public function marcacoes(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min($limit, 100));

        $debitos = Debito::query()
            ->join('clientes', 'clientes.id', '=', 'debitos.cliente_id')
            ->select([
                DB::raw("'debito' as tipo_marcacao"),
                'debitos.id',
                'debitos.descricao',
                'debitos.created_at',
                'clientes.nome as cliente_nome',
            ]);

        $pagamentos = Pagamento::query()
            ->join('clientes', 'clientes.id', '=', 'pagamentos.cliente_id')
            ->select([
                DB::raw("'pagamento' as tipo_marcacao"),
                'pagamentos.id',
                'pagamentos.descricao',
                'pagamentos.created_at',
                'clientes.nome as cliente_nome',
            ]);

        $marcacoes = DB::query()
            ->fromSub($debitos->unionAll($pagamentos), 'marcacoes')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'cliente' => $row->cliente_nome,
                'tipo' => $row->tipo_marcacao,
                'descricao' => $row->descricao,
                'created_at' => $row->created_at,
            ])
            ->all();

        return response()->json([
            'marcacoes' => $marcacoes,
        ]);
    }
}
