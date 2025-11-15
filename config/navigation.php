<?php

return [
    'sidebar' => [
        [
            'label' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge-high',
            'route' => 'dashboard',
        ],
        [
            'label' => 'Ventas',
            'icon' => 'fa-solid fa-cash-register',
            'children' => [
                ['label' => 'POS', 'route' => 'pos.index', 'icon' => 'fa-solid fa-cash-register'],
                ['label' => 'Facturas', 'route' => 'sales.invoices.index', 'icon' => 'fa-solid fa-file-invoice'],
                ['label' => 'Notas de Venta', 'route' => 'sales.sales_notes.index', 'icon' => 'fa-solid fa-receipt'],
                ['label' => 'Cotizaciones', 'route' => 'sales.quotations.index', 'icon' => 'fa-solid fa-file-contract'],
            ],
        ],
        [
            'label' => 'Taller',
            'icon' => 'fa-solid fa-screwdriver-wrench',
            'children' => [
                ['label' => 'Categorías', 'route' => 'taller.categorias', 'icon' => 'fa-solid fa-tags'],
                ['label' => 'Estados', 'route' => 'taller.estados', 'icon' => 'fa-solid fa-circle-check'],
                ['label' => 'Marcas', 'route' => 'taller.marcas', 'icon' => 'fa-solid fa-trademark'],
                ['label' => 'Modelos', 'route' => 'taller.modelos', 'icon' => 'fa-solid fa-cube'],
                ['label' => 'Equipos', 'route' => 'taller.equipos', 'icon' => 'fa-solid fa-toolbox'],
                ['label' => 'Abonos', 'route' => 'taller.abonos', 'icon' => 'fa-solid fa-money-bill-wave'],
                ['label' => 'Órdenes de trabajo', 'route' => 'taller.ordenes_de_trabajo', 'icon' => 'fa-solid fa-clipboard-list'],
            ],
        ],
        [
            'label' => 'Inventario',
            'icon' => 'fa-solid fa-boxes-stacked',
            'children' => [
                ['label' => 'Movimientos', 'route' => 'inventory.product_transfers.index', 'icon' => 'fa-solid fa-arrows-rotate'],
                ['label' => 'Bodegas', 'route' => 'configuration.warehouses', 'icon' => 'fa-solid fa-warehouse'],
                ['label' => 'Productos', 'route' => 'inventory.products.index', 'icon' => 'fa-solid fa-box'],
                ['label' => 'Precios', 'route' => 'configuration.price_lists', 'icon' => 'fa-solid fa-tags'],
                ['label' => 'Proveedores', 'route' => 'inventory.providers', 'icon' => 'fa-solid fa-truck'],
            ],
        ],
        [
            'label' => 'Contabilidad',
            'icon' => 'fa-solid fa-file-invoice-dollar',
            'children' => [
                ['label' => 'Cuentas por cobrar', 'route' => 'accounting.receivables.index', 'icon' => 'fa-solid fa-hand-holding-dollar'],
                ['label' => 'Cuentas por pagar', 'route' => 'accounting.payables.index', 'icon' => 'fa-solid fa-file-invoice-dollar'],
                ['label' => 'Ingresos', 'route' => 'accounting.incomes.index', 'icon' => 'fa-solid fa-arrow-down'],
                ['label' => 'Egresos', 'route' => 'accounting.expenses.index', 'icon' => 'fa-solid fa-arrow-up'],
                ['label' => 'Ventas', 'route' => 'accounting.sales', 'icon' => 'fa-solid fa-chart-line'],
                // ['label' => 'Cierre de caja', 'route' => 'contabilidad.cierre_de_caja', 'icon' => 'fa-solid fa-cash-register'], // Oculto temporalmente
            ],
        ],
        [
            'label' => 'Clientes',
            'icon' => 'fa-solid fa-user-group',
            'children' => [
                ['label' => 'Clientes naturales', 'route' => 'clientes.naturales', 'icon' => 'fa-solid fa-user'],
                ['label' => 'Empresas', 'route' => 'clientes.empresas', 'icon' => 'fa-solid fa-building'],
                ['label' => 'Categorías', 'route' => 'clientes.categorias.index', 'icon' => 'fa-solid fa-tags'],
            ],
        ],
        [
            'label' => 'Catálogo',
            'icon' => 'fa-solid fa-layer-group',
            'children' => [
                ['label' => 'Líneas', 'route' => 'catalog.lines', 'icon' => 'fa-solid fa-layer-group'],
                ['label' => 'Categorías', 'route' => 'catalog.categories', 'icon' => 'fa-solid fa-folder'],
                ['label' => 'Subcategorías', 'route' => 'catalog.subcategories', 'icon' => 'fa-solid fa-folder-open'],
            ],
        ],
        [
            'label' => 'Servicios',
            'icon' => 'fa-solid fa-clipboard-list',
            'children' => [
                ['label' => 'Categorías', 'route' => 'configuration.service_categories', 'icon' => 'fa-solid fa-tags'],
                ['label' => 'Servicios', 'route' => 'configuration.services', 'icon' => 'fa-solid fa-wrench'],
            ],
        ],
        [
            'label' => 'Seguridad',
            'icon' => 'fa-solid fa-shield-halved',
            'children' => [
                ['label' => 'Usuarios', 'route' => 'security.users', 'icon' => 'fa-solid fa-users'],
                ['label' => 'Roles', 'route' => 'security.roles', 'icon' => 'fa-solid fa-user-shield'],
                ['label' => 'Permisos', 'route' => 'security.permissions', 'icon' => 'fa-solid fa-key'],
            ],
        ],
        [
            'label' => 'Configuración',
            'icon' => 'fa-solid fa-gear',
            'children' => [
                ['label' => 'Empresa', 'route' => 'configuration.company', 'icon' => 'fa-solid fa-building'],
                ['label' => 'Sucursales', 'route' => 'configuration.branches', 'icon' => 'fa-solid fa-map-marker-alt'],
                ['label' => 'Secuenciales', 'route' => 'configuration.document_sequences', 'icon' => 'fa-solid fa-list-ol'],
                ['label' => 'Tipos de ingresos', 'route' => 'configuration.income_types', 'icon' => 'fa-solid fa-arrow-down'],
                ['label' => 'Tipos de egresos', 'route' => 'configuration.expense_types', 'icon' => 'fa-solid fa-arrow-up'],
                ['label' => 'Cuentas por cobrar', 'route' => 'configuration.receivable_categories', 'icon' => 'fa-solid fa-hand-holding-dollar'],
                ['label' => 'Cuentas por pagar', 'route' => 'configuration.payable_categories', 'icon' => 'fa-solid fa-file-invoice-dollar'],
                ['label' => 'Cuentas de banco', 'route' => 'configuration.bank_accounts', 'icon' => 'fa-solid fa-university'],
                ['label' => 'Tarjetas', 'route' => 'configuration.cards', 'icon' => 'fa-solid fa-credit-card'],
            ],
        ],
    ],

    'pages' => [
        'pos.index' => [
            'path' => 'pos',
            'title' => 'Punto de Venta',
            'parent' => 'Ventas',
        ],
        'ventas.pos' => [
            'path' => 'ventas/pos',
            'title' => 'POS',
            'parent' => 'Ventas',
        ],
        'sales.invoices.index' => [
            'path' => 'sales/invoices',
            'title' => 'Facturas',
            'parent' => 'Ventas',
        ],
        'sales.sales_notes.index' => [
            'path' => 'sales/sales-notes',
            'title' => 'Notas de Venta',
            'parent' => 'Ventas',
        ],
        'sales.quotations.index' => [
            'path' => 'sales/quotations',
            'title' => 'Cotizaciones',
            'parent' => 'Ventas',
        ],
        'sales.quotations.create' => [
            'path' => 'sales/quotations/create',
            'title' => 'Nueva Cotización',
            'parent' => 'Ventas',
        ],
        'sales.quotations.edit' => [
            'path' => 'sales/quotations/{quotation}/edit',
            'title' => 'Editar Cotización',
            'parent' => 'Ventas',
        ],
        'sales.quotations.show' => [
            'path' => 'sales/quotations/{quotation}',
            'title' => 'Ver Cotización',
            'parent' => 'Ventas',
        ],
        'taller.categorias' => [
            'path' => 'workshop/categories',
            'title' => 'Categorías de taller',
            'parent' => 'Taller',
        ],
        'taller.estados' => [
            'path' => 'workshop/states',
            'title' => 'Estados de taller',
            'parent' => 'Taller',
        ],
        'taller.marcas' => [
            'path' => 'workshop/brands',
            'title' => 'Marcas de taller',
            'parent' => 'Taller',
        ],
        'taller.modelos' => [
            'path' => 'workshop/models',
            'title' => 'Modelos de taller',
            'parent' => 'Taller',
        ],
        'taller.abonos' => [
            'path' => 'workshop/advances',
            'title' => 'Abonos de órdenes',
            'parent' => 'Taller',
        ],
        'taller.equipos' => [
            'path' => 'workshop/equipments',
            'title' => 'Equipos de taller',
            'parent' => 'Taller',
        ],
        'taller.ordenes_de_trabajo' => [
            'path' => 'workshop/work-orders',
            'title' => 'Órdenes de trabajo',
            'parent' => 'Taller',
        ],
        'inventory.product_transfers.index' => [
            'path' => 'inventory/product-transfers',
            'title' => 'Movimientos de inventario',
            'parent' => 'Inventario',
        ],
        'configuration.warehouses' => [
            'path' => 'configuration/warehouses',
            'title' => 'Bodegas',
            'parent' => 'Inventario',
        ],
        'inventory.products.index' => [
            'path' => 'inventory/products',
            'title' => 'Productos',
            'parent' => 'Inventario',
        ],
        'configuration.price_lists' => [
            'path' => 'configuration/price-lists',
            'title' => 'Listas de precios',
            'parent' => 'Inventario',
        ],
        'configuration.document_sequences' => [
            'path' => 'configuration/document-sequences',
            'title' => 'Secuenciales',
            'parent' => 'Configuración',
        ],
        'inventory.providers' => [
            'path' => 'inventory/providers',
            'title' => 'Proveedores',
            'parent' => 'Inventario',
        ],
        'configuration.service_categories' => [
            'path' => 'configuration/service-categories',
            'title' => 'Categorías de servicios',
            'parent' => 'Servicios',
        ],
        'configuration.services' => [
            'path' => 'configuration/services',
            'title' => 'Servicios',
            'parent' => 'Servicios',
        ],
        'catalog.lines' => [
            'path' => 'catalog/lines',
            'title' => 'Líneas',
            'parent' => 'Catálogo',
        ],
        'catalog.categories' => [
            'path' => 'catalog/categories',
            'title' => 'Categorías de catálogo',
            'parent' => 'Catálogo',
        ],
        'catalog.subcategories' => [
            'path' => 'catalog/subcategories',
            'title' => 'Subcategorías',
            'parent' => 'Catálogo',
        ],
        'accounting.incomes.index' => [
            'path' => 'accounting/incomes',
            'title' => 'Ingresos',
            'parent' => 'Contabilidad',
        ],
        'accounting.expenses.index' => [
            'path' => 'accounting/expenses',
            'title' => 'Egresos',
            'parent' => 'Contabilidad',
        ],
        'accounting.receivables.index' => [
            'path' => 'accounting/receivables',
            'title' => 'Cuentas por cobrar',
            'parent' => 'Contabilidad',
        ],
        'accounting.payables.index' => [
            'path' => 'accounting/payables',
            'title' => 'Cuentas por pagar',
            'parent' => 'Contabilidad',
        ],
        'accounting.sales' => [
            'path' => 'accounting/sales',
            'title' => 'Ventas contables',
            'parent' => 'Contabilidad',
        ],
        'contabilidad.cierre_de_caja' => [
            'path' => 'contabilidad/cierre-de-caja',
            'title' => 'Cierre de caja',
            'parent' => 'Contabilidad',
        ],
        'clientes.naturales' => [
            'path' => 'customers/individuals',
            'title' => 'Clientes naturales',
            'parent' => 'Clientes',
        ],
        'clientes.empresas' => [
            'path' => 'customers/companies',
            'title' => 'Empresas',
            'parent' => 'Clientes',
        ],
        'clientes.categorias.index' => [
            'path' => 'customers/categories',
            'title' => 'Categorías de clientes',
            'parent' => 'Clientes',
        ],
        'security.users' => [
            'path' => 'security/users',
            'title' => 'Usuarios',
            'parent' => 'Seguridad',
        ],
        'security.roles' => [
            'path' => 'security/roles',
            'title' => 'Roles',
            'parent' => 'Seguridad',
        ],
        'security.permissions' => [
            'path' => 'security/permissions',
            'title' => 'Permisos',
            'parent' => 'Seguridad',
        ],
        'configuration.company' => [
            'path' => 'configuration/company',
            'title' => 'Empresa',
            'parent' => 'Configuración',
        ],
        'configuration.branches' => [
            'path' => 'configuration/branches',
            'title' => 'Sucursales',
            'parent' => 'Configuración',
        ],
        'configuration.income_types' => [
            'path' => 'configuration/income-types',
            'title' => 'Tipos de ingresos',
            'parent' => 'Configuración',
        ],
        'configuration.expense_types' => [
            'path' => 'configuration/expense-types',
            'title' => 'Tipos de egresos',
            'parent' => 'Configuración',
        ],
        'configuration.receivable_categories' => [
            'path' => 'configuration/accounts-receivable',
            'title' => 'Cuentas por cobrar',
            'parent' => 'Configuración',
        ],
        'configuration.payable_categories' => [
            'path' => 'configuration/accounts-payable',
            'title' => 'Cuentas por pagar',
            'parent' => 'Configuración',
        ],
        'configuration.bank_accounts' => [
            'path' => 'configuration/bank-accounts',
            'title' => 'Cuentas de banco',
            'parent' => 'Configuración',
        ],
        'configuration.cards' => [
            'path' => 'configuration/cards',
            'title' => 'Tarjetas',
            'parent' => 'Configuración',
        ],
        'perfil.show' => [
            'path' => 'perfil',
            'title' => 'Mi perfil',
            'parent' => null,
        ],
    ],
];
