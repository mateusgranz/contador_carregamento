<?php

namespace Database\Seeders;

use App\Models\PackageType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dados de demonstração para testar a plataforma localmente.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Usuários de teste — um de cada perfil
        User::updateOrCreate(
            ['code' => 'gestor'],
            [
                'name'     => 'Gestor Demo',
                'password' => Hash::make('senha1234'),
                'role'     => 'gestor',
            ],
        );

        User::updateOrCreate(
            ['code' => 'carregador'],
            [
                'name'     => 'Carregador Demo',
                'password' => Hash::make('senha1234'),
                'role'     => 'carregador',
            ],
        );

        // Produtos com tipos de pacote reais de madeireira
        $catalogo = [
            [
                'name'        => 'Forro PVC Branco',
                'unit'        => 'm2',
                'description' => 'Forro em PVC, acabamento liso.',
                'pacotes'     => [
                    ['length_cm' => 300, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8],
                    ['length_cm' => 400, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8],
                    ['length_cm' => 600, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8],
                ],
            ],
            [
                'name'        => 'Deck Cumaru',
                'unit'        => 'm2',
                'description' => 'Régua de deck em cumaru aparelhado.',
                'pacotes'     => [
                    ['length_cm' => 200, 'width_mm' => 90, 'thickness_mm' => 21, 'pieces_count' => 20],
                    ['length_cm' => 300, 'width_mm' => 90, 'thickness_mm' => 21, 'pieces_count' => 20],
                ],
            ],
            [
                'name'        => 'Tábua Pinus Bruta',
                'unit'        => 'm3',
                'description' => 'Tábua de pinus serrada bruta.',
                'pacotes'     => [
                    ['length_cm' => 250, 'width_mm' => 150, 'thickness_mm' => 25, 'pieces_count' => 30],
                    ['length_cm' => 350, 'width_mm' => 150, 'thickness_mm' => 25, 'pieces_count' => 30],
                ],
            ],
        ];

        foreach ($catalogo as $item) {
            $produto = Product::updateOrCreate(
                ['name' => $item['name']],
                [
                    'unit'        => $item['unit'],
                    'description' => $item['description'],
                ],
            );

            foreach ($item['pacotes'] as $pacote) {
                // sqm_per_package é calculado no Model (evento saving)
                PackageType::updateOrCreate(
                    [
                        'product_id'   => $produto->id,
                        'length_cm'    => $pacote['length_cm'],
                        'width_mm'     => $pacote['width_mm'],
                        'thickness_mm' => $pacote['thickness_mm'],
                    ],
                    ['pieces_count' => $pacote['pieces_count']],
                );
            }
        }
    }
}
