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
        $limit = (int) $request->query('limit', 50);
        $limit = max(1, min($limit, 200));

        $debitosAtivos = Debito::query()
            ->join('clientes', 'clientes.id', '=', 'debitos.cliente_id')
            ->select([
                DB::raw("'debito' as tipo_marcacao"),
                'debitos.id',
                'debitos.descricao',
                'debitos.tipo',
                'debitos.valor',
                'debitos.created_at',
                'clientes.nome as cliente_nome',
            ])
            ->where('debitos.excluido', false)
            ->orderByDesc('debitos.id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'cliente' => $row->cliente_nome,
                'tipo' => $row->tipo_marcacao,
                'categoria' => $row->tipo,
                'descricao' => $row->descricao,
                'valor' => (float) $row->valor,
                'created_at' => $row->created_at,
                'status' => 'ativo',
            ])
            ->all();

        $debitosExcluidos = Debito::query()
            ->join('clientes', 'clientes.id', '=', 'debitos.cliente_id')
            ->select([
                DB::raw("'debito' as tipo_marcacao"),
                'debitos.id',
                'debitos.descricao',
                'debitos.tipo',
                'debitos.valor',
                'debitos.updated_at',
                'clientes.nome as cliente_nome',
            ])
            ->where('debitos.excluido', true)
            ->orderByDesc('debitos.id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'cliente' => $row->cliente_nome,
                'tipo' => $row->tipo_marcacao,
                'categoria' => $row->tipo,
                'descricao' => $row->descricao,
                'valor' => (float) $row->valor,
                'excluido_em' => $row->updated_at,
                'status' => 'excluido',
            ])
            ->all();

        $pagamentosAtivos = Pagamento::query()
            ->join('clientes', 'clientes.id', '=', 'pagamentos.cliente_id')
            ->select([
                DB::raw("'pagamento' as tipo_marcacao"),
                'pagamentos.id',
                'pagamentos.descricao',
                'pagamentos.valor',
                'pagamentos.created_at',
                'clientes.nome as cliente_nome',
            ])
            ->where('pagamentos.excluido', false)
            ->orderByDesc('pagamentos.id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'cliente' => $row->cliente_nome,
                'tipo' => $row->tipo_marcacao,
                'descricao' => $row->descricao,
                'valor' => (float) $row->valor,
                'created_at' => $row->created_at,
                'status' => 'ativo',
            ])
            ->all();

        $pagamentosExcluidos = Pagamento::query()
            ->join('clientes', 'clientes.id', '=', 'pagamentos.cliente_id')
            ->select([
                DB::raw("'pagamento' as tipo_marcacao"),
                'pagamentos.id',
                'pagamentos.descricao',
                'pagamentos.valor',
                'pagamentos.updated_at',
                'clientes.nome as cliente_nome',
            ])
            ->where('pagamentos.excluido', true)
            ->orderByDesc('pagamentos.id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'cliente' => $row->cliente_nome,
                'tipo' => $row->tipo_marcacao,
                'descricao' => $row->descricao,
                'valor' => (float) $row->valor,
                'excluido_em' => $row->updated_at,
                'status' => 'excluido',
            ])
            ->all();

        return response()->json([
            'debitos_ativos' => $debitosAtivos,
            'pagamentos_ativos' => $pagamentosAtivos,
            'debitos_excluidos' => $debitosExcluidos,
            'pagamentos_excluidos' => $pagamentosExcluidos,
        ]);
    }
}
