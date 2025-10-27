<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Debito;
use App\Models\Pagamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClienteMovimentoController extends Controller
{
    private const DEBITO_CATEGORIAS = ['Ferragens', 'PET'];

    /**
     * Lista débitos e pagamentos do cliente, ordenados por data desc.
     */
    public function index(Cliente $cliente)
    {
        $movimentos = $cliente->debitos()
            ->select('id', 'descricao', 'tipo', 'valor', 'data', 'created_at')
            ->get()
            ->map(function (Debito $debito) {
                return [
                    'id' => $debito->id,
                    'tipo_movimento' => 'debito',
                    'descricao' => $debito->descricao,
                    'categoria' => $debito->tipo,
                    'valor' => (float) $debito->valor,
                    'data' => $debito->data,
                    'created_at' => $debito->created_at,
                ];
            })
            ->concat($cliente->pagamentos()
                ->select('id', 'descricao', 'valor', 'data', 'created_at')
                ->get()
                ->map(function (Pagamento $pagamento) {
                    return [
                        'id' => $pagamento->id,
                        'tipo_movimento' => 'pagamento',
                        'descricao' => $pagamento->descricao,
                        'categoria' => null,
                        'valor' => (float) $pagamento->valor,
                        'data' => $pagamento->data,
                        'created_at' => $pagamento->created_at,
                    ];
                }))
            ->sortByDesc('data')
            ->values()
            ->all();

        return response()->json([
            'cliente_id' => $cliente->id,
            'nome' => $cliente->nome,
            'saldo_atual' => $cliente->saldo_atual,
            'movimentos' => $movimentos,
        ]);
    }

    /**
     * Registra um débito para o cliente.
     */
    public function storeDebito(Request $request, Cliente $cliente)
    {
        $categoriaNormalizada = $this->normalizarCategoria($request->input('tipo'));
        $request->merge(['tipo' => $categoriaNormalizada]);

        $dados = $request->validate([
            'descricao' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(self::DEBITO_CATEGORIAS)],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data' => ['required', 'date'],
        ]);

        $debito = DB::transaction(function () use ($cliente, $dados) {
            return $cliente->debitos()->create($dados);
        });

        return response()->json([
            'mensagem' => 'Débito registrado com sucesso.',
            'debito' => [
                'id' => $debito->id,
                'descricao' => $debito->descricao,
                'tipo' => $debito->tipo,
                'valor' => (float) $debito->valor,
                'data' => $debito->data,
                'created_at' => $debito->created_at,
            ],
        ], 201);
    }

    /**
     * Registra um pagamento para o cliente.
     */
    public function storePagamento(Request $request, Cliente $cliente)
    {
        $dados = $request->validate([
            'descricao' => ['nullable', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data' => ['required', 'date'],
        ]);

        $pagamento = DB::transaction(function () use ($cliente, $dados) {
            return $cliente->pagamentos()->create($dados);
        });

        return response()->json([
            'mensagem' => 'Pagamento registrado com sucesso.',
            'pagamento' => [
                'id' => $pagamento->id,
                'descricao' => $pagamento->descricao,
                'valor' => (float) $pagamento->valor,
                'data' => $pagamento->data,
                'created_at' => $pagamento->created_at,
            ],
        ], 201);
    }

    /**
     * Remove um débito do cliente.
     */
    public function destroyDebito(Cliente $cliente, Debito $debito)
    {
        if ($debito->cliente_id !== $cliente->id) {
            abort(404);
        }

        $debito->delete();

        return response()->json([
            'mensagem' => 'Débito removido com sucesso.',
        ]);
    }

    /**
     * Remove um pagamento do cliente.
     */
    public function destroyPagamento(Cliente $cliente, Pagamento $pagamento)
    {
        if ($pagamento->cliente_id !== $cliente->id) {
            abort(404);
        }

        $pagamento->delete();

        return response()->json([
            'mensagem' => 'Pagamento removido com sucesso.',
        ]);
    }

    private function normalizarCategoria(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valorLimpo = trim($valor);

        foreach (self::DEBITO_CATEGORIAS as $categoria) {
            if (strcasecmp($categoria, $valorLimpo) === 0) {
                return $categoria;
            }
        }

        return $valorLimpo === '' ? null : $valorLimpo;
    }
}
