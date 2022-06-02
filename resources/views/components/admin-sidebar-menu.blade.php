<ul class="sidebar-menu tree" data-widget="tree">
    <li class=""><a href="{{route('home')}}"><i class="fa fas fa-tachometer-alt"></i> <span>Home</span></a></li>
    <li class="treeview ">
        <a href="#">
            <i class="fa fas fa-users"></i> <span>User Management</span>
            <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
        </a>
        <ul class="treeview-menu" style="display: none;">
            <li @if(request()->segment(1) == 'users') class="active" @endif ><a href="{{route('users.index')}}"><i
                        class="fa fas fa-user"></i> <span>Users</span></a></li>
            <li @if(request()->segment(1) == 'roles') class="active" @endif ><a href="{{route('roles.index')}}"><i
                        class="fa fas fa-briefcase"></i> <span>Roles</span></a></li>
            <li @if(request()->segment(1) == 'sales-commission-agents') class="active" @endif><a
                    href="{{route('sales-commission-agents.index')}}"><i class="fa fas fa-handshake"></i> <span>Sales Commission Agents</span></a>
            </li>

        </ul>
    </li>
    <li class="treeview" id="tour_step4">
        <a href="#">
            <i class="fa fas fa-address-book"></i> <span>Contacts</span>
            <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="{{route('contacts.index',['type' => 'customer'])}}"><i class="fa fas fa-star"></i> <span>Customers</span></a>
            </li>
            <li><a href="{{route('customer-group.index')}}"><i class="fa fas fa-users"></i> <span>Customer Groups</span></a>
            </li>
            <li><a href="{{route('contacts.import')}}"><i class="fa fas fa-download"></i>
                    <span>Import Contacts</span></a></li>
        </ul>
    </li>
    <li class="treeview" id="tour_step5">
        <a href="#">
            <i class="fa fas fa-cubes"></i> <span>Products</span>
            <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="{{route('products.index')}}"><i class="fa fas fa-list"></i> <span>List Products</span></a></li>
            <li><a href="{{route('products.create')}}"><i class="fa fas fa-plus-circle"></i>
                    <span>Add Product</span></a></li>
            <li><a href="{{route('labels.show')}}"><i class="fa fas fa-barcode"></i> <span>Print Labels</span></a></li>
            <li><a href="{{route('variation-templates.index')}}"><i class="fa fas fa-circle"></i>
                    <span>Variations</span></a></li>
            <li><a href="{{route('products.import')}}"><i class="fa fas fa-download"></i>
                    <span>Import Products</span></a></li>
            <li><a href="{{route('opening.stock.import')}}"><i class="fa fas fa-download"></i> <span>Import Opening Stock</span></a>
            </li>
            <li><a href="{{route('selling-price-group.index')}}"><i class="fa fas fa-circle"></i> <span>Selling Price Group</span></a>
            </li>
            <li><a href="{{route('units.index')}}"><i class="fa fas fa-balance-scale"></i> <span>Units</span></a></li>
            <li><a href="{{route('taxonomies.index',['type' => 'product'])}}"><i class="fa fas fa-tags"></i> <span>Categories</span></a>
            </li>
        </ul>
    </li>
    <li class="treeview" id="tour_step6">
        <a href="#">
            <i class="fa fas fa-arrow-circle-down"></i> <span>Supplier Purchases</span>
            <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                          </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="{{route('supplier.index')}}"><i class="fa fas fa-star"></i> <span>Lists Suppliers</span></a>
            </li>
            <li><a href="{{route('supplier-purchases.index')}}"><i class="fa fas fa-list"></i> <span>List Supplier Purchases</span></a>
            </li>
            <li><a href="{{route('supplier-purchases.create')}}"><i class="fa fas fa-plus-circle"></i> <span>Add Supplier Purchase</span></a>
            </li>
            <li><a href="{{route('purchase-return.index')}}"><i class="fa fas fa-undo"></i>
                    <span>List Purchase Return</span></a></li>
        </ul>
    </li>
    <li class="treeview" id="tour_step6">
        <a href="#">
            <i class="fa fas fa-arrow-circle-down"></i> <span>Purchases</span>
            <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="{{route('purchases.index')}}"><i class="fa fas fa-list"></i> <span>List Purchases</span></a>
            </li>
            <li><a href="{{route('purchases.create')}}"><i class="fa fas fa-plus-circle"></i> <span>Add Purchase</span></a>
            </li>
            <!-- <li><a href="https://rcgerp.com/purchase-return"><i class="fa fas fa-undo"></i> <span>List Purchase Return</span></a></li> -->

        </ul>
    </li>
    <li class="treeview" id="tour_step7">
        <a href="#">
            <i class="fa fas fa-arrow-circle-up"></i> <span>Sell</span>
            <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="{{route('sells.index')}}"><i class="fa fas fa-list"></i> <span>All sales</span></a></li>
            <li><a href="{{route('sells.create')}}"><i class="fa fas fa-plus-circle"></i> <span>Add Sale</span></a></li>
            <li><a href="{{route('pos.index')}}"><i class="fa fas fa-list"></i> <span>List POS</span></a></li>
            <li><a href="{{route('pos.create')}}"><i class="fa fas fa-plus-circle"></i> <span>POS</span></a></li>
            <li><a href="{{route('sells.create',['status' => 'draft'])}}"><i class="fa fas fa-plus-circle"></i> <span>Add Draft</span></a>
            </li>
            <li><a href="{{route('sells.drafts')}}"><i class="fa fas fa-pen-square"></i> <span>List Drafts</span></a>
            </li>
            <li><a href="{{route('sells.create',['status' => 'quotation'])}}"><i class="fa fas fa-plus-circle"></i>
                    <span>Add Quotation</span></a></li>
            <li><a href="{{route('sells.quotations')}}"><i class="fa fas fa-pen-square"></i>
                    <span>List quotations</span></a></li>
            <li><a href="{{route('sell-return.index')}}"><i class="fa fas fa-undo"></i>
                    <span>List Sell Return</span></a></li>
            <li><a href="{{route('shipments')}}"><i class="fa fas fa-truck"></i> <span>Shipments</span></a></li>
            <li><a href="{{route('discount.index')}}"><i class="fa fas fa-percent"></i> <span>Discounts</span></a></li>
            <li><a href="{{route('sales.import')}}"><i class="fa fas fa-file-import"></i> <span>Import Sales</span></a>
            </li>

        </ul>
    </li>
    <li class="treeview" id="tour_step6">
        <a href="#">
            <i class="fa fas fa-truck"></i> <span>Drivers</span>
            <span class="pull-right-container">
                              <i class="fa fa-angle-left pull-right"></i>
                            </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="{{route('driver.index')}}"><i class="fa fas fa-star"></i> <span>Lists Drivers</span></a></li>
        </ul>
    </li>
    </li>
    <li class="treeview">
        <a href="#">
            <i class="fa fas fa-truck"></i> <span>Stock Transfers</span>
            <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="{{route('stock-transfers.index')}}"><i class="fa fas fa-list"></i>
                    <span>List Stock Transfers</span></a></li>
            <li><a href="{{route('stock-transfers.create')}}"><i class="fa fas fa-plus-circle"></i> <span>Add Stock Transfer</span></a>
            </li>

        </ul>
    </li>
    <li class="treeview">
        <a href="#">
            <i class="fa fas fa-database"></i> <span>Stock Adjustment</span>
            <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="{{route('stock-transfers.index')}}"><i class="fa fas fa-list"></i>
                    <span>List Stock Adjustments</span></a></li>
            <li><a href="{{route('stock-adjustments.create')}}"><i class="fa fas fa-plus-circle"></i> <span>Add Stock Adjustment</span></a>
            </li>

        </ul>
    </li>
    <li class="treeview">
        <a href="#">
            <i class="fa fas fa-minus-circle"></i> <span>Expenses</span>
            <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="{{route('expenses.index')}}"><i class="fa fas fa-list"></i> <span>List Expenses</span></a></li>
            <li><a href="{{route('expenses.create')}}"><i class="fa fas fa-plus-circle"></i>
                    <span>Add Expense</span></a></li>
            <li><a href="{{route('expense-categories.index')}}"><i class="fa fas fa-circle"></i> <span>Expense Categories</span></a>
            </li>

        </ul>
    </li>
    <li class="treeview" id="tour_step8">
        <a href="#">
            <i class="fa fas fa-chart-bar"></i> <span>Reports</span>
            <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="https://rcgerp.com/reports/profit-loss"><i class="fa fas fa-file-invoice-dollar"></i> <span>Profit / Loss Report</span></a>
            </li>
            <li><a href="https://rcgerp.com/reports/product-purchase-report"><i class="fa fas fa-arrow-circle-down"></i>
                    <span>Product Purchase Report</span></a></li>
            <li><a href="https://rcgerp.com/reports/sales-representative-report"><i class="fa fas fa-user"></i> <span>Sales Representative Report</span></a>
            </li>
            <li><a href="https://rcgerp.com/reports/register-report"><i class="fa fas fa-briefcase"></i> <span>Register Report</span></a>
            </li>
            <li><a href="https://rcgerp.com/reports/expense-report"><i class="fa fas fa-search-minus"></i> <span>Expense Report</span></a>
            </li>
            <li><a href="https://rcgerp.com/reports/sell-payment-report"><i class="fa fas fa-search-dollar"></i> <span>Sell Payment Report</span></a>
            </li>
            <li><a href="https://rcgerp.com/reports/purchase-payment-report"><i class="fa fas fa-search-dollar"></i>
                    <span>Purchase Payment Report</span></a></li>
            <li><a href="https://rcgerp.com/reports/product-sell-report"><i class="fa fas fa-arrow-circle-up"></i>
                    <span>Product Sell Report</span></a></li>
            <li><a href="https://rcgerp.com/reports/items-report"><i class="fa fas fa-tasks"></i>
                    <span>Items Report</span></a></li>
            <li><a href="https://rcgerp.com/reports/purchase-sell"><i class="fa fas fa-exchange-alt"></i> <span>Purchase &amp; Sale</span></a>
            </li>
            <li><a href="https://rcgerp.com/reports/trending-products"><i class="fa fas fa-chart-line"></i> <span>Trending Products</span></a>
            </li>
            <li><a href="https://rcgerp.com/reports/stock-adjustment-report"><i class="fa fas fa-sliders-h"></i> <span>Stock Adjustment Report</span></a>
            </li>
            <li><a href="https://rcgerp.com/reports/stock-report"><i class="fa fas fa-hourglass-half"></i> <span>Stock Report</span></a>
            </li>
            <li><a href="https://rcgerp.com/reports/customer-group"><i class="fa fas fa-users"></i> <span>Customer Groups Report</span></a>
            </li>
            <li><a href="https://rcgerp.com/reports/customer-supplier"><i class="fa fas fa-address-book"></i> <span>Supplier &amp; Customer Report</span></a>
            </li>
            <li><a href="https://rcgerp.com/reports/tax-report"><i class="fa fas fa-percent"></i>
                    <span>Tax Report</span></a></li>
            <li><a href="https://rcgerp.com/reports/activity-log"><i class="fa fas fa-user-secret"></i> <span>Activity Log</span></a>
            </li>

        </ul>
    </li>
    <li><a href="{{route('notification-templates.index')}}"><i class="fa fas fa-envelope"></i> <span>Notification Templates</span></a>
    </li>
    <li class="treeview" id="tour_step3">
        <a href="#">
            <i class="fa fas fa-cog"></i> <span>Settings</span>
            <span class="pull-right-container">
                          <i class="fa fa-angle-left pull-right"></i>
                        </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="{{route('business.getBusinessSettings')}}" id="tour_step2"><i class="fa fas fa-cogs"></i>
                    <span>Business Settings</span></a></li>
            <li><a href="{{route('api-setting.index')}}"><i class="fa fas fa-map-marker"></i>
                    <span>Api Setting</span></a></li>
            <li><a href="{{route('business-location.index')}}"><i class="fa fas fa-map-marker"></i> <span>Business Locations</span></a>
            </li>
            <li><a href="{{route('kitchen-location.index')}}"><i class="fa fas fa-plus-circle"></i> <span>Kitchen Location</span></a>
            </li>
            <li><a href="{{route('invoice-schemes.index')}}"><i class="fa fas fa-file"></i>
                    <span>Invoice Settings</span></a></li>
            <li><a href="{{route('barcodes.index')}}"><i class="fa fas fa-barcode"></i>
                    <span>Barcode Settings</span></a></li>
            <li><a href="{{route('printers.index')}}"><i class="fa fas fa-share-alt"></i> <span>Receipt Printers</span></a>
            </li>
            <li><a href="{{route('tax-rates.index')}}"><i class="fa fas fa-bolt"></i> <span>Tax Rates</span></a></li>

        </ul>
    </li>

</ul>
