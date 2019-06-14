<?php

namespace App\Http\Controllers;

use App\ActionLog;
use App\HomeGroup;
use App\Staff;
use App\StaffHomeGroup;
use App\Student;
use App\StudentStaff;
use App\YearLevel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

use LucaDegasperi\OAuth2Server\Authorizer;
use App\LogType;
use App\Role;
use App\User;

class StaffController extends BaseAdminController
{
    public function __construct(Authorizer $authorizer)
    {
        parent::__construct($authorizer);
    }

    /**
     * Display a listing of the resource.
     *
     * @return object
     */
    public function index() {

        $this->addLog(array(
            "log_type_id" => LogType::INFO,
            "message"     => "Getting all Staff ".self::class,
            "status_code" => Response::HTTP_OK
        ));

        return Staff::with('user')
            ->leftJoin('user_role', function ($join) {
            $join->on('staff.user_id', '=', 'user_role.user_id');
            $join->where('user_role.role_id', '=', Role::COORDINATOR);
        })->orderBy('is_coordinator','desc')->get(['staff.*','user_role.role_id as is_coordinator']);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $user_id
     * @return object
     */
    public function getByUserId($user_id) {

        $this->addLog(array(
            "log_type_id" => LogType::INFO,
            "message"     => "Get staff by user id",
            "status_code" => Response::HTTP_OK
        ));

        return Staff::where('user_id', $user_id)->with('user')->first();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $user_id
     * @return object
     */
    public function get_staff_name_by_user_id($user_id) {

        $this->addLog(array(
            "log_type_id" => LogType::INFO,
            "message"     => "Get staff name by user id",
            "status_code" => Response::HTTP_OK
        ));

        return Staff::where('user_id', $user_id)->with('user')->first(['staff.staff_name']); // TODO Need to check, in the case '->first(['staff.staff_name'])' from users table will return  null
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function addUploadItems(Request $request) {

        $response['status'] = false;
        try{
            $saved_one_time = false;
            $statusCode = Response::HTTP_OK;
            $what_to_do = isset($request['what_to_do']) ? $request['what_to_do'] : null;
            $uploaded_data = isset($request['uploaded_data']) ? json_decode($request['uploaded_data']) : null;

            $do_action = false;
            if($what_to_do == 1){ //Add new and update existing staff
                $do_action = true;
            }else if($what_to_do == 2){ //Purge existing and replace with data in spreadsheet
                $do_action = true;
            }else{
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] = "Invalid \'What to do?\' param";
                $this->addLog(array(
                    "log_type_id" => LogType::ERROR,
                    "message"     => "Invalid \'What to do?\' param",
                    "status_code" => $statusCode
                ));
                return $response;
            }


            $role_staff = Role::where("name", "staff")->first();

            $truncate_done = false;
            $coordinators = Staff::join('user_role', function ($join) {
                $join->on('staff.user_id', '=', 'user_role.user_id');
                $join->where('user_role.role_id', '=', Role::COORDINATOR);
            })->join('users', function ($join) {
                $join->on('staff.user_id', '=', 'users.id');
            })->get(['staff.*','users.email as staff_email']);

            $students = Student::whereNotNull('student_email')->get(['user_id as student_user_id','student_email']);
            $student_emails = [];
            if(!empty($students)){
                foreach ($students as $student){
                    $student_emails[] = $student->student_email;
                }
            }
            $uploaded_count = 0;

            if(!empty($uploaded_data)) {
                foreach ($uploaded_data as $upload_item) {

                    $upload_item = (array)$upload_item;


                    $validation_data['staff_code'] = isset($upload_item['staff_code']) ? $upload_item['staff_code'] : null;
                    $validation_data['staff_name'] = isset($upload_item['staff_name']) ? $upload_item['staff_name'] : null;
                    $validation_data['staff_email'] = isset($upload_item['staff_email']) ? $upload_item['staff_email'] : null;
                    $continue = false;
                    $coordinator_ids = [];

                    foreach ($coordinators as $coordinator) {
                        if (!in_array($coordinator->user_id, $coordinator_ids)) {
                            $coordinator_ids[] = $coordinator->user_id;
                        }
                        if ($coordinator->staff_code == $validation_data['staff_code']) {
                            $response['errors'][] = $validation_data['staff_code'] . ' is coordinator';
                            $continue = true;
                        }
                        if ($coordinator->staff_name == $validation_data['staff_name']) {
                            $response['errors'][] = $validation_data['staff_name'] . ' is coordinator';
                            $continue = true;
                        }
                        if ($coordinator->staff_email == $validation_data['staff_email']) {
                            $response['errors'][] = $validation_data['staff_email'] . ' already used by coordinator';
                            $continue = true;
                        }
                    }

                    if (in_array($validation_data['staff_email'], $student_emails)) {
                        $response['errors'][] = $validation_data['staff_email'] . ' already used by student';
                        $continue = true;
                    }
                    if ($continue) {
                        continue;
                    }
                    $validate_rules = array(
                        'staff_code' => 'required',
                        'staff_name' => 'required',
                        'staff_email' => 'required|email',
                    );

                    $validator = Validator::make($validation_data, $validate_rules);

                    if ($validator->fails()) {
                        $statusCode = Response::HTTP_BAD_REQUEST;
                        $response['errors'][] = $validator->errors();
                        $this->addLog(array(
                            "log_type_id" => LogType::ERROR,
                            "message" => $validator->errors(),
                            "status_code" => $statusCode
                        ));
                        continue;
                    }

                    $space_contain = str_contains($validation_data['staff_code'], ' ');
                    $comma_contain = str_contains($validation_data['staff_code'], ',');

                    if ($space_contain || $comma_contain) {
                        $statusCode = Response::HTTP_BAD_REQUEST;
                        $err_msg = $validation_data['staff_code'] . ' contains space or comma.';
                        $response['errors'][] = $err_msg;
                        $this->addLog(array(
                            "log_type_id" => LogType::ERROR,
                            "message" => $err_msg,
                            "status_code" => $statusCode
                        ));
                        continue;
                    }

                    try {
                        if ($what_to_do == 1) { //Add new and update existing staff
                            $user_staff = User::firstOrNew(array(
                                'username' => $upload_item['staff_code']
                            ));
                            if (!$user_staff->exists) {
                                $check_email_staff = User::firstOrNew(array(
                                    'email' => $upload_item['staff_email']
                                ));
                                if (!$check_email_staff->exists) {
                                    //users table
                                    $user_staff = new User();
                                    $user_staff->username = $upload_item['staff_code'];
                                    $user_staff->email = $upload_item['staff_email'];

                                    if (isset($upload_item['staff_pin']) && !empty($upload_item['staff_pin'])) {
                                        $user_staff->password = Hash::make($upload_item['staff_pin']);
                                    } else {
                                        $user_staff->password = Hash::make(str_random(8));
                                    }

                                    if($user_staff->save()){
                                        $user_staff->roles()->attach($role_staff);
                                    }

                                    //staff  table
                                    $staff_item = new Staff();
                                    $staff_item->user_id = $user_staff->id;
                                    $staff_item->staff_code = $upload_item['staff_code'];
                                    $staff_item->staff_name = isset($upload_item['staff_name']) ? $upload_item['staff_name'] : null;
                                    if (isset($upload_item['staff_pin']) && !empty($upload_item['staff_pin'])) {
                                        $staff_item->staff_pin_type = 1;
                                    }
                                } else {
                                    $response['errors'][] = "Duplicate StaffEmail: " . $upload_item['staff_email'];
                                    $this->addLog(array(
                                        "log_type_id" => LogType::ERROR,
                                        "message" => "Duplicate StaffEmail: " . $upload_item['staff_email'],
                                        "status_code" => Response::HTTP_BAD_REQUEST
                                    ));
                                }
                            } else {

                                if ($user_staff->hasRole(Role::$role_name[Role::STAFF])) {

                                    if ($user_staff->email != $upload_item['staff_email']) {

                                        $check_email_user_staff = User::firstOrNew(array(
                                            'email' => $upload_item['staff_email']
                                        ));

                                        if (!$check_email_user_staff->exists) {
                                            //users table
                                            $user_staff->email = $upload_item['staff_email'];

                                            if (isset($upload_item['staff_pin']) && !empty($upload_item['staff_pin'])) {
                                                $user_staff->password = Hash::make($upload_item['staff_pin']);
                                            }
                                            $user_staff->save();
                                            $staff_item = Staff::where('user_id', $user_staff->id)->first();
                                            $staff_item->staff_code = $upload_item['staff_code'];

                                            if (isset($upload_item['staff_name'])) {
                                                $staff_item->staff_name = $upload_item['staff_name'];
                                            }

                                            if (isset($upload_item['staff_pin']) && !empty($upload_item['staff_pin'])) {
                                                $staff_item->staff_pin_type = 1;
                                            }

                                        } else {
                                            $response['errors'][] = "Duplicate StaffEmail: " . $upload_item['staff_email'];
                                            $this->addLog(array(
                                                "log_type_id" => LogType::ERROR,
                                                "message" => "Duplicate StaffEmail: " . $upload_item['staff_email'],
                                                "status_code" => Response::HTTP_BAD_REQUEST
                                            ));
                                        }
                                    } else {
                                        if (isset($upload_item['staff_pin']) && !empty($upload_item['staff_pin'])) {
                                            $user_staff->password = Hash::make($upload_item['staff_pin']);
                                        }
                                        $user_staff->save();
                                        $staff_item = Staff::where('user_id', $user_staff->id)->first();
                                        $staff_item->staff_code = $upload_item['staff_code'];

                                        if (isset($upload_item['staff_name'])) {
                                            $staff_item->staff_name = $upload_item['staff_name'];
                                        }

                                        if (isset($upload_item['staff_pin']) && !empty($upload_item['staff_pin'])) {
                                            $staff_item->staff_pin_type = 1;
                                        }
                                    }
                                }
                            }

                        } else if ($what_to_do == 2) { //Purge existing and replace with data in spreadsheet

                            if (!$truncate_done) {
                                $staff_accounts = Staff::whereNotIn('user_id', $coordinator_ids)->get();
                                foreach ($staff_accounts as $delete_staff) {
                                    $delete_staff = User::find($delete_staff->user_id);
                                    $delete_staff->delete();
                                }
                                $truncate_done = true;
                            }
                            $check_user_staff = User::firstOrNew(array(
                                'username' => $upload_item['staff_code']
                            ));

                            if (!$check_user_staff->exists) {
                                $check_email_user_staff = User::firstOrNew(array(
                                    'email' => $upload_item['staff_email']
                                ));
                                if (!$check_email_user_staff->exists) {
                                    //users table
                                    $user_staff = new User();
                                    $user_staff->username = $upload_item['staff_code'];
                                    $user_staff->email = $upload_item['staff_email'];
                                    if (isset($upload_item['staff_pin']) && !empty($upload_item['staff_pin'])) {
                                        $user_staff->password = Hash::make($upload_item['staff_pin']);
                                    } else {
                                        $user_staff->password = Hash::make(str_random(8));
                                    }
                                    $user_staff->save();
                                    $user_staff->roles()->attach($role_staff);

                                    //staff  table
                                    $staff_item = new Staff();
                                    $staff_item->user_id = $user_staff->id;
                                    $staff_item->staff_code = $upload_item['staff_code'];

                                    $staff_item->staff_name = isset($upload_item['staff_name']) ? $upload_item['staff_name'] : null;
                                    if (isset($upload_item['staff_pin']) && !empty($upload_item['staff_pin'])) {
                                        $staff_item->staff_pin_type = 1;
                                    }

                                } else {
                                    $response['errors'][] = "Duplicate StaffEmail: " . $upload_item['staff_email'];
                                    $this->addLog(array(
                                        "log_type_id" => LogType::ERROR,
                                        "message" => "Duplicate StaffEmail: " . $upload_item['staff_email'],
                                        "status_code" => Response::HTTP_BAD_REQUEST
                                    ));
                                }
                            } else {
                                $response['errors'][] = "Duplicate StaffCode: " . $upload_item['staff_code'];
                                $this->addLog(array(
                                    "log_type_id" => LogType::ERROR,
                                    "message" => "Duplicate StaffCode: " . $upload_item['staff_code'],
                                    "status_code" => Response::HTTP_BAD_REQUEST
                                ));
                            }

                        }

                        $original = $staff_item->toArray();
                        if ($do_action && !empty($staff_item)) {

                            if ($staff_item->save()) {
                                $log_msg = 'Uploaded new staff<br>';
                                foreach ($original as $name => $value) {
                                    $log_msg .= $this->get_log_msg($name,$value);
                                }
                                $this->pushBulkLogs(array(
                                    "log_type_id" => LogType::INFO,
                                    "message"     => $log_msg,
                                    "status_code" => Response::HTTP_CREATED,
                                    "show_status" => ActionLog::SHOW
                                ));
                                $uploaded_count++;
                                $saved_one_time = true;
                            }
                        }
                    } catch (\Exception $e) {
                        $statusCode = Response::HTTP_BAD_REQUEST;
                        $response['errors'][] = $e->getMessage();
                        $this->addLog(array(
                            "log_type_id" => LogType::ERROR,
                            "message" => $e->getMessage(),
                            "status_code" => $statusCode
                        ));
                    }
                }
            }
            if($saved_one_time){
                $this->pushBulkLogs(array(
                    "log_type_id" => LogType::INFO,
                    "message"     => "Staffs uploaded successfully. Total count of uploaded staffs is ".$uploaded_count
                                .". Upload option is: ".($what_to_do ==  1 ? 'Add new and update existing staff' : 'Purge existing and replace with data in spreadsheet'),
                    "status_code" => Response::HTTP_OK,
                    "show_status" => ActionLog::SHOW
                ));
                $this->saveBulkLogs();
                $response['status'] = true;
            }
        }catch (\Exception $e){
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message"     => $e->getMessage(),
                "status_code" => $statusCode
            ));
        }finally{
            return \Response::json($response, $statusCode);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request) {

        $response['status'] = false;

        $validation_data['staff_code'] = $validation_data['username'] = isset($request['staff_code']) ? $request['staff_code'] : null;
        $validation_data['staff_name'] = isset($request['staff_name']) ? $request['staff_name'] : null;
        $validation_data['staff_email'] = $validation_data['email'] = isset($request['staff_email']) ? $request['staff_email'] : null;

        $username_uniq_msg = 'You have already student with this Code';
        $isset_staff = Staff::where('staff_code','=',$validation_data['staff_code'])->count();
        if($isset_staff > 0) {
            $username_uniq_msg = 'You have already staff with this StaffCode';
        }

        $messages = array(
            'email.email'=>'Invalid StaffEmail',
            'email.unique'=>'The StaffEmail has already been taken.',
            'username.required'=>'The StaffCode field is required.',
            'username.unique'=>$username_uniq_msg,
        );

        $validate_rules = array(
            'username'   => 'required|unique:users',
            'staff_name'   => 'required',
            'email'   => 'required|email|unique:users'
        );

        $validator = Validator::make($validation_data, $validate_rules, $messages);


        if ($validator->fails()) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] =  $validator->errors();
            return \Response::json($response, $statusCode);
        }
        
