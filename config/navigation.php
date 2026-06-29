<?php

return [
    'brand' => [
        'name' => 'Gestão Técnica',
        'subtitle' => 'Painel de Controle',
    ],

    'items' => [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'icon' => 'home',
            'permission' => null,
        ],
    ],

    'sections' => [
        [
            'title' => 'Cadastros',
            'items' => [
                ['label' => 'Clientes', 'route' => 'clientes.index', 'icon' => 'building-office', 'permission' => 'clientes.view'],
                ['label' => 'Usuários', 'route' => 'usuarios.index', 'icon' => 'user-circle', 'permission' => 'usuarios.view'],
            ],
        ],
        [
            'title' => 'Atividades',
            'items' => [
                ['label' => 'Agenda', 'route' => 'agenda.index', 'icon' => 'calendar', 'permission' => 'agenda.view'],
                ['label' => 'Tarefas', 'route' => 'tarefas.index', 'icon' => 'clipboard-document-list', 'permission' => 'tarefas.view', 'badge' => 5],
                ['label' => 'Ordem Serviço', 'route' => 'ordens-servico.index', 'icon' => 'document-text', 'permission' => 'ordem-servico.view'],
            ],
        ],
        // [
        //     'title' => 'Projetos',
        //     'items' => [
        //         ['label' => 'Projetos', 'route' => '#', 'icon' => 'folder', 'permission' => 'projetos.view'],
        //         ['label' => 'Lista de Projetos', 'route' => '#', 'icon' => 'queue-list', 'permission' => 'projetos.view'],
        //         ['label' => 'Andamentos', 'route' => '#', 'icon' => 'chart-bar', 'permission' => 'projetos.view'],
        //     ],
        // ],
        [
            'title' => 'Equipe',
            'items' => [
                ['label' => 'Técnicos', 'route' => 'tecnicos.index', 'icon' => 'users', 'permission' => 'equipe.view'],
                // ['label' => 'Alocações', 'route' => '#', 'icon' => 'user-group', 'permission' => 'equipe.view'],
            ],
        ],
        // [
        //     'title' => 'Relatórios',
        //     'items' => [
        //         ['label' => 'Relatórios', 'route' => '#', 'icon' => 'document-chart-bar', 'permission' => 'relatorios.view'],
        //         ['label' => 'Indicadores', 'route' => '#', 'icon' => 'presentation-chart-line', 'permission' => 'relatorios.view'],
        //     ],
        // ],
        [
            'title' => 'Configurações',
            'items' => [
                ['label' => 'Configurações', 'route' => 'configuracoes.index', 'icon' => 'cog-6-tooth', 'permission' => 'config.view'],
                ['label' => 'Permissões', 'route' => '#', 'icon' => 'shield-check', 'permission' => 'permissoes.view'],
            ],
        ],
    ],
];
