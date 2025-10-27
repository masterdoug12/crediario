<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Lista clientes com filtro opcional por nome ou telefone.
     */
    public function index(Request $request)
    {
        $busca = $request->string('q')->trim();

        $clientes = Cliente::query()
            ->when($busca->isNotEmpty(), function ($query) use ($busca) {
                $query->where(function ($sub) use ($busca) {
                    $sub->where('nome', 'ilike', '%'.$busca.'%')
                        ->orWhere('telefone', 'ilike', '%'.$busca.'%');
                });
            })
            ->withSum('debitos as total_debitos', 'valor')
            ->withSum('pagamentos as total_pagamentos', 'valor')
            ->orderBy('nome')
            ->get()
            ->map(fn (Cliente $cliente) => $this->formatClienteResumo($cliente))
            ->values()
            ->all();

        return response()->json($clientes);
    }

    /**
     * Cria um novo cliente.
     */
    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:50'],
            'endereco' => ['nullable', 'string', 'max:255'],
        ]);

        $cliente = Cliente::create($dados);

        return response()->json($this->formatClienteDetalhe($cliente), 201);
    }

    /**
     * Exibe um cliente específico com totais.
     */
    public function show(Cliente $cliente)
    {
        $cliente->loadSum('debitos as total_debitos', 'valor');
        $cliente->loadSum('pagamentos as total_pagamentos', 'valor');

        return response()->json($this->formatClienteDetalhe($cliente));
    }

    /**
     * Atualiza um cliente.
     */
    public function update(Request $request, Cliente $cliente)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:50'],
            'endereco' => ['nullable', 'string', 'max:255'],
        ]);

        $cliente->update($dados);
        $cliente->loadSum('debitos as total_debitos', 'valor');
        $cliente->loadSum('pagamentos as total_pagamentos', 'valor');

        return response()->json($this->formatClienteDetalhe($cliente));
    }

    /**
     * Remove um cliente.
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return response()->noContent();
    }

    private function formatClienteResumo(Cliente $cliente): array
    {
        $totalDebitos = (float) ($cliente->total_debitos ?? 0);
        $totalPagamentos = (float) ($cliente->total_pagamentos ?? 0);

        return [
            'id' => $cliente->id,
            'nome' => $cliente->nome,
            'telefone' => $cliente->telefone,
            'endereco' => $cliente->endereco,
            'saldo_atual' => $totalDebitos - $totalPagamentos,
            'total_debitos' => $totalDebitos,
            'total_pagamentos' => $totalPagamentos,
            'created_at' => $cliente->created_at,
            'updated_at' => $cliente->updated_at,
        ];
    }

    private function formatClienteDetalhe(Cliente $cliente): array
    {
        $clienteArray = $this->formatClienteResumo($cliente);
        $clienteArray['debitos'] = $cliente->debitos()
            ->orderByDesc('data')
            ->get(['id', 'descricao', 'tipo', 'valor', 'data', 'created_at'])
            ->map(fn ($debito) => [
                'id' => $debito->id,
                'descricao' => $debito->descricao,
                'tipo' => $debito->tipo,
                'valor' => (float) $debito->valor,
                'data' => $debito->data,
                'created_at' => $debito->created_at,
            ])
            ->values()
            ->all();

        $clienteArray['pagamentos'] = $cliente->pagamentos()
            ->orderByDesc('data')
            ->get(['id', 'descricao', 'valor', 'data', 'created_at'])
            ->map(fn ($pagamento) => [
                'id' => $pagamento->id,
                'descricao' => $pagamento->descricao,
                'valor' => (float) $pagamento->valor,
                'data' => $pagamento->data,
                'created_at' => $pagamento->created_at,
            ])
            ->values()
            ->all();

        return $clienteArray;
    }
}