        $email_exists_in_students = Student::where('student_email', $request['staff_email'])->first();
        if(!empty($email_exists_in_students)){
            $statusCode = Response::HTTP_BAD_REQUEST;
            $err_msg = 'The StaffEmail already used by student';
            $response['errors'][] = $err_msg;
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message"     => $err_msg,
                "status_code" => $statusCode
            ));

            return \Response::json($response, $statusCode);
        }

        $space_contain = str_contains($validation_data['staff_code'], ' ');
        $comma_contain = str_contains($validation_data['staff_code'], ',');

        if ($space_contain || $comma_contain) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $err_msg = $validation_data['staff_code'] . ' contains space or comma.';
            $response['errors'][] =  $err_msg;
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message"     => $err_msg,
                "status_code" => $statusCode
            ));

            return \Response::json($response, $statusCode);
        }

        try{
            $statusCode = Response::HTTP_CREATED;

            $role_staff = Role::where("name", "staff")->first();

            //users table
            $user_staff = new User();
            $user_staff->username = $request['staff_code'];
            $user_staff->email = $request['staff_email'];

            if(isset($request['staff_pin']) && !empty($request['staff_pin']) ){
                $user_staff->password = Hash::make($request['staff_pin']);
            }else{
                $user_staff->password = Hash::make(str_random(8));
            }

            if ($user_staff->save()) {
                $user_staff->roles()->attach($role_staff);

                $staff = new Staff();
                $staff->user_id = $user_staff->id;
                $staff->staff_code = $validation_data['staff_code'];
                $staff->staff_name = $validation_data['staff_name'];

                if(isset($request['staff_pin']) && !empty($request['staff_pin']) ){
                    $staff->staff_pin_type = 1;
                }

                if ($staff->save()) {
                    $log_msg = "Added new staff<br> Code: ".$staff->staff_code.", Name: ".$staff->staff_name. ", Email: ".$user_staff->email;
                    $response['status'] = true;
                    $this->addLog(array(
                        "message"     => $log_msg,
                        "status_code" => $statusCode,
                        "show_status" => ActionLog::SHOW
                    ));
                }
            }else{
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['status'] = true;
                $this->addLog(array(
                    "log_type_id" => LogType::ERROR,
                    "message"     => "Something went wrong when adding new staff.",
                    "status_code" => $statusCode
                ));
            }
        }catch (\Exception $e){
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message"     => $e->getMessage(),
                "status_code" => $statusCode
            ));
        }finally{
            return \Response::json($response, $statusCode);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id) {

        $response['status'] = false;
        try{
            $statusCode = Response::HTTP_OK;
            $staff = Staff::where('staff.id','=', $id)->with('user')
                ->leftJoin('user_role', function ($join) {
                    $join->on('staff.user_id', '=', 'user_role.user_id');
                    $join->where('user_role.role_id', '=', Role::COORDINATOR);
                })->first(['staff.*','user_role.role_id as is_coordinator']);
            if($staff->is_coordinator > 0) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] =  $staff->staff_name . ' is Coordintaor';
                $this->addLog(array(
                    "log_type_id" => LogType::ERROR,
                    "message"     => 'Try to edit coordinator',
                    "status_code" => $statusCode
                ));

                return $response;
            }
            $staff_original = $staff->getOriginal();
            $response['$origin']= $staff_original;

            $old_staff_code = isset($staff->user->username) ? $staff->user->username: null;
            $old_staff_email = isset($staff->user->email) ? $staff->user->email: null;

            $validation_data['staff_code'] = $validation_data['username'] = isset($request['staff_code']) ? $request['staff_code'] : null;
            $validation_data['staff_name'] = isset($request['staff_name']) ? $request['staff_name'] : null;
            $validation_data['email'] = isset($request['staff_email']) ? $request['staff_email'] : null;

            $messages = array(
                'email.required'=>'The StaffEmail field is required.',
                'email.email'=>'Invalid StaffEmail',
                'email.unique'=>'The StaffEmail has already been taken.',
                'username.required'=>'The StaffCode field is required.',
                'username.unique'=>'The StaffCode has already been taken.',
            );

            $unique_field = '|unique:users';

            $validate_rules = array(
                'username'   => 'required',
                'staff_name'   => 'required',
                'email'   => 'required|email'
            );
            if($old_staff_code != $request['staff_code']){
                $validate_rules['username'] = $validate_rules['username'].$unique_field;
            }
            if($old_staff_email != $request['staff_email']){
                $validate_rules['email'] = $validate_rules['email'].$unique_field;
            }

            $validator = Validator::make($validation_data, $validate_rules, $messages);

            if ($validator->fails()) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'] =  $validator->errors();
                return \Response::json($response, $statusCode);
            }


            $email_exists_in_students = Student::where('student_email', $request['staff_email'])->first();
            if(!empty($email_exists_in_students)){
                $statusCode = Response::HTTP_BAD_REQUEST;
                $err_msg = 'The StaffEmail already used by student';
                $response['errors'][] = $err_msg;
                $this->addLog(array(
                    "log_type_id" => LogType::ERROR,
                    "message"     => $err_msg,
                    "status_code" => $statusCode
                ));

                return \Response::json($response, $statusCode);
            }


            $space_contain = str_contains($validation_data['staff_code'], ' ');
            $comma_contain = str_contains($validation_data['staff_code'], ',');

            if ($space_contain || $comma_contain) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $err_msg = $validation_data['staff_code'] . ' contains space or comma.';
                $response['errors'][] =  $err_msg;
                $this->addLog(array(
                    "log_type_id" => LogType::ERROR,
                    "message"     => $err_msg,
                    "status_code" => $statusCode
                ));

                return $response;
            }



            $staff->staff_code = $request['staff_code'];
            $staff->staff_name = $request['staff_name'];
            if(!empty($staff->user)){
                $staff->user->username = $request['staff_code'];
                $staff->user->email = $request['staff_email'];
            }
            if(isset($request['staff_pin']) && !empty($request['staff_pin']) ){
                $staff->staff_pin_type = 1;
                if(!empty($staff->user)){
                    $staff->user->password = Hash::make($request['staff_pin']);
                }

            }
