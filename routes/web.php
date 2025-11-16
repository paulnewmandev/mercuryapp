<?php

use App\Http\Controllers\Auth\AuthenticateUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Company\CompanyProfileController;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\Accounting\ExpenseController;
use App\Http\Controllers\Accounting\IncomeController;
use App\Http\Controllers\Accounting\PayableEntryController;
use App\Http\Controllers\Accounting\ReceivableEntryController;
use App\Http\Controllers\Configuration\BankAccountController;
use App\Http\Controllers\Configuration\BranchController;
use App\Http\Controllers\Configuration\IncomeTypeController;
use App\Http\Controllers\Configuration\PaymentCardController;
use App\Http\Controllers\Configuration\ExpenseTypeController;
use App\Http\Controllers\Configuration\DocumentSequenceController;
use App\Http\Controllers\Configuration\PriceListController;
use App\Http\Controllers\Configuration\ProductLineController;
use App\Http\Controllers\Configuration\ProductCategoryController;
use App\Http\Controllers\Configuration\ReceivableCategoryController;
use App\Http\Controllers\Configuration\PayableCategoryController;
use App\Http\Controllers\Configuration\ServiceCategoryController;
use App\Http\Controllers\Configuration\ServiceController;
use App\Http\Controllers\Configuration\WarehouseController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\ProductTransferController;
use App\Http\Controllers\Inventory\ProviderController;
use App\Http\Controllers\Customers\CustomerCategoryController;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\PlaceholderPageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Security\RoleController;
use App\Http\Controllers\Security\PermissionController;
use App\Http\Controllers\Security\UserController;
use App\Http\Controllers\Workshop\WorkshopBrandController;
use App\Http\Controllers\Workshop\WorkshopCategoryController;
use App\Http\Controllers\Workshop\WorkshopEquipmentController;
use App\Http\Controllers\Workshop\WorkshopOrderController;
use App\Http\Controllers\Workshop\WorkshopOrderAdvanceController;
use App\Http\Controllers\Workshop\WorkshopOrderNoteController;
use App\Http\Controllers\Workshop\WorkshopOrderItemController;
use App\Http\Controllers\Workshop\WorkshopOrderServiceController;
use App\Http\Controllers\Workshop\WorkshopModelController;
use App\Http\Controllers\Workshop\WorkshopAccessoryController;
use App\Http\Controllers\Workshop\WorkshopStateController;
use App\Http\Controllers\PointOfSale\POSController;
use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\SalesNoteController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Accounting\AccountingSalesController;
use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Chat\ChatToolController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Rutas públicas iniciales para la demostración de MercuryApp.
|
*/

Route::redirect('/', '/login');
Route::get('/login', LoginController::class)
    ->middleware('guest')
    ->name('login');

Route::post('/login', AuthenticateUserController::class)
    ->middleware('guest')
    ->name('login.attempt');

