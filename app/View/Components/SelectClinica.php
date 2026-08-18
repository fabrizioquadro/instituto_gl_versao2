<?php

namespace App\View\Components;

use App\Models\Clinica;
use Illuminate\View\Component;

class SelectClinica extends Component
{
    /**
     * Valor especial que indica "use a clínica ativa do usuário logado".
     */
    public const PADRAO_USUARIO = '__clinica_do_usuario__';

    public array $clinicas;
    public mixed $valor;

    public function __construct(
        public string $name = 'clinica_id',
        public ?string $id = null,
        public string $label = 'Clínica',
        public bool $required = false,
        public mixed $value = self::PADRAO_USUARIO,
        public ?string $placeholder = null,
        public string $class = 'form-select',
    ) {
        $this->clinicas = Clinica::orderBy('nome')->get(['id', 'nome'])->all();

        // Lógica SEMPRE aplicável: por padrão, o select já vem com a clínica
        // ativa do usuário logado (escolhida no perfil). Para não pré-selecionar
        // nada, passe :value="''".
        $this->valor = $value === self::PADRAO_USUARIO ? auth()->user()?->clinica_id : $value;
        $this->id = $id ?? $name;
    }

    public function render()
    {
        return view('components.select-clinica');
    }
}
