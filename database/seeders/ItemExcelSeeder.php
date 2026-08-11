<?php 
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ItemExcelSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = public_path('items.xlsx');
        $logPath = storage_path('logs/item_excel_import.log');

        if (!file_exists($filePath)) {
            echo "❌ File not found: items.xlsx in public folder\n";
            return;
        }

        // تعطيل تحقق المفاتيح الخارجية مؤقتًا
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Item::truncate();
        ItemCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // إعداد ملف اللوق
        Log::build(['driver' => 'single', 'path' => $logPath])
            ->info("🚨 Previous items and categories deleted.");
        Log::build(['driver' => 'single', 'path' => $logPath])
            ->info("==== Starting Import at " . now() . " ====");

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $header = array_map('strtolower', $rows[0]);
        $success = 0;
        $failed = 0;

        // التحقق من تكرار الأسماء فقط
        $existingNames = Item::pluck('name')->toArray();
        $importedNames = [];

        for ($i = 1; $i < count($rows); $i++) {
            try {
                $row = array_combine($header, $rows[$i]);

                $name = trim($row['name'] ?? '');

                // ✂️ قص الاسم إذا تجاوز 255 حرف
                if (mb_strlen($name) > 255) {
                    Log::build(['driver' => 'single', 'path' => $logPath])
                        ->warning("Row $i: name too long, truncated to 255 characters.");
                    $name = mb_substr($name, 0, 255);
                }

                $code = trim($row['code'] ?? '');
                $categoryName = trim($row['category'] ?? '');

                if (!$name) {
                    $failed++;
                    Log::build(['driver' => 'single', 'path' => $logPath])
                        ->warning("Row $i skipped: name is empty.");
                    continue;
                }

                // إنشاء أو استرجاع التصنيف
                $category = ItemCategory::firstOrCreate(
                    ['name' => $categoryName ?: 'غير مصنّف'],
                    ['user_create_id' => 1, 'user_update_id' => 1]
                );

                // منع تكرار الاسم
                $originalName = $name;
                $suffix = 1;
                while (in_array($name, $existingNames) || in_array($name, $importedNames)) {
                    $name = $originalName . '-' . $suffix;
                    $suffix++;
                }

                Item::create([
                    'name' => $name,
                    'code' => $code ?: null,
                    'description' => null,
                    'item_category_id' => $category->id,
                    'measuring_unit' => null,
                    'user_create_id' => 1,
                    'user_update_id' => 1,
                ]);

                $importedNames[] = $name;
                $success++;
            } catch (\Throwable $e) {
                $failed++;
                Log::build(['driver' => 'single', 'path' => $logPath])
                    ->error("Row $i failed: " . $e->getMessage());
            }
        }

        Log::build(['driver' => 'single', 'path' => $logPath])
            ->info("✅ Import finished: $success succeeded, $failed failed.");

        echo "✅ Import finished: $success succeeded, $failed failed.\n";
        echo "📄 Log file: storage/logs/item_excel_import.log\n";
    }
}
