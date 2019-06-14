<?php


namespace App\Http\Controllers;

use App\ActionLog;
use App\Staff;
use App\StudentAppointments;
use App\UserRole;
use Illuminate\Support\Facades\DB;
use Mail;
use App\Admin;
use App\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Setting;
use App\User;
use App\ResetPassword;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

use LucaDegasperi\OAuth2Server\Authorizer;
use App\LogType;

class SettingsController extends BaseAdminController
{

    public function __construct(Authorizer $authorizer)
    {
        parent::__construct($authorizer);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {

        $response['status'] = false;
        if (isset($request['name'])
            && in_array($request['name'], Setting::$only_super_admin)
            && !$request->logged_user->hasRole(Role::$role_name[Role::SSOADMIN])) {

            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'] = "Access denied for this action.";
            return \Response::json($response, $statusCode);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:settings'
        ]);

        if ($validator->fails()) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $validator->errors();
            return \Response::json($response, $statusCode);
        }

        try {
            $statusCode = Response::HTTP_CREATED;
            $setting = new Setting;

            if ($request->input('name') == 'appointment_way') {
                $appointments = StudentAppointments::whereNotNull('student_user_id')->get();
                if (empty($appointments[0])) {
                    StudentAppointments::truncate();
                    $this->addLog(array(
                        "log_type_id" => LogType::INFO,
                        "message" => "All appointments deleted (because changed appointment setting)",
                        "status_code" => Response::HTTP_OK
                    ));
                } else {
                    $statusCode = Response::HTTP_BAD_REQUEST;
                    $response['errors'] = array('You have booked apointments, you cannot change the setting');
                    return \Response::json($response, $statusCode);
                }
            }

            $setting->name = $request->input('name');
            $setting->value = $request->input('value');
            if ($setting->save()) {
                $response['status'] = true;
                $response['inserted_id'] = $setting->id;
                if (!empty($request->input('azure_image_url_path')) && ($setting->name == 'login_background' || $setting->name == 'school_banner' || $setting->name == 'school_logo')) {
                    $url = $request->input('azure_image_url_path') . "images/school/" . $request->input('school_id') . "/" . $setting->value;
                    $log_msg = $this->get_log_msg($setting->name, $url);
                } else {
                    $log_msg = $this->get_log_msg($setting->name, $setting->value);
                }
                $response['log'] = $log_msg;
                $this->addLog(array(
                    "log_type_id" => LogType::INFO,
                    "message" => $log_msg,
                    "status_code" => $statusCode,
                    "show_status" => ActionLog::SHOW
                ));

            }

        } catch (\Exception $e) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message" => $response['errors'],
                "status_code" => $statusCode
            ));
        } finally {
            return \Response::json($response, $statusCode);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @return Response
     */
    public function change_school_status(Request $request)
    {

        $response['status'] = false;

        if (isset($request['status'])
            && in_array($request['status'], Setting::$only_super_admin)
            && !$request->logged_user->hasRole(Role::$role_name[Role::SSOADMIN])) {

            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'] = "Access denied for this action.";
            return \Response::json($response, $statusCode);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|integer|between:0,1'
        ]);

        if ($validator->fails()) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $validator->errors();
            return \Response::json($response, $statusCode);
        }

        try {
            $statusCode = Response::HTTP_CREATED;
            $setting_result = Setting::where('name', 'school_status')->first();
            if (empty($setting_result)) {
                $setting = new Setting;

                $setting->name = 'school_status';
                $setting->value = $request->input('status');
                if ($setting->save()) {
                    $response['status'] = true;
                    $response['inserted_id'] = $setting->id;
                }
            } else {
                $response['status'] = Setting::where('id', $setting_result->id)->update(['value' => $request->input('status')]);
            }
            $message_active_inactive = $request->input('status') == 1 ? 'from Inactive to Active' : 'From Active to inactive';
            $this->addLog(array(
                "message" => "Success update school status " . $message_active_inactive . " .",
                "status_code" => $statusCode
            ));
        } catch (\Exception $e) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message" => $response['errors'],
                "status_code" => $statusCode
            ));
        } finally {
            return \Response::json($response, $statusCode);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {


        $this->addLog(array(
            "log_type_id" => LogType::INFO,
            "message" => "Success",
            "status_code" => Response::HTTP_OK
        ));

        return Setting::find($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     * @param  int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {

        $response['status'] = false;

        if (isset($request['name'])
            && in_array($request['name'], Setting::$only_super_admin)
            && !$request->logged_user->hasRole(Role::$role_name[Role::SSOADMIN])) {

            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'] = "Access denied for this action.";
            return \Response::json($response, $statusCode);
        }

        try {

            $statusCode = Response::HTTP_OK;
            $setting = Setting::find($id);
            $old_name = $setting->name;
            $old_value = $setting->value;
            $unique_field = '|unique:settings';
            $validate_rules = array(
                'name' => 'required',
            );
            if ($old_name != $request->input('name')) {
                $validate_rules = array(
                    'name' => 'required' . $unique_field,
                );
            }
            if ($request->input('name') == 'appointment_way') {
                $appointment = StudentAppointments::whereNotNull('student_user_id')->first(['id']);
                if (empty($appointment)) {
                    StudentAppointments::truncate();
                    $this->addLog(array(
                        "log_type_id" => LogType::INFO,
                        "message" => "All appointments deleted (because changed appointment setting)",
                        "status_code" => Response::HTTP_OK
                    ));
                } else {
                    $statusCode = Response::HTTP_BAD_REQUEST;
                    $response['errors'] = array('You have booked apointments, you cannot change the setting');
                    return \Response::json($response, $statusCode);
                }
            }
            $setting->name = $request->input('name');
            $setting->value = $request->input('value');


            $validator = Validator::make($request->all(), $validate_rules);

            if ($validator->fails()) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'] = $validator->errors();
                return \Response::json($response, $statusCode);
            }
            if ($old_value == $setting->value) {
                $response['status'] = true;
            } else if ($setting->save()) {
                $response['status'] = true;
                if (!empty($request->input('azure_image_url_path')) && ($setting->name == 'login_background' || $setting->name == 'school_banner' || $setting->name == 'school_logo')) {
                    $url = $request->input('azure_image_url_path') . "images/school/" . $request->input('school_id') . "/" . $setting->value;
                    $old_value = $request->input('azure_image_url_path') . "images/school/" . $request->input('school_id') . "/" . $old_value;
                    $log_msg = $this->get_log_msg($setting->name, $url, $old_value);
                } else {
                    $log_msg = $this->get_log_msg($setting->name, $setting->value, $old_value);
                }
                $response['url'] = $log_msg;

                $this->addLog(array(
                    "log_type_id" => LogType::INFO,
                    "message" => $log_msg,
                    "status_code" => $statusCode,
                    "show_status" => ActionLog::SHOW
                ));
            }
        } catch (\Exception $e) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message" => $response['errors'],
                "status_code" => $statusCode
            ));
        } finally {
            return \Response::json($response, $statusCode);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Request $request
     * @return Response
     */
    public function destroy(Request $request)
    {

        $response['status'] = false;

        try {
            $statusCode = Response::HTTP_OK;
            $setting = Setting::find($request->input('id'));

            if ($setting->delete()) {
                $response['status'] = true;
            }
            $this->addLog(array(
                "log_type_id" => LogType::INFO,
                "message" => "Success",
                "status_code" => $statusCode
            ));
        } catch (\Exception $e) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message" => $response['errors'],
                "status_code" => $statusCode
            ));
        } finally {
            return \Response::json($response, $statusCode);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string $field_name
     * @param  string $field_value
     * @return Response
     */
    public function getByField($field_name, $field_value)
    {

        $response['result'] = false;
        if (!Schema::hasColumn('settings', $field_name)) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['error'] = "Invalid column name";
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message" => $response['error'],
                "status_code" => $statusCode
            ));
            return \Response::json($response, $statusCode);
        }


        try {
            $statusCode = Response::HTTP_OK;
            $response['result'] = Setting::where($field_name, $field_value)->first();
            $this->addLog(array(
                "log_type_id" => LogType::INFO,
                "message" => "Success",
                "status_code" => $statusCode
            ));
        } catch (\Exception $e) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['error'] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message" => $response['errors'],
                "status_code" => $statusCode
            ));
        } finally {
            return \Response::json($response, $statusCode);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  Request $request
     * @return Response
     */
    public function getOptions(Request $request)
    {

        $response['result'] = null;

        try {
            $statusCode = Response::HTTP_OK;
            $fields = $request->input('fields');
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $field = trim($field);
                    $response['result'][$field] = Setting::where("name", $field)->first();
                }
            }
            $this->addLog(array(
                "log_type_id" => LogType::INFO,
                "message" => "Success",
                "status_code" => $statusCode
            ));
        } catch (\Exception $e) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['error'] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::INFO,
                "message" => $response['error'],
                "status_code" => $statusCode
            ));
        } finally {
            return \Response::json($response, $statusCode);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     * @return Response
     */
    public function addUpdateBulk(Request $request)
    {

        $response['status'] = false;
        $response['errors'] = array();
        $saved = false;
        $req = $request['add_update_data'];
        if (!empty($req)) {
            foreach ($req as $item) {

                if (!isset($item['name'])) {
                    $response['errors'][] = "Something went wrong";
                    continue;
                }
                try {
                    $setting = Setting::where('name', $item['name'])->first();
                    if (!empty($setting)) {
                        $setting->value = isset($item['value']) ? $item['value'] : null;
                    } else {
                        $setting = new Setting();
                        $setting->name = $item['name'];
                        $setting->value = isset($item['value']) ? $item['value'] : null;
                    }
                    if ($setting->save()) {
                        $saved = true;
                    }
                } catch (\Exception $e) {
                    $response['errors'][] = $e->getMessage();
                }

            }
        }
        if ($saved) {
            $statusCode = Response::HTTP_OK;
            $this->addLog(array(
                "log_type_id" => LogType::INFO,
                "message" => "Success",
                "status_code" => Response::HTTP_OK
            ));
        } else {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message" => "Something went wrong",
                "status_code" => $statusCode
            ));
        }
        return \Response::json($response, $statusCode);
    }

    /**
     * Update school settings.
     *
     * @param  Request $request
     * @return Response
     */
    public function update_school_settings(Request $request)
    {
        $response['status'] = false;
        $response['errors'] = [];
        try {
            $statusCode = Response::HTTP_OK;
            $school_name = $request['school_name'];
            $time_zone = $request['time_zone'];
            $payment_gateway_status = $request['payment_gateway_status'];
            $currency_type = $request['currency_type'];
            $administered_from = $request['administered_from'];

            if (!empty($school_name)) {
                $setting = Setting::firstOrNew(array(
                    'name' => 'school_name'
                ));
                $setting->value = $school_name;
                if ($setting->save()) {
                    $this->addLog(array(
                        "message" => "Success set school name option of settings.",
                        "status_code" => $statusCode
                    ));
                } else {
                    $this->addLog(array(
                        "message" => "Error - can`t save school name option of settings",
                        "status_code" => $statusCode
                    ));
                    $response['errors'][] = 'Error - can`t save school name option of settings';
                }
            }
            if (!empty($time_zone)) {
                $setting = Setting::firstOrNew(array(
                    'name' => 'time_zone'
                ));
                $setting->value = $time_zone;
                if ($setting->save()) {
                    $this->addLog(array(
                        "message" => "Success set time zone option of settings.",
                        "status_code" => $statusCode
                    ));
                } else {
                    $this->addLog(array(
                        "message" => "Error - can`t set time zone option of settings.",
                        "status_code" => $statusCode
                    ));
                    $response['errors'][] = 'Error - can`t set time zone option of settings.';
                }
            }
            if ($payment_gateway_status == '0' || $payment_gateway_status == '1') {
                $setting = Setting::firstOrNew(array(
                    'name' => 'payment_gateway_status'
                ));
                $setting->value = $payment_gateway_status;
                if ($setting->save()) {
                    $this->addLog(array(
                        "message" => "Success set payment gateway status option of settings.",
                        "status_code" => $statusCode
                    ));
                } else {
                    $this->addLog(array(
                        "message" => "Error - can`t set payment gateway status option of settings.",
                        "status_code" => $statusCode
                    ));
                    $response['errors'][] = 'Error - can`t set payment gateway status option of settings.';
                }
            }
            if ($currency_type) {
                $setting = Setting::firstOrNew(array(
                    'name' => 'currency_type'
                ));
                $setting->value = $currency_type;
                if ($setting->save()) {
                    $this->addLog(array(
                        "message" => "Success set currency type  option of settings.",
                        "status_code" => $statusCode
                    ));
                } else {
                    $this->addLog(array(
                        "message" => "Error - can`t set currency type  option of settings.",
                        "status_code" => $statusCode
                    ));
                    $response['errors'][] = 'Error - can`t set currency type  option of settings.';
                }
            }
            if (!empty($administered_from)) {
                $setting = Setting::firstOrNew(array(
                    'name' => 'administered_from'
                ));
                $setting->value = $administered_from;
                if ($setting->save()) {
                    $this->addLog(array(
                        "message" => "Success set administered from option of settings.",
                        "status_code" => $statusCode
                    ));
                } else {
                    $this->addLog(array(
                        "message" => "Error - can`t set administered from option of settings.",
                        "status_code" => $statusCode
                    ));
                    $response['errors'][] = 'Error - can`t set administered from option of settings.';
                }
            }
            if (empty($response['errors'])) {
                $response['status'] = true;
            } else {
                $response['status'] = false;
            }
        } catch (\Exception $e) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message" => $e->getMessage(),
                "status_code" => $statusCode
            ));
        } finally {
            return \Response::json($response, $statusCode);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     * @return Response
     */
    public function add_coordinators(Request $request)
    {

        $response['status'] = false;
        try {
            $statusCode = Response::HTTP_OK;
            $saved_one_time = false;
            $coordinators_data = $request['coordinators_data'];

            $role_co_ordinator = Role::where("name", Role::$role_name[Role::COORDINATOR])->first();

            $school_logo = 'sso_logo.png';
            $school_code = $request['X_SCHOOL_CODE'];
            $SSO_CLIENT_URL = env('SSO_CLIENT_URL');
            $school_name = $request['school_name'];

            if (empty($coordinators_data)) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] = 'coordinators_data is empty';
                return \Response::json($response, $statusCode);
            }
            $user_names = [];


            foreach ($coordinators_data as $coordinator) {
                $validation_data['email'] = isset($coordinator['email_address']) ? $coordinator['email_address'] : null;

                $validation_data['first_name'] = isset($coordinator['first_name']) ? $coordinator['first_name'] : null;
                $validation_data['surname'] = isset($coordinator['surname']) ? $coordinator['surname'] : null;
                $validation_data['code'] = isset($coordinator['code']) ? $coordinator['code'] : null;
                $validate_rules = array(
                    'email' => 'required|email',
                    'first_name' => 'required',
                    'surname' => 'required',
                    'code' => 'required'
                );
                $log_msg = '';

                $validator = Validator::make($validation_data, $validate_rules);

                if ($validator->fails()) {
                    $statusCode = Response::HTTP_BAD_REQUEST;
                    $response['email_errors'][] = $validation_data['email'];
                    $response['errors'][] = $validator->errors();
                    $this->addLog(array(
                        "log_type_id" => LogType::ERROR,
                        "message" => $validator->errors(),
                        "status_code" => $statusCode
                    ));
                    continue;
                }

                $check_username = User::where([
                    ['email', '!=', $coordinator['email_address']],
                    ['username', '=', $coordinator['email_address']]
                ])->first(['users.id']);
                $check_student_email = User::join('students', function ($join) use ($coordinator) {
                    $join->on('users.id', '=', 'students.user_id');
                    $join->where('students.student_email', '=', $coordinator['email_address']);
                })->first(['users.id']);
                if (!is_null($check_username) && isset($check_username->id)) {
                    $response['errors'][] = 'Username ' . $coordinator['email_address'] . ' has already been taken';
                    return $response;
                }
                if (!is_null($check_student_email) && isset($check_student_email->id)) {
                    $response['errors'][] = 'Email ' . $coordinator['email_address'] . ' has already been taken for student';
                    return $response;
                }
                try {

                    $username = $validation_data['email'];
                    while (in_array($username, $user_names)) {
                        $username = strtolower($validation_data['surname']) . rand(4);
                    }
                    $user = User::where('email', '=', $coordinator['email_address'])
                        ->first();
                    if (!empty($user) && $user->hasAnyRole([
                            Role::$role_name[Role::STUDENT],
                            Role::$role_name[Role::COORDINATOR]
                        ])) {
                        $user = null;
                    }

                    $user = (!empty($user)) ? $user : null;
                    // new email dont much with staff(no coordinator) emails
                    if (!is_null($user)) {

                        // new email much with staff email (no coordinator)
                        $user_co_ordinator = User::where('id', '=', $user->id)->first();
                        $staff = Staff::where('user_id', '=', $user->id)->first();
                        $log_msg = 'Added coordinator role to staff "' . $staff->staff_code . '-' . $staff->staff_name . ' "<br>';


                    } else {
                        $user_co_ordinator = new User();
                        $staff = new Staff();
                        $log_msg = 'Added new coordinator with username "' . $validation_data['email'] . '"';
                    }

                    $transaction = false;
                    // Begin transaction for saving user
                    DB::beginTransaction();
                    try {
                        $user_co_ordinator->username = $validation_data['email'];
                        $user_co_ordinator->email = $validation_data['email'];
                        $password = str_random(8);
                        $user_co_ordinator->password = Hash::make($password);
                        $user_co_ordinator->save();
                        $response['coords'][] = $user_co_ordinator;
                        array_push($user_names, $validation_data['email']);
                        $user_co_ordinator->roles()->attach($role_co_ordinator);

                        $staff->user_id = $user_co_ordinator->id;
                        $staff->staff_code = $validation_data['email'];
                        $staff->staff_name = $validation_data['first_name'] . ' ' . $validation_data['surname'];
                        $staff->staff_code = $validation_data['email'];

                        $admin = new Admin();
                        $admin->email = $user_co_ordinator->email;
                        $admin->first_name = $validation_data['first_name'];
                        $admin->surname = $validation_data['surname'];
                        $admin->user_id = $user_co_ordinator->id;
                        $response['admin'][] = $admin;
                        $staff->save();
                        $admin->save();
                        $transaction = true;
                        DB::commit();
                        // all good
                    } catch (\Exception $e) {
                        $response['terror'] = $response['errors'] = $e->getMessage();
                        DB::rollback();
                        // something went wrong
                    }

                    if ($transaction) {
                        if (!$user_co_ordinator->hasAnyRole(Role::$role_name[Role::STAFF])) {
                            $role_staff = Role::where("name", Role::$role_name[Role::STAFF])->first();
                            $user_co_ordinator->roles()->attach($role_staff);

                        }
                        $resetPassword = new ResetPassword();

                        $resetPassword->user_id = $user_co_ordinator->id;
                        $resetPassword->code = $coordinator['code'];
                        $resetPassword->save();

                        $setting = Setting::where('name', '=', 'administered_from')->first();
                        $administered_from = !empty($setting) && isset($setting->value) ? $setting->value : 'Australia';
                        $suffix = $administered_from == 'UK/Europe' ? 'UK' : 'AU';
                        $admin->from_email = env('EMAIL_SUPORT_FROM_' . $suffix);
                        $admin->from_name = 'SSO';
                        $saved_one_time = true;
                        if (Mail::send('emails.send_login_details_email', [
                            'co_ordinator' => $user_co_ordinator,
                            'first_name' => $admin->first_name,
                            'SSO_CLIENT_URL' => $SSO_CLIENT_URL,
                            'school_logo' => $SSO_CLIENT_URL . '/public/images/' . $school_logo,
                            'school_name' => $school_name,
                            'code' => $coordinator['code'],
                            'school_code' => $school_code,
                            'suport_email' => env('EMAIL_SUPORT_' . $suffix),
                            'suport_phone' => env('EMAIL_SUPORT_PHONE_' . $suffix),
                            'administered_from' => $administered_from
                        ], function ($message) use ($admin) {
                            //$message->from set in the config/mail.php file
                            $message->from($admin->from_email, $admin->from_name);
                            $message->to($admin->email, $admin->first_name)->subject('Invitation to school');
                        })
                        ) {
                            //log about sent email here
                            try {
                                $log_send_message = "Sent email to <strong>" . $admin->email . " - " . $admin->first_name . "</strong>";
                                $log_send_message .= "<br> Your administrative access to SSO has been set up.";
                                ActionLog::addBySendEmail($log_send_message);
                            } catch (\Exception $er) {
                            }
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
            if ($saved_one_time) {
                $this->addLog(array(
                    "log_type_id" => LogType::INFO,
                    "message" => $log_msg,
                    "status_code" => Response::HTTP_OK,
                    "show_status" => ActionLog::SHOW
                ));
                $response['status'] = true;
            }
        } catch (\Exception $e) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message" => $e->getMessage(),
                "status_code" => $statusCode
            ));
        } finally {
            return \Response::json($response, $statusCode);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     * @return Response
     */
    public function update_coordinator(Request $request)
    {
        $response['status'] = false;
        try {
            $statusCode = Response::HTTP_OK;
            $coordinator = isset($request['coordinator_data']) ? $request['coordinator_data'] : null;

            if (empty($coordinator)) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] = 'Coordinators data is empty';
                return $response;
            }
            if (!isset($coordinator['email_address']) || !isset($coordinator['original_email'])) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] = 'Oops... Something went wrong with coordinator emails.';
                return $response;
            }

            $validation_data['email'] = isset($coordinator['email_address']) ? $coordinator['email_address'] : null;

            $validation_data['first_name'] = isset($coordinator['first_name']) ? $coordinator['first_name'] : null;
            $validation_data['surname'] = isset($coordinator['surname']) ? $coordinator['surname'] : null;


            $validate_rules = array(
                'email' => 'required|email',
                'first_name' => 'required',
                'surname' => 'required'
            );

            $validator = Validator::make($validation_data, $validate_rules);

            if ($validator->fails()) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['email_errors'][] = $validation_data['email'];
                $response['errors'] = $validator->errors();
                $this->addLog(array(
                    "log_type_id" => LogType::ERROR,
                    "message" => $validator->errors(),
                    "status_code" => $statusCode
                ));
                return $response;
            }

            $check_username = User::where([
                ['email', '!=', $coordinator['email_address']],
                ['username', '=', $coordinator['email_address']]
            ])->first(['users.id']);
            $check_student_email = User::join('students', function ($join) use ($coordinator) {
                $join->on('users.id', '=', 'students.user_id');
                $join->where('students.student_email', '=', $coordinator['email_address']);
            })->first(['users.id']);
            if (!is_null($check_username) && isset($check_username->id)) {
                $response['errors'][] = 'Username ' . $coordinator['email_address'] . ' has already been taken';
                return $response;
            }
            if (!is_null($check_student_email) && isset($check_student_email->id)) {
                $response['errors'][] = 'Email ' . $coordinator['email_address'] . ' has already been taken for student';
                return $response;
            }
            $log_msg = '';
            try {
                $user = User::where('email', '=', $coordinator['email_address'])
                    ->first();
                if (!empty($user) && $user->hasAnyRole([
                        Role::$role_name[Role::STUDENT],
                        Role::$role_name[Role::COORDINATOR]
                    ])) {
                    $user = null;
                }

                $user = (!empty($user)) ? $user : null;
                $response['$user'] = $user;

                // new email dont much with staff(no coordinator) emails
                if (!is_null($user)) {
                    // new email much with staff email (no coordinator)
                    $user_co_ordinator = User::where('id', '=', $user->id)->first();
                    $admin = new Admin();
                    $admin->user_id = $user_co_ordinator->id;

                    // delete old user and assign coordinator role to staff
                    $req = new Request();
                    $req['email'] = $coordinator['original_email'];
                    $this->destroy_coordinator($req);

                    $log_msg = 'Added coordiantor role to staff "' . $validation_data['first_name'] . '-' . $validation_data['surname'] . '" ';
                } else {
                    $response['coord'] = $coordinator;
                    $user_co_ordinator = User::where('email', '=', $coordinator['original_email'])->first();
                    $admin = Admin::where('user_id', '=', $user_co_ordinator->id)->first();
                    $log_msg = 'Added new coordinator "' . $validation_data['email'] . '" ';
                }
                // Begin transaction for saving user
                DB::beginTransaction();
                try {
                    $user_co_ordinator->username = $validation_data['email'];
                    $user_co_ordinator->email = $validation_data['email'];
                    $user_co_ordinator->save();

                    if (!$user_co_ordinator->hasAnyRole(Role::$role_name[Role::COORDINATOR])) {
                        $role_co_ordinator = Role::where("name", Role::$role_name[Role::COORDINATOR])->first();
                        $user_co_ordinator->roles()->attach($role_co_ordinator);

                    }
                    $staff_coordinator = Staff::where('user_id', '=', $user_co_ordinator->id)->first();
                    if (!is_null($staff_coordinator) && ($staff_coordinator->staff_name != ($validation_data['first_name'] . ' ' . $validation_data['surname']) || $staff_coordinator->staff_code != $validation_data['email'])) {
                        if ($staff_coordinator->staff_name != ($validation_data['first_name'] . ' ' . $validation_data['surname'])) {
                            $staff_log_msg = LogType::updateFromTo('Staff name', $staff_coordinator->staff_name, $validation_data['first_name'] . ' ' . $validation_data['surname']);
                        }
                        $staff_coordinator->staff_name = $validation_data['first_name'] . ' ' . $validation_data['surname'];
                        $staff_coordinator->staff_code = $validation_data['email'];
                        $staff_coordinator->save();
                    }
                    if (!is_null($admin) && ($admin->email != $validation_data['email'] || $admin->first_name != $validation_data['first_name'] || $admin->surname != $validation_data['surname'])) {
                        if ($admin->email != $validation_data['email']) {
                            $admin_log_msg = LogType::updateFromTo('email', $admin->email, $validation_data['email']);
                        }
                        if ($admin->first_name != $validation_data['first_name']) {
                            $admin_log_msg = ' ' . LogType::updateFromTo('First name', $admin->first_name, $validation_data['first_name']);
                        }
                        if ($admin->surname != $validation_data['surname']) {
                            $admin_log_msg = ' ' . LogType::updateFromTo('Surname', $admin->surname, $validation_data['surname']);
                        }
                        $admin->email = $validation_data['email'];
                        $admin->first_name = $validation_data['first_name'];
                        $admin->surname = $validation_data['surname'];
                        $admin->save();
                    }
                    DB::commit();
                    // all good
                } catch (\Exception $e) {
                    $response['errors'] = $e->getMessage();
                    DB::rollback();
                    // something went wrong
                }
                $response['transaction'] = true;
                if (!is_null($user_co_ordinator) && empty($response['errors'])) {
                    //  send message when changing email
                    if ($validation_data['email'] != $coordinator['original_email']) {
                        $school_logo = isset($request['school_logo']) ? $request['school_logo'] : env('SSO_CLIENT_URL') . '/public/images/sso_logo.png';
                        $school_code = $request['X_SCHOOL_CODE'];
                        $school_name = isset($request['school_name']) ? $request['school_name'] : '';

                        $setting = Setting::where('name', '=', 'administered_from')->first();
                        $administered_from = !empty($setting) && isset($setting->value) ? $setting->value : 'Australia';
                        $suffix = $administered_from == 'UK/Europe' ? 'UK' : 'AU';
                        $admin->from_email = env('EMAIL_SUPORT_FROM_' . $suffix);
                        $admin->from_name = 'SSO';

                        if (Mail::send('emails.send_coordinator_new_details_email', [
                            'co_ordinator' => $user_co_ordinator,
                            'first_name' => $admin->first_name,
                            'SSO_CLIENT_URL' => env('SSO_CLIENT_URL'),
                            'school_logo' => env('SSO_CLIENT_URL') . '/public/images/' . $school_logo,
                            'school_name' => $school_name,
                            'school_code' => $school_code,
                            'suport_email' => env('EMAIL_SUPORT_' . $suffix),
                            'suport_phone' => env('EMAIL_SUPORT_PHONE_' . $suffix),
                            'administered_from' => $administered_from
                        ], function ($message) use ($admin) {
                            //$message->from set in the config/mail.php file

                            $message->from($admin->from_email, $admin->from_name);
                            $message->to($admin->email, $admin->first_name)->subject('Coordinator administrative access to SSO has been changed');
                        })
                        ) {
                            //log about sent email here
                            try {
                                $log_send_message = "Sent email to <strong>" . $admin->email . " - " . $admin->first_name . "</strong>";
                                $log_send_message .= "<br> Your administrative access to SSO has been changed.";
                                ActionLog::addBySendEmail($log_send_message);
                            } catch (\Exception $er) {
                            }
                        }
                    }
                    $statusCode = Response::HTTP_OK;
                    $this->addLog(array(
                        "log_type_id" => LogType::INFO,
                        "message" => $log_msg,
                        "status_code" => $statusCode,
                        "show_status" => ActionLog::SHOW
                    ));
                    $response['status'] = true;
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
        } catch (\Exception $e) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message" => $e->getMessage(),
                "status_code" => $statusCode
            ));
        } finally {
            return \Response::json($response, $statusCode);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     * @return Response
     */
    public function destroy_coordinator(Request $request)
    {
        $response['status'] = false;
        $statusCode = Response::HTTP_BAD_REQUEST;
        try {
            if (isset($request['email'])) {
                $email = $request['email'];
                $user = User::where([
                    ['users.email', '=', $email],
                    ['users.id', '!=', 1]
                ])->join('user_role', function ($join) {
                    $join->on('users.id', '=', 'user_role.user_id');
                })->join('roles', function ($join) {
                    $join->on('user_role.role_id', '=', 'roles.id');
                    $join->where('roles.id', '!=', Role::STUDENT);
                    $join->where('roles.id', '!=', Role::COORDINATOR);
                    $join->where('roles.id', '!=', Role::ORGANISATION_ADMIN);
                    $join->where('roles.id', '!=', Role::STAFF);
                })->first([
                    'users.id'
                ]);
                $user_id = isset($user->id) ? $user->id : null;
                if (is_null($user_id)) {
                    User::where([
                        ['email', '=', $email],
                        ['id', '!=', 1]
                    ])->delete();
                } else {
                    UserRole::where([
                        ['user_id', '=', $user_id],
                        ['role_id', '=', Role::COORDINATOR]
                    ])->delete();
                    Admin::where('user_id', '=', $user_id)->delete();
                }
                $response['status'] = true;
                $statusCode = Response::HTTP_OK;
            } else {
                $response['errors'][] = 'Undefined email';
            }
        } catch (\Exception $e) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response['errors'][] = $e->getMessage();
            $this->addLog(array(
                "log_type_id" => LogType::ERROR,
                "message" => $e->getMessage(),
                "status_code" => $statusCode
            ));
        } finally {
            return \Response::json($response, $statusCode);
        }

    }

    protected function get_log_msg($name, $value, $original = null)
    {
        $msg = '';
        switch ($name) {
            case 'school_name':
                if ($original) {
                    $msg = 'New change in Settings. School name changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'Added new school name "' . $value . '"';
                }
                break;
            case 'login_welcome_message':
                if ($original) {
                    $msg = 'New change in Settings. Login messages > heading changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'New change in Settings. Added new heading "' . $value . '" in Login messages';
                }
                break;
            case 'login_message':
                if ($original) {
                    $msg = 'New change in Settings. Login messages > message changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'New change in Settings. Added new message "' . $value . '" in Login messages';
                }
                break;
            case 'selection_screen_message':
                if ($original) {
                    $msg = 'New change in Settings. Selection screen messages > message changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'New change in Settings. Added new message "' . $value . '" in Selection screen messages';
                }
                break;
            case 'login_prompt':
                if ($original) {
                    $msg = 'New change in Settings. Login prompt changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'New change in Settings. Added new Login prompt "' . $value . '"';
                }
                break;
            case 'pass_prompt':
                if ($original) {
                    $msg = 'New change in Settings. PIN/Password prompt changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'New change in Settings. Added new PIN/Password prompt "' . $value . '"';
                }
                break;
            case 'show_course_code':
                if ($original == 1 || $original == 0) {
                    $msg = 'New change in Settings. Show selection course code changed from "' . ($original == 1 ? 'Yes' : 'No') . '" to "' . ($value == 1 ? 'Yes' : 'No') . '"';
                } else {
                    $msg = 'New change in Settings. Show selection course code changed to "' . ($value == 1 ? 'Yes' : 'No') . '"';
                }
                break;
            case 'show_sel_unit':
                if ($original == 1 || $original == 0) {
                    $msg = 'New change in Settings. Display costs and units changed from "' . ($original == 1 ? 'Icon' : 'Text') . '" to "' . ($value == 1 ? 'Icon' : 'Text') . '"';
                } else {
                    $msg = 'New change in Settings. Display costs and units changed to "' . ($value == 1 ? 'Icon' : 'Text') . '"';
                }
                break;
            case 'show_next_sections':
                if ($original == 1 || $original == 0) {
                    $msg = 'New change in Settings. Show next section changed from "' . ($original == 1 ? 'Yes' : 'No') . '" to "' . ($value == 1 ? 'Yes' : 'No') . '"';
                } else {
                    $msg = 'New change in Settings. Show next section changed to "' . ($value == 1 ? 'Yes' : 'No') . '"';
                }
                break;

            case 'school_logo_banner_active':
                if ($original) {
                    $msg = 'New change in Settings. School banner changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'New change in Settings. Added new school banner "' . $value . '"';
                }
                break;
            case 'school_logo':
                if ($original) {
                    $msg = 'New change in Settings. School logo changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'New change in Settings. Added new school logo "' . $value . '"';
                }
                break;
            case 'school_banner':
                if ($original) {
                    $msg = 'New change in Settings. School banner changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'New change in Settings. Added new school banner "' . $value . '"';
                }
                break;
            case 'login_background':
                if ($original) {
                    $msg = 'New change in Settings. School login background image changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'New change in Settings. Added new school login background image "' . $value . '"';
                }
                break;
            case 'currency_type':
                if ($original) {
                    $msg = 'New change in Settings. Currency type changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'New change in Settings. Added new currency type "' . $value . '"';
                }
                break;
            case 'payment_gateway_status':
                if ($original == 1 || $original == 0) {
                    $msg = 'New change in Settings. Payment gateway status changed from "' . ($original == 1 ? 'Yes' : 'No') . '" to "' . ($value == 1 ? 'Yes' : 'No') . '"';
                } else {
                    $msg = 'New change in Settings. Payment gateway status changed to "' . ($value == 1 ? 'Yes' : 'No') . '"';
                }
                break;
            case 'time_zone':
                if ($original) {
                    $msg = 'New change in Settings. Time zone changed from "' . $original . '" to "' . $value . '"';
                } else {
                    $msg = 'New change in Settings. Added new time zone "' . $value . '"';
                }
                break;
            case 'appointment_way':
                if ($original == 1 || $original == 0) {
                    if ($original == 1) {
                        $original = "The Course Counsellor assigned to the student in SSO";
                    } else {
                        $original = "An unassigned Course Counsellor who is available at the time";
                    }
                    if ($value == 1) {
                        $value = "The Course Counsellor assigned to the student in SSO";
                    } else {
                        $value = "An unassigned Course Counsellor who is available at the time";
                    }

                    $msg = 'New change in Settings. Appointment way changed from "' . $original . '" to "' . $value . '"';
                } else {
                    if ($value == 1) {
                        $value = "The Course Counsellor assigned to the student in SSO";
                    } else {
                        $value = "An unassigned Course Counsellor who is available at the time";
                    }

                    $msg = 'New change in Settings. Added new appointment way "' . $value . '"';
                }
                break;
            default:
                $msg = request()->path();
        }

        return $msg;
    }
}
