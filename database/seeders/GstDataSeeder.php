<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GstDataSeeder extends Seeder
{
    /**
     * Seed the MongoDB database with GST tax slabs, HSN codes, and state codes.
     */
    public function run(): void
    {
        $this->seedTaxSlabs();
        $this->seedStateCodes();
        $this->seedHsnCodes();

        $this->command->info('✅ GST data seeded successfully.');
    }

    private function seedTaxSlabs(): void
    {
        $count = DB::connection('mongodb')->table('tax_slabs')->count();

        if ($count > 0) {
            $this->command->warn('Tax slabs already exist, skipping...');
            return;
        }

        $slabs = [
            ['name' => 'GST Exempt',  'rate' => 0,    'status' => 'active', 'effective_date' => '2017-07-01', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'GST 0.25%',   'rate' => 0.25, 'status' => 'active', 'effective_date' => '2017-07-01', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'GST 3%',      'rate' => 3,    'status' => 'active', 'effective_date' => '2017-07-01', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'GST 5%',      'rate' => 5,    'status' => 'active', 'effective_date' => '2017-07-01', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'GST 12%',     'rate' => 12,   'status' => 'active', 'effective_date' => '2017-07-01', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'GST 18%',     'rate' => 18,   'status' => 'active', 'effective_date' => '2017-07-01', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'GST 28%',     'rate' => 28,   'status' => 'active', 'effective_date' => '2017-07-01', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($slabs as $slab) {
            DB::connection('mongodb')->table('tax_slabs')->insert($slab);
        }
        $this->command->info("  Seeded " . count($slabs) . " tax slabs.");
    }

    private function seedStateCodes(): void
    {
        $count = DB::connection('mongodb')->table('state_codes')->count();

        if ($count > 0) {
            $this->command->warn('State codes already exist, skipping...');
            return;
        }

        $states = [
            ['code' => '01', 'state_name' => 'Jammu & Kashmir'],
            ['code' => '02', 'state_name' => 'Himachal Pradesh'],
            ['code' => '03', 'state_name' => 'Punjab'],
            ['code' => '04', 'state_name' => 'Chandigarh'],
            ['code' => '05', 'state_name' => 'Uttarakhand'],
            ['code' => '06', 'state_name' => 'Haryana'],
            ['code' => '07', 'state_name' => 'Delhi'],
            ['code' => '08', 'state_name' => 'Rajasthan'],
            ['code' => '09', 'state_name' => 'Uttar Pradesh'],
            ['code' => '10', 'state_name' => 'Bihar'],
            ['code' => '11', 'state_name' => 'Sikkim'],
            ['code' => '12', 'state_name' => 'Arunachal Pradesh'],
            ['code' => '13', 'state_name' => 'Nagaland'],
            ['code' => '14', 'state_name' => 'Manipur'],
            ['code' => '15', 'state_name' => 'Mizoram'],
            ['code' => '16', 'state_name' => 'Tripura'],
            ['code' => '17', 'state_name' => 'Meghalaya'],
            ['code' => '18', 'state_name' => 'Assam'],
            ['code' => '19', 'state_name' => 'West Bengal'],
            ['code' => '20', 'state_name' => 'Jharkhand'],
            ['code' => '21', 'state_name' => 'Odisha'],
            ['code' => '22', 'state_name' => 'Chhattisgarh'],
            ['code' => '23', 'state_name' => 'Madhya Pradesh'],
            ['code' => '24', 'state_name' => 'Gujarat'],
            ['code' => '25', 'state_name' => 'Daman & Diu'],
            ['code' => '26', 'state_name' => 'Dadra & Nagar Haveli'],
            ['code' => '27', 'state_name' => 'Maharashtra'],
            ['code' => '28', 'state_name' => 'Andhra Pradesh (Old)'],
            ['code' => '29', 'state_name' => 'Karnataka'],
            ['code' => '30', 'state_name' => 'Goa'],
            ['code' => '31', 'state_name' => 'Lakshadweep'],
            ['code' => '32', 'state_name' => 'Kerala'],
            ['code' => '33', 'state_name' => 'Tamil Nadu'],
            ['code' => '34', 'state_name' => 'Puducherry'],
            ['code' => '35', 'state_name' => 'Andaman & Nicobar Islands'],
            ['code' => '36', 'state_name' => 'Telangana'],
            ['code' => '37', 'state_name' => 'Andhra Pradesh (New)'],
            ['code' => '38', 'state_name' => 'Ladakh'],
            ['code' => '97', 'state_name' => 'Other Territory'],
            ['code' => '99', 'state_name' => 'Centre Jurisdiction'],
        ];

        foreach ($states as $state) {
            $state['created_at'] = now();
            $state['updated_at'] = now();
            DB::connection('mongodb')->table('state_codes')->insert($state);
        }
        $this->command->info("  Seeded " . count($states) . " state codes.");
    }

    private function seedHsnCodes(): void
    {
        $count = DB::connection('mongodb')->table('hsn_codes')->count();

        if ($count > 0) {
            $this->command->warn('HSN codes already exist, skipping...');
            return;
        }

        $codes = [
            ['hsn_code' => '0101', 'description' => 'Live horses, asses, mules and hinnies', 'category' => 'Animals', 'gst_rate' => 0],
            ['hsn_code' => '0201', 'description' => 'Meat of bovine animals, fresh or chilled', 'category' => 'Food', 'gst_rate' => 0],
            ['hsn_code' => '0401', 'description' => 'Milk and cream, not concentrated', 'category' => 'Dairy', 'gst_rate' => 0],
            ['hsn_code' => '0713', 'description' => 'Dried leguminous vegetables (pulses)', 'category' => 'Food', 'gst_rate' => 0],
            ['hsn_code' => '1001', 'description' => 'Wheat and meslin', 'category' => 'Cereals', 'gst_rate' => 0],
            ['hsn_code' => '1006', 'description' => 'Rice', 'category' => 'Cereals', 'gst_rate' => 5],
            ['hsn_code' => '1701', 'description' => 'Cane or beet sugar', 'category' => 'Sugar', 'gst_rate' => 5],
            ['hsn_code' => '1905', 'description' => 'Bread, pastry, cakes, biscuits', 'category' => 'Bakery', 'gst_rate' => 18],
            ['hsn_code' => '2201', 'description' => 'Mineral waters, aerated water', 'category' => 'Beverages', 'gst_rate' => 18],
            ['hsn_code' => '2202', 'description' => 'Sweetened or flavoured water', 'category' => 'Beverages', 'gst_rate' => 28],
            ['hsn_code' => '3004', 'description' => 'Medicaments for therapeutic use', 'category' => 'Pharma', 'gst_rate' => 12],
            ['hsn_code' => '3304', 'description' => 'Beauty or make-up preparations', 'category' => 'Cosmetics', 'gst_rate' => 28],
            ['hsn_code' => '3401', 'description' => 'Soap, washing preparations', 'category' => 'FMCG', 'gst_rate' => 18],
            ['hsn_code' => '4901', 'description' => 'Printed books, brochures, leaflets', 'category' => 'Publishing', 'gst_rate' => 0],
            ['hsn_code' => '6109', 'description' => 'T-shirts, singlets and other vests', 'category' => 'Apparel', 'gst_rate' => 5],
            ['hsn_code' => '6203', 'description' => 'Mens or boys suits, trousers', 'category' => 'Apparel', 'gst_rate' => 12],
            ['hsn_code' => '6403', 'description' => 'Footwear with outer soles of rubber/plastic', 'category' => 'Footwear', 'gst_rate' => 18],
            ['hsn_code' => '7113', 'description' => 'Articles of jewellery and parts thereof', 'category' => 'Jewellery', 'gst_rate' => 3],
            ['hsn_code' => '8471', 'description' => 'Automatic data processing machines (computers)', 'category' => 'Electronics', 'gst_rate' => 18],
            ['hsn_code' => '8517', 'description' => 'Telephone sets, smartphones', 'category' => 'Electronics', 'gst_rate' => 18],
            ['hsn_code' => '8703', 'description' => 'Motor cars for transport of persons', 'category' => 'Automobile', 'gst_rate' => 28],
            ['hsn_code' => '9403', 'description' => 'Furniture and parts thereof', 'category' => 'Furniture', 'gst_rate' => 18],
            ['hsn_code' => '9954', 'description' => 'Construction services', 'category' => 'Services', 'gst_rate' => 18],
            ['hsn_code' => '9971', 'description' => 'Financial and related services', 'category' => 'Services', 'gst_rate' => 18],
            ['hsn_code' => '9983', 'description' => 'Other professional, technical services', 'category' => 'Services', 'gst_rate' => 18],
            ['hsn_code' => '9992', 'description' => 'Education services', 'category' => 'Services', 'gst_rate' => 0],
            ['hsn_code' => '9993', 'description' => 'Human health and social care services', 'category' => 'Services', 'gst_rate' => 0],
        ];

        foreach ($codes as $code) {
            $code['status'] = 'active';
            $code['effective_date'] = '2017-07-01';
            $code['created_at'] = now();
            $code['updated_at'] = now();
            DB::connection('mongodb')->table('hsn_codes')->insert($code);
        }
        $this->command->info("  Seeded " . count($codes) . " HSN codes.");
    }
}
