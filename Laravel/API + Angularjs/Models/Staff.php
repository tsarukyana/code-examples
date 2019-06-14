<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Staff extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'staff';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'staff_code',
        'staff_name',
        'staff_pin_type'
    ];


    /**
     * Get the user that owns the staff.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the user roles that owns the staff.
     */
    public function user_roles()
    {
        return $this->hasMany('App\UserRole', 'user_id', 'user_id');
    }


    public static function archive($archive_time)
    {

        $items = self::get();

        $bulk_insert = [];
        if (!$items->isEmpty()) {
            foreach ($items as $item) {
                $now = date('Y-m-d H:i:s');
                $bulk_insert[] = [
                    'user_id' => $item->user_id,
                    'staff_code' => $item->staff_code,
                    'staff_name' => $item->staff_name,
                    'staff_pin_type' => $item->staff_pin_type,

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
            return ArchiveStaff::insert($bulk_insert);
        }

        return true;
    }

    public static function updateCounsellorRoleAfterArchive()
    {
        return UserRole::where('role_id', '=', Role::COURSECOUNSELLOR)
            ->leftJoin('student_staff', function ($join) {
                $join->on('user_role.user_id', '=', 'student_staff.staff_user_id');
            })
            ->whereNull('student_staff.staff_user_id')
            ->delete();
    }
}