Route::get('/password/forgot', [PasswordResetController::class, 'showEmailForm'])->name('password.request');
Route::post('/password/forgot', [PasswordResetController::class, 'sendOtp'])->name('password.email');
Route::get('/password/otp', [PasswordResetController::class, 'showOtpForm'])->name('password.otp');
Route::post('/password/otp', [PasswordResetController::class, 'verifyOtp'])->name('password.otp.verify');
Route::get('/password/reset', [PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])->name('password.update');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/kpis', [DashboardController::class, 'kpis'])->name('dashboard.kpis');
    Route::get('/dashboard/charts', [DashboardController::class, 'charts'])->name('dashboard.charts');
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity'])->name('dashboard.recent-activity');
    Route::post('/logout', LogoutController::class)->name('logout');

    // POS Routes
    Route::prefix('pos')->name('pos.')->group(function (): void {
        Route::get('/', [POSController::class, 'index'])->name('index');
        Route::get('search/customers', [POSController::class, 'searchCustomers'])->name('search.customers');
        Route::get('search/products', [POSController::class, 'searchProducts'])->name('search.products');
        Route::get('search/services', [POSController::class, 'searchServices'])->name('search.services');
        Route::get('search/users', [POSController::class, 'searchUsers'])->name('search.users');
        Route::get('search/work-orders', [POSController::class, 'searchWorkOrders'])->name('search.work_orders');
        Route::get('payment-methods', [POSController::class, 'paymentMethods'])->name('payment_methods');
        Route::post('/', [POSController::class, 'store'])->name('store');
    });

    // Sales Routes - Invoices
    Route::prefix('sales/invoices')->name('sales.invoices.')->group(function (): void {
        Route::get('data', [InvoiceController::class, 'list'])->name('data');
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::post('{invoice}/authorize', [InvoiceController::class, 'authorize'])->name('authorize')->whereUuid('invoice');
        Route::get('{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('pdf')->whereUuid('invoice');
        Route::get('{invoice}', [InvoiceController::class, 'show'])->name('show')->whereUuid('invoice');
    });

    // Sales Routes - Sales Notes
    Route::prefix('sales/sales-notes')->name('sales.sales_notes.')->group(function (): void {
        Route::get('data', [SalesNoteController::class, 'list'])->name('data');
        Route::get('/', [SalesNoteController::class, 'index'])->name('index');
        Route::get('{invoice}', [SalesNoteController::class, 'show'])->name('show')->whereUuid('invoice');
    });

    // Sales Routes - Quotations
    Route::prefix('sales/quotations')->name('sales.quotations.')->group(function (): void {
        Route::get('data', [QuotationController::class, 'list'])->name('data');
        Route::get('search/customers', [QuotationController::class, 'searchCustomers'])->name('search.customers');
        Route::get('search/products', [QuotationController::class, 'searchProducts'])->name('search.products');
        Route::get('search/services', [QuotationController::class, 'searchServices'])->name('search.services');
        Route::get('/', [QuotationController::class, 'index'])->name('index');
        Route::get('create', [QuotationController::class, 'create'])->name('create');
        Route::post('/', [QuotationController::class, 'store'])->name('store');
        Route::get('{quotation}/pdf', [QuotationController::class, 'pdf'])->name('pdf')->whereUuid('quotation');
        Route::get('{quotation}/edit', [QuotationController::class, 'edit'])->name('edit')->whereUuid('quotation');
        Route::get('{quotation}', [QuotationController::class, 'show'])->name('show')->whereUuid('quotation');
        Route::patch('{quotation}', [QuotationController::class, 'update'])->name('update')->whereUuid('quotation');
    });

    Route::get('catalog/lines', [ProductLineController::class, 'index'])->name('catalog.lines');
    Route::get('catalog/categories', [ProductCategoryController::class, 'index'])->name('catalog.categories');
    Route::get('catalog/subcategories', [ProductCategoryController::class, 'subcategories'])->name('catalog.subcategories');

    Route::get('configuration/company', [CompanyProfileController::class, 'show'])->name('configuration.company');
    Route::patch('configuration/company', [CompanyProfileController::class, 'update'])->name('configuration.company.update');

    Route::get('profile', [UserProfileController::class, 'show'])->name('profile.show');
    Route::patch('profile', [UserProfileController::class, 'update'])->name('profile.update');
    Route::patch('profile/password', [UserProfileController::class, 'updatePassword'])->name('profile.update-password');

    Route::get('configuration/branches/data', [BranchController::class, 'list'])->name('configuration.branches.data');
    Route::get('configuration/branches', [BranchController::class, 'index'])->name('configuration.branches');
    Route::post('configuration/branches', [BranchController::class, 'store'])->name('configuration.branches.store');
    Route::get('configuration/branches/{branch}', [BranchController::class, 'show'])->name('configuration.branches.show');
    Route::patch('configuration/branches/{branch}', [BranchController::class, 'update'])->name('configuration.branches.update');
    Route::patch('configuration/branches/{branch}/status', [BranchController::class, 'toggleStatus'])->name('configuration.branches.status');
    Route::delete('configuration/branches/{branch}', [BranchController::class, 'destroy'])->name('configuration.branches.destroy');

    Route::get('configuration/document-sequences/data', [DocumentSequenceController::class, 'list'])->name('configuration.document_sequences.data');
    Route::get('configuration/document-sequences', [DocumentSequenceController::class, 'index'])->name('configuration.document_sequences');
    Route::post('configuration/document-sequences', [DocumentSequenceController::class, 'store'])->name('configuration.document_sequences.store');
    Route::get('configuration/document-sequences/{documentSequence}', [DocumentSequenceController::class, 'show'])->name('configuration.document_sequences.show')->whereUuid('documentSequence');
    Route::patch('configuration/document-sequences/{documentSequence}', [DocumentSequenceController::class, 'update'])->name('configuration.document_sequences.update')->whereUuid('documentSequence');
    Route::patch('configuration/document-sequences/{documentSequence}/status', [DocumentSequenceController::class, 'toggleStatus'])->name('configuration.document_sequences.status')->whereUuid('documentSequence');
    Route::delete('configuration/document-sequences/{documentSequence}', [DocumentSequenceController::class, 'destroy'])->name('configuration.document_sequences.destroy')->whereUuid('documentSequence');

    Route::get('configuration/cards/data', [PaymentCardController::class, 'list'])->name('configuration.cards.data');
    Route::get('configuration/cards', [PaymentCardController::class, 'index'])->name('configuration.cards');
    Route::post('configuration/cards', [PaymentCardController::class, 'store'])->name('configuration.cards.store');
    Route::get('configuration/cards/{card}', [PaymentCardController::class, 'show'])->name('configuration.cards.show');
    Route::patch('configuration/cards/{card}', [PaymentCardController::class, 'update'])->name('configuration.cards.update');
    Route::patch('configuration/cards/{card}/status', [PaymentCardController::class, 'toggleStatus'])->name('configuration.cards.status');
    Route::delete('configuration/cards/{card}', [PaymentCardController::class, 'destroy'])->name('configuration.cards.destroy');

    Route::get('configuration/bank-accounts/data', [BankAccountController::class, 'list'])->name('configuration.bank_accounts.data');
    Route::get('configuration/bank-accounts', [BankAccountController::class, 'index'])->name('configuration.bank_accounts');
    Route::post('configuration/bank-accounts', [BankAccountController::class, 'store'])->name('configuration.bank_accounts.store');
    Route::get('configuration/bank-accounts/{account}', [BankAccountController::class, 'show'])->name('configuration.bank_accounts.show');
    Route::patch('configuration/bank-accounts/{account}', [BankAccountController::class, 'update'])->name('configuration.bank_accounts.update');
    Route::patch('configuration/bank-accounts/{account}/status', [BankAccountController::class, 'toggleStatus'])->name('configuration.bank_accounts.status');
    Route::delete('configuration/bank-accounts/{account}', [BankAccountController::class, 'destroy'])->name('configuration.bank_accounts.destroy');

    Route::get('configuration/income-types/data', [IncomeTypeController::class, 'list'])->name('configuration.income_types.data');
    Route::get('configuration/income-types', [IncomeTypeController::class, 'index'])->name('configuration.income_types');
    Route::post('configuration/income-types', [IncomeTypeController::class, 'store'])->name('configuration.income_types.store');
    Route::get('configuration/income-types/{incomeType}', [IncomeTypeController::class, 'show'])->name('configuration.income_types.show');
    Route::patch('configuration/income-types/{incomeType}', [IncomeTypeController::class, 'update'])->name('configuration.income_types.update');
    Route::patch('configuration/income-types/{incomeType}/status', [IncomeTypeController::class, 'toggleStatus'])->name('configuration.income_types.status');
    Route::delete('configuration/income-types/{incomeType}', [IncomeTypeController::class, 'destroy'])->name('configuration.income_types.destroy');

    Route::get('configuration/expense-types/data', [ExpenseTypeController::class, 'list'])->name('configuration.expense_types.data');
    Route::get('configuration/expense-types', [ExpenseTypeController::class, 'index'])->name('configuration.expense_types');
    Route::post('configuration/expense-types', [ExpenseTypeController::class, 'store'])->name('configuration.expense_types.store');
    Route::get('configuration/expense-types/{expenseType}', [ExpenseTypeController::class, 'show'])->name('configuration.expense_types.show');
    Route::patch('configuration/expense-types/{expenseType}', [ExpenseTypeController::class, 'update'])->name('configuration.expense_types.update');
    Route::patch('configuration/expense-types/{expenseType}/status', [ExpenseTypeController::class, 'toggleStatus'])->name('configuration.expense_types.status');
    Route::delete('configuration/expense-types/{expenseType}', [ExpenseTypeController::class, 'destroy'])->name('configuration.expense_types.destroy');

    Route::get('configuration/accounts-receivable/data', [ReceivableCategoryController::class, 'list'])->name('configuration.receivable_categories.data');
    Route::get('configuration/accounts-receivable', [ReceivableCategoryController::class, 'index'])->name('configuration.receivable_categories');
    Route::post('configuration/accounts-receivable', [ReceivableCategoryController::class, 'store'])->name('configuration.receivable_categories.store');
    Route::get('configuration/accounts-receivable/{receivableCategory}', [ReceivableCategoryController::class, 'show'])->name('configuration.receivable_categories.show')->whereUuid('receivableCategory');
    Route::patch('configuration/accounts-receivable/{receivableCategory}', [ReceivableCategoryController::class, 'update'])->name('configuration.receivable_categories.update')->whereUuid('receivableCategory');
    Route::patch('configuration/accounts-receivable/{receivableCategory}/status', [ReceivableCategoryController::class, 'toggleStatus'])->name('configuration.receivable_categories.status')->whereUuid('receivableCategory');
    Route::delete('configuration/accounts-receivable/{receivableCategory}', [ReceivableCategoryController::class, 'destroy'])->name('configuration.receivable_categories.destroy')->whereUuid('receivableCategory');

    Route::get('configuration/accounts-payable/data', [PayableCategoryController::class, 'list'])->name('configuration.payable_categories.data');
    Route::get('configuration/accounts-payable', [PayableCategoryController::class, 'index'])->name('configuration.payable_categories');
    Route::post('configuration/accounts-payable', [PayableCategoryController::class, 'store'])->name('configuration.payable_categories.store');
    Route::get('configuration/accounts-payable/{payableCategory}', [PayableCategoryController::class, 'show'])->name('configuration.payable_categories.show')->whereUuid('payableCategory');
    Route::patch('configuration/accounts-payable/{payableCategory}', [PayableCategoryController::class, 'update'])->name('configuration.payable_categories.update')->whereUuid('payableCategory');
    Route::patch('configuration/accounts-payable/{payableCategory}/status', [PayableCategoryController::class, 'toggleStatus'])->name('configuration.payable_categories.status')->whereUuid('payableCategory');
    Route::delete('configuration/accounts-payable/{payableCategory}', [PayableCategoryController::class, 'destroy'])->name('configuration.payable_categories.destroy')->whereUuid('payableCategory');

    Route::get('accounting/incomes/data', [IncomeController::class, 'list'])->name('accounting.incomes.data');
    Route::get('accounting/incomes/options', [IncomeController::class, 'options'])->name('accounting.incomes.options');
    Route::get('accounting/incomes', [IncomeController::class, 'index'])->name('accounting.incomes.index');
    Route::post('accounting/incomes', [IncomeController::class, 'store'])->name('accounting.incomes.store');
    Route::get('accounting/incomes/{income}', [IncomeController::class, 'show'])->name('accounting.incomes.show');
    Route::patch('accounting/incomes/{income}', [IncomeController::class, 'update'])->name('accounting.incomes.update');
    Route::patch('accounting/incomes/{income}/status', [IncomeController::class, 'toggleStatus'])->name('accounting.incomes.status');
    Route::delete('accounting/incomes/{income}', [IncomeController::class, 'destroy'])->name('accounting.incomes.destroy');

    Route::get('accounting/receivables/data', [ReceivableEntryController::class, 'list'])->name('accounting.receivables.data');
    Route::get('accounting/receivables/options', [ReceivableEntryController::class, 'options'])->name('accounting.receivables.options');
    Route::get('accounting/receivables', [ReceivableEntryController::class, 'index'])->name('accounting.receivables.index');
    Route::post('accounting/receivables', [ReceivableEntryController::class, 'store'])->name('accounting.receivables.store');
    Route::get('accounting/receivables/{receivable}', [ReceivableEntryController::class, 'show'])->name('accounting.receivables.show')->whereUuid('receivable');
    Route::patch('accounting/receivables/{receivable}', [ReceivableEntryController::class, 'update'])->name('accounting.receivables.update')->whereUuid('receivable');
    Route::patch('accounting/receivables/{receivable}/settlement', [ReceivableEntryController::class, 'toggleSettlement'])->name('accounting.receivables.settlement')->whereUuid('receivable');
    Route::delete('accounting/receivables/{receivable}', [ReceivableEntryController::class, 'destroy'])->name('accounting.receivables.destroy')->whereUuid('receivable');

    Route::get('accounting/payables/data', [PayableEntryController::class, 'list'])->name('accounting.payables.data');
    Route::get('accounting/payables/options', [PayableEntryController::class, 'options'])->name('accounting.payables.options');
    Route::get('accounting/payables', [PayableEntryController::class, 'index'])->name('accounting.payables.index');
    Route::post('accounting/payables', [PayableEntryController::class, 'store'])->name('accounting.payables.store');
    Route::get('accounting/payables/{payable}', [PayableEntryController::class, 'show'])->name('accounting.payables.show')->whereUuid('payable');
    Route::patch('accounting/payables/{payable}', [PayableEntryController::class, 'update'])->name('accounting.payables.update')->whereUuid('payable');
    Route::patch('accounting/payables/{payable}/settlement', [PayableEntryController::class, 'toggleSettlement'])->name('accounting.payables.settlement')->whereUuid('payable');
    Route::delete('accounting/payables/{payable}', [PayableEntryController::class, 'destroy'])->name('accounting.payables.destroy')->whereUuid('payable');

    Route::get('accounting/expenses/data', [ExpenseController::class, 'list'])->name('accounting.expenses.data');
    Route::get('accounting/expenses/options', [ExpenseController::class, 'options'])->name('accounting.expenses.options');
    Route::get('accounting/expenses', [ExpenseController::class, 'index'])->name('accounting.expenses.index');
    Route::post('accounting/expenses', [ExpenseController::class, 'store'])->name('accounting.expenses.store');
    Route::get('accounting/expenses/{expense}', [ExpenseController::class, 'show'])->name('accounting.expenses.show');
    Route::patch('accounting/expenses/{expense}', [ExpenseController::class, 'update'])->name('accounting.expenses.update');
    Route::patch('accounting/expenses/{expense}/status', [ExpenseController::class, 'toggleStatus'])->name('accounting.expenses.status');
    Route::delete('accounting/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('accounting.expenses.destroy');

    Route::get('accounting/sales/data', [AccountingSalesController::class, 'list'])->name('accounting.sales.data');
    Route::get('accounting/sales', [AccountingSalesController::class, 'index'])->name('accounting.sales');
    Route::get('accounting/sales/{invoice}', [AccountingSalesController::class, 'show'])->name('accounting.sales.show')->whereUuid('invoice');

    Route::get('configuration/service-categories/data', [ServiceCategoryController::class, 'list'])->name('configuration.service_categories.data');
    Route::get('configuration/service-categories/options', [ServiceCategoryController::class, 'options'])->name('configuration.service_categories.options');
    Route::get('configuration/service-categories', [ServiceCategoryController::class, 'index'])->name('configuration.service_categories');
    Route::post('configuration/service-categories', [ServiceCategoryController::class, 'store'])->name('configuration.service_categories.store');
    Route::get('configuration/service-categories/{serviceCategory}', [ServiceCategoryController::class, 'show'])->name('configuration.service_categories.show');
    Route::patch('configuration/service-categories/{serviceCategory}', [ServiceCategoryController::class, 'update'])->name('configuration.service_categories.update');
    Route::patch('configuration/service-categories/{serviceCategory}/status', [ServiceCategoryController::class, 'toggleStatus'])->name('configuration.service_categories.status');
    Route::delete('configuration/service-categories/{serviceCategory}', [ServiceCategoryController::class, 'destroy'])->name('configuration.service_categories.destroy');

    Route::get('configuration/services/data', [ServiceController::class, 'list'])->name('configuration.services.data');
    Route::get('configuration/services', [ServiceController::class, 'index'])->name('configuration.services');
    Route::post('configuration/services', [ServiceController::class, 'store'])->name('configuration.services.store');
    Route::get('configuration/services/{service}', [ServiceController::class, 'show'])->name('configuration.services.show');
    Route::patch('configuration/services/{service}', [ServiceController::class, 'update'])->name('configuration.services.update');
    Route::patch('configuration/services/{service}/status', [ServiceController::class, 'toggleStatus'])->name('configuration.services.status');
    Route::delete('configuration/services/{service}', [ServiceController::class, 'destroy'])->name('configuration.services.destroy');

    Route::get('configuration/warehouses/data', [WarehouseController::class, 'list'])->name('configuration.warehouses.data');
    Route::get('configuration/warehouses', [WarehouseController::class, 'index'])->name('configuration.warehouses');
    Route::post('configuration/warehouses', [WarehouseController::class, 'store'])->name('configuration.warehouses.store');
    Route::get('configuration/warehouses/{warehouse}', [WarehouseController::class, 'show'])->name('configuration.warehouses.show');
    Route::patch('configuration/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('configuration.warehouses.update');
    Route::patch('configuration/warehouses/{warehouse}/status', [WarehouseController::class, 'toggleStatus'])->name('configuration.warehouses.status');
    Route::delete('configuration/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('configuration.warehouses.destroy');

    Route::get('configuration/price-lists/options', [PriceListController::class, 'options'])->name('configuration.price_lists.options');
    Route::get('configuration/price-lists/data', [PriceListController::class, 'list'])->name('configuration.price_lists.data');
    Route::get('configuration/price-lists', [PriceListController::class, 'index'])->name('configuration.price_lists');
    Route::post('configuration/price-lists', [PriceListController::class, 'store'])->name('configuration.price_lists.store');
    Route::get('configuration/price-lists/{priceList}', [PriceListController::class, 'show'])->name('configuration.price_lists.show');
    Route::patch('configuration/price-lists/{priceList}', [PriceListController::class, 'update'])->name('configuration.price_lists.update');
    Route::patch('configuration/price-lists/{priceList}/status', [PriceListController::class, 'toggleStatus'])->name('configuration.price_lists.status');
    Route::delete('configuration/price-lists/{priceList}', [PriceListController::class, 'destroy'])->name('configuration.price_lists.destroy');

    Route::get('inventory/providers/data', [ProviderController::class, 'list'])->name('inventory.providers.data');
    Route::get('inventory/providers/options', [ProviderController::class, 'options'])->name('inventory.providers.options');
    Route::get('inventory/providers', [ProviderController::class, 'index'])->name('inventory.providers');
    Route::post('inventory/providers', [ProviderController::class, 'store'])->name('inventory.providers.store');
    Route::get('inventory/providers/{provider}', [ProviderController::class, 'show'])->name('inventory.providers.show')->whereUuid('provider');
    Route::patch('inventory/providers/{provider}', [ProviderController::class, 'update'])->name('inventory.providers.update')->whereUuid('provider');
    Route::patch('inventory/providers/{provider}/status', [ProviderController::class, 'toggleStatus'])->name('inventory.providers.status')->whereUuid('provider');
    Route::delete('inventory/providers/{provider}', [ProviderController::class, 'destroy'])->name('inventory.providers.destroy')->whereUuid('provider');

    Route::get('inventory/products/options', [ProductController::class, 'options'])->name('inventory.products.options');
    Route::get('inventory/products/data', [ProductController::class, 'list'])->name('inventory.products.data');
    Route::get('inventory/products', [ProductController::class, 'index'])->name('inventory.products.index');
    Route::get('inventory/products/create', [ProductController::class, 'create'])->name('inventory.products.create');
    Route::post('inventory/products', [ProductController::class, 'store'])->name('inventory.products.store');
    Route::get('inventory/products/{product}', [ProductController::class, 'show'])->name('inventory.products.show')->whereUuid('product');
    Route::get('inventory/products/{product}/edit', [ProductController::class, 'edit'])->name('inventory.products.edit')->whereUuid('product');
    Route::patch('inventory/products/{product}', [ProductController::class, 'update'])->name('inventory.products.update')->whereUuid('product');
    Route::patch('inventory/products/{product}/status', [ProductController::class, 'toggleStatus'])->name('inventory.products.status')->whereUuid('product');
    Route::get('inventory/products/{product}/barcode-label', [ProductController::class, 'barcodeLabel'])->name('inventory.products.barcode')->whereUuid('product');
    Route::delete('inventory/products/{product}', [ProductController::class, 'destroy'])->name('inventory.products.destroy')->whereUuid('product');

    Route::get('inventory/product-transfers/data', [ProductTransferController::class, 'list'])->name('inventory.product_transfers.data');
    Route::get('inventory/product-transfers', [ProductTransferController::class, 'index'])->name('inventory.product_transfers.index');
    Route::get('inventory/product-transfers/create', [ProductTransferController::class, 'create'])->name('inventory.product_transfers.create');
    Route::post('inventory/product-transfers', [ProductTransferController::class, 'store'])->name('inventory.product_transfers.store');
    Route::get('inventory/product-transfers/{product_transfer}', [ProductTransferController::class, 'show'])->name('inventory.product_transfers.show')->whereUuid('product_transfer');
    Route::get('inventory/product-transfers/{product_transfer}/edit', [ProductTransferController::class, 'edit'])->name('inventory.product_transfers.edit')->whereUuid('product_transfer');
    Route::patch('inventory/product-transfers/{product_transfer}', [ProductTransferController::class, 'update'])->name('inventory.product_transfers.update')->whereUuid('product_transfer');
    Route::delete('inventory/product-transfers/{product_transfer}', [ProductTransferController::class, 'destroy'])->name('inventory.product_transfers.destroy')->whereUuid('product_transfer');

    Route::get('workshop/categories/data', [WorkshopCategoryController::class, 'list'])->name('taller.categorias.data');
    Route::get('workshop/categories/options', [WorkshopCategoryController::class, 'options'])->name('taller.categorias.options');
    Route::get('workshop/categories', [WorkshopCategoryController::class, 'index'])->name('taller.categorias');
    Route::post('workshop/categories', [WorkshopCategoryController::class, 'store'])->name('taller.categorias.store');
    Route::get('workshop/categories/{workshop_category}', [WorkshopCategoryController::class, 'show'])->name('taller.categorias.show')->whereUuid('workshop_category');
    Route::patch('workshop/categories/{workshop_category}', [WorkshopCategoryController::class, 'update'])->name('taller.categorias.update')->whereUuid('workshop_category');
    Route::patch('workshop/categories/{workshop_category}/status', [WorkshopCategoryController::class, 'toggleStatus'])->name('taller.categorias.status')->whereUuid('workshop_category');
    Route::delete('workshop/categories/{workshop_category}', [WorkshopCategoryController::class, 'destroy'])->name('taller.categorias.destroy')->whereUuid('workshop_category');

    Route::get('workshop/brands/data', [WorkshopBrandController::class, 'list'])->name('taller.marcas.data');
    Route::get('workshop/brands/options', [WorkshopBrandController::class, 'options'])->name('taller.marcas.options');
    Route::get('workshop/brands', [WorkshopBrandController::class, 'index'])->name('taller.marcas');
    Route::post('workshop/brands', [WorkshopBrandController::class, 'store'])->name('taller.marcas.store');
    Route::get('workshop/brands/{workshop_brand}', [WorkshopBrandController::class, 'show'])->name('taller.marcas.show')->whereUuid('workshop_brand');
    Route::patch('workshop/brands/{workshop_brand}', [WorkshopBrandController::class, 'update'])->name('taller.marcas.update')->whereUuid('workshop_brand');
    Route::patch('workshop/brands/{workshop_brand}/status', [WorkshopBrandController::class, 'toggleStatus'])->name('taller.marcas.status')->whereUuid('workshop_brand');
    Route::delete('workshop/brands/{workshop_brand}', [WorkshopBrandController::class, 'destroy'])->name('taller.marcas.destroy')->whereUuid('workshop_brand');

    Route::get('workshop/models/data', [WorkshopModelController::class, 'list'])->name('taller.modelos.data');
    Route::get('workshop/models/options', [WorkshopModelController::class, 'options'])->name('taller.modelos.options');
    Route::get('workshop/models', [WorkshopModelController::class, 'index'])->name('taller.modelos');
    Route::post('workshop/models', [WorkshopModelController::class, 'store'])->name('taller.modelos.store');
    Route::get('workshop/models/{workshop_model}', [WorkshopModelController::class, 'show'])->name('taller.modelos.show')->whereUuid('workshop_model');
    Route::patch('workshop/models/{workshop_model}', [WorkshopModelController::class, 'update'])->name('taller.modelos.update')->whereUuid('workshop_model');
    Route::patch('workshop/models/{workshop_model}/status', [WorkshopModelController::class, 'toggleStatus'])->name('taller.modelos.status')->whereUuid('workshop_model');
    Route::delete('workshop/models/{workshop_model}', [WorkshopModelController::class, 'destroy'])->name('taller.modelos.destroy')->whereUuid('workshop_model');

    Route::get('workshop/equipments/data', [WorkshopEquipmentController::class, 'list'])->name('taller.equipos.data');
    Route::get('workshop/equipments', [WorkshopEquipmentController::class, 'index'])->name('taller.equipos');
    Route::post('workshop/equipments', [WorkshopEquipmentController::class, 'store'])->name('taller.equipos.store');
    Route::get('workshop/equipments/{workshop_equipment}/barcode-label', [WorkshopEquipmentController::class, 'barcodeLabel'])->name('taller.equipos.barcode')->whereUuid('workshop_equipment');
    Route::get('workshop/equipments/{workshop_equipment}', [WorkshopEquipmentController::class, 'show'])->name('taller.equipos.show')->whereUuid('workshop_equipment');
    Route::patch('workshop/equipments/{workshop_equipment}', [WorkshopEquipmentController::class, 'update'])->name('taller.equipos.update')->whereUuid('workshop_equipment');
    Route::patch('workshop/equipments/{workshop_equipment}/status', [WorkshopEquipmentController::class, 'toggleStatus'])->name('taller.equipos.status')->whereUuid('workshop_equipment');
    Route::delete('workshop/equipments/{workshop_equipment}', [WorkshopEquipmentController::class, 'destroy'])->name('taller.equipos.destroy')->whereUuid('workshop_equipment');

    Route::get('workshop/accessories/data', [WorkshopAccessoryController::class, 'list'])->name('taller.accesorios.data');
    Route::get('workshop/accessories/options', [WorkshopAccessoryController::class, 'options'])->name('taller.accesorios.options');
    Route::post('workshop/accessories', [WorkshopAccessoryController::class, 'store'])->name('taller.accesorios.store');

    Route::get('workshop/work-orders/options', [WorkshopOrderController::class, 'options'])->name('taller.ordenes.options');
    Route::get('workshop/work-orders/search/customers', [WorkshopOrderController::class, 'searchCustomers'])->name('taller.ordenes.search.customers');
    Route::get('workshop/work-orders/search/equipments', [WorkshopOrderController::class, 'searchEquipments'])->name('taller.ordenes.search.equipos');
    Route::get('workshop/work-orders/search/responsibles', [WorkshopOrderController::class, 'searchResponsibles'])->name('taller.ordenes.search.responsables');
    Route::get('workshop/work-orders/data', [WorkshopOrderController::class, 'list'])->name('taller.ordenes.data');
    Route::get('workshop/work-orders/create', [WorkshopOrderController::class, 'create'])->name('taller.ordenes.create');
    Route::get('workshop/work-orders', [WorkshopOrderController::class, 'index'])->name('taller.ordenes_de_trabajo');
    Route::post('workshop/work-orders', [WorkshopOrderController::class, 'store'])->name('taller.ordenes.store');
    Route::get('workshop/work-orders/{workshop_order}/edit', [WorkshopOrderController::class, 'edit'])->name('taller.ordenes.edit')->whereUuid('workshop_order');
    Route::get('workshop/work-orders/{workshop_order}/label', [WorkshopOrderController::class, 'label'])->name('workshop.orders.label')->whereUuid('workshop_order');
    Route::get('workshop/work-orders/{workshop_order}/ticket', [WorkshopOrderController::class, 'ticket'])->name('workshop.orders.ticket')->whereUuid('workshop_order');
    Route::get('workshop/work-orders/{workshop_order}', [WorkshopOrderController::class, 'show'])->name('taller.ordenes.show')->whereUuid('workshop_order');
    Route::patch('workshop/work-orders/{workshop_order}', [WorkshopOrderController::class, 'update'])->name('taller.ordenes.update')->whereUuid('workshop_order');
    Route::get('workshop/work-orders/{workshop_order}/json', [WorkshopOrderController::class, 'showJson'])->name('taller.ordenes.show.json')->whereUuid('workshop_order');
    Route::patch('workshop/work-orders/{workshop_order}/status', [WorkshopOrderController::class, 'toggleStatus'])->name('taller.ordenes.status')->whereUuid('workshop_order');
    Route::delete('workshop/work-orders/{workshop_order}', [WorkshopOrderController::class, 'destroy'])->name('taller.ordenes.destroy')->whereUuid('workshop_order');

    // Workshop Order Notes
    Route::get('workshop/work-orders/{workshop_order}/notes', [WorkshopOrderNoteController::class, 'index'])->name('taller.ordenes.notas.index')->whereUuid('workshop_order');
    Route::post('workshop/work-orders/{workshop_order}/notes', [WorkshopOrderNoteController::class, 'store'])->name('taller.ordenes.notas.store')->whereUuid('workshop_order');
    Route::patch('workshop/work-orders/{workshop_order}/notes/{note}', [WorkshopOrderNoteController::class, 'update'])->name('taller.ordenes.notas.update')->whereUuid(['workshop_order', 'note']);
    Route::delete('workshop/work-orders/{workshop_order}/notes/{note}', [WorkshopOrderNoteController::class, 'destroy'])->name('taller.ordenes.notas.destroy')->whereUuid(['workshop_order', 'note']);

    // Workshop Order Items
    Route::get('workshop/work-orders/{workshop_order}/items', [WorkshopOrderItemController::class, 'index'])->name('taller.ordenes.items.index')->whereUuid('workshop_order');
    Route::get('workshop/work-orders/items/search/products', [WorkshopOrderItemController::class, 'searchProducts'])->name('taller.ordenes.items.search.products');
    Route::get('workshop/work-orders/items/products/{product}/price', [WorkshopOrderItemController::class, 'getProductPrice'])->name('taller.ordenes.items.products.price')->whereUuid('product');
    Route::post('workshop/work-orders/{workshop_order}/items', [WorkshopOrderItemController::class, 'store'])->name('taller.ordenes.items.store')->whereUuid('workshop_order');
    Route::patch('workshop/work-orders/{workshop_order}/items/{item}', [WorkshopOrderItemController::class, 'update'])->name('taller.ordenes.items.update')->whereUuid(['workshop_order', 'item']);
    Route::delete('workshop/work-orders/{workshop_order}/items/{item}', [WorkshopOrderItemController::class, 'destroy'])->name('taller.ordenes.items.destroy')->whereUuid(['workshop_order', 'item']);

    // Workshop Order Services
    Route::get('workshop/work-orders/{workshop_order}/services', [WorkshopOrderServiceController::class, 'index'])->name('taller.ordenes.servicios.index')->whereUuid('workshop_order');
    Route::get('workshop/work-orders/services/search/services', [WorkshopOrderServiceController::class, 'searchServices'])->name('taller.ordenes.servicios.search.services');
    Route::post('workshop/work-orders/{workshop_order}/services', [WorkshopOrderServiceController::class, 'store'])->name('taller.ordenes.servicios.store')->whereUuid('workshop_order');
    Route::patch('workshop/work-orders/{workshop_order}/services/{service}', [WorkshopOrderServiceController::class, 'update'])->name('taller.ordenes.servicios.update')->whereUuid(['workshop_order', 'service']);
    Route::delete('workshop/work-orders/{workshop_order}/services/{service}', [WorkshopOrderServiceController::class, 'destroy'])->name('taller.ordenes.servicios.destroy')->whereUuid(['workshop_order', 'service']);

    Route::get('workshop/advances/options', [WorkshopOrderAdvanceController::class, 'options'])->name('taller.abonos.options');
    Route::get('workshop/advances/search/orders', [WorkshopOrderAdvanceController::class, 'searchOrders'])->name('taller.abonos.search.ordenes');
    Route::get('workshop/advances/data', [WorkshopOrderAdvanceController::class, 'list'])->name('taller.abonos.data');
    Route::get('workshop/advances', [WorkshopOrderAdvanceController::class, 'index'])->name('taller.abonos');
    Route::post('workshop/advances', [WorkshopOrderAdvanceController::class, 'store'])->name('taller.abonos.store');
    Route::get('workshop/advances/{workshop_order_advance}', [WorkshopOrderAdvanceController::class, 'show'])->name('taller.abonos.show')->whereUuid('workshop_order_advance');
    Route::patch('workshop/advances/{workshop_order_advance}', [WorkshopOrderAdvanceController::class, 'update'])->name('taller.abonos.update')->whereUuid('workshop_order_advance');
    Route::patch('workshop/advances/{workshop_order_advance}/status', [WorkshopOrderAdvanceController::class, 'toggleStatus'])->name('taller.abonos.status')->whereUuid('workshop_order_advance');
    Route::delete('workshop/advances/{workshop_order_advance}', [WorkshopOrderAdvanceController::class, 'destroy'])->name('taller.abonos.destroy')->whereUuid('workshop_order_advance');

    Route::get('workshop/states/data', [WorkshopStateController::class, 'list'])->name('taller.estados.data');
    Route::get('workshop/states/options', [WorkshopStateController::class, 'options'])->name('taller.estados.options');
    Route::get('workshop/states', [WorkshopStateController::class, 'index'])->name('taller.estados');
    Route::post('workshop/states', [WorkshopStateController::class, 'store'])->name('taller.estados.store');
    Route::get('workshop/states/{workshop_state}', [WorkshopStateController::class, 'show'])->name('taller.estados.show')->whereUuid('workshop_state');
    Route::patch('workshop/states/{workshop_state}', [WorkshopStateController::class, 'update'])->name('taller.estados.update')->whereUuid('workshop_state');
    Route::patch('workshop/states/{workshop_state}/status', [WorkshopStateController::class, 'toggleStatus'])->name('taller.estados.status')->whereUuid('workshop_state');
    Route::delete('workshop/states/{workshop_state}', [WorkshopStateController::class, 'destroy'])->name('taller.estados.destroy')->whereUuid('workshop_state');

Route::prefix('customers')->name('clientes.')->group(function (): void {
    Route::get('/', [CustomerController::class, 'indexIndividuals'])->name('index');
    Route::get('individuals', [CustomerController::class, 'indexIndividuals'])->name('naturales');
    Route::get('companies', [CustomerController::class, 'indexBusinesses'])->name('empresas');

    Route::get('data', [CustomerController::class, 'list'])->name('data');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    Route::get('{customer}', [CustomerController::class, 'show'])->name('show')->whereUuid('customer');
    Route::patch('{customer}', [CustomerController::class, 'update'])->name('update')->whereUuid('customer');
    Route::patch('{customer}/status', [CustomerController::class, 'toggleStatus'])->name('status')->whereUuid('customer');
    Route::delete('{customer}', [CustomerController::class, 'destroy'])->name('destroy')->whereUuid('customer');

    Route::post('validate/document', [CustomerController::class, 'validateDocument'])->name('validate.document');
    Route::post('validate/email', [CustomerController::class, 'validateEmail'])->name('validate.email');
});

Route::prefix('customers/categories')->name('clientes.categorias.')->group(function (): void {
    Route::get('data', [CustomerCategoryController::class, 'list'])->name('data');
    Route::get('options', [CustomerCategoryController::class, 'options'])->name('options');
    Route::get('/', [CustomerCategoryController::class, 'index'])->name('index');
    Route::post('/', [CustomerCategoryController::class, 'store'])->name('store');
    Route::get('{category}', [CustomerCategoryController::class, 'show'])->name('show')->whereUuid('category');
    Route::patch('{category}', [CustomerCategoryController::class, 'update'])->name('update')->whereUuid('category');
    Route::patch('{category}/status', [CustomerCategoryController::class, 'toggleStatus'])->name('status')->whereUuid('category');
    Route::delete('{category}', [CustomerCategoryController::class, 'destroy'])->name('destroy')->whereUuid('category');
});

    Route::get('security/users/data', [UserController::class, 'list'])->name('security.users.data');
    Route::get('security/users', [UserController::class, 'index'])->name('security.users');
    Route::post('security/users', [UserController::class, 'store'])->name('security.users.store');
    Route::get('security/users/{user}', [UserController::class, 'show'])->name('security.users.show');
    Route::patch('security/users/{user}', [UserController::class, 'update'])->name('security.users.update');
    Route::patch('security/users/{user}/status', [UserController::class, 'toggleStatus'])->name('security.users.status');
    Route::delete('security/users/{user}', [UserController::class, 'destroy'])->name('security.users.destroy');

    Route::get('security/roles/data', [RoleController::class, 'list'])->name('security.roles.data');
    Route::get('security/roles/options', [RoleController::class, 'options'])->name('security.roles.options');
    Route::get('security/roles', [RoleController::class, 'index'])->name('security.roles');
    Route::post('security/roles', [RoleController::class, 'store'])->name('security.roles.store');
    Route::get('security/roles/{role}', [RoleController::class, 'show'])->name('security.roles.show');
    Route::patch('security/roles/{role}', [RoleController::class, 'update'])->name('security.roles.update');
    Route::patch('security/roles/{role}/status', [RoleController::class, 'toggleStatus'])->name('security.roles.status');
    Route::delete('security/roles/{role}', [RoleController::class, 'destroy'])->name('security.roles.destroy');

    Route::get('security/permissions/data', [PermissionController::class, 'list'])->name('security.permissions.data');
    Route::get('security/permissions/options', [PermissionController::class, 'options'])->name('security.permissions.options');
    Route::get('security/permissions', [PermissionController::class, 'index'])->name('security.permissions');
    Route::post('security/permissions', [PermissionController::class, 'store'])->name('security.permissions.store');
    Route::get('security/permissions/{permission}', [PermissionController::class, 'show'])->name('security.permissions.show');
    Route::patch('security/permissions/{permission}', [PermissionController::class, 'update'])->name('security.permissions.update');
    Route::patch('security/permissions/{permission}/status', [PermissionController::class, 'toggleStatus'])->name('security.permissions.status');
    Route::delete('security/permissions/{permission}', [PermissionController::class, 'destroy'])->name('security.permissions.destroy');

    Route::get('configuration/product-lines/data', [ProductLineController::class, 'list'])->name('configuration.product_lines.data');
    Route::post('configuration/product-lines', [ProductLineController::class, 'store'])->name('configuration.product_lines.store');
    Route::get('configuration/product-lines/{productLine}', [ProductLineController::class, 'show'])->name('configuration.product_lines.show');
    Route::patch('configuration/product-lines/{productLine}', [ProductLineController::class, 'update'])->name('configuration.product_lines.update');
    Route::patch('configuration/product-lines/{productLine}/status', [ProductLineController::class, 'toggleStatus'])->name('configuration.product_lines.status');
    Route::delete('configuration/product-lines/{productLine}', [ProductLineController::class, 'destroy'])->name('configuration.product_lines.destroy');

    Route::get('configuration/product-categories/data', [ProductCategoryController::class, 'list'])->name('configuration.product_categories.data');
    Route::get('configuration/product-categories/options', [ProductCategoryController::class, 'options'])->name('configuration.product_categories.options');
    Route::post('configuration/product-categories', [ProductCategoryController::class, 'store'])->name('configuration.product_categories.store');
    Route::get('configuration/product-categories/{productCategory}', [ProductCategoryController::class, 'show'])->name('configuration.product_categories.show');
    Route::patch('configuration/product-categories/{productCategory}', [ProductCategoryController::class, 'update'])->name('configuration.product_categories.update');
    Route::patch('configuration/product-categories/{productCategory}/status', [ProductCategoryController::class, 'toggleStatus'])->name('configuration.product_categories.status');
    Route::delete('configuration/product-categories/{productCategory}', [ProductCategoryController::class, 'destroy'])->name('configuration.product_categories.destroy');

    foreach (config('navigation.pages', []) as $routeName => $page) {
        if (in_array($routeName, [
            'configuration.company',
            'configuration.branches',
            'configuration.cards',
            'configuration.bank_accounts',
            'configuration.income_types',
            'configuration.expense_types',
            'configuration.document_sequences',
            'configuration.receivable_categories',
            'configuration.payable_categories',
            'configuration.service_categories',
            'configuration.services',
            'configuration.warehouses',
            'configuration.price_lists',
            'inventory.products.index',
            'inventory.products.create',
            'inventory.products.show',
            'inventory.products.edit',
            'inventory.providers',
            'inventory.product_transfers.index',
            'taller.categorias',
            'taller.marcas',
            'taller.modelos',
            'taller.equipos',
            'taller.estados',
            'security.users',
            'security.roles',
            'security.permissions',
            'clientes.index',
            'clientes.naturales',
            'clientes.empresas',
            'clientes.categorias.index',
            'accounting.incomes.index',
            'accounting.receivables.index',
            'accounting.payables.index',
            'accounting.expenses.index',
            'accounting.sales',
            'accounting.sales.data',
            'accounting.sales.show',
            'catalog.lines',
            'catalog.categories',
            'catalog.subcategories',
            'taller.ordenes_de_trabajo',
            'taller.abonos',
            'pos.index',
            'sales.invoices.index',
            'sales.sales_notes.index',
            'sales.quotations.index',
            'sales.quotations.create',
            'sales.quotations.edit',
            'sales.quotations.show',
            'profile.show',
        ], true)) {
            continue;
        }

        Route::get($page['path'], PlaceholderPageController::class)
            ->name($routeName)
            ->defaults('pageKey', $routeName);
    }

    // Rutas de notificaciones
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Chat routes
    Route::prefix('chat')->name('chat.')->group(function (): void {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/conversations/{conversation}', [ChatController::class, 'show'])->name('show')->whereUuid('conversation');
        Route::post('/conversations', [ChatController::class, 'store'])->name('store');
        Route::delete('/conversations/{conversation}', [ChatController::class, 'destroy'])->name('destroy')->whereUuid('conversation');
        Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('messages.send')->whereUuid('conversation');
        
        // Tool endpoints (llamadas desde el LLM)
        Route::post('/tools/search-customer-by-document', [ChatToolController::class, 'searchCustomerByDocument'])->name('tools.search-customer');
        Route::post('/tools/get-workshop-order-by-number', [ChatToolController::class, 'getWorkshopOrderByNumber'])->name('tools.get-order');
        Route::post('/tools/search-products-by-names', [ChatToolController::class, 'searchProductsByNames'])->name('tools.search-products');
    });
});
