<?php

namespace App\Utils;

use App\Events\SupplierTransactionPaymentAdded;
use App\Events\SupplierTransactionPaymentDeleted;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ReferenceCount;
use App\Models\Supplier;
use App\Models\SupplierPurchaseLine;
use App\Models\SupplierTransaction;
use App\Models\SupplierTransactionPayments;
use App\Models\SupplierTransactionSellLine;
use App\Models\SupplierTransactionSellLinesPurchaseLines;
use App\Models\Variation;
use Carbon\Carbon;
use DB;

class SupplierTransactionUtil extends Util
{
   /**
     * Creates a new opening balance transaction for a supplier
     *
     * @param  int $business_id
     * @param  int $supplier_id
     * @param  int $amount
     *
     * @return void
     */
    public function createOpeningBalanceTransaction($business_id, $supplier_id, $amount, $created_by, $uf_data = true)
    {
        $business_location = BusinessLocation::where('business_id', $business_id)->first();
        $final_amount = $uf_data ? $this->num_uf($amount) : $amount;
        $ob_data = [
                    'business_id' => $business_id,
                    'location_id' => $business_location->id,
                    'type' => 'opening_balance',
                    'status' => 'final',
                    'payment_status' => 'due',
                    'supplier_id' => $supplier_id,
                    'transaction_date' => Carbon::now(),
                    'total_before_tax' => $final_amount,
                    'final_total' => $final_amount,
                    'created_by' => $created_by
                ];
        //Update reference count
        $ob_ref_count = $this->setAndGetReferenceCount('opening_balance', $business_id);
        //Generate reference number
        $ob_data['ref_no'] = $this->generateReferenceNumber('opening_balance', $ob_ref_count, $business_id);
        //Create opening balance transaction
        SupplierTransaction::create($ob_data);
    }

    /**
     * Increments reference count for a given type and given business
     * and gives the updated reference count
     *
     * @param string $type
     * @param int $business_id
     *
     * @return int
    */

    public function setAndGetReferenceCount($type, $business_id = null)
    {
        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }

