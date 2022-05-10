<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterList;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;

class MasterListController extends Controller
{

    // public $orderRepository;

    // public function __construct(OrderRepository $orderRepository)
    // {
    //     $this->orderRepository = $orderRepository;
    // }

    public function createMasterList()
    {
        try {
            $transections = Transaction::with('contact', 'sell_lines', 'sell_lines.sell_lines_days')->has('sell_lines.sell_lines_days')->get();

            foreach ($transections as $key => $transaction) {
                foreach ($transaction->sell_lines as $key => $saleLine) {
                    foreach ($saleLine->sell_lines_days as $key => $saleLineDay) {
                        $delivery_date = $this->getDeliveryDate($saleLine, $saleLineDay);
                        MasterList::create(
                            [
                                'transaction_sell_lines_id' => $saleLineDay->transaction_sell_lines_id,
                                'transaction_id' => $transaction->id,
                                'contacts_id' => $transaction->contact->id,
                                'contacts_name' => $transaction->contact->name,
                                'shipping_address_line_1' => $transaction->shipping_address_line_1,
                                'shipping_address_line_2' => $transaction->shipping_address_line_2,
                                'shipping_city' => $transaction->shipping_city,
                                'shipping_state' => $transaction->shipping_state,
                                'shipping_country' => $transaction->shipping_country,
                                'shipping_zip_code' => $transaction->shipping_zip_code,
                                'additional_notes' => $transaction->additional_notes,
                                'delivery_note' => 'dummy',
                                'delivery_date' => $delivery_date,
                                'delivery_time' => 'dummy',
                                'shipping_phone' => 'dummy',
                                'status' => 1,
                                'staff_notes' => ($transaction->staff_note) ? $transaction->staff_note : 'dummy',
                                'created_by' => 1,
                        ]);
                    }
                }
            }
        } catch (Exception $e) {
            dd('Ex. - ', $e);
        }
    }

    public function getDeliveryDate($sellLine, $saleLineDay)
    {

        $deliveryDate = $sellLine->start_date;
        $delivery_day = Carbon::parse($deliveryDate)->format('N');
        $result = null;
        while(true) {

            if ($delivery_day === $saleLineDay->day) {
                $result = $deliveryDate;

                break;
            }
            $delivery_day = $delivery_day + 1;
            if ($delivery_day > 7) {
                $delivery_day = 1;
            }
            $deliveryDate = Carbon::parse($deliveryDate)->addDay();

        }
        return $result;
    }
}
