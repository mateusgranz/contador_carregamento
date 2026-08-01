<?php

use App\Http\Controllers\LoadingController;
use App\Http\Controllers\LoadingFieldController;
use App\Http\Controllers\LoadingItemController;
use App\Http\Controllers\LoadingWeighingController;
use App\Http\Controllers\PackageTypeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// A raiz leva direto para a área do usuário; quem não está logado vai para o login
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Dashboard redireciona para a área correta conforme o role do usuário
Route::get('/dashboard', function () {
    return match (auth()->user()->role) {
        'gestor'     => redirect()->route('produtos.index'),
        'carregador' => redirect()->route('carregamento.index'),
        default      => abort(403),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rotas do Gestor — URLs em português conforme spec
Route::resourceVerbs(['create' => 'criar', 'edit' => 'editar']);

Route::middleware(['auth', 'gestor'])->group(function () {
    Route::resource('produtos', ProductController::class)
        ->except(['show'])
        ->parameters(['produtos' => 'produto']);

    Route::delete('produtos/{produto}/pacotes/{pacote}', [PackageTypeController::class, 'destroy'])
        ->name('pacotes.destroy');

    // Campos extras que o carregador deve preencher
    Route::resource('campos', LoadingFieldController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['campos' => 'campo']);

    // Usuários da ferramenta — acesso por código
    Route::resource('usuarios', UserController::class)
        ->except(['show'])
        ->parameters(['usuarios' => 'usuario']);
});

// Rotas do Carregador
Route::middleware(['auth', 'carregador'])->group(function () {
    // Passo 1: escolher o produto
    Route::get('carregamento', [LoadingController::class, 'index'])->name('carregamento.index');
    // Passo 2: informar a metragem do pedido
    Route::get('carregamento/produto/{produto}', [LoadingController::class, 'quantidade'])
        ->name('carregamento.quantidade');
    // Passo 3: contador
    Route::post('carregamento', [LoadingController::class, 'store'])->name('carregamento.store');
    Route::get('carregamento/{carregamento}', [LoadingController::class, 'show'])->name('carregamento.show');

    // Contador — um toque adiciona, um toque remove
    Route::post('carregamento/{carregamento}/itens', [LoadingItemController::class, 'store'])
        ->name('carregamento.itens.store');
    Route::delete('carregamento/{carregamento}/itens/{pacote}', [LoadingItemController::class, 'destroy'])
        ->name('carregamento.itens.destroy');

    // Pesagens — modalidade de conversão de peso
    Route::post('carregamento/{carregamento}/pesagens', [LoadingWeighingController::class, 'store'])
        ->name('carregamento.pesagens.store');
    Route::delete('carregamento/{carregamento}/pesagens/{pesagem}', [LoadingWeighingController::class, 'destroy'])
        ->name('carregamento.pesagens.destroy');

    // Finalização, resumo e PDF
    Route::post('carregamento/{carregamento}/finalizar', [LoadingController::class, 'finalizar'])
        ->name('carregamento.finalizar');
    Route::get('carregamento/{carregamento}/finalizar', [LoadingController::class, 'resumo'])
        ->name('carregamento.resumo');
    Route::get('carregamento/{carregamento}/pdf', [LoadingController::class, 'pdf'])
        ->name('carregamento.pdf');
});

require __DIR__.'/auth.php';
