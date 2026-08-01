<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoadingFieldRequest;
use App\Http\Requests\UpdateLoadingFieldRequest;
use App\Models\LoadingField;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoadingFieldController extends Controller
{
    public function index(): View
    {
        $campos = LoadingField::orderBy('position')->orderBy('id')->get();

        return view('campos.index', compact('campos'));
    }

    public function store(StoreLoadingFieldRequest $request): RedirectResponse
    {
        LoadingField::create([
            'label'    => $request->label,
            'type'     => $request->type,
            'required' => $request->required,
            'active'   => $request->active,
            'position' => (LoadingField::max('position') ?? 0) + 1,
        ]);

        return redirect()->route('campos.index')
            ->with('sucesso', 'Campo criado com sucesso.');
    }

    public function update(UpdateLoadingFieldRequest $request, LoadingField $campo): RedirectResponse
    {
        // O toggle da listagem altera apenas a ativação
        if ($request->apenasToggle()) {
            $campo->update(['active' => $request->active]);

            return redirect()->route('campos.index')->with(
                'sucesso',
                $campo->active
                    ? "O campo \"{$campo->label}\" agora aparece para o carregador."
                    : "O campo \"{$campo->label}\" foi desativado.",
            );
        }

        $campo->update($request->only(['label', 'type', 'required', 'active']));

        return redirect()->route('campos.index')
            ->with('sucesso', 'Campo atualizado com sucesso.');
    }

    public function destroy(LoadingField $campo): RedirectResponse
    {
        $campo->delete();

        return redirect()->route('campos.index')
            ->with('sucesso', 'Campo excluído.');
    }
}
