<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InputVoucher;
use App\Models\InputVoucherItem;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class InputVoucherExcelSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = public_path('inputVoucher.xlsx');
        $logPath = storage_path('logs/input_voucher_import.log');

        if (!file_exists($filePath)) {
            echo "❌ File not found: $filePath\n";
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        InputVoucherItem::truncate();
        InputVoucher::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Log::build(['driver' => 'single', 'path' => $logPath])
            ->info("🗑️ All previous input vouchers and items deleted.");
        Log::build(['driver' => 'single', 'path' => $logPath])
            ->info("==== Start import from inputVoucher.xlsx at " . now() . " ====");

        $spreadsheet = IOFactory::load($filePath);
        $allData = collect();

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $rows = $sheet->toArray(null, true, true, true);

            if (empty($rows)) {
                Log::warning("⚠️ Sheet '$sheetName' is empty. Skipping...");
                continue;
            }

            $firstRowKey = array_key_first($rows);
            $headerRow = $rows[$firstRowKey];

            if (!is_array($headerRow)) {
                Log::warning("⚠️ Sheet '$sheetName' has no valid header. Skipping...");
                continue;
            }

            $header = array_map('strtolower', array_map('trim', array_values($headerRow)));
            unset($rows[$firstRowKey]);

            $data = collect($rows)->map(function ($row) use ($header) {
                return array_combine($header, array_values($row));
            });

            $allData = $allData->merge($data);
        }

        $grouped = $allData->filter(function ($row) use ($logPath) {
            if (empty($row['date'])) {
                Log::build(['driver' => 'single', 'path' => $logPath])
                    ->warning("⚠️ Skipped row due to missing date: " . json_encode($row));
                return false;
            }
            if (empty($row['number'])) {
                $row['number'] = '0'; // Default to 0 if number is missing
            }
            try {
                $parsed = $this->convertDate($row['date'], false);
                return $parsed !== null;
            } catch (\Exception $e) {
                Log::build(['driver' => 'single', 'path' => $logPath])
                    ->warning("⚠️ Skipped row with invalid date format: " . $row['date']);
                return false;
            }
        })->groupBy(function ($row) {
            $number = trim($row['number'] ?? '0');
            $date = $this->convertDate($row['date'], false);
            $year = Carbon::parse($date)->format('Y');
            return $number . '_' . $year;
        });

        $success = 0;
        $failed = 0;

        foreach ($grouped as $groupKey => $entries) {
            [$number, $year] = explode('_', $groupKey);
            $firstRow = $entries->first();

            $stockName = trim($firstRow['stock'] ?? 'Default');
            $stock = Stock::firstOrCreate(['name' => $stockName]);

            $date = $this->convertDate($firstRow['date']);

            $voucher = InputVoucher::create([
                'number' => $number,
                'date' => $date,
                'notes' => $firstRow['notes'] ?? null,
                'stock_id' => $stock->id,
                'input_voucher_state_id' => ($firstRow['date_gov'] ?? '') === 'مستلم' ? 3 : 1,
                'date_receive' => $date,
                'date_bill' => $date,
                'user_create_id' => 1,
                'user_update_id' => 1,
            ]);

            foreach ($entries as $entry) {
                $itemName = trim($entry['name'] ?? '');
                $categoryName = trim($entry['category'] ?? 'Other');

                if (empty($itemName)) {
                    $failed++;
                    Log::build(['driver' => 'single', 'path' => $logPath])
                        ->warning("❌ Empty item name in voucher number $number-$year. Skipping...");
                    continue;
                }

                $category = ItemCategory::firstOrCreate(['name' => $categoryName]);

                $item = Item::firstOrCreate(
                    ['name' => $itemName],
                    [
                        'code' => $entry['code'] ?? null,
                        'item_category_id' => $category->id,
                        'measuring_unit' => 'unit',
                        'user_create_id' => 1,
                        'user_update_id' => 1,
                    ]
                );

                InputVoucherItem::create([
                    'input_voucher_id' => $voucher->id,
                    'item_id' => $item->id,
                    'count' => is_numeric($entry['count'] ?? null) ? $entry['count'] : 1,
                    'price' => is_numeric($entry['price'] ?? null) ? $entry['price'] * 100 : 0,
                    'value' => is_numeric($entry['total'] ?? null) ? $entry['total'] * 100 : 0,
                    'notes' => trim($entry['notes'] ?? null),
                ]);

                $success++;
            }
        }

        Log::info("✅ Finished: $success items imported, $failed failed.");
        echo "✅ Done. $success items imported, $failed failed.\n";
        echo "📄 Log file: $logPath\n";
    }

    private function convertDate($value, $returnNowIfInvalid = true): ?string
    {
        $formats = ['d-m-Y', 'd/m/Y'];
        $value = trim($value);

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        if ($returnNowIfInvalid) {
            Log::warning("❌ Invalid date: '$value'. Using today's date.");
            return now()->format('Y-m-d');
        }

        return null;
    }
}