        $ref = ReferenceCount::where('ref_type', $type)
                          ->where('business_id', $business_id)
                          ->first();
        if (!empty($ref)) {
            $ref->ref_count += 1;
            $ref->save();
            return $ref->ref_count;
        } else {
            $new_ref = ReferenceCount::create([
                'ref_type' => $type,
                'business_id' => $business_id,
                'ref_count' => 1
            ]);
            return $new_ref->ref_count;
        }
    }

    /**
     * Generates reference number
     *
     * @param string $type
     * @param int $business_id
     *
     * @return int
    */
    public function generateReferenceNumber($type, $ref_count, $business_id = null, $default_prefix = null)
    {
        $prefix = '';

        if (session()->has('business') && !empty(request()->session()->get('business.ref_no_prefixes')[$type])) {
            $prefix = request()->session()->get('business.ref_no_prefixes')[$type];
        }
        if (!empty($business_id)) {
            $business = Business::find($business_id);
            $prefixes = $business->ref_no_prefixes;
            $prefix = !empty($prefixes[$type]) ? $prefixes[$type] : '';
        }

        if (!empty($default_prefix)) {
            $prefix = $default_prefix;
        }

        $ref_digits =  str_pad($ref_count, 4, 0, STR_PAD_LEFT);

        if (!in_array($type, ['contacts', 'business_location', 'username'])) {
            $ref_year = Carbon::now()->year;
            $ref_number = $prefix . $ref_year . '/' . $ref_digits;
        } else {
            $ref_number = $prefix . $ref_digits;
        }

        return $ref_number;
    }

    public function getTotalAmountPaid($supplier_transaction_id)
    {
        $paid = SupplierTransactionPayments::where('supplier_transaction_id',$supplier_transaction_id)->sum('amount');
        return $paid;
    }

    public function updatePaymentStatus($supplier_transaction_id, $final_amount = null)
    {
        $status = $this->calculatePaymentStatus($supplier_transaction_id, $final_amount);
        SupplierTransaction::where('id', $supplier_transaction_id)
            ->update(['payment_status' => $status]);
        return $status;
    }

    public function calculatePaymentStatus($transaction_id, $final_amount = null)
    {
        $total_paid = $this->getTotalPaid($transaction_id);

        if (is_null($final_amount)) {
            $final_amount = SupplierTransaction::find($transaction_id)->final_total;
        }

        $status = 'due';
        if ($final_amount <= $total_paid) {
            $status = 'paid';
        } elseif ($total_paid > 0 && $final_amount > $total_paid) {
            $status = 'partial';
        }

        return $status;
    }

    public function getTotalPaid($transaction_id)
    {
        $total_paid = SupplierTransactionPayments::where('supplier_transaction_id', $transaction_id)
                ->select(DB::raw('SUM(IF( is_return = 0, amount, amount*-1))as total_paid'))
                ->first()
                ->total_paid;

        return $total_paid;
    }

    public function getLedgerDetails($supplier_id, $start, $end)
    {
        $business_id = request()->session()->get('user.business_id');
        //Get sum of totals before start date
        $previous_transaction_sums = $this->__transactionQuery($supplier_id, $start)
                ->select(
                    DB::raw("SUM(IF(type = 'purchase', final_total, 0)) as total_purchase"),
                    DB::raw("SUM(IF(type = 'sell' AND status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF(type = 'sell_return', final_total, 0)) as total_sell_return"),
                    DB::raw("SUM(IF(type = 'purchase_return', final_total, 0)) as total_purchase_return"),
                    DB::raw("SUM(IF(type = 'opening_balance', final_total, 0)) as total_opening_balance")
                )->first();

        //Get payment totals before start date
        $prev_payments = $this->__paymentQuery($supplier_id, $start)
                            ->select('supplier_transaction_payments.*', 'bl.name as location_name', 't.type as supplier_transaction_type', 'is_advance')
                                    ->get();

        $prev_total_invoice_paid = $prev_payments->where('supplier_transaction_type', 'sell')->where('is_return', 0)->sum('amount');
        $prev_total_ob_paid = $prev_payments->where('supplier_transaction_type', 'opening_balance')->where('is_return', 0)->sum('amount');
        $prev_total_sell_change_return = $prev_payments->where('supplier_transaction_type', 'sell')->where('is_return', 1)->sum('amount');
        $prev_total_sell_change_return = !empty($prev_total_sell_change_return) ? $prev_total_sell_change_return : 0;
        $prev_total_invoice_paid -= $prev_total_sell_change_return;
        $prev_total_purchase_paid = $prev_payments->where('supplier_transaction_type', 'purchase')->where('is_return', 0)->sum('amount');
        $prev_total_sell_return_paid = $prev_payments->where('supplier_transaction_type', 'sell_return')->sum('amount');
        $prev_total_purchase_return_paid = $prev_payments->where('supplier_transaction_type', 'purchase_return')->sum('amount');
        $prev_total_advance_payment = $prev_payments->where('is_advance', 1)->sum('amount');

        $total_prev_paid = $prev_total_invoice_paid + $prev_total_purchase_paid - $prev_total_sell_return_paid - $prev_total_purchase_return_paid + $prev_total_ob_paid + $prev_total_advance_payment;

        $total_prev_invoice = $previous_transaction_sums->total_purchase + $previous_transaction_sums->total_invoice -  $previous_transaction_sums->total_sell_return -  $previous_transaction_sums->total_purchase_return + $previous_transaction_sums->total_opening_balance;
        //$total_prev_paid = $prev_payments_sum->total_paid;
        $beginning_balance = $total_prev_invoice - $total_prev_paid;

        $contact = Supplier::find($supplier_id);

        //Get transaction totals between dates
        $transactions = $this->__transactionQuery($supplier_id, $start, $end)
                            ->with(['location'])->get();
        $transaction_types = SupplierTransaction::supplierTransactionTypes();
        $ledger = [];

        $opening_balance = 0;
        $opening_balance_paid = 0;

        foreach ($transactions as $transaction) {

            if($transaction->type == 'opening_balance'){
                //Skip opening balance, it will be added in the end
                $opening_balance += $transaction->final_total;

                continue;
            }

            $ledger[] = [
                'date' => $transaction->transaction_date,
                'ref_no' => in_array($transaction->type, ['sell', 'sell_return']) ? $transaction->invoice_no : $transaction->ref_no,
                'type' => $transaction_types[$transaction->type],
                'location' => $transaction->location->name,
                'payment_status' =>  __('lang_v1.' . $transaction->payment_status),
                'total' => '',
                'payment_method' => '',
                'debit' => in_array($transaction->type, ['sell', 'purchase_return'])  ? $transaction->final_total : '',
                'credit' => in_array($transaction->type, ['purchase', 'sell_return']) ? $transaction->final_total : '',
                'others' => $transaction->additional_notes
            ];
        }

        $invoice_sum = $transactions->where('type', 'sell')->sum('final_total');
        $purchase_sum = $transactions->where('type', 'purchase')->sum('final_total');
        $sell_return_sum = $transactions->where('type', 'sell_return')->sum('final_total');
        $purchase_return_sum = $transactions->where('type', 'purchase_return')->sum('final_total');

        //Get payment totals between dates
        $payments = $this->__paymentQuery($supplier_id, $start, $end)
                        ->select('supplier_transaction_payments.*', 'bl.name as location_name', 't.type as supplier_transaction_type', 't.ref_no', 't.invoice_no')
                        ->get();

        $paymentTypes = $this->payment_types(null, true, $business_id);

        foreach ($payments as $payment) {

            if($payment->transaction_type == 'opening_balance'){
                $opening_balance_paid += $payment->amount;
            }

            //Hide all the adjusted payments because it has already been summed as advance payment
            if (!empty($payment->parent_id)) {
                continue;
            }

            $ref_no = in_array($payment->transaction_type, ['sell', 'sell_return']) ?  $payment->invoice_no :  $payment->ref_no;
            $note = $payment->note;
            if (!empty($ref_no)) {
                $note .='<small>' . __('account.payment_for') . ': ' . $ref_no . '</small>';
            }

            if ($payment->is_advance == 1) {
                $note .='<small>' . __('lang_v1.advance_payment') . '</small>';
            }

            if ($payment->is_return == 1) {
                $note .='<small>(' . __('lang_v1.change_return') . ')</small>';
            }

            $ledger[] = [
                'date' => $payment->paid_on,
                'ref_no' => $payment->payment_ref_no,
                'type' => $transaction_types['payment'],
                'location' => $payment->location_name,
                'payment_status' => '',
                'total' => '',
                'payment_method' => !empty($paymentTypes[$payment->method]) ? $paymentTypes[$payment->method] : '',
                'payment_method_key' => $payment->method,
                'debit' => in_array($payment->transaction_type, ['purchase', 'sell_return']) || ($payment->is_advance == 1 && $contact->type == 'supplier') ? $payment->amount : '',
                'credit' => (in_array($payment->transaction_type, ['sell', 'purchase_return', 'opening_balance']) || ($payment->is_advance == 1 && in_array($contact->type, ['customer', 'both']))) && $payment->is_return == 0 ? $payment->amount : '',
                'others' =>  $note
            ];
        }

        $total_invoice_paid = $payments->where('supplier_transaction_type', 'sell')->where('is_return', 0)->sum('amount');
        $total_sell_change_return = $payments->where('supplier_transaction_type', 'sell')->where('is_return', 1)->sum('amount');
        $total_sell_change_return = !empty($total_sell_change_return) ? $total_sell_change_return : 0;
        $total_invoice_paid -= $total_sell_change_return;
        $total_purchase_paid = $payments->where('supplier_transaction_type', 'purchase')->where('is_return', 0)->sum('amount');
        $total_sell_return_paid = $payments->where('supplier_transaction_type', 'sell_return')->sum('amount');
        $total_purchase_return_paid = $payments->where('supplier_transaction_type', 'purchase_return')->sum('amount');

        $total_invoice_paid += $opening_balance_paid;

        $start_date = $this->format_date($start);
        $end_date = $this->format_date($end);

        $total_invoice = $invoice_sum - $sell_return_sum;
        $total_purchase = $purchase_sum - $purchase_return_sum;

        $opening_balance_due = $opening_balance;

        $total_paid = $total_invoice_paid + $total_purchase_paid - $total_sell_return_paid - $total_purchase_return_paid;
        $curr_due = $total_invoice + $total_purchase - $total_paid + $beginning_balance + $opening_balance_due;

        //Sort by date
        if (!empty($ledger)) {
            usort($ledger, function ($a, $b) {
                $t1 = strtotime($a['date']);
                $t2 = strtotime($b['date']);
                return $t1 - $t2;
            });
        }

        $total_opening_bal = $beginning_balance + $opening_balance_due;
        //Add Beginning balance & openining balance to ledger
        $ledger = array_merge([[
            'date' => $start,
            'ref_no' => '',
            'type' => __('lang_v1.opening_balance'),
            'location' => '',
            'payment_status' => '',
            'total' => '',
            'payment_method' => '',
            'debit' => $total_opening_bal ? abs($total_opening_bal) : '',
            'credit' => $total_opening_bal ? abs($total_opening_bal) : '',
            'others' => ''
        ]], $ledger) ;

        $bal = 0;
        foreach($ledger as $key => $val) {
            $credit = !empty($val['credit']) ? $val['credit'] : 0;
            $debit = !empty($val['debit']) ? $val['debit'] : 0;

            if (!empty($val['payment_method_key']) && $val['payment_method_key'] == 'advance') {
                $credit = 0;
                $debit = 0;
            }
            $bal += ($credit - $debit);
            $balance = $this->num_f(abs($bal));

            if ($bal < 0) {
                $balance .= ' ' . __('lang_v1.dr');
            } else if ($bal > 0) {
                $balance .= ' ' . __('lang_v1.cr');
            }

            $ledger[$key]['balance'] = $balance;
        }

        $output = [
            'ledger' => $ledger,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_invoice' => $total_invoice,
            'total_purchase' => $total_purchase,
            'beginning_balance' => $beginning_balance + $opening_balance_due,
            'balance_due' => $curr_due,
            'total_paid' => $total_paid
        ];

        return $output;
    }

    /**
     * Query to get transaction totals for a customer
     *
     */
    private function __transactionQuery($supplier_id, $start, $end = null)
    {
        $business_id = request()->session()->get('user.business_id');
        $transaction_type_keys = array_keys(SupplierTransaction::supplierTransactionTypes());

        $query = SupplierTransaction::where('supplier_transactions.supplier_id', $supplier_id)
                        ->where('supplier_transactions.business_id', $business_id)
                        ->where('status', '!=', AppConstant::PAYMENT_PENDING)
                        ->whereIn('type', $transaction_type_keys);

        if (!empty($start)  && !empty($end)) {
            $query->whereDate(
                'supplier_transactions.transaction_date',
                '>=',
                $start
            )
                ->whereDate('supplier_transactions.transaction_date', '<=', $end)->get();
        }

        if (!empty($start)  && empty($end)) {
            $query->whereDate('supplier_transactions.transaction_date', '<', $start);
        }

        return $query;
    }

    /**
     * Query to get payment details for a customer
     *
     */
    private function __paymentQuery($supplier_id, $start, $end = null)
    {
        $business_id = request()->session()->get('user.business_id');

        $query = SupplierTransactionPayments::leftJoin(
            'supplier_transactions as t',
            'supplier_transaction_payments.supplier_transaction_id',
            '=',
            't.id'
        )
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('supplier_transaction_payments.payment_for', $supplier_id);
            //->whereNotNull('transaction_payments.transaction_id');
            //->whereNull('transaction_payments.parent_id');

        if (!empty($start)  && !empty($end)) {
            $query->whereDate('paid_on', '>=', $start)
                        ->whereDate('paid_on', '<=', $end);
        }

        if (!empty($start)  && empty($end)) {
            $query->whereDate('paid_on', '<', $start);
        }

        return $query;
    }

    public function getListPurchases($business_id)
    {
        $supplierPurchases = SupplierTransaction::leftJoin('supplier', 'supplier_transactions.supplier_id', '=', 'supplier.id')
                    ->join(
                        'kitchens_locations',
                        'supplier_transactions.location_id',
                        '=',
                        'kitchens_locations.id'
                    )
                    ->leftJoin(
                        'supplier_transaction_payments AS TP',
                        'supplier_transactions.id',
                        '=',
                        'TP.supplier_transaction_id'
                    )
                    ->leftJoin(
                        'supplier_transactions AS PR',
                        'supplier_transactions.id',
                        '=',
                        'PR.return_parent_id'
                    )
                    ->leftJoin('users as u', 'supplier_transactions.created_by', '=', 'u.id')
                    ->where('supplier_transactions.business_id', $business_id)
                    ->where('supplier_transactions.type', 'purchase')
                    ->select(
                        'supplier_transactions.id',
                        'supplier_transactions.document',
                        'supplier_transactions.transaction_date',
                        'supplier_transactions.ref_no',
                        'supplier.name',
                        'supplier.supplier_business_name',
                        'supplier_transactions.status',
                        'supplier_transactions.payment_status',
                        'supplier_transactions.final_total',
                        'kitchens_locations.name as location_name',
                        'supplier_transactions.pay_term_number',
                        'supplier_transactions.pay_term_type',
                        'PR.id as return_supplier_transaction_id',
                        DB::raw('SUM(TP.amount) as amount_paid'),
                        DB::raw('(SELECT SUM(TP2.amount) FROM supplier_transaction_payments AS TP2 WHERE TP2.supplier_transaction_id=PR.id ) as return_paid'),
                        DB::raw('COUNT(PR.id) as return_exists'),
                        DB::raw('COALESCE(PR.final_total, 0) as amount_return'),
                        DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
                    )
                    ->groupBy('supplier_transactions.id');

        return $supplierPurchases;
    }

    /**
     * Purchase currency details
     *
     * @param int $business_id
     *
     * @return object
     */

    public function purchaseCurrencyDetails($business_id)
    {
        $business = Business::find($business_id);
        $output = ['purchase_in_diff_currency' => false,
                    'p_exchange_rate' => 1,
                    'decimal_seperator' => '.',
                    'thousand_seperator' => ',',
                    'symbol' => '',
                ];

        //Check if diff currency is used or not.
        if ($business->purchase_in_diff_currency == 1) {
            $output['purchase_in_diff_currency'] = true;
            $output['p_exchange_rate'] = $business->p_exchange_rate;

            $currency_id = $business->purchase_currency_id;
        } else {
            $output['purchase_in_diff_currency'] = false;
            $output['p_exchange_rate'] = 1;
            $currency_id = $business->currency_id;
        }

        $currency = Currency::find($currency_id);
        $output['thousand_separator'] = $currency->thousand_separator;
        $output['decimal_separator'] = $currency->decimal_separator;
        $output['symbol'] = $currency->symbol;
        $output['code'] = $currency->code;
        $output['name'] = $currency->currency;

        return (object)$output;
    }

    public function updatePurchaseOrderStatus($purchase_order_ids = [])
    {
        foreach ($purchase_order_ids as $purchase_order_id) {
            $purchase_order = SupplierTransaction::with(['supplierPurchaseLines'])->find($purchase_order_id);

            if (empty($purchase_order)) {
                continue;
            }
            $total_ordered = $purchase_order->supplierPurchaseLines->sum('quantity');
            $total_received = $purchase_order->supplierPurchaseLines->sum('po_quantity_purchased');

            $status = $total_received == 0 ? 'ordered' : 'partial';
            if ($total_ordered == $total_received) {
                $status = 'completed';
            }
            $purchase_order->status = $status;
            $purchase_order->save();
        }
    }

    /**
     * Add supplier line for payment
     *
     * @param object/int $Supplier transaction
     * @param array $payments
     *
     * @return boolean
     */

    public function createOrUpdateSupplierPaymentLines($supplier_transaction, $payments, $business_id = null, $user_id = null, $uf_data = true)
    {
        $payments_formatted = [];
        $edit_ids = [0];
        $account_transactions = [];

        if (!is_object($supplier_transaction)) {
            $supplier_transaction = SupplierTransaction::findOrFail($supplier_transaction);
        }

        //If status is draft don't add payment
        if ($supplier_transaction->status == AppConstant::PAYMENT_PENDING) {
            return true;
        }
        $c = 0;
        $prefix_type = 'sell_payment';
        if ($supplier_transaction->type == 'purchase') {
            $prefix_type = 'purchase_payment';
        }
        $contact_balance = Supplier::where('id', $supplier_transaction->supplier_id)->value('balance');
        foreach ($payments as $payment) {
            //Check if transaction_sell_lines_id is set.
            if (!empty($payment['payment_id'])) {
                $edit_ids[] = $payment['payment_id'];
                $this->editPaymentLine($payment, $supplier_transaction, $uf_data);
            } else {
                $payment_amount = $uf_data ? $this->num_uf($payment['amount']) : $payment['amount'];
                if ($payment['method'] == 'advance' && $payment_amount > $contact_balance) {
                    throw new AdvanceBalanceNotAvailable(__('lang_v1.required_advance_balance_not_available'));
                }
                //If amount is 0 then skip.
                if ($payment_amount != 0) {
                    $prefix_type = 'sell_payment';
                    if ($supplier_transaction->type == 'purchase') {
                        $prefix_type = 'purchase_payment';
                    }
                    $ref_count = $this->setAndGetReferenceCount($prefix_type, $business_id);
                    //Generate reference number
                    $payment_ref_no = $this->generateReferenceNumber($prefix_type, $ref_count, $business_id);

                    //If change return then set account id same as the first payment line account id
                    if (isset($payment['is_return']) && $payment['is_return'] == 1) {
                        $payment['account_id'] = !empty($payments[0]['account_id']) ? $payments[0]['account_id'] : null;
                    }

                    if (!empty($payment['paid_on'])) {
                        $paid_on = $uf_data ? $this->uf_date($payment['paid_on'], true) : $payment['paid_on'];
                    } else {
                        $paid_on = Carbon::now()->toDateTimeString();
                    }

                    $payment_data = [
                        'amount' => $payment_amount,
                        'method' => $payment['method'],
                        'business_id' => $supplier_transaction->business_id,
                        'is_return' => isset($payment['is_return']) ? $payment['is_return'] : 0,
                        'card_transaction_number' => isset($payment['card_transaction_number']) ? $payment['card_transaction_number'] : null,
                        'card_number' => isset($payment['card_number']) ? $payment['card_number'] : null,
                        'card_type' => isset($payment['card_type']) ? $payment['card_type'] : null,
                        'card_holder_name' => isset($payment['card_holder_name']) ? $payment['card_holder_name'] : null,
                        'card_month' => isset($payment['card_month']) ? $payment['card_month'] : null,
                        'card_security' => isset($payment['card_security']) ? $payment['card_security'] : null,
                        'cheque_number' => isset($payment['cheque_number']) ? $payment['cheque_number'] : null,
                        'bank_account_number' => isset($payment['bank_account_number']) ? $payment['bank_account_number'] : null,
                        'note' => isset($payment['note']) ? $payment['note'] : null,
                        'paid_on' => $paid_on,
                        'created_by' => empty($user_id) ? auth()->user()->id : $user_id,
                        'payment_for' => $supplier_transaction->supplier_id,
                        'payment_ref_no' => $payment_ref_no,
                        'account_id' => !empty($payment['account_id']) && $payment['method'] != 'advance' ? $payment['account_id'] : null
                    ];

                    for ($i=1; $i<8; $i++) {
                        if ($payment['method'] == 'custom_pay_' . $i) {
                            $payment_data['transaction_no'] = $payment["transaction_no_{$i}"];
                        }
                    }

                    $payments_formatted[] = new SupplierTransactionPayments($payment_data);

                    $account_transactions[$c] = [];

                    //create account transaction
                    $payment_data['transaction_type'] = $supplier_transaction->type;
                    $account_transactions[$c] = $payment_data;

                    $c++;
                }
            }
        }

        //Delete the payment lines removed.
        if (!empty($edit_ids)) {
            $deleted_transaction_payments = $supplier_transaction->paymentLines()->whereNotIn('id', $edit_ids)->get();

            $supplier_transaction->paymentLines()->whereNotIn('id', $edit_ids)->delete();

            //Fire delete transaction payment event
            foreach ($deleted_transaction_payments as $deleted_transaction_payment) {
                event(new SupplierTransactionPaymentDeleted($deleted_transaction_payment));
            }
        }

        if (!empty($payments_formatted)) {
            $supplier_transaction->paymentLines()->saveMany($payments_formatted);

            foreach ($supplier_transaction->paymentLines as $key => $value) {
                if (!empty($account_transactions[$key])) {
                    event(new SupplierTransactionPaymentAdded($value, $account_transactions[$key]));
                }
            }
        }

        return true;
    }

    public function editPaymentLine($payment, $transaction = null, $uf_data = true)
    {
        $payment_id = $payment['payment_id'];
        unset($payment['payment_id']);

        for ($i=1; $i<8; $i++) {
            if ($payment['method'] == 'custom_pay_' . $i) {
                $payment['transaction_no'] = $payment["transaction_no_{$i}"];
            }
            unset($payment["transaction_no_{$i}"]);
        }

        if (!empty($payment['paid_on'])) {
            $payment['paid_on'] = $uf_data ? $this->uf_date($payment['paid_on'], true) : $payment['paid_on'];
        }

        $payment['amount'] = $uf_data ? $this->num_uf($payment['amount']) : $payment['amount'];

        $tp = SupplierTransactionPayments::where('id', $payment_id)
                            ->first();

        $transaction_type = !empty($transaction->type) ? $transaction->type : null;

        $tp->update($payment);

        //event
        event(new SupplierTransactionPaymentUpdated($tp, $transaction->type));

        return true;
    }

    /**
     * Check if transaction can be edited based on business     transaction_edit_days
     *
     * @param  int/object $transaction
     * @param  int $edit_duration
     *
     * @return boolean
     */

    public function canBeEdited($transaction, $edit_duration)
    {
        if (!is_object($transaction)) {
            $transaction = SupplierTransaction::find($transaction);
        }
        if (empty($transaction)) {
            return false;
        }

        $date = Carbon::parse($transaction->transaction_date)->addDays($edit_duration);

        $today = today();

        if ($date->gte($today)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if return exist for a particular purchase or sell
     * @param id $transacion_id
     *
     * @return boolean
     */
    public function isReturnExist($transacion_id)
    {
        return SupplierTransaction::where('return_parent_id', $transacion_id)->exists();
    }

    /**
     * Adjust the existing mapping between purchase & sell on edit of
     * purchase
     *
     * @param  string $before_status
     * @param  object $transaction
     * @param  object $delete_purchase_lines
     *
     * @return void
     */
    public function adjustMappingSupplierPurchaseSellAfterEditingSupplierPurchase($before_status, $transaction, $delete_purchase_lines)
    {
        if ($before_status == 'received' && $transaction->status == 'received') {
            //Check if there is some irregularities between purchase & sell and make appropiate adjustment.

            //Get all purchase line having irregularities.
            $purchase_lines = SupplierTransaction::join(
                'supplier_purchase_lines AS SPL',
                'supplier_transactions.id',
                '=',
                'SPL.supplier_transactions_id'
            )
                    ->join(
                        'supplier_transaction_sell_lines_purchase_lines AS STSPL',
                        'SPL.id',
                        '=',
                        'STSPL.purchase_line_id'
                    )
                    ->groupBy('STSPL.purchase_line_id')
                    ->where('supplier_transactions.id', $transaction->id)
                    ->havingRaw('SUM(STSPL.quantity) > MAX(SPL.quantity)')
                    ->select(['STSPL.purchase_line_id AS id',
                            DB::raw('SUM(STSPL.quantity) AS tspl_quantity'),
                            DB::raw('MAX(SPL.quantity) AS pl_quantity')
                        ])
                    ->get()
                    ->toArray();
        } elseif ($before_status == 'received' && $transaction->status != 'received') {
            //Delete sell for those & add new sell or throw error.
            $purchase_lines = SupplierTransaction::join(
                'supplier_purchase_lines AS SPL',
                'supplier_transactions.id',
                '=',
                'SPL.supplier_transactions_id'
            )
                    ->join(
                        'supplier_transaction_sell_lines_purchase_lines AS STSPL',
                        'SPL.id',
                        '=',
                        'STSPL.purchase_line_id'
                    )
                    ->groupBy('STSPL.purchase_line_id')
                    ->where('supplier_transactions.id', $transaction->id)
                    ->select(['STSPL.purchase_line_id AS id',
                        DB::raw('MAX(SPL.quantity) AS pl_quantity')
                    ])
                    ->get()
                    ->toArray();
        } else {
            return true;
        }

        //Get detail of purchase lines deleted
        if (!empty($delete_purchase_lines)) {
            $purchase_lines = $delete_purchase_lines->toArray() + $purchase_lines;
        }

        //All sell lines & Stock adjustment lines.
        $sell_lines = [];
        $stock_adjustment_lines = [];
        foreach ($purchase_lines as $purchase_line) {
            $tspl_quantity = isset($purchase_line['tspl_quantity']) ? $purchase_line['tspl_quantity'] : 0;
            $pl_quantity = isset($purchase_line['pl_quantity']) ? $purchase_line['pl_quantity'] : $purchase_line['quantity'];


            $extra_sold = abs($tspl_quantity - $pl_quantity);

            //Decrease the quantity from transaction_sell_lines_purchase_lines or delete it if zero
            $tspl = SupplierTransactionSellLinesPurchaseLines::where('purchase_line_id', $purchase_line['id'])
                ->leftjoin(
                    'supplier_transaction_sell_lines AS SL',
                    'supplier_transaction_sell_lines_purchase_lines.sell_line_id',
                    '=',
                    'SL.id'
                )
                ->leftjoin(
                    'supplier_stock_adjustment_lines AS SAL',
                    'supplier_transaction_sell_lines_purchase_lines.stock_adjustment_line_id',
                    '=',
                    'SAL.id'
                )
                ->orderBy('supplier_transaction_sell_lines_purchase_lines.id', 'desc')
                ->select(['SL.product_id AS sell_product_id',
                        'SL.variation_id AS sell_variation_id',
                        'SL.id AS sell_line_id',
                        'SAL.product_id AS adjust_product_id',
                        'SAL.variation_id AS adjust_variation_id',
                        'SAL.id AS adjust_line_id',
                        'supplier_transaction_sell_lines_purchase_lines.quantity',
                        'supplier_transaction_sell_lines_purchase_lines.purchase_line_id', 'supplier_transaction_sell_lines_purchase_lines.id as tslpl_id'])
                ->get();

            foreach ($tspl as $row) {
                if ($row->quantity <= $extra_sold) {
                    if (!empty($row->sell_line_id)) {
                        $sell_lines[] = (object)['id' => $row->sell_line_id,
                                'quantity' => $row->quantity,
                                'product_id' => $row->sell_product_id,
                                'variation_id' => $row->sell_variation_id,
                            ];
                        SupplierPurchaseLine::where('id', $row->purchase_line_id)
                            ->decrement('quantity_sold', $row->quantity);
                    } else {
                        $stock_adjustment_lines[] =
                            (object)['id' => $row->adjust_line_id,
                                'quantity' => $row->quantity,
                                'product_id' => $row->adjust_product_id,
                                'variation_id' => $row->adjust_variation_id,
                            ];
                        SupplierPurchaseLine::where('id', $row->purchase_line_id)
                            ->decrement('quantity_adjusted', $row->quantity);
                    }

                    $extra_sold = $extra_sold - $row->quantity;
                    SupplierTransactionSellLinesPurchaseLines::where('id', $row->tslpl_id)->delete();
                } else {
                    if (!empty($row->sell_line_id)) {
                        $sell_lines[] = (object)['id' => $row->sell_line_id,
                                'quantity' => $extra_sold,
                                'product_id' => $row->sell_product_id,
                                'variation_id' => $row->sell_variation_id,
                            ];
                        SupplierPurchaseLine::where('id', $row->purchase_line_id)
                            ->decrement('quantity_sold', $extra_sold);
                    } else {
                        $stock_adjustment_lines[] =
                            (object)['id' => $row->adjust_line_id,
                                'quantity' => $extra_sold,
                                'product_id' => $row->adjust_product_id,
                                'variation_id' => $row->adjust_variation_id,
                            ];

                        SupplierPurchaseLine::where('id', $row->purchase_line_id)
                            ->decrement('quantity_adjusted', $extra_sold);
                    }

                    SupplierTransactionSellLinesPurchaseLines::where('id', $row->tslpl_id)->update(['quantity' => $row->quantity - $extra_sold]);

                    $extra_sold = 0;
                }

                if ($extra_sold == 0) {
                    break;
                }
            }
        }

        $business = Business::find($transaction->business_id)->toArray();
        $business['location_id'] = $transaction->location_id;

        //Allocate the sold lines to purchases.
        if (!empty($sell_lines)) {
            $sell_lines = (object)$sell_lines;
            $this->mapPurchaseSell($business, $sell_lines, 'purchase');
        }

        //Allocate the stock adjustment lines to purchases.
        if (!empty($stock_adjustment_lines)) {
            $stock_adjustment_lines = (object)$stock_adjustment_lines;
            $this->mapPurchaseSell($business, $stock_adjustment_lines, 'stock_adjustment');
        }
    }

    /**
     * Add a mapping between purchase & sell lines.
     * NOTE: Don't use request variable here, request variable don't exist while adding
     * dummybusiness via command line
     *
     * @param array $business
     * @param array $transaction_lines
     * @param string $mapping_type = purchase (purchase or stock_adjustment)
     * @param boolean $check_expiry = true
     * @param int $purchase_line_id (default: null)
     *
     * @return object
     */
    public function mapPurchaseSell($business, $transaction_lines, $mapping_type = 'purchase', $check_expiry = true, $purchase_line_id = null)
    {
        if (empty($transaction_lines)) {
            return false;
        }

        if (!empty($business['pos_settings']) && !is_array($business['pos_settings'])) {
            $business['pos_settings'] = json_decode($business['pos_settings'], true);
        }
        $allow_overselling = !empty($business['pos_settings']['allow_overselling']) ?
                            true : false;

        //Set flag to check for expired items during SELLING only.
        $stop_selling_expired = false;
        if ($check_expiry) {
            if (session()->has('business') && request()->session()->get('business')['enable_product_expiry'] == 1 && request()->session()->get('business')['on_product_expiry'] == 'stop_selling') {
                if ($mapping_type == 'purchase') {
                    $stop_selling_expired = true;
                }
            }
        }

        $qty_selling = null;
        foreach ($transaction_lines as $line) {
            //Check if stock is not enabled then no need to assign purchase & sell
            $product = Product::find($line->product_id);
            if ($product->enable_stock != 1) {
                continue;
            }

            $qty_sum_query = $this->get_pl_quantity_sum_string('PL');

            //Get purchase lines, only for products with enable stock.
            $query = SupplierTransaction::join('supplier_purchase_lines AS SPL', 'supplier_transactions.id', '=', 'SPL.supplier_transactions_id')
                ->where('supplier_transactions.business_id', $business['id'])
                ->where('supplier_transactions.location_id', $business['location_id'])
                ->whereIn('supplier_transactions.type', ['purchase', 'purchase_transfer',
                    'opening_stock', 'production_purchase'])
                ->where('supplier_transactions.status', 'received')
                ->whereRaw("( $qty_sum_query ) < SPL.quantity")
                ->where('SPL.product_id', $line->product_id)
                ->where('SPL.variation_id', $line->variation_id);

            //If product expiry is enabled then check for on expiry conditions
            if ($stop_selling_expired && empty($purchase_line_id)) {
                $stop_before = request()->session()->get('business')['stop_selling_before'];
                $expiry_date = Carbon::today()->addDays($stop_before)->toDateString();
                $query->where( function($q) use($expiry_date) {
                    $q->whereNull('SPL.exp_date')
                        ->orWhereRaw('SPL.exp_date > ?', [$expiry_date]);
                });
            }

            //If lot number present consider only lot number purchase line
            if (!empty($line->lot_no_line_id)) {
                $query->where('SPL.id', $line->lot_no_line_id);
            }

            //If purchase_line_id is given consider only that purchase line
            if (!empty($purchase_line_id)) {
                $query->where('SPL.id', $purchase_line_id);
            }

            //Sort according to LIFO or FIFO
            if ($business['accounting_method'] == 'lifo') {
                $query = $query->orderBy('transaction_date', 'desc');
            } else {
                $query = $query->orderBy('transaction_date', 'asc');
            }

            $rows = $query->select(
                'SPL.id as purchase_lines_id',
                DB::raw("(SPL.quantity - ( $qty_sum_query )) AS quantity_available"),
                'SPL.quantity_sold as quantity_sold',
                'SPL.quantity_adjusted as quantity_adjusted',
                'SPL.quantity_returned as quantity_returned',
                'SPL.mfg_quantity_used as mfg_quantity_used',
                'supplier_transactions.invoice_no'
                    )->get();

            $purchase_sell_map = [];

            //Iterate over the rows, assign the purchase line to sell lines.
            $qty_selling = $line->quantity;
            foreach ($rows as $k => $row) {
                $qty_allocated = 0;

                //Check if qty_available is more or equal
                if ($qty_selling <= $row->quantity_available) {
                    $qty_allocated = $qty_selling;
                    $qty_selling = 0;
                } else {
                    $qty_selling = $qty_selling - $row->quantity_available;
                    $qty_allocated = $row->quantity_available;
                }

                //Check for sell mapping or stock adjsutment mapping
                if ($mapping_type == 'stock_adjustment') {
                    //Mapping of stock adjustment
                    if ($qty_allocated != 0) {
                        $purchase_adjustment_map[] =
                            ['stock_adjustment_line_id' => $line->id,
                                'purchase_line_id' => $row->purchase_lines_id,
                                'quantity' => $qty_allocated,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                        //Update purchase line
                        SupplierPurchaseLine::where('id', $row->purchase_lines_id)
                            ->update(['quantity_adjusted' => $row->quantity_adjusted + $qty_allocated]);
                    }
                } elseif ($mapping_type == 'purchase') {
                    //Mapping of purchase
                    if ($qty_allocated != 0) {
                        $purchase_sell_map[] = ['sell_line_id' => $line->id,
                                'purchase_line_id' => $row->purchase_lines_id,
                                'quantity' => $qty_allocated,
                                'created_at' =>  Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                        //Update purchase line
                        SupplierPurchaseLine::where('id', $row->purchase_lines_id)
                            ->update(['quantity_sold' => $row->quantity_sold + $qty_allocated]);
                    }
                } elseif ($mapping_type == 'production_purchase') {
                    //Mapping of purchase
                    if ($qty_allocated != 0) {
                        $purchase_sell_map[] = ['sell_line_id' => $line->id,
                                'purchase_line_id' => $row->purchase_lines_id,
                                'quantity' => $qty_allocated,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                        //Update purchase line
                        SupplierPurchaseLine::where('id', $row->purchase_lines_id)
                            ->update(['mfg_quantity_used' => $row->mfg_quantity_used + $qty_allocated]);
                    }
                }

                if ($qty_selling == 0) {
                    break;
                }
            }

            if (! ($qty_selling == 0 || is_null($qty_selling))) {
                //If overselling not allowed through exception else create mapping with blank purchase_line_id
                if (!$allow_overselling) {
                    $variation = Variation::find($line->variation_id);
                    $mismatch_name = $product->name;
                    if (!empty($variation->sub_sku)) {
                        $mismatch_name .= ' ' . 'SKU: ' . $variation->sub_sku;
                    }
                    if (!empty($qty_selling)) {
                        $mismatch_name .= ' ' . 'Quantity: ' . abs($qty_selling);
                    }

                    if ($mapping_type == 'purchase') {
                        $mismatch_error = trans(
                            "messages.purchase_sell_mismatch_exception",
                            ['product' => $mismatch_name]
                        );

                        if ($stop_selling_expired) {
                            $mismatch_error .= __('lang_v1.available_stock_expired');
                        }
                    } elseif ($mapping_type == 'stock_adjustment') {
                        $mismatch_error = trans(
                            "messages.purchase_stock_adjustment_mismatch_exception",
                            ['product' => $mismatch_name]
                        );
                    } else {
                        $mismatch_error = trans(
                            "lang_v1.quantity_mismatch_exception",
                            ['product' => $mismatch_name]
                        );
                    }

                    $business_name = optional(Business::find($business['id']))->name;
                    $location_name = optional(BusinessLocation::find($business['location_id']))->name;
                    \Log::emergency($mismatch_error . ' Business: ' . $business_name . ' Location: ' . $location_name);
                    throw new PurchaseSellMismatch($mismatch_error);
                } else {
                    //Mapping with no purchase line
                    $purchase_sell_map[] = ['sell_line_id' => $line->id,
                            'purchase_line_id' => 0,
                            'quantity' => $qty_selling,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ];
                }
            }

            //Insert the mapping
            if (!empty($purchase_adjustment_map)) {
                SupplierTransactionSellLinesPurchaseLines::insert($purchase_adjustment_map);
            }
            if (!empty($purchase_sell_map)) {
                SupplierTransactionSellLinesPurchaseLines::insert($purchase_sell_map);
            }
        }
    }

    /**
     * Check if lot number is used in any sell
     * @param obj $transaction
     *
     * @return boolean
     */
    public function isLotUsed($transaction)
    {
        foreach ($transaction->purchase_lines as $purchase_line) {
            $exists = SupplierTransactionSellLine::where('lot_no_line_id', $purchase_line->id)->exists();
            if ($exists) {
                return true;
            }
        }

        return false;
    }

    /**
     * Updates contact balance
     * @param obj $supplier
     * @param float $amount
     * @param string $type [add, deduct]
     *
     * @return obj $recurring_invoice
     */
    public function updateSupplierBalance($supplier, $amount, $type = 'add')
    {
        if (!is_object($supplier)) {
            $supplierData = Supplier::findOrFail($supplier);
        }

        if ($type == 'add') {
           $supplierData->balance += $amount;
        } elseif ($type == 'deduct') {
            $supplierData->balance -= $amount;
        }
        $supplierData->save();
    }

    public function sumGroupTaxDetails($group_tax_details)
    {
        $output = [];

        foreach ($group_tax_details as $group_tax_detail) {
            if (!isset($output[$group_tax_detail['name']])) {
                $output[$group_tax_detail['name']] = 0;
            }
            $output[$group_tax_detail['name']] += $group_tax_detail['calculated_tax'];
        }

        return $output;
    }

    public function getSupplierPurchaseProducts($business_id, $transaction_id)
    {
        $products = SupplierTransaction::join('supplier_purchase_lines as Spl', 'supplier_transactions.id', '=', 'Spl.supplier_transactions_id')
                            ->leftjoin('products as p', 'Spl.product_id', '=', 'p.id')
                            ->leftjoin('variations as v', 'Spl.variation_id', '=', 'v.id')
                            ->where('supplier_transactions.business_id', $business_id)
                            ->where('supplier_transactions.id', $transaction_id)
                            ->where('supplier_transactions.type', 'purchase')
                            ->select('p.id as product_id', 'p.name as product_name', 'v.id as variation_id', 'v.name as variation_name', 'Spl.quantity as quantity', 'Spl.exp_date', 'Spl.lot_number')
                            ->get();
        return $products;
    }
}
