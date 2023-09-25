<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Product\Models\StockAlert;
use App\Product\Notifications\ProductInStock;

class StockAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:stock-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send emails to Customers for their stock alerts';

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
     * Handle the command.
     *
     * @return int
     */
    public function handle()
    {
        // Retrieve stock alerts with related product and customer
        $stockAlerts = StockAlert::with(['product', 'customer'])
            ->whereHas('product', fn ($query) => $query->where('stock', '>', 0))
            ->get();

        // Send alert for each stock alert if conditions are met
        $stockAlerts->each(fn ($stockAlert) => $this->sendAlertIfConditionsAreMet($stockAlert));

        // Return success status
        return Command::SUCCESS;
    }

    /**
     * Sends a stock alert to a customer if the conditions are met.
     *
     * @param StockAlert $stockAlert The stock alert object.
     */
    protected function sendAlertIfConditionsAreMet(StockAlert $stockAlert)
    {
        // Get the customer object from the stock alert
        $customer = $stockAlert->customer;

        // Get the product name from the stock alert
        $productName = $stockAlert->product->name;

        // Get the customer UUID
        $customerUuid = $customer->uuid;

        // Log the stock alert being sent
        $this->line("Sending Stock Alert ({$stockAlert->id}) for '{$productName}' to '{$customerUuid}'");

        // Send the stock alert notification to the customer
        $customer->notify(new ProductInStock($stockAlert));
    }
}
