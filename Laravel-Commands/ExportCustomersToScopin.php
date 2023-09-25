<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use App\Customer\Models\Customer;
use App\Scopin\Actions\UpsertCustomerAction;
use App\Scopin\Exceptions\ScopinClientException;

class ExportCustomersToScopin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-customers-to-scopin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all migrated customers to Scopin';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Display an alert message
        $this->alert('Export Customers to Scopin');

        // Retrieve customers from the database
        $customers = $this->query()->get();
        $count = count($customers);

        // Display the total number of customers to export
        $this->info("Total Customers to export: {$count}");

        // Create a progress bar
        $bar = $this->output->createProgressBar($count);

        // Initialize an array to store failed customer exports
        $failed = [];

        // Iterate over each customer and export them to Scopin
        foreach ($customers as $customer) {
            try {
                // Execute the action to upsert the customer in Scopin
                app(UpsertCustomerAction::class)->execute($customer);
            } catch (ScopinClientException $e) {
                // Store the customer ID and exception message for failed exports
                $failed[] = [$customer->id, $e->getMessage()];
            }
            $bar->advance();
        }

        // Finish the progress bar
        $bar->finish();

        // Display a table with the failed customer exports
        $this->table(['Customer ID', 'Exception'], $failed);

        // Return the command success status
        return Command::SUCCESS;
    }

    /**
     * Retrieves a query builder instance for the Customer model.
     *
     * @return Builder The query builder instance.
     */
    protected function query(): Builder
    {
        return Customer::query()
            ->whereNotNull('wms_number')
            ->whereNull('scopin_guid');
    }
}
