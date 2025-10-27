<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Debito;
use App\Models\Pagamento;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cliente::factory(8)
            ->create()
            ->each(function (Cliente $cliente): void {
                Debito::factory()
                    ->count(rand(1, 5))
                    ->for($cliente)
                    ->create();

                Pagamento::factory()
                    ->count(rand(1, 3))
                    ->for($cliente)
                    ->create();
            });
    }
}
