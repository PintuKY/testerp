<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\Api\ManageDataOfProductOrderAndCustomerController;
// use App\Http\Controllers\Api\MasterListController;

include_once('install_r.php');

Route::middleware(['setData'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Auth::routes();

    Route::get('/business/register', 'BusinessController@getRegister')->name('business.getRegister');
    Route::post('/business/register', 'BusinessController@postRegister')->name('business.postRegister');
    Route::post('/business/register/check-username', 'BusinessController@postCheckUsername')->name('business.postCheckUsername');
    Route::post('/business/register/check-email', 'BusinessController@postCheckEmail')->name('business.postCheckEmail');

    Route::get('/invoice/{token}', 'SellPosController@showInvoice')
        ->name('show_invoice');
    Route::get('/quote/{token}', 'SellPosController@showInvoice')
        ->name('show_quote');

    Route::get('/pay/{token}', 'SellPosController@invoicePayment')
        ->name('invoice_payment');
    Route::post('/confirm-payment/{id}', 'SellPosController@confirmPayment')
        ->name('confirm_payment');
});

//Routes for authenticated users only,
Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])->group(function () {
    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/home/get-totals', 'HomeController@getTotals');
    Route::get('/home/product-stock-alert', 'HomeController@getProductStockAlert');
    Route::get('/home/purchase-payment-dues', 'HomeController@getPurchasePaymentDues');
    Route::get('/home/sales-payment-dues', 'HomeController@getSalesPaymentDues');
    Route::post('/attach-medias-to-model', 'HomeController@attachMediasToGivenModel')->name('attach.medias.to.model');
    Route::get('/calendar', 'HomeController@getCalendar')->name('calendar');

    Route::post('/test-email', 'BusinessController@testEmailConfiguration');
    Route::post('/test-sms', 'BusinessController@testSmsConfiguration');
    Route::get('/business/settings', 'BusinessController@getBusinessSettings')->name('business.getBusinessSettings');
    Route::post('/business/update', 'BusinessController@postBusinessSettings')->name('business.postBusinessSettings');
    Route::get('/user/profile', 'UserController@getProfile')->name('user.getProfile');
    Route::post('/user/update', 'UserController@updateProfile')->name('user.updateProfile');
    Route::post('/user/update-password', 'UserController@updatePassword')->name('user.updatePassword');


   //Route::resource('payment-account', 'PaymentAccountController');

    Route::resource('tax-rates', 'TaxRateController');

    Route::resource('units', 'UnitController');

    Route::post('check-mobile', 'ContactController@checkMobile');
    Route::get('/get-contact-due/{contact_id}', 'ContactController@getContactDue');
    Route::get('/contacts/payments/{contact_id}', 'ContactController@getContactPayments');
    Route::get('/contacts/map', 'ContactController@contactMap');
    Route::get('/contacts/update-status/{id}', 'ContactController@updateStatus');
    Route::get('/contacts/stock-report/{supplier_id}', 'ContactController@getSupplierStockReport');
    Route::get('/contacts/ledger', 'ContactController@getLedger');
    Route::post('/contacts/send-ledger', 'ContactController@sendLedger');
    Route::post('/contacts/check-contacts-id', 'ContactController@checkContactId');
    Route::get('/contacts/customers', 'ContactController@getCustomers');
    Route::resource('contacts', 'ContactController');

    Route::get('taxonomies-ajax-index-page', 'TaxonomyController@getTaxonomyIndexPage');
    Route::resource('taxonomies', 'TaxonomyController');

    Route::resource('variation-templates', 'VariationTemplateController');


    Route::get('/delete-media/{media_id}', 'ProductController@deleteMedia');
    Route::post('/products/mass-deactivate', 'ProductController@massDeactivate');
    Route::get('/products/activate/{id}', 'ProductController@activate');
    Route::get('/products/view-product-group-price/{id}', 'ProductController@viewGroupPrice');
    Route::get('/products/add-selling-prices/{id}', 'ProductController@addSellingPrices');
    Route::post('/products/save-selling-prices', 'ProductController@saveSellingPrices');
    Route::post('/products/mass-delete', 'ProductController@massDestroy');
    Route::get('/products/view/{id}', 'ProductController@view');
    Route::get('/products/list', 'ProductController@getProducts');
    Route::get('/products/list-no-variation', 'ProductController@getProductsWithoutVariations');
    Route::post('/products/bulk-edit', 'ProductController@bulkEdit');
    Route::post('/products/bulk-update', 'ProductController@bulkUpdate');
    Route::post('/products/bulk-update-location', 'ProductController@updateProductLocation');
    Route::get('/products/get-product-to-edit/{product_id}', 'ProductController@getProductToEdit');

    Route::post('/products/get_sub_categories', 'ProductController@getSubCategories');
    Route::get('/products/get_sub_units', 'ProductController@getSubUnits');
    Route::post('/products/product_form_part', 'ProductController@getProductVariationFormPart');
    Route::post('/products/get_product_variation_row', 'ProductController@getProductVariationRow');
    Route::post('/products/get_variation_template', 'ProductController@getVariationTemplate');
    Route::get('/products/get_variation_value_row', 'ProductController@getVariationValueRow');
    Route::post('/products/check_product_sku', 'ProductController@checkProductSku');
    Route::get('/products/quick_add', 'ProductController@quickAdd');
    Route::post('/products/save_quick_product', 'ProductController@saveQuickProduct');
    Route::get('/products/get-combo-product-entry-row', 'ProductController@getComboProductEntryRow');
    Route::post('/products/toggle-woocommerce-sync', 'ProductController@toggleWooCommerceSync');

    Route::resource('products', 'ProductController');


    Route::get('/toggle-subscription/{id}', 'SellPosController@toggleRecurringInvoices');
    Route::post('/sells/pos/get-types-of-service-details', 'SellPosController@getTypesOfServiceDetails');
    Route::get('/sells/subscriptions', 'SellPosController@listSubscriptions');
    Route::get('/sells/duplicate/{id}', 'SellController@duplicateSell');
    Route::get('/sells/transaction-activity/{id}', 'SellController@transactionActivity');
    Route::get('/sells/drafts', 'SellController@getDrafts')->name('sells.drafts');
    Route::get('/sells/convert-to-draft/{id}', 'SellPosController@convertToInvoice');
    Route::get('/sells/convert-to-proforma/{id}', 'SellPosController@convertToProforma');
    Route::get('/sells/quotations', 'SellController@getQuotations')->name('sells.quotations');
    Route::get('sells/products/list', 'SellController@getProducts');
    Route::get('/sells/draft-dt', 'SellController@getDraftDatables');

    Route::resource('sells', 'SellController')->except(['show']);
    Route::resource('transaction_activity', 'TransactionActivityController');
    Route::resource('masters', 'SellController@master');

    Route::get('/import-sales', 'ImportSalesController@index')->name('sales.import');
    Route::post('/import-sales/preview', 'ImportSalesController@preview');
    Route::post('/import-sales', 'ImportSalesController@import');
    Route::get('/revert-sale-import/{batch}', 'ImportSalesController@revertSaleImport');

    Route::get('/sells/pos/get_product_row/{variation_id}/{location_id}', 'SellPosController@getProductRow');
    Route::post('/sells/pos/get_payment_row', 'SellPosController@getPaymentRow');
    Route::post('/sells/pos/get-reward-details', 'SellPosController@getRewardDetails');
    Route::get('/sells/pos/get-recent-transactions', 'SellPosController@getRecentTransactions');
    Route::get('/sells/pos/get-product-suggestion', 'SellPosController@getProductSuggestion');
    Route::get('/sells/pos/get-featured-products/{location_id}', 'SellPosController@getFeaturedProducts');
    Route::get('/reset-mapping', 'SellController@resetMapping');
    Route::get('/sells/get_product_row/{product_id}/{location_id}', 'SellController@getSellProductRow');

    Route::resource('pos', 'SellPosController');

    Route::resource('roles', 'RoleController');

    Route::resource('users', 'ManageUserController');

    Route::resource('group-taxes', 'GroupTaxController');

    Route::get('/barcodes/set_default/{id}', 'BarcodeController@setDefault');
    Route::resource('barcodes', 'BarcodeController');

    //Invoice schemes..
    Route::get('/invoice-schemes/set_default/{id}', 'InvoiceSchemeController@setDefault');
    Route::resource('invoice-schemes', 'InvoiceSchemeController');

    //Print Labels
    Route::get('/labels/show', 'LabelsController@show')->name("labels.show");
    Route::get('/supplier/purchase/labels/show', 'LabelsController@supplierPurchaseShow')->name("labels.show");
    Route::get('/labels/add-product-row', 'LabelsController@addProductRow');
    Route::get('/labels/preview', 'LabelsController@preview');

    //Reports...
    Route::get('/reports/get-stock-by-sell-price', 'ReportController@getStockBySellingPrice');
    Route::get('/reports/purchase-report', 'ReportController@purchaseReport');
    Route::get('/reports/sale-report', 'ReportController@saleReport');
    Route::get('/reports/service-staff-report', 'ReportController@getServiceStaffReport');
    Route::get('/reports/service-staff-line-orders', 'ReportController@serviceStaffLineOrders');
    Route::get('/reports/table-report', 'ReportController@getTableReport');
    Route::get('/reports/profit-loss', 'ReportController@getProfitLoss');
    Route::get('/reports/get-opening-stock', 'ReportController@getOpeningStock');
    Route::get('/reports/purchase-sell', 'ReportController@getPurchaseSell');
    Route::get('/reports/customers', 'ReportController@getCustomers');
    Route::get('/reports/suppliers', 'ReportController@getSuppliers');
    Route::get('/reports/stock-report', 'ReportController@getStockReport');
    Route::get('/reports/stock-details', 'ReportController@getStockDetails');
    Route::get('/reports/tax-report', 'ReportController@getTaxReport');
    Route::get('/reports/tax-details', 'ReportController@getTaxDetails');
    Route::get('/reports/trending-products', 'ReportController@getTrendingProducts');
    Route::get('/reports/expense-report', 'ReportController@getExpenseReport');
    Route::get('/reports/stock-adjustment-report', 'ReportController@getStockAdjustmentReport');
    Route::get('/reports/register-report', 'ReportController@getRegisterReport');
    Route::get('/reports/sales-representative-report', 'ReportController@getSalesRepresentativeReport');
    Route::get('/reports/sales-representative-total-expense', 'ReportController@getSalesRepresentativeTotalExpense');
    Route::get('/reports/sales-representative-total-sell', 'ReportController@getSalesRepresentativeTotalSell');
    Route::get('/reports/sales-representative-total-commission', 'ReportController@getSalesRepresentativeTotalCommission');
    Route::get('/reports/stock-expiry', 'ReportController@getStockExpiryReport');
    Route::get('/reports/stock-expiry-edit-modal/{purchase_line_id}', 'ReportController@getStockExpiryReportEditModal');
    Route::post('/reports/stock-expiry-update', 'ReportController@updateStockExpiryReport')->name('updateStockExpiryReport');
    Route::get('/reports/customer-group', 'ReportController@getCustomerGroup');
    Route::get('/reports/product-purchase-report', 'ReportController@getproductPurchaseReport');
    Route::get('/reports/product-sell-grouped-by', 'ReportController@productSellReportBy');
    Route::get('/reports/product-sell-report', 'ReportController@getproductSellReport');
    Route::get('/reports/product-sell-report-with-purchase', 'ReportController@getproductSellReportWithPurchase');
    Route::get('/reports/product-sell-grouped-report', 'ReportController@getproductSellGroupedReport');
    Route::get('/reports/lot-report', 'ReportController@getLotReport');
    Route::get('/reports/purchase-payment-report', 'ReportController@purchasePaymentReport');
    Route::get('/reports/sell-payment-report', 'ReportController@sellPaymentReport');
    Route::get('/reports/product-stock-details', 'ReportController@productStockDetails');
    Route::get('/reports/adjust-product-stock', 'ReportController@adjustProductStock');
    Route::get('/reports/get-profit/{by?}', 'ReportController@getProfit');
    Route::get('/reports/items-report', 'ReportController@itemsReport');
    Route::get('/reports/get-stock-value', 'ReportController@getStockValue');

    Route::get('business-location/activate-deactivate/{location_id}', 'BusinessLocationController@activateDeactivateLocation');

    //Business Location Settings...
    Route::prefix('business-location/{location_id}')->name('location.')->group(function () {
        Route::get('settings', 'LocationSettingsController@index')->name('settings');
        Route::post('settings', 'LocationSettingsController@updateSettings')->name('settings_update');
    });

    //Business Locations...
    Route::post('business-location/check-location-id', 'BusinessLocationController@checkLocationId');
    Route::resource('business-location', 'BusinessLocationController');

    //kitchen Locations...
    Route::resource('kitchen-location', 'KitchenLocationController');

    // APi Setting...
    Route::resource('api-setting', 'ApiController');

    //Invoice layouts..
    Route::resource('invoice-layouts', 'InvoiceLayoutController');

    Route::post('get-expense-sub-categories', 'ExpenseCategoryController@getSubCategories');

    //Expense Categories...
    Route::resource('expense-categories', 'ExpenseCategoryController');

    //Expenses...
    Route::resource('expenses', 'ExpenseController');

    //Transaction payments...
    // Route::get('/payments/opening-balance/{contact_id}', 'TransactionPaymentController@getOpeningBalancePayments');
    Route::get('/payments/show-child-payments/{payment_id}', 'TransactionPaymentController@showChildPayments');
    Route::get('/payments/view-payment/{payment_id}', 'TransactionPaymentController@viewPayment');
    Route::get('/payments/add_payment/{transaction_id}', 'TransactionPaymentController@addPayment');
    Route::get('/payments/pay-contact-due/{contact_id}', 'TransactionPaymentController@getPayContactDue');
    Route::post('/payments/pay-contact-due', 'TransactionPaymentController@postPayContactDue');
    Route::resource('payments', 'TransactionPaymentController');

    //Printers...
    Route::resource('printers', 'PrinterController');

    Route::get('/stock-adjustments/remove-expired-stock/{purchase_line_id}', 'StockAdjustmentController@removeExpiredStock');
    Route::post('/stock-adjustments/get_product_row', 'StockAdjustmentController@getProductRow');
    Route::post('/stock-adjustments/get_supplier_product_row', 'StockAdjustmentController@getSupplierProductRow');
    Route::resource('stock-adjustments', 'StockAdjustmentController');

    Route::get('/cash-register/register-details', 'CashRegisterController@getRegisterDetails');
    Route::get('/cash-register/close-register/{id?}', 'CashRegisterController@getCloseRegister');
    Route::post('/cash-register/close-register', 'CashRegisterController@postCloseRegister');
    Route::resource('cash-register', 'CashRegisterController');


    //Sales Commission Agent
    Route::resource('sales-commission-agents', 'SalesCommissionAgentController');

    //Stock Transfer
    Route::get('stock-transfers/print/{id}', 'StockTransferController@printInvoice');
    Route::post('stock-transfers/update-status/{id}', 'StockTransferController@updateStatus');
    Route::resource('stock-transfers', 'StockTransferController');

    Route::get('/opening-stock/add/{product_id}', 'OpeningStockController@add');
    Route::post('/opening-stock/save', 'OpeningStockController@save');

    //Customer Groups
    Route::resource('customer-group', 'CustomerGroupController');

    //Sell return
    Route::resource('sell-return', 'SellReturnController');
    Route::get('sell-return/get-product-row', 'SellReturnController@getProductRow');
    Route::get('/sell-return/print/{id}', 'SellReturnController@printInvoice');
    Route::get('/sell-return/add/{id}', 'SellReturnController@add');

    //Backup
    Route::get('backup/download/{file_name}', 'BackUpController@download');
    Route::get('backup/delete/{file_name}', 'BackUpController@delete');
    Route::resource('backup', 'BackUpController', ['only' => [
        'index', 'create', 'store'
    ]]);

    Route::get('selling-price-group/activate-deactivate/{id}', 'SellingPriceGroupController@activateDeactivate');
    Route::get('export-selling-price-group', 'SellingPriceGroupController@export');
    Route::post('import-selling-price-group', 'SellingPriceGroupController@import');

    Route::resource('selling-price-group', 'SellingPriceGroupController');

    Route::get('ingredients/activate-deactivate/{id}', 'IngredientController@activateDeactivate');
    Route::get('export-ingredients', 'IngredientController@export');
    Route::post('import-ingredients', 'IngredientController@import');

    Route::resource('ingredients', 'IngredientController');
    Route::get('recipe/ingredients/list', 'RecipeController@getIngredients');
    Route::get('recipe/get_ingredients_row/{id}', 'RecipeController@getIngRow');
    Route::resource('recipe', 'RecipeController');
    Route::resource('menu', 'MenuController');

    Route::resource('notification-templates', 'NotificationTemplateController')->only(['index', 'store']);
    Route::get('notification/get-template/{transaction_id}/{template_for}', 'NotificationController@getTemplate');
    Route::post('notification/send', 'NotificationController@send');

    Route::post('/purchase-return/update', 'CombinedPurchaseReturnController@update');
    Route::get('/purchase-return/edit/{id}', 'CombinedPurchaseReturnController@edit');
    Route::post('/purchase-return/save', 'CombinedPurchaseReturnController@save');
    Route::post('/purchase-return/get_product_row', 'CombinedPurchaseReturnController@getProductRow');
    Route::get('/purchase-return/create', 'CombinedPurchaseReturnController@create');
    Route::get('/purchase-return/add/{id}', 'PurchaseReturnController@add');
    Route::resource('/purchase-return', 'PurchaseReturnController', ['except' => ['create']]);

    Route::get('/discount/activate/{id}', 'DiscountController@activate');
    Route::post('/discount/mass-deactivate', 'DiscountController@massDeactivate');
    Route::resource('discount', 'DiscountController');

    Route::group(['prefix' => 'account'], function () {
        Route::resource('/account', 'AccountController');
        Route::get('/fund-transfer/{id}', 'AccountController@getFundTransfer');
        Route::post('/fund-transfer', 'AccountController@postFundTransfer');
        Route::get('/deposit/{id}', 'AccountController@getDeposit');
        Route::post('/deposit', 'AccountController@postDeposit');
        Route::get('/close/{id}', 'AccountController@close');
        Route::get('/activate/{id}', 'AccountController@activate');
        Route::get('/delete-account-transaction/{id}', 'AccountController@destroyAccountTransaction');
        Route::get('/edit-account-transaction/{id}', 'AccountController@editAccountTransaction');
        Route::post('/update-account-transaction/{id}', 'AccountController@updateAccountTransaction');
        Route::get('/get-account-balance/{id}', 'AccountController@getAccountBalance');
        Route::get('/balance-sheet', 'AccountReportsController@balanceSheet');
        Route::get('/trial-balance', 'AccountReportsController@trialBalance');
        Route::get('/payment-account-report', 'AccountReportsController@paymentAccountReport');
        Route::get('/link-account/{id}', 'AccountReportsController@getLinkAccount');
        Route::post('/link-account', 'AccountReportsController@postLinkAccount');
        Route::get('/cash-flow', 'AccountController@cashFlow');
    });

    Route::resource('account-types', 'AccountTypeController');

    //Restaurant module
    Route::group(['prefix' => 'modules'], function () {
        Route::resource('tables', 'Restaurant\TableController');
        Route::resource('modifiers', 'Restaurant\ModifierSetsController');

        //Map modifier to products
        Route::get('/product-modifiers/{id}/edit', 'Restaurant\ProductModifierSetController@edit');
        Route::post('/product-modifiers/{id}/update', 'Restaurant\ProductModifierSetController@update');
        Route::get('/product-modifiers/product-row/{product_id}', 'Restaurant\ProductModifierSetController@product_row');

        Route::get('/add-selected-modifiers', 'Restaurant\ProductModifierSetController@add_selected_modifiers');

        Route::get('/kitchen', 'Restaurant\KitchenController@index');
        Route::get('/kitchen/mark-as-cooked/{id}', 'Restaurant\KitchenController@markAsCooked');
        Route::post('/refresh-orders-list', 'Restaurant\KitchenController@refreshOrdersList');
        Route::post('/refresh-line-orders-list', 'Restaurant\KitchenController@refreshLineOrdersList');

        Route::get('/orders', 'Restaurant\OrderController@index');
        Route::get('/orders/mark-as-served/{id}', 'Restaurant\OrderController@markAsServed');
        Route::get('/data/get-pos-details', 'Restaurant\DataController@getPosDetails');
        Route::get('/orders/mark-line-order-as-served/{id}', 'Restaurant\OrderController@markLineOrderAsServed');
        Route::get('/print-line-order', 'Restaurant\OrderController@printLineOrder');
    });

    Route::get('bookings/get-todays-bookings', 'Restaurant\BookingController@getTodaysBookings');
    Route::resource('bookings', 'Restaurant\BookingController');

    Route::resource('types-of-service', 'TypesOfServiceController');
    Route::get('sells/edit-shipping/{id}', 'SellController@editShipping');
    Route::put('sells/update-shipping/{id}', 'SellController@updateShipping');
    Route::get('shipments', 'SellController@shipments')->name('shipments');

    Route::post('upload-module', 'Install\ModulesController@uploadModule');
    Route::resource('manage-modules', 'Install\ModulesController')
        ->only(['index', 'destroy', 'update']);

    Route::resource('dashboard-configurator', 'DashboardConfiguratorController')
    ->only(['edit', 'update']);

    Route::get('view-media/{model_id}', 'SellController@viewMedia');

    //common controller for document & note
    Route::get('get-document-note-page', 'DocumentAndNoteController@getDocAndNoteIndexPage');
    Route::get('contact/get-document-note-page', 'ContactController@getDocAndNoteIndexPage');
    Route::get('contact/note-documents', 'ContactController@getNoteDocument');
    Route::post('post-document-upload', 'DocumentAndNoteController@postMedia');
    Route::resource('note-documents', 'DocumentAndNoteController');
    Route::resource('purchase-order', 'PurchaseOrderController');
    Route::get('get-purchase-orders/{contact_id}', 'PurchaseOrderController@getPurchaseOrders');
    Route::get('get-purchase-order-lines/{purchase_order_id}', 'PurchaseOrderController@getPurchaseOrderLines');
    Route::get('edit-purchase-orders/{id}/status', 'PurchaseOrderController@getEditPurchaseOrderStatus');
    Route::put('update-purchase-orders/{id}/status', 'PurchaseOrderController@postEditPurchaseOrderStatus');
    Route::resource('sales-order', 'SalesOrderController')->only(['index']);
    Route::get('get-sales-orders/{customer_id}', 'SalesOrderController@getSalesOrders');
    Route::get('get-sales-order-lines', 'SellPosController@getSalesOrderLines');
    Route::get('edit-sales-orders/{id}/status', 'SalesOrderController@getEditSalesOrderStatus');
    Route::put('update-sales-orders/{id}/status', 'SalesOrderController@postEditSalesOrderStatus');
    Route::get('reports/activity-log', 'ReportController@activityLog');
    Route::get('user-location/{latlng}', 'HomeController@getUserLocation');

    // supplier modules
    Route::resource('supplier', 'SupplierController');
    Route::post('/supplier/check-supplier-id', 'SupplierController@checkSupplierId');
    Route::post('/supplier/check-mobile', 'SupplierController@checkMobile');
    Route::get('/supplier/update-status/{id}', 'SupplierController@updateStatus');
    Route::get('/supplier/stock-report/{supplier_id}', 'SupplierController@getSupplierStockReport');
    Route::get('/suppliers/ledger', 'SupplierController@getLedger');
    Route::get('/supplier/payments/{supplier_id}', 'SupplierController@getSupplierPayments')->name('supplier.payments');

    // supplier product 
    Route::get('/supplier-products/quick_add', 'SupplierProductController@quickAdd');
    Route::get('/supplier-products/stock-history/{id}', 'SupplierProductController@productStockHistory');
    Route::post('/supplier-products/save_quick_product', 'SupplierProductController@saveQuickProduct');
    Route::get('supplier-products/list', 'SupplierProductController@getProducts');
    Route::resource('supplier-products', 'SupplierProductController');

    // supplier product unit
    Route::resource('supplier-product-units', 'SupplierProductUnitController');
    Route::resource('supplier-product-categories', 'SupplierProductCategoryController');
    
    // supplier product brands
    Route::resource('supplier-product-brands', 'SupplierProductBrandController');
    
    // supplier purchases modules
    Route::post('/supplier-purchases/update-status', 'SupplierPurchaseController@updateStatus');
    Route::get('/supplier-purchases/get_products', 'SupplierPurchaseController@getProducts');
    Route::get('/suppliers-purchases/get_suppliers', 'SupplierPurchaseController@getSuppliers');
    Route::post('/supplier-purchases/get_purchase_entry_row', 'SupplierPurchaseController@getPurchaseEntryRow');
    Route::post('/supplier-purchases/check_ref_number', 'SupplierPurchaseController@checkRefNumber');
    Route::resource('/supplier-purchases', 'SupplierPurchaseController');
    Route::post('/import-supplier-purchase-products', 'SupplierPurchaseController@importPurchaseProducts');



    //Supplier Transaction payments...
    // Route::get('/payments/opening-balance/{contact_id}', 'TransactionPaymentController@getOpeningBalancePayments');
    Route::get('/supplier/payments/show-child-payments/{payment_id}', 'SupplierTransactionPaymentController@showChildPayments');
    Route::get('/supplier/payments/view-payment/{payment_id}', 'SupplierTransactionPaymentController@viewPayment');
    Route::get('/supplier/payments/add_payment/{transaction_id}', 'SupplierTransactionPaymentController@addPayment');
    Route::get('/supplier/payments/pay-contact-due/{contact_id}', 'SupplierTransactionPaymentController@getPayContactDue');
    Route::post('/supplier/payments/pay-contact-due', 'SupplierTransactionPaymentController@postPayContactDue');
    Route::resource('/suppliers/payments', 'SupplierTransactionPaymentController');

    //Driver module...
    Route::post('/driver/check-mobile', 'DriverController@checkMobile');
    Route::post('/driver/check-email', 'DriverController@checkEmail');
    Route::get('/driver/edit_all', 'DriverController@editAll');
    Route::get('/driver/attendence', 'DriverController@driverAttendence');
    Route::post('/driver/update/all', 'DriverController@updateAll');
    Route::resource('driver/attendence', 'DriverAttendenceController');
    Route::resource('driver', 'DriverController');


});


