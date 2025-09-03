<?php

return [
    /**
     * Control if the seeder should create a user per role while seeding the data.
     */
    'create_users' => [
        'landlord' => false,
        'tenant'   => false,
    ],

    /**
     * Control if all the permissions tables should be truncated before running the seeder.
     */
    'truncate_tables' => true,

    'roles_structure' => [
        'landlord' => [
            'Superadministrador' => [
                'Usuários'             => 'c,r,u,d',
                'Planos'               => 'c,r,u,d',
                'Contas de Clientes'   => 'c,r,u,d',
                'Categorias de Contas' => 'c,r,u,d',
                'Níveis de Acessos'    => 'c,r,u,d',
            ],
            'Cliente' => [
                //
            ],
            'Administrador' => [
                'Usuários'             => 'c,r,u,d',
                'Planos'               => 'c,r,u,d',
                'Contas de Clientes'   => 'c,r,u',
                'Categorias de Contas' => 'c,r,u,d',
            ],
        ],
        'tenant' => [
            'Superadministrador' => [
                'Usuários'          => 'c,r,u,d',
                'Níveis de Acessos' => 'c,r,u,d',
                'Agências'          => 'c,r,u,d',
                'Equipes'           => 'c,r,u,d',

                // '[CRM] Origens dos Contatos/Negócios' => 'c,r,u,d',
                // '[CRM] Tipos de Contatos'             => 'c,r,u,d',
                // '[CRM] Contatos'                      => 'c,r,u,d',
                // '[CRM] Funis de Negócios'             => 'c,r,u,d',
                // '[CRM] Negócios'                      => 'c,r,u,d',
                // '[CRM] Filas'                         => 'c,r,u,d',

                // '[Financeiro] Instituições Bancárias' => 'c,r,u,d',
                // '[Financeiro] Contas Bancárias'       => 'c,r,u,d',
                // '[Financeiro] Transações Financeiras' => 'c,r,u,d',
                // '[Financeiro] Categorias'             => 'c,r,u,d',

                // '[IMB] Tipos de Imóveis'             => 'c,r,u,d',
                // '[IMB] Subtipos de Imóveis'          => 'c,r,u,d',
                // '[IMB] Características dos Imóveis'  => 'c,r,u,d',
                // '[IMB] Imóveis à Venda e/ou Aluguel' => 'c,r,u,d',
                // '[IMB] Lançamentos'                  => 'c,r,u,d',

                // '[CMS] Páginas'         => 'c,r,u,d',
                // '[CMS] Blog'            => 'c,r,u,d',
                // '[CMS] Depoimentos'     => 'c,r,u,d',
                // '[CMS] Parceiros'       => 'c,r,u,d',
                // '[CMS] Links Externos'  => 'c,r,u,d',
                // '[CMS] Stories'         => 'c,r,u,d',
                // '[CMS] Árvore de Links' => 'c,r,u,d',
                // '[CMS] Categorias'      => 'c,r,u,d',
                // '[CMS] Sliders'         => 'c,r,u,d',
            ],
            'Cliente' => [
                //
            ],
            'Administrador' => [
                'Usuários'          => 'c,r,u,d',
                'Níveis de Acessos' => 'c,r,u,d',
                'Agências'          => 'c,r,u,d',
                'Equipes'           => 'c,r,u,d',

                // '[CRM] Origens dos Contatos/Negócios' => 'c,r,u,d',
                // '[CRM] Tipos de Contatos'             => 'c,r,u,d',
                // '[CRM] Contatos'                      => 'c,r,u,d',
                // '[CRM] Funis de Negócios'             => 'c,r,u,d',
                // '[CRM] Negócios'                      => 'c,r,u,d',
                // '[CRM] Filas'                         => 'c,r,u,d',

                // // '[Financeiro] Instituições Bancárias' => 'c,r,u,d',
                // '[Financeiro] Contas Bancárias'       => 'c,r,u,d',
                // '[Financeiro] Transações Financeiras' => 'c,r,u,d',
                // '[Financeiro] Categorias'             => 'c,r,u,d',

                // '[IMB] Tipos de Imóveis'             => 'c,r,u,d',
                // '[IMB] Subtipos de Imóveis'          => 'c,r,u,d',
                // '[IMB] Características dos Imóveis'  => 'c,r,u,d',
                // '[IMB] Imóveis à Venda e/ou Aluguel' => 'c,r,u,d',
                // '[IMB] Lançamentos'                  => 'c,r,u,d',

                // '[CMS] Páginas'         => 'c,r,u',
                // '[CMS] Blog'            => 'c,r,u,d',
                // '[CMS] Depoimentos'     => 'c,r,u,d',
                // '[CMS] Parceiros'       => 'c,r,u,d',
                // '[CMS] Links Externos'  => 'c,r,u,d',
                // '[CMS] Stories'         => 'c,r,u,d',
                // '[CMS] Árvore de Links' => 'c,r,u,d',
                // '[CMS] Categorias'      => 'c,r,u,d',
                // '[CMS] Sliders'         => 'c,r,u,d',
            ],
            'Líder' => [
                // '[CRM] Contatos' => 'c,r,u,d',
                // '[CRM] Negócios' => 'c,r,u,d',

                // // '[IMB] Tipos de Imóveis'             => 'c,r,u,d',
                // // '[IMB] Subtipos de Imóveis'          => 'c,r,u,d',
                // // '[IMB] Características dos Imóveis'  => 'c,r,u,d',
                // '[IMB] Imóveis à Venda e/ou Aluguel' => 'c,r,u,d',
                // '[IMB] Lançamentos'                  => 'c,r,u,d',
            ],
            'Coordenador' => [
                // '[CRM] Contatos' => 'c,r,u,d',
                // '[CRM] Negócios' => 'c,r,u,d',

                // // '[IMB] Tipos de Imóveis'             => 'c,r,u,d',
                // // '[IMB] Subtipos de Imóveis'          => 'c,r,u,d',
                // // '[IMB] Características dos Imóveis'  => 'c,r,u,d',
                // '[IMB] Imóveis à Venda e/ou Aluguel' => 'c,r,u,d',
                // '[IMB] Lançamentos'                  => 'c,r,u,d',
            ],
            'Corretor' => [
                // '[CRM] Contatos' => 'c,r,u,d',
                // '[CRM] Negócios' => 'c,r,u,d',

                // // '[IMB] Tipos de Imóveis'             => 'c,r,u,d',
                // // '[IMB] Subtipos de Imóveis'          => 'c,r,u,d',
                // // '[IMB] Características dos Imóveis'  => 'c,r,u,d',
                // '[IMB] Imóveis à Venda e/ou Aluguel' => 'c,r,u,d',
                // '[IMB] Lançamentos'                  => 'c,r,u,d',
            ],
            'Captador' => [
                // // '[IMB] Tipos de Imóveis'             => 'c,r,u,d',
                // // '[IMB] Subtipos de Imóveis'          => 'c,r,u,d',
                // // '[IMB] Características dos Imóveis'  => 'c,r,u,d',
                // '[IMB] Imóveis à Venda e/ou Aluguel' => 'c,r,u,d',
                // '[IMB] Lançamentos'                  => 'c,r,u,d',
            ],
            'Operacional' => [
                'Usuários'          => 'c,r,u,d',
                // 'Níveis de Acessos' => 'c,r,u,d',
                'Agências'          => 'c,r,u,d',
                'Equipes'           => 'c,r,u,d',

                // '[CRM] Origens dos Contatos/Negócios' => 'c,r,u,d',
                // '[CRM] Tipos de Contatos'             => 'c,r,u,d',
                // // '[CRM] Contatos'                      => 'c,r,u,d',
                // '[CRM] Funis de Negócios'             => 'c,r,u,d',
                // // '[CRM] Negócios'                      => 'c,r,u,d',
                // '[CRM] Filas'                         => 'c,r,u,d',

                // '[IMB] Tipos de Imóveis'             => 'c,r,u,d',
                // '[IMB] Subtipos de Imóveis'          => 'c,r,u,d',
                // '[IMB] Características dos Imóveis'  => 'c,r,u,d',
                // '[IMB] Imóveis à Venda e/ou Aluguel' => 'c,r,u,d',
                // '[IMB] Lançamentos'                  => 'c,r,u,d',
            ],
            'Financeiro' => [
                // // '[Financeiro] Instituições Bancárias' => 'c,r,u,d',
                // '[Financeiro] Contas Bancárias'       => 'c,r,u,d',
                // '[Financeiro] Transações Financeiras' => 'c,r,u,d',
                // '[Financeiro] Categorias'             => 'c,r,u,d',
            ],
            'Marketing' => [
                // '[CMS] Páginas'         => 'c,r,u',
                // '[CMS] Blog'            => 'c,r,u,d',
                // '[CMS] Depoimentos'     => 'c,r,u,d',
                // '[CMS] Parceiros'       => 'c,r,u,d',
                // '[CMS] Links Externos'  => 'c,r,u,d',
                // '[CMS] Stories'         => 'c,r,u,d',
                // '[CMS] Árvore de Links' => 'c,r,u,d',
                // '[CMS] Categorias'      => 'c,r,u,d',
                // '[CMS] Sliders'         => 'c,r,u,d',
            ],
        ]
    ],

    'permissions_map' => [
        'c' => 'Cadastrar',
        'r' => 'Visualizar',
        'u' => 'Editar',
        'd' => 'Deletar'
    ]
];
