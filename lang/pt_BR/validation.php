<?php

/**
 * Mensagens de validação em português.
 *
 * Só as regras usadas pelo projeto estão traduzidas; o que faltar cai no
 * fallback em inglês definido em APP_FALLBACK_LOCALE.
 */
return [
    'accepted'   => 'O campo :attribute deve ser aceito.',
    'after'      => 'O campo :attribute deve conter uma data posterior a :date.',
    'array'      => 'O campo :attribute deve ser uma lista.',
    'before'     => 'O campo :attribute deve conter uma data anterior a :date.',
    'boolean'    => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed'  => 'A confirmação do campo :attribute não confere.',
    'current_password' => 'A senha está incorreta.',
    'date'       => 'O campo :attribute não é uma data válida.',
    'declined'   => 'O campo :attribute deve ser recusado.',
    'different'  => 'Os campos :attribute e :other devem ser diferentes.',
    'email'      => 'O campo :attribute deve ser um e-mail válido.',
    'exists'     => 'O :attribute selecionado é inválido.',
    'filled'     => 'O campo :attribute é obrigatório.',
    'in'         => 'O :attribute selecionado é inválido.',
    'integer'    => 'O campo :attribute deve ser um número inteiro.',
    'lowercase'  => 'O campo :attribute deve estar em letras minúsculas.',
    'numeric'    => 'O campo :attribute deve ser um número.',
    'present'    => 'O campo :attribute deve estar presente.',
    'regex'      => 'O formato do campo :attribute é inválido.',
    'required'   => 'O campo :attribute é obrigatório.',
    'required_with'      => 'O campo :attribute é obrigatório quando :values está presente.',
    'required_without'   => 'O campo :attribute é obrigatório quando :values não está presente.',
    'same'       => 'Os campos :attribute e :other devem ser iguais.',
    'string'     => 'O campo :attribute deve ser um texto.',
    'unique'     => 'Este :attribute já está em uso.',
    'uploaded'   => 'Falha ao enviar o campo :attribute.',
    'url'        => 'O campo :attribute deve ser uma URL válida.',

    'min' => [
        'array'   => 'O campo :attribute deve ter no mínimo :min itens.',
        'file'    => 'O campo :attribute deve ter no mínimo :min kilobytes.',
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string'  => 'O campo :attribute deve ter no mínimo :min caracteres.',
    ],

    'max' => [
        'array'   => 'O campo :attribute não pode ter mais que :max itens.',
        'file'    => 'O campo :attribute não pode ter mais que :max kilobytes.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string'  => 'O campo :attribute não pode ter mais que :max caracteres.',
    ],

    'between' => [
        'array'   => 'O campo :attribute deve ter entre :min e :max itens.',
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'string'  => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'mensagem personalizada',
        ],
    ],

    'attributes' => [
        'name'     => 'nome',
        'code'     => 'código de usuário',
        'email'    => 'e-mail',
        'password' => 'senha',
        'role'     => 'perfil',
    ],
];
