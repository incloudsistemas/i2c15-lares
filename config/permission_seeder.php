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
            // 'Diretor' => [
            //     //
            // ],
            // 'Gerente' => [
            //     //
            // ],
            // 'Equipe' => [
            //     //
            // ],
            // 'Captador' => [
            //     //
            // ],
            // 'Suporte' => [
            //     //
            // ],
            // 'Financeiro' => [
            //     //
            // ],
            // 'Marketing' => [
            //     //
            // ],
        ],
        'tenant' => [
            'Superadministrador' => [
                'Usuários'          => 'c,r,u,d',
                'Níveis de Acessos' => 'c,r,u,d',
            ],
            'Cliente' => [
                //
            ],
            'Administrador' => [
                'Usuários'          => 'c,r,u,d',
                'Níveis de Acessos' => 'c,r,u,d',
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
