<?php

namespace App\Utils;

use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Utils\SupplierTransactionUtil;
use App\Models\Transaction;
use DB;

class SupplierUtil extends Util
{

    /**
     * Returns the supplier info
     *
     * @param int $business_id
     * @param int $supplier_id
     *
     * @return array
     */
    public function getSupplierInfo($business_id, $supplier_id)
    {
        $supplier = Supplier::where('supplier.id', $supplier_id)
                    ->where('supplier.business_id', $business_id)
                    ->leftjoin('supplier_transactions AS t', 'supplier.id', '=', 't.supplier_id')
                    ->with(['business'])
                    ->select(
                        DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                        DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                        DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM supplier_transaction_payments WHERE supplier_transaction_payments.supplier_transaction_id=t.id), 0)) as purchase_paid"),
                        DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM supplier_transaction_payments WHERE supplier_transaction_payments.supplier_transaction_id=t.id), 0)) as invoice_received"),
                        DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                        DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(amount) FROM supplier_transaction_payments WHERE supplier_transaction_payments.supplier_transaction_id=t.id), 0)) as opening_balance_paid"),
                        'supplier.*'
                    )->first();

        return $supplier;
    }

    public function createNewSupplier($input)
    {
        //Check Supplier Id
        $count = 0;
        if (!empty($input['contact_id'])) {
            $count = Supplier::where('business_id', $input['business_id'])
                            ->where('supplier_id', $input['supplier_id'])
                            ->count();
        }
        if ($count == 0) {
            //Update reference count
            $ref_count = $this->setAndGetReferenceCount('supplier', $input['business_id']);

            if (empty($input['supplier_id'])) {
                //Generate reference number
                $input['supplier_id'] = $this->generateReferenceNumber('supplier', $ref_count, $input['business_id']);
            }
            
            $opening_balance = isset($input['opening_balance']) ? $input['opening_balance'] : 0;
            if (isset($input['opening_balance'])) {
                unset($input['opening_balance']);
            }

            $supplier = Supplier::create($input);
         
            // Add opening balance
            if (!empty($opening_balance)) {
                $transactionUtil = new SupplierTransactionUtil();
                $transactionUtil->createOpeningBalanceTransaction($supplier->business_id, $supplier->id, $opening_balance, $supplier->created_by, false);
            }

            $output = ['success' => true,
                        'data' => $supplier,
                        'msg' => __("supplier.added_success")
                    ];
            return $output;
        } else {
            throw new \Exception("Error Processing Request", 1);
        }
    }

    public function updateSupplier($input, $id, $business_id)
    {
        $count = 0;
        //Check Supplier Id
        if (!empty($input['supplier_id'])) {
            $count = Supplier::where('business_id', $business_id)
                    ->where('supplier_id', $input['supplier_id'])
                    ->where('id', '!=', $id)
                    ->count();
        }

        if ($count == 0) {
            //Get opening balance if exists
            $ob_transaction =  SupplierTransaction::where('supplier_id', $id)
                                    ->where('type', 'opening_balance')
                                    ->first();
            $opening_balance = isset($input['opening_balance']) ? $input['opening_balance'] : 0;

            if (isset($input['opening_balance'])) {
                unset($input['opening_balance']);
            }

            $supplier = Supplier::where('business_id', $business_id)->findOrFail($id);
            foreach ($input as $key => $value) {
                $supplier->$key = $value;
            }
            $supplier->save();

            $transactionUtil = new SupplierTransactionUtil();
            if (!empty($ob_transaction)) {
                $opening_balance_paid = $transactionUtil->getTotalAmountPaid($ob_transaction->id);
                if (!empty($opening_balance_paid)) {
                    $opening_balance += $opening_balance_paid;
                }

                $ob_transaction->final_total = $opening_balance;
                $ob_transaction->save();
                //Update opening balance payment status
                $transactionUtil->updatePaymentStatus($ob_transaction->id, $ob_transaction->final_total);
            } else {
                //Add opening balance
                if (!empty($opening_balance)) {
                    $transactionUtil->createOpeningBalanceTransaction($business_id, $supplier->id, $opening_balance, $supplier->created_by, false);
                }
            }

            $output = ['success' => true,
                        'msg' => __("supplier.updated_success"),
                        'data' => $supplier
                        ];
        } else {
            throw new \Exception("Error Processing Request", 1);
        }

        return $output;
    }

    public function getSupplierQuery($business_id)
    {   
        $query = Supplier::where('business_id','=',$business_id);
        $query = Supplier::leftjoin('supplier_transactions AS t', 'supplier.id', '=', 't.supplier_id')
                    ->where('supplier.business_id', $business_id);
 
        if (!empty($supplier_ids)) {
            $query->whereIn('supplier.id', $supplier_ids);
        }

        $query->select([
            'supplier.*',
            DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
            DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM supplier_transaction_payments WHERE supplier_transaction_payments.supplier_transaction_id=t.id), 0)) as opening_balance_paid"),
            DB::raw("MAX(DATE(transaction_date)) as max_transaction_date"),
            't.transaction_date'
        ]);

        
        $query->addSelect([
            DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
            DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM supplier_transaction_payments WHERE supplier_transaction_payments.supplier_transaction_id=t.id), 0)) as purchase_paid"),
            DB::raw("SUM(IF(t.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
            DB::raw("SUM(IF(t.type = 'purchase_return', (SELECT SUM(amount) FROM supplier_transaction_payments WHERE supplier_transaction_payments.supplier_transaction_id=t.id), 0)) as purchase_return_paid")
        ]);

        // if (in_array($type, ['customer', 'both'])) {
        //     $query->addSelect([
        //         DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
        //         DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
        //         DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
        //         DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid")
        //     ]);
        // }
        $query->groupBy('supplier.id');

        return $query;
    }
}
