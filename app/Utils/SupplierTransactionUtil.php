<?php

namespace App\Utils;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Currency;
use App\Models\ReferenceCount;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Models\SupplierTransactionPayments;
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
                        ->where('status', '!=', 'draft')
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
        $purchases = SupplierTransaction::leftJoin('supplier', 'supplier_transactions.supplier_id', '=', 'supplier.id')
                    ->join(
                        'business_locations AS BS',
                        'supplier_transactions.location_id',
                        '=',
                        'BS.id'
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
                        'BS.name as location_name',
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

        return $purchases;
    }

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
}
