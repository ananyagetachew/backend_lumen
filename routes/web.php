<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$router->group(['prefix' => 'api'], function ($router) {

    $router->post('login', 'AuthController@login');
    $router->get('logout', 'AuthController@logout');

    $router->group(['middleware' => 'api_token_auth'], function ($router) {
        $router->get('delivery/search/{query}', 'DeliveryOrderController@search');
        $router->get('proforma/search/{query}', 'ProformaOrderController@search');

        $router->get('companies/{from_table}', 'CompanyController@index');

        $router->get('filter/{filterable_table}', 'FilterableController@filter');

        // reporting module
        $router->get('report/{report_table}', 'ReportController@getReport');

        //sales module
        $router->group(['prefix' => 'sales'], function ($router) {

            $router->post('delivery', 'DeliveryOrderController@importFromExcel');
            $router->post('delivery/manual', 'DeliveryOrderController@manuallyInsert');
            $router->post('delivery/send-to-production/{id}', 'DeliveryOrderController@sendAllToProduction');
            $router->get('deliveries', 'DeliveryOrderController@index');
            $router->get('delivery/detail/{id}', 'DeliveryOrderController@getDeliveryOrderDetail');
            $router->patch('delivery/persist/{id}', 'DeliveryOrderController@persistEditedData');
            $router->post('delivery/package/{order_id}', 'DeliveryPackageController@store');
            $router->post('delivery/items', 'DeliveryItemController@store');

            //production
            $router->post('delivery/send-selected-to-production', 'ProductionOrderController@store');
            $router->get('production/{delivery_id}', 'ProductionOrderController@getHistory');
            $router->get('productions', 'ProductionOrderController@index');

            //proforma
            $router->post('proforma', 'ProformaOrderController@importFromExcel');
            $router->post('proforma/manual', 'ProformaOrderController@manuallyInsert');
            $router->get('proformas', 'ProformaOrderController@index');
            $router->get('proforma/detail/{id}', 'ProformaOrderController@getProformaOrderDetail');
            $router->patch('proforma/persist/{id}', 'ProformaOrderController@persistEditedData');
            $router->post('proforma/package/{order_id}', 'ProformaPackageController@store');
            $router->post('proforma/items', 'ProformaItemController@store');
            $router->get('proforma/to-delivery/{id}', 'ProformaOrderController@convertToDeliveryOrder');
        });

        // grouping the factory loader department api endpoints
        $router->group(['prefix' => 'factoryloader'], function ($router) {

            $router->post('delivered/confirm-selected-delivered', 'DeliveredOrderController@store');
            $router->get('delivered/{id}', 'DeliveredOrderController@getProductionDetail');

            $router->get('delivered-orders', 'DeliveredOrderController@index');

            $router->get('productions', 'ProductionOrderController@index');
            $router->get('production/{delivery_id}', 'ProductionOrderController@getProductionDetail');
        });

        // grouping the stock manager department api endpoints
        $router->group(['prefix' => 'stockmanager'], function ($router) {

            $router->get('products', 'StockController@getProducts');
            $router->post('product', 'StockController@store');
            $router->get('stocks', 'StockController@index');
            $router->post('transfer', 'StockController@update');
            $router->get('search/{query}', 'StockController@search');
            $router->get('delivery-numbers', 'StockController@getDeliveryNos');
            $router->get('filter', 'FilterableController@filterStockItems');
        });

        // grouping the finance department api endpoints
        $router->group(['prefix' => 'financemanager'], function ($router) {

            $router->get('aggregate-stocks', 'StockController@getAggregateStockStatus');
            $router->get('aggregate/{type}', 'StockController@getOrdersAggregateFinanceReport');
            $router->post('stock-prices', 'StockController@getPriceForEachStock');
        });
    });
});
