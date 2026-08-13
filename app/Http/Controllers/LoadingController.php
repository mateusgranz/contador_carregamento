<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoadingRequest;
use App\Models\Loading;
use App\Models\LoadingField;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoadingController extends Controller
{
    /**
     * Passo 1: escolha do produto.
     */
    public function index(): View
    {
        // Só entram produtos prontos para carregar em alguma das modalidades
        $produtos = Product::withCount('packageTypes')
            ->where(function ($query) {
                $query->whereIn('calc_mode', ['pacote', 'volume'])->has('packageTypes');
            })
            ->orWhere(function ($query) {
                $query->where('calc_mode', 'peso')->whereNotNull('kg_per_unit');
            })
            ->orderBy('name')
            ->get();

        $emAndamento = Loading::with('product')
            ->where('user_id', auth()->id())
            ->where('status', 'em_andamento')
            ->latest()
            ->first();

        return view('carregamento.index', compact('produtos', 'emAndamento'));
    }

    /**
     * Passo 2: pergunta a quantidade do pedido, na unidade do produto.
     */
    public function quantidade(Product $produto): View|RedirectResponse
    {
        if (! $produto->usaPeso() && $produto->packageTypes()->doesntExist()) {
            return redirect()->route('carregamento.index')
                ->withErrors('Este produto ainda não tem tipos de pacote cadastrados.');
        }

        if ($produto->usaPeso() && $produto->kg_per_unit === null) {
            return redirect()->route('carregamento.index')
                ->withErrors('Este produto ainda não tem o peso por unidade cadastrado.');
        }

        $campos = LoadingField::ativos()->get();

        return view('carregamento.quantidade', compact('produto', 'campos'));
    }

    /**
     * Inicia um novo carregamento para o produto escolhido.
     */
    public function store(StoreLoadingRequest $request): RedirectResponse
    {
        $carregamento = DB::transaction(function () use ($request) {
            $carregamento = Loading::create([
                'user_id'       => $request->user()->id,
                'product_id'    => $request->product_id,
                'target_amount' => $request->quantidade,
                'loaded_amount' => 0,
                'status'        => 'em_andamento',
            ]);

            // Guarda o que o carregador preencheu nos campos extras ativos
            foreach ($request->camposAtivos() as $campo) {
                $carregamento->fieldValues()->create([
                    'loading_field_id' => $campo->id,
                    'value'            => $request->input("campos.{$campo->id}"),
                ]);
            }

            return $carregamento;
        });

        return redirect()->route('carregamento.show', $carregamento);
    }

    /**
     * Passo 3: contador de pacotes ou calculadora de peso, conforme o produto.
     */
    public function show(Loading $carregamento, Request $request): View|RedirectResponse
    {
        $this->autorizarDono($carregamento);

        if (! $carregamento->emAndamento()) {
            return redirect()->route('carregamento.resumo', $carregamento);
        }

        $carregamento->load('product');

        return $carregamento->product->usaPeso()
            ? $this->telaDePesagem($carregamento, $request)
            : $this->telaDeContagem($carregamento);
    }

    /**
     * Contador de pacotes — serve tanto ao modo pacote (m²) quanto ao volume (m³).
     */
    private function telaDeContagem(Loading $carregamento): View
    {
        $tipos = $carregamento->product
            ->packageTypes()
            ->orderBy('length_cm')
            ->get();

        $quantidades = $carregamento->loadingItems()
            ->pluck('quantity', 'package_type_id');

        // Quando falta menos de um pacote, o pacote sugerido alimenta o aviso de fechamento
        $ideal = $carregamento->pacoteIdealPara($tipos);

        return view('carregamento.show', [
            'carregamento' => $carregamento,
            'produto'      => $carregamento->product,
            'tipos'        => $tipos,
            'quantidades'  => $quantidades,
            'ideal'        => $ideal,
            'restante'     => $carregamento->restante(),
        ]);
    }

    /**
     * Calculadora de peso (modo peso).
     *
     * O peso digitado chega por query string: o cálculo é só uma consulta,
     * nada é gravado até o carregador escolher o que registrar.
     */
    private function telaDePesagem(Loading $carregamento, Request $request): View
    {
        $produto  = $carregamento->product;
        $restante = $carregamento->restante();

        $calculo = null;
        $peso    = $request->query('peso');

        if (is_numeric($peso) && (float) $peso > 0) {
            $peso       = (float) $peso;
            $quantidade = $produto->kgParaUnidade($peso);
            $excedente  = $quantidade - max($restante, 0);

            $calculo = [
                'peso_kg'         => $peso,
                'quantidade'      => $quantidade,
                'excedente'       => $excedente,
                // Quanto sobra/falta convertido de volta para a balança
                'excedente_kg'    => $produto->unidadeParaKg(abs($excedente)),
                // Peso que a balança deve marcar para fechar exatamente o pedido
                'peso_alvo_kg'    => $produto->unidadeParaKg(max($restante, 0)),
                'quantidade_alvo' => max($restante, 0),
            ];
        }

        return view('carregamento.pesagem', [
            'carregamento' => $carregamento,
            'produto'      => $produto,
            'pesagens'     => $carregamento->weighings()->latest('id')->get(),
            'restante'     => $restante,
            'pesoAlvoKg'   => $produto->unidadeParaKg(max($restante, 0)),
            'calculo'      => $calculo,
        ]);
    }

    /**
     * Marca o carregamento como finalizado.
     */
    public function finalizar(Loading $carregamento): RedirectResponse
    {
        $this->autorizarDono($carregamento);

        if ($carregamento->emAndamento()) {
            // Garante que o total salvo reflete exatamente os registros
            $carregamento->recalcularTotal();

            $carregamento->update([
                'status'      => 'finalizado',
                'finished_at' => now(),
            ]);
        }

        return redirect()->route('carregamento.resumo', $carregamento);
    }

    /**
     * Resumo do carregamento finalizado — origem do PDF e do envio por WhatsApp.
     */
    public function resumo(Loading $carregamento): View|RedirectResponse
    {
        $this->autorizarDono($carregamento);

        if ($carregamento->emAndamento()) {
            return redirect()->route('carregamento.show', $carregamento);
        }

        $carregamento->load($this->relacoesDoResumo($carregamento));

        return view('carregamento.resumo', compact('carregamento'));
    }

    /**
     * Gera o PDF do carregamento finalizado.
     */
    public function pdf(Loading $carregamento): Response
    {
        $this->autorizarDono($carregamento);

        abort_if($carregamento->emAndamento(), 404, 'Carregamento ainda não finalizado.');

        $carregamento->load($this->relacoesDoResumo($carregamento));

        // O comprovante sai com ~850 KB porque a DejaVu vai embutida inteira.
        // Tentei ligar isFontSubsettingEnabled, que derruba para 20 KB, mas o
        // DomPDF quebra na geração com "Path must not be empty" ao montar o
        // subset. Fica o arquivo maior: melhor pesado que indisponível.
        $pdf = Pdf::loadView('carregamento.pdf', compact('carregamento'))
            ->setPaper('a4');

        return $pdf->download($carregamento->nomeDoArquivoPdf());
    }

    /**
     * @return array<int, string>
     */
    private function relacoesDoResumo(Loading $carregamento): array
    {
        $relacoes = ['product', 'user', 'fieldValues.loadingField'];

        return $carregamento->product->usaPeso()
            ? [...$relacoes, 'weighings']
            : [...$relacoes, 'loadingItems.packageType'];
    }

    /**
     * Um carregador só enxerga os próprios carregamentos.
     */
    private function autorizarDono(Loading $carregamento): void
    {
        abort_if($carregamento->user_id !== auth()->id(), 403, 'Este carregamento é de outro carregador.');
    }
}
