<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * A small, realistic stationery and office-supplies shop. Safe to run more
 * than once: records are matched by email, name or SKU and only missing ones
 * are created. Stock is added through StockService so the history and
 * quantities always agree.
 */
class DemoDataSeeder extends Seeder
{
    public const PASSWORD = 'Password123';

    private const CATEGORIES = [
        'Paper & Notebooks' => 'Copy paper, notebooks and pads.',
        'Writing Instruments' => 'Pens, pencils and markers.',
        'Desk Accessories' => 'Staplers, organisers and small desk tools.',
        'Filing & Storage' => 'Folders, binders and archive boxes.',
        'Printer Supplies' => 'Toner, ink and printer paper.',
        'Cleaning' => 'Office cleaning products.',
    ];

    private const SUPPLIERS = [
        ['name' => 'Northwind Office Supply', 'contact_person' => 'Ana Trujillo', 'email' => 'orders@northwind.test', 'phone' => '+1 555 010 2030'],
        ['name' => 'Contoso Paper Mills', 'contact_person' => 'Liam Walker', 'email' => 'sales@contoso-paper.test', 'phone' => '+1 555 019 4410'],
        ['name' => 'Fabrikam Print Solutions', 'contact_person' => 'Mei Chen', 'email' => 'trade@fabrikam.test', 'phone' => '+1 555 017 8822'],
        ['name' => 'Tailspin Cleaning Co', 'contact_person' => 'Omar Haddad', 'email' => 'hello@tailspin.test', 'phone' => '+1 555 013 5567'],
    ];

    /**
     * [sku, name, category, supplier index, purchase, selling, reorder level, units received, units issued]
     */
    private const PRODUCTS = [
        ['PAP-A4-500', 'A4 Copy Paper, 500 sheets', 'Paper & Notebooks', 1, 3.20, 5.49, 40, 300, 180],
        ['PAP-A3-250', 'A3 Copy Paper, 250 sheets', 'Paper & Notebooks', 1, 4.10, 6.99, 15, 60, 52],
        ['NB-A5-RULED', 'A5 Ruled Notebook', 'Paper & Notebooks', 1, 1.15, 2.99, 30, 120, 70],
        ['NB-A4-SPIRAL', 'A4 Spiral Notebook', 'Paper & Notebooks', 1, 1.90, 3.99, 25, 80, 80],
        ['PAD-STICKY-Y', 'Sticky Notes, Yellow, 12 pack', 'Paper & Notebooks', 0, 2.40, 4.50, 20, 90, 40],
        ['PEN-BALL-BLU', 'Ballpoint Pen, Blue, 10 pack', 'Writing Instruments', 0, 1.80, 3.49, 50, 400, 260],
        ['PEN-BALL-BLK', 'Ballpoint Pen, Black, 10 pack', 'Writing Instruments', 0, 1.80, 3.49, 50, 400, 362],
        ['PEN-GEL-RED', 'Gel Pen, Red', 'Writing Instruments', 0, 0.65, 1.49, 30, 150, 60],
        ['PCL-HB-12', 'HB Pencils, 12 pack', 'Writing Instruments', 0, 1.20, 2.79, 20, 100, 45],
        ['MRK-WB-4', 'Whiteboard Markers, 4 colours', 'Writing Instruments', 0, 3.10, 5.99, 15, 60, 51],
        ['HL-YEL-5', 'Highlighters, Yellow, 5 pack', 'Writing Instruments', 0, 2.20, 4.25, 15, 70, 70],
        ['STP-DESK', 'Desk Stapler', 'Desk Accessories', 0, 4.50, 8.99, 8, 30, 12],
        ['STP-PINS-5K', 'Staples 26/6, 5000', 'Desk Accessories', 0, 0.90, 1.99, 20, 100, 35],
        ['TAPE-DISP', 'Tape Dispenser with Tape', 'Desk Accessories', 0, 3.60, 6.49, 8, 25, 20],
        ['SCIS-21CM', 'Scissors, 21 cm', 'Desk Accessories', 0, 2.10, 3.99, 10, 40, 14],
        ['ORG-MESH', 'Mesh Desk Organiser', 'Desk Accessories', 0, 6.80, 12.99, 5, 20, 6],
        ['FLD-MAN-A4', 'Manila Folders A4, 100 pack', 'Filing & Storage', 1, 7.50, 12.49, 10, 40, 22],
        ['BND-RING-2', 'Ring Binder, 2 rings', 'Filing & Storage', 1, 1.70, 3.29, 20, 90, 30],
        ['BOX-ARCH', 'Archive Storage Box', 'Filing & Storage', 1, 2.30, 4.49, 15, 50, 41],
        ['DIV-A4-10', 'A4 Dividers, 10 tabs', 'Filing & Storage', 1, 0.95, 1.99, 20, 60, 22],
        ['TNR-HP-85A', 'Toner Cartridge 85A', 'Printer Supplies', 2, 38.00, 59.99, 4, 16, 13],
        ['INK-CMY-SET', 'Ink Cartridge Set, Colour', 'Printer Supplies', 2, 24.50, 39.99, 4, 12, 12],
        ['LBL-A4-21', 'Address Labels A4, 21 per sheet', 'Printer Supplies', 2, 8.90, 14.99, 6, 20, 5],
        ['CLN-WIPES', 'Surface Wipes, 100 pack', 'Cleaning', 3, 2.80, 4.99, 12, 48, 30],
        ['CLN-SPRAY', 'Screen Cleaning Spray', 'Cleaning', 3, 3.40, 6.49, 6, 18, 9],
    ];

