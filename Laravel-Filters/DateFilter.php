<?php

namespace App\Filters;

use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class DateFilter extends Filter
{
    /**
     * Retrieves the form schema.
     *
     * @return array The form schema.
     */
    public function getFormSchema(): array
    {
        return [
            Grid::make()
                ->columns(2)
                ->schema([
                    $this->makeDatePicker($this->getNameWithSuffix("_from")),
                    $this->makeDatePicker($this->getNameWithSuffix("_until")),
                ]),
        ];
    }

    /**
     * Apply date filters to the base query.
     *
     * @param Builder $query The base query.
     * @param array $data The data containing the date filters.
     * @return Builder The modified query.
     */
    public function applyToBaseQuery(Builder $query, array $data = []): Builder
    {
        // Check if the "from" date filter is set
        if (isset($data["{$this->name}_from"])) {
            // Add a where condition to filter records where the date is greater than or equal to the "from" date
            $query->whereDate($this->name, '>=', $data["{$this->name}_from"]);
        }

        // Check if the "until" date filter is set
        if (isset($data["{$this->name}_until"])) {
            // Add a where condition to filter records where the date is less than or equal to the "until" date
            $query->whereDate($this->name, '<=', $data["{$this->name}_until"]);
        }

        // Return the modified query
        return $query;
    }

    /**
     * Create a date picker.
     *
     * @param string $name The name of the date picker.
     * @return array The created date picker.
     */
    private function makeDatePicker(string $name): array
    {
        return DatePicker::make($name);
    }

    /**
     * A function that returns the name of the object
     * concatenated with a given suffix.
     *
     * @param string $suffix The suffix to append to the name.
     * @return string The name with the suffix appended.
     */
    private function getNameWithSuffix(string $suffix): string
    {
        return "{$this->name}{$suffix}";
    }
}
