<?php

namespace App\Exports;

use App\Models\MasterList;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\BeforeExport;

class MasterListExport implements FromCollection, WithHeadings, WithMapping ,WithEvents , ShouldAutoSize , WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return MasterList::all();
    }


    public function headings(): array
    {
        $role = 'user';
        $masterListCols = config('masterlist.'.$role.'_columns');
        $colName = [];
        foreach ($masterListCols as $key => $value) {
            $colName[] = $value['name'];
        }
        return $colName;
    }

    public function map($transaction): array
    {
        $role = 'user';
        $masterListCols = config('masterlist.'.$role.'_columns');
        $colValue = [];
        foreach ($masterListCols as $key => $value) {
            if ($value['data'] === 'address') {
                $colValue[] = 'Address';
                continue;
            }
            if ($value['data'] === 'remark') {
                $colValue[] = 'remark';
                continue;
            }
            if ($value['data'] === 'hp_number') {
                $colValue[] = 'Hp Number';
                continue;
            }
            if ($value['data'] === 'driver_name') {
                $colValue[] = 'Driver Name';
                continue;
            }
            if ($value['data'] === 'pax') {
                $pax = [];
                if (isset($transaction->transaction_sell_lines->transactionSellLinesVariants)) {
                    foreach ($transaction->transaction_sell_lines->transactionSellLinesVariants as $value) {
                        if (str_contains($value->name, 'Serving Pax')) {
                            $pax[] = $value->value;
                        }
                    }

                }
                $colValue[] = implode(',', $pax);
                continue;
            }
            if ($value['data'] === 'postal') {
                $colValue[] = $transaction->shipping_zip_code;
                continue;
            }
            if ($value['data'] === 'addon') {
                $addon = [];
                if (isset($transaction->transaction_sell_lines->transactionSellLinesVariants)) {
                    foreach ($transaction->transaction_sell_lines->transactionSellLinesVariants as $value) {
                        if (str_contains($value->name, 'Add on')) {
                            $addon_pax = ($value->value  != 'None') ? '+'.$value->value : '';
                            $addon[] = str_replace("Add on:","",$value->name).''.$addon_pax;
                        }
                    }

                }
                $colValue[] = implode(',', $addon);
                continue;
            }

            $colValue[] = $transaction->{$value['data']};
        }
        return $colValue;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:Z1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ]
                ]);
            },

        ];
    }
    public function title(): string
    {
    	return 'Master List';
    }
}
