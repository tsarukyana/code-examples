<?php

namespace App;


class Setting extends ModelBase
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'value'
    ];

    public static $only_super_admin = [
        'school_name',
        'time_zone',
    ];

    public static function archive($archive_time)
    {

        $items = self::get();

        $bulk_insert = [];
        if (!$items->isEmpty()) {
            foreach ($items as $item) {
                $now = date('Y-m-d H:i:s');
                $bulk_insert[] = [
                    'name' => $item->name,
                    'value' => $item->value,

                    'original_id' => $item->id,
                    'original_created_at' => $item->created_at,
                    'original_updated_at' => $item->updated_at,
                    'archive_time' => $archive_time,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        }
        if (!empty($bulk_insert)) {
            foreach (array_chunk($bulk_insert, 1000) as $bulk_insert_item) {
                ArchiveSetting::insert($bulk_insert_item);
            }
        }

        return true;
    }

}
