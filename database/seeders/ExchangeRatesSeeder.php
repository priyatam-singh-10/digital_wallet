<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExchangeRatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            DB::table('exchange_rates')->insert([
            ['from_currency'=>'USD','to_currency'=>'INR','rate'=>82.5,'created_at'=>now(),'updated_at'=>now()],
            ['from_currency'=>'INR','to_currency'=>'USD','rate'=>0.012121,'created_at'=>now(),'updated_at'=>now()],
            ['from_currency'=>'USD','to_currency'=>'EUR','rate'=>0.92,'created_at'=>now(),'updated_at'=>now()],
            ['from_currency'=>'EUR','to_currency'=>'USD','rate'=>1.087,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}