Route::middleware(['EcomApi'])->prefix('api/ecom')->group(function () {
    Route::get('products/{id?}', 'ProductController@getProductsApi');
    //Route::get('categories', 'CategoryController@getCategoriesApi');
    // Route::get('brands', 'BrandController@getBrandsApi');
    Route::post('customers', 'ContactController@postCustomersApi');
    Route::get('settings', 'BusinessController@getEcomSettings');
    Route::get('variations', 'ProductController@getVariationsApi');
    Route::post('orders', 'SellPosController@placeOrdersApi');
});
Route::post('master/fetch-business-location', 'MasterController@fetchBusinessLocation');
Route::get('master/total', 'MasterController@totalIndex');
Route::resource('master', 'MasterController');

//common route
Route::middleware(['auth'])->group(function () {
    Route::get('/logout', 'Auth\LoginController@logout')->name('logout');
});

Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone'])->group(function () {
    Route::get('/load-more-notifications', 'HomeController@loadMoreNotifications');
    Route::get('/get-total-unread', 'HomeController@getTotalUnreadNotifications');
    Route::get('/download-purchase-order/{id}/pdf', 'PurchaseOrderController@downloadPdf')->name('purchaseOrder.downloadPdf');
    Route::get('/sells/{id}', 'SellController@show');
    Route::get('/master_list/{id}/{sell_id}', 'MasterController@getMasterList');
    Route::get('/sells/{transaction_id}/print', 'SellPosController@printInvoice')->name('sell.printInvoice');
    Route::get('/download-sells/{transaction_id}/pdf', 'SellPosController@downloadPdf')->name('sell.downloadPdf');
    Route::get('/download-quotation/{id}/pdf', 'SellPosController@downloadQuotationPdf')
        ->name('quotation.downloadPdf');
    Route::get('/download-packing-list/{id}/pdf', 'SellPosController@downloadPackingListPdf')
        ->name('packing.downloadPdf');
    Route::get('/sells/invoice-url/{id}', 'SellPosController@showInvoiceUrl');
    Route::get('/show-notification/{id}', 'HomeController@showNotification');
});


Route::get('sync/orders/{business_location_id}', [ManageDataOfProductOrderAndCustomerController::class,'syncOrderDetails'])->name('sync.orders');
Route::get('sync/products/{business_location_id}', [ManageDataOfProductOrderAndCustomerController::class,'syncProductDetails'])->name('sync.products');
Route::get('sync/customers/{business_location_id}', [ManageDataOfProductOrderAndCustomerController::class,'syncCustomerDetails'])->name('sync.customers');
Route::get('sync/all', [ManageDataOfProductOrderAndCustomerController::class,'syncAllDetails'])->name('sync.all');
// Route::get('create/master/list', [MasterListController::class,'createMasterList']);
Route::get('master/export/{type}', 'MasterController@exportExcel')->name('exportExcel');
