<?php

namespace App\Console\Commands;

use App\Support\Xignite\Client;
use Illuminate\Console\Command;
use App\Metal\Models\Metal;
use App\Product\Models\Product;
use App\Metal\Models\MetalPrice;

class LoadMetalPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:load-metal-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load the latest metal prices from Xignite';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Client $client)
    {
        // Fetch all metals
        $metals = Metal::all();

        // Create a collection to store the created MetalPrice instances
        $created = collect();

        // Iterate over each metal
        foreach ($metals as $metal) {
            // Get the real-time extended metal quote using the client
            $metalQuote = $client->getRealTimeExtendedMetalQuote($metal->symbol);

            // Create a MetalPrice instance from the metal and metal quote
            $created->push(MetalPrice::createFromRealTimeMetalQuoteResponse($metal, $metalQuote));
        }

        // Map MetalPrice instances to table data format
        $tableData = $created->map(function (MetalPrice $metalPrice) {
            return [
                'uuid' => $metalPrice->uuid,
                'metal' => "{$metalPrice->metal->name} ({$metalPrice->metal->symbol})",
                'price_spot' => $metalPrice->price_spot,
                'percentage_change' => $metalPrice->percentage_change,
                'absolute_change' => $metalPrice->absolute_change,
                'xignite_date' => $metalPrice->xignite_date,
                'xignite_time' => $metalPrice->xignite_time,
            ];
        });

        // Display the table
        $this->table(
            ['UUID', 'Metal', 'Spot Price', 'Percent Change', 'Absolute Change', 'Xignite Date', 'Xignite Time'],
            $tableData
        );

        // Recalculate and save the price of metal properties for each product
        Product::query()
            ->whereNotNull('metal_properties_id')
            ->with('metalProperties')
            ->cursor()
            ->each(function (Product $product) {
                $product->metalProperties->recalculatePrice();
                $product->metalProperties->save();
            });

        // Return success status
        return Command::SUCCESS;
    }
}
