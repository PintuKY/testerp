<div class="modal-dialog modal-xl" role="document">
	<div class="modal-content">
		<div class="modal-header">
		    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      <h4 class="modal-title" id="modalTitle">{{$supplier_product->name}}</h4>
	    </div>
        <div class="modal-body">
			<div class="row">
				<div class="col-md-10 col-md-offset-1 col-xs-12">
				  <div class="table-responsive">
					<table class="table table-condensed bg-gray">
					  <tr>
						<th>Name</th>
						<th>Supplier</th>
						<th>Category</th>
						<th>Unit</th>
						<th>Price</th>
						<th>Description</th>
					  </tr>
					 
						  <tr>
							<td>{{$supplier_product->name}}</td>
							<td>
							  {{$supplier_product->supplier->name ?? $supplier_product->supplier->supplier_business_name }}
							</td>
							<td>
								{{$supplier_product->category->name}}
							</td>
							<td>
								{{$supplier_product->unit->name}}
							</td>
							<td>
								{{$supplier_product->purchase_price}}
							</td>
							<td>
								{{$supplier_product->description}}
							</td>
						  </tr>
					</table>
				  </div>
				</div>
			  </div>
        </div>
      	<div class="modal-footer">
      		<button type="button" class="btn btn-primary no-print"
	        aria-label="Print"
	          onclick="$(this).closest('div.modal').printThis();">
	        <i class="fa fa-print"></i> @lang( 'messages.print' )
	      </button>
	      	<button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
	    </div>
	</div>
</div>