    public function run(StockService $stock): void
    {
        $admin = $this->user('Demo Administrator', 'admin@example.com', UserRole::Admin);
        $staff = $this->user('Demo Staff', 'staff@example.com', UserRole::Staff);

        $categories = collect(self::CATEGORIES)
            ->map(fn (string $description, string $name) => Category::firstOrCreate(['name' => $name], ['description' => $description]));

        $suppliers = collect(self::SUPPLIERS)
            ->map(fn (array $supplier) => Supplier::firstOrCreate(['name' => $supplier['name']], [...$supplier, 'is_active' => true]));

        foreach (self::PRODUCTS as [$sku, $name, $category, $supplierIndex, $purchase, $selling, $reorder, $received, $issued]) {
            if (Product::where('sku', $sku)->exists()) {
                continue;
            }

            $supplier = $suppliers[$supplierIndex];
            $product = Product::create([
                'sku' => $sku,
                'name' => $name,
                'category_id' => $categories[$category]->id,
                'supplier_id' => $supplier->id,
                'purchase_price' => $purchase,
                'selling_price' => $selling,
                'reorder_level' => $reorder,
                'is_active' => true,
            ]);

            $this->recordHistory($stock, $product, $supplier, $admin, $staff, $received, $issued);
        }

        $this->command?->info('Demo accounts: admin@example.com and staff@example.com, password '.self::PASSWORD);
    }

    private function user(string $name, string $email, UserRole $role): User
    {
        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->fill(['name' => $name, 'password' => self::PASSWORD]);
            $user->role = $role;
            $user->email_verified_at = now();
            $user->save();
        }

        return $user;
    }

    /**
     * Spreads two deliveries and several stock-outs over the last few weeks.
     */
    private function recordHistory(
        StockService $stock,
        Product $product,
        Supplier $supplier,
        User $admin,
        User $staff,
        int $received,
        int $issued,
    ): void {
        $firstDelivery = (int) ceil($received * 0.6);

        $this->travelTo(now()->subDays(28), fn () => $stock->stockIn(
            $product, $firstDelivery, $staff, $supplier, 'DN-'.random_int(10000, 99999), 'Opening delivery',
        ));

        $this->travelTo(now()->subDays(12), fn () => $stock->stockIn(
            $product, $received - $firstDelivery, $staff, $supplier, 'DN-'.random_int(10000, 99999),
        ));

        $remaining = $issued;
        foreach ([24, 18, 9, 5, 2] as $daysAgo) {
            $portion = $daysAgo === 2 ? $remaining : (int) floor($issued / 5);
            if ($portion > 0) {
                $this->travelTo(now()->subDays($daysAgo), fn () => $stock->stockOut(
                    $product, $portion, $daysAgo % 2 === 0 ? $staff : $admin, 'SO-'.random_int(10000, 99999),
                ));
                $remaining -= $portion;
            }
        }

        // One stocktake correction for a few products, as happens in a real shop.
        if ($product->quantity > 5 && crc32($product->sku) % 4 === 0) {
            $this->travelTo(now()->subDay(), fn () => $stock->adjust(
                $product, $product->quantity - 1, $admin, 'Stocktake: one unit damaged',
            ));
        }
    }

    private function travelTo(\DateTimeInterface $moment, callable $callback): void
    {
        Carbon::setTestNow($moment);

        try {
            $callback();
        } finally {
            Carbon::setTestNow();
        }
    }
}