//            $staff->staff_email = $request['staff_email'];
            $original = $staff->getOriginal();
            $changes = $staff->isDirty() ? $staff->getDirty() : false;

            if($staff->save()){
                $staff->user->save();
                $response['status'] = true;
                $log_msg = 'Staff "'.$staff->staff_name.'-'.$staff->staff_code.'" details has been changed<br>';
                if($changes) {
                    foreach ($changes as $name => $value) {

                        $log_msg .= $this->get_log_msg($name,$value,$original[$name]);
                    }
                }
                if($old_staff_email != $request['staff_email']) {
                    $log_msg .= 'Staff email changed from "'.$old_staff_email.'" to "'.$request['staff_email'].'"<br>';
                }
                if(isset($request['staff_pin']) && !empty($request['staff_pin'])) {
                    $log_msg .= 'Staff pin has been changed';
                }
                if(empty($log_msg)){
                    $log_msg = request()->path();
                }
                $this->addLog(array(
                    "log_type_id" => LogType::INFO,
                    "message"     => $log_msg,
                    "status_code" => Response::HTTP_OK,
                    "show_status" => ActionLog::SHOW
                ));
            }else{
                $statusCode = Response::HTTP_BAD_REQUEST;
                $er_msg = "Oops... Something went wrong.";
                $response['errors'][] = $er_msg;
                $this->addLog(array(
                    "log_type_id" => LogType::ERROR,
                    "message"     => $er_msg,
                    "status_code" => $statusCode
                ));
            }
        }catch (\Exception $e){
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message"     => $e->getMessage(),
                "status_code" => $statusCode
            ));
        }finally{
            return \Response::json($response, $statusCode);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param  Request  $request
     * @return Response
     */
    public function destroyItems(Request $request) {

        $response['status'] = false;
        $removed_count = 0;
        try {
            $statusCode = Response::HTTP_OK;
            $deleted_one_time = false;
            if (!empty($request['ids']) && is_array($request['ids'])) {
                $log_msg = '';
                foreach ($request['ids'] as $id) {
                    $item = Staff::where('staff.id', '=', $id)->with('user')
                        ->leftJoin('user_role', function ($join) {
                            $join->on('staff.user_id', '=', 'user_role.user_id');
                            $join->where('user_role.role_id', '=', Role::COORDINATOR);
                        })->first(['staff.*', 'user_role.role_id as is_coordinator']);
                    // if staff have
                    $log_msg .= '"'.$item->staff_code.'/'.$item->staff_name.'", ';
                    if ($item->is_coordinator > 0) {
                        $statusCode = Response::HTTP_BAD_REQUEST;
                        $response['errors'][] = $item->staff_name . ' is Coordintaor';
                        $this->addLog(array(
                            "log_type_id" => LogType::ERROR,
                            "message" => 'Try to remove coordinator',
                            "status_code" => $statusCode
                        ));

                        return $response;
                    } else {
                        if (!empty($item)) {
                            $removed_code = $item->staff_code;
                            if (User::find($item->user_id)->delete()) {
                                $removed_count ++;
                                $deleted_one_time = true;
                            }
                        }
                    }
                }
            }else{
                $response['errors'][] = 'The ids are required';
            }
            if($deleted_one_time){
                $log_msg = !empty($log_msg) ? substr($log_msg,0,-2) : '';
                $log_msg = 'Staff(s) has been deleted: '.$log_msg;
                $response['status'] = true;
                $this->addLog(array(
                    "log_type_id" => LogType::INFO,
                    "message"     => $log_msg.'. Total count of deleted records is '.$removed_count,
                    "status_code" => $statusCode,
                    "show_status" => ActionLog::SHOW
                ));
            }
        }catch (\Exception $e){
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message"     => $e->getMessage(),
                "status_code" => $statusCode
            ));
        }finally{
            return \Response::json($response, $statusCode);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $role_id
     * @return Response
     */
    public function getAllStaffByRole($role_id) {
        return Role::where('id', $role_id)->with(array('users'=>function($query){
            $query->join('staff', 'staff.user_id', '=', 'users.id')
                ->get(['staff.*']);
        }))->get();
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function getAllStaff() {
        return Role::where('id', Role::STAFF)->with(array('users'=>function($query){
            $query->join('staff', 'staff.user_id', '=', 'users.id')
                ->get([
                    'users.username',
                    'staff.*'
                ]);
        }))->get();
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function get_staff() {
        return Role::where('id', Role::STAFF)->with(array('users'=>function($query){
            $query->with('staff_item');
        }))->first();
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function getAllHolasWithSelectionMatrices($year_level_id) {

        $result = Role::with(array('users'=>function($query) use($year_level_id) {
            $query->join('staff', function ($join){
                $join->on('users.id', '=', 'staff.user_id');
            })->join('selection_matrix_holas', function ($join){
                $join->on('users.id', '=', 'selection_matrix_holas.user_id');
            })->join('selection_matrices', function ($join) use($year_level_id){
                $join->on('selection_matrix_holas.selection_matrix_id', '=', 'selection_matrices.id');
                $join->where('selection_matrices.year_level_id', '=', $year_level_id);
            })->get(
                [
                    'users.username as staff_code',
                    'staff.staff_name',
                    'staff.id as staff_id',
                    'selection_matrices.*',
                    'selection_matrix_holas.*'
                ]);
        }))->where('roles.id', '=', Role::HOLA)->first();

        return $result;
    }

    /**
     * Display the specified resource.
     * @param  Request  $request
     * @return Response
     */
    public function getAllStaffByRoleWithStudents(Request  $request) {

        $response['result'] = false;
        $response['errors'] = [];
        try{
            $statusCode = Response::HTTP_OK;

            $validate_rules = array(
                'staff_role'   => 'required|integer',
                'year_level_id'   => 'required|integer'
            );
            $validator = Validator::make($request->all(), $validate_rules);
            if ($validator->fails()) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] =  $validator->errors();
                return \Response::json($response, $statusCode);
            }
            if( $request['staff_role'] != Role::HOMETEACHER
                && $request['staff_role'] != Role::COURSECOUNSELLOR
                && $request['staff_role'] != Role::HOLA
            ){
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] =  "Invalid Staff Role";
                return \Response::json($response, $statusCode);
            }

            $year_level_id = $request['year_level_id'];

            $response['result'] = Role::with(array('users'=>function($query) use($year_level_id) {
                $query->join('staff', function ($join){
                    $join->on('users.id', '=', 'staff.user_id');
                })->join('student_staff', function ($join){
                    $join->on('users.id', '=', 'student_staff.staff_user_id');
                })->join('students', function ($join) use($year_level_id){
                    $join->on('student_staff.student_user_id', '=', 'students.user_id');
                })->where('students.year_level_id', '=', $year_level_id)->get(
                    [
                        'users.username as staff_code',
                        'staff.staff_name',
                        'staff.id as staff_id',
                        'students.id as student_user_id',
                        'students.student_name',
                        'student_staff.*'
                    ]);
            }))->where('roles.id', '=', $request['staff_role'])->first();

        }catch (\Exception $e){
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message"     => $e->getMessage(),
                "status_code" => $statusCode
            ));
        }finally{
            return \Response::json($response, $statusCode);
        }
    }


    /**
     * Display the specified resource.
     * @param  Request  $request
     * @return Response
     */
    public function getAllStaffByRoleWithHomeGroups(Request  $request) {

        $response['result'] = false;
        $response['errors'] = [];
        try{
            $statusCode = Response::HTTP_OK;

            $validate_rules = array(
                'staff_role'   => 'required|integer',
                'year_level_id'   => 'required|integer'
            );
            $validator = Validator::make($request->all(), $validate_rules);
            if ($validator->fails()) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] =  $validator->errors();
                return \Response::json($response, $statusCode);
            }
            if( $request['staff_role'] != Role::HOMETEACHER
                && $request['staff_role'] != Role::COURSECOUNSELLOR
                && $request['staff_role'] != Role::HOLA
            ){
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] =  "Invalid Staff Role";
                return \Response::json($response, $statusCode);
            }

            $year_level_id = $request['year_level_id'];


            $response['result'] = Role::with(array('users'=>function($query) use($year_level_id) {
                $query->join('staff', function ($join){
                    $join->on('users.id', '=', 'staff.user_id');
                })->join('staff_homegroups', function ($join){
                    $join->on('users.id', '=', 'staff_homegroups.staff_user_id');
                })->join('home_groups', function ($join){
                    $join->on('staff_homegroups.home_group_id', '=', 'home_groups.id');
                })->where('home_groups.year_level_id', '=', $year_level_id)
                    ->get(
                    [
                        'users.username as staff_code',
                        'users.id as staff_user_id',
                        'staff.staff_name',
                        'staff.id as staff_id',
                        'home_groups.*',
                    ]);
            }))->where('roles.id', '=', $request['staff_role'])->first();

        }catch (\Exception $e){
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message"     => $e->getMessage(),
                "status_code" => $statusCode
            ));
        }finally{
            return \Response::json($response, $statusCode);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function assign_home_group(Request $request) {

        $response['status'] = false;
        try{
            $statusCode = Response::HTTP_OK;

            $validate_rules = array(
                'home_group_id'   => 'required|integer'
            );
            if(isset($request['staff_user_id'])
                && isset($request['no_required'])
                && $request['no_required'] == "1"){
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] =  'Cannot select "Home Teacher" and "No Home Teacher Required" together. Please select only one of them.';
                return \Response::json($response, $statusCode);
            }
            if(!isset($request['staff_user_id'])
                && !isset($request['no_required']) ){
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] =  'Please choose "Home Teacher" or "No Home Teacher Required"';
                return \Response::json($response, $statusCode);
            }

            $validator = Validator::make($request->all(), $validate_rules);

            if ($validator->fails()) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] =  $validator->errors();
                return \Response::json($response, $statusCode);
            }

            $response['shg_id'] = $shg_id = isset($request['shg_id']) ? $request['shg_id'] : null;

            $staff_homegroup = StaffHomeGroup::find($shg_id);
            if(empty($staff_homegroup)){
                $home_group = HomeGroup::find($request['home_group_id']);
                if(!empty($home_group)){
                    $staff_homegroup = new StaffHomeGroup();
                    $staff_homegroup->staff_user_id = $request['staff_user_id'];
                    $staff_homegroup->home_group_id = isset($request['home_group_id']) && !empty($request['home_group_id']) ? $request['home_group_id'] : null;
                    $staff_homegroup->no_required = isset($request['no_required']) ? $request['no_required'] : "0";
                }else{
                    $statusCode = Response::HTTP_BAD_REQUEST;
                    $er_msg = "Invalid Home Group for assign";
                    $response['errors'][] = $er_msg;
                    $this->addLog(array(
                        "log_type_id" => LogType::ERROR,
                        "message"     => $er_msg,
                        "status_code" => $statusCode
                    ));
                }
            }else{
                if(isset($request['staff_user_id']) && !empty($request['staff_user_id'])){
                    $staff_homegroup->staff_user_id =  $request['staff_user_id'];
                    $staff_homegroup->no_required =  "0";
                }else if(isset($request['no_required'])){
                    $staff_homegroup->no_required =  $request['no_required'];
                    $staff_homegroup->staff_user_id =  null;
                }
            }

            if(!empty($staff_homegroup) && $staff_homegroup->save()){
                $response['shg_id'] = $staff_homegroup->id;
                if(isset($request['staff_user_id']) && !empty($request['staff_user_id'])){
                    $role_home_teacher = Role::where("name", "home_teacher")->first();
                    $user = User::find($request['staff_user_id']);
                    if(!$user->hasAnyRole('home_teacher')){ //TODO from db name
                        $user->roles()->attach($role_home_teacher);
                    }
                }
                $response['status'] = true;
                $response['sd'] = 'asdasa';
                $response['log_msg'] = 'Staff "'.$staff_homegroup->staff_user->staff_code.'-'.$staff_homegroup->staff_user->staff_name.'" assigned to Home Group"'.$staff_homegroup->home_group->code.'"';
                $this->addLog(array(
                    "log_type_id" => LogType::INFO,
                    "message"     => 'Staff "'.$staff_homegroup->staff_user->staff_code.'-'.$staff_homegroup->staff_user->staff_name.'" assigned to Home Group"'.$staff_homegroup->home_group->code.'"',
                    "status_code" => Response::HTTP_OK,
                    "show_status" => ActionLog::SHOW
                ));
            }else{
                $statusCode = Response::HTTP_BAD_REQUEST;
                $er_msg = "Oops... Something went wrong. Please try again later.";
                $response['errors'][] = $er_msg;
                $this->addLog(array(
                    "log_type_id" => LogType::ERROR,
                    "message"     => $er_msg,
                    "status_code" => $statusCode
                ));
            }
        }catch (\Exception $e){
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message"     => $e->getMessage(),
                "status_code" => $statusCode
            ));
        }finally{
            return \Response::json($response, $statusCode);
        }
    }


    public function get_all_assigned_course_counsellors(){
        $response['status'] = false;

        try{
            $statusCode = Response::HTTP_OK;

                $response['status'] = true;
                $response['result'] = Staff::join('student_staff', 'student_staff.staff_user_id', '=', 'staff.user_id')
                    ->join('students', 'students.user_id', '=', 'student_staff.student_user_id')
                    ->join('year_levels', 'students.year_level_id', '=', 'year_levels.id')
                    ->distinct()
                    ->get(['staff.id', 'staff.user_id', 'staff.staff_name', 'year_levels.year', 'year_levels.title', 'students.year_level_id']);

                $this->addLog(array(
                    "log_type_id" => LogType::INFO,
                    "message"     => "Getting all assigned course counsellors",
                    "status_code" => Response::HTTP_OK
                ));

        }catch (\Exception $e){
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message"     => $e->getMessage(),
                "status_code" => $statusCode
            ));
        }finally{
            return \Response::json($response, $statusCode);
        }
    }
    protected function get_log_msg($name,$value,$original=null){
        $log_msg = '';
        switch ($name){
            case 'staff_code':
                if($original){
                    $log_msg = 'Staff code changed from "'.$original.'" to "'.$value.'"<br>';
                } else {
                    $log_msg = 'Staff code is: "'.$value.'"<br>';
                }
                break;
            case 'staff_name':
                if($original){
                    $log_msg = 'Staff name changed from "'.$original.'" to "'.$value.'"<br>';
                } else {
                    $log_msg = 'Staff name is: "'.$value.'"<br>';
                }
                break;
            case 'staff_email':
                if($original){
                    $log_msg = 'Staff Email changed from "'.$original.'" to "'.$value.'"<br>';
                } else {
                    $log_msg = 'Staff Email is: "'.$value.'"<br>';
                }
                break;
            case 'staff_pin':
                if($original){
                    $log_msg = 'Staff pin has been changed<br>';
                } else {
                    $log_msg = 'Added staff pin<br>';
                }
                break;

        }
        return $log_msg;
    }
}
