<?php

namespace App;


class Role extends ModelBase
{
    const ORGANISATION_ADMIN = 8;
    const SSOADMIN = 7;
    const COORDINATOR = 6;
    const HOLA = 5;
    const COURSECOUNSELLOR = 4;
    const HOMETEACHER = 3;
    const STAFF = 2;
    const STUDENT = 1;

    public static $role_name = [
        self::ORGANISATION_ADMIN => 'org_admin',
        self::SSOADMIN => 'sso_admin',
        self::COORDINATOR => 'co_ordinator',
        self::HOLA => 'hola',
        self::COURSECOUNSELLOR => 'course_counsellor',
        self::HOMETEACHER => 'home_teacher',
        self::STAFF => 'staff',
        self::STUDENT => 'student',
    ];

    public function users()
    {
        return $this->belongsToMany('App\User', 'user_role', 'role_id', 'user_id');
    }


    public static function archive($archive_time)
    {

        $items = self::get();

        $bulk_insert = [];
        if (!$items->isEmpty()) {
            foreach ($items as $item) {
                $now = date('Y-m-d H:i:s');
                $bulk_insert[] = [
                    'name' => $item->name,
                    'description' => $item->description,
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
                ArchiveRole::insert($bulk_insert_item);
            }
        }

        return true;
    }
}
