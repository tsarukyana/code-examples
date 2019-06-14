<?php

namespace App\Http\Controllers;


use App\ActionLog;
use App\AllowTickFlickSelection;
use App\Instruction;
use App\Selection;
use App\SelectionMatrix;
use App\StudentSelection;
use App\TickFlickOverride;
use App\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Mail;
use League\Flysystem\Exception;
use LucaDegasperi\OAuth2Server\Authorizer;
use App\LogType;

class TickFlickController extends BaseAdminController
{
    public function __construct(Authorizer $authorizer)
    {
        parent::__construct($authorizer);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $this->addLog(array(
            "log_type_id" => LogType::INFO,
            "message" => "Get All TickFlickOverride",
            "status_code" => Response::HTTP_OK
        ));

        return TickFlickOverride::all();
    }

    /**
     * Display a listing of the resource.
     * @param  int $year_level_id
     * @return Response
     */
    public function get_by_year_id($year_level_id)
    {
        $this->addLog(array(
            "log_type_id" => LogType::INFO,
            "message" => "Get All TickFlickOverride By YearID",
            "status_code" => Response::HTTP_OK
        ));

        return TickFlickOverride::where('year_level_id', $year_level_id)->get();
    }

    /**
     * Display a listing of the resource.
     * @param  int $year_level_id
     * @return Response
     */
    public function get_by_year_id_with_student_selection_matrix($year_level_id)
    {
        $this->addLog(array(
            "log_type_id" => LogType::INFO,
            "message" => "Get All TickFlickOverride with student and selection matrix By YearID",
            "status_code" => Response::HTTP_OK
        ));

        return TickFlickOverride::where([
            ['year_level_id', '=', $year_level_id],
            ['requested', '=', 1],
        ])
            ->with(array('student' => function ($q) {
                $q->with('student_results');
            }))
            ->with(array('selection_matrix' => function ($q) {
                $q->with(array('selections' => function ($q1) {
                    $q1->with(array('instruction' => function ($q2) {
                        $q2->with('section');
                    }));
                }));
            }))
            ->get();
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

        try {
            $statusCode = Response::HTTP_CREATED;

            $year_level_id = isset($request['year_level_id']) ? $request['year_level_id'] : null;

            $validate_rules = array(
                'year_level_id' => 'required|integer|exists:year_levels,id',
                'selection_matrix_id' => 'required|integer|exists:selection_matrices,id',
                'student_user_id' => 'required|integer|exists:users,id|unique_with:tick_flick_overrides,selection_matrix_id',
                'override' => 'integer|between:0,1',
                'recommend' => 'integer|between:0,1',
                'tick_flick' => 'integer|between:0,1',
                'requested' => 'integer|between:0,1',
                'eligible' => 'integer|between:0,1',
                'reason' => 'max:255'

            );

            $validator = Validator::make($request->all(), $validate_rules);

            if ($validator->fails()) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] = $validator->errors();
                return \Response::json($response, $statusCode);
            }

            //sections table
            $item = new TickFlickOverride();
            $item->year_level_id = $year_level_id;
            $item->selection_matrix_id = $request['selection_matrix_id'];
            $item->student_user_id = $request['student_user_id'];
            $item->student_user_id = $request['student_user_id'];

            $item->override = isset($request['override']) ? ($request['override'] != '') ? $request['override'] : null : null;
            $item->recommend = isset($request['recommend']) ? $request['recommend'] : 0;
            $item->tick_flick = isset($request['tick_flick']) ? $request['tick_flick'] : null;
            $item->reason = isset($request['reason']) ? $request['reason'] : null;
            $item->requested = isset($request['requested']) ? $request['requested'] : 0;
            $item->eligible = isset($request['eligible']) ? $request['eligible'] : 0;


            if ($item->save()) {
                $response['inserted_id'] = $item->id;
                $response['status'] = true;
                $log_msg = ' Tick and flick new changes in year level "' . $item->year_level->title
                    . '" for student "' . $item->student->student_code . '-' . $item->student->student_name . '" '
                    . ' and selection matrix "' . $item->selection_matrix->course_code . '-' . $item->selection_matrix->course_name . '"<br>';

                foreach ($request->all() as $name => $value) {
                    $log_msg .= $this->get_log_msg($name, $value);
                }
                $this->addLog(array(
                    "log_type_id" => LogType::INFO,
                    "message" => $log_msg,
                    "status_code" => $statusCode,
                    "show_status" => ActionLog::SHOW
                ));
                $response['inserted_tick_flick'] = $item;
                $response['update_tick_flick_conditions'] = $this->update_tick_flick_conditions($item);
            } else {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['status'] = false;
                $this->addLog(array(
                    "log_type_id" => LogType::ERROR,
                    "message" => "Something went wrong when adding new Tick Flick Override.",
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
     * @param  int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {


        $response['status'] = false;

        try {
            $statusCode = Response::HTTP_CREATED;

            $year_level_id = isset($request['year_level_id']) ? $request['year_level_id'] : null;

            $item = TickFlickOverride::find($id);

            if (empty($item)) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] = "Invalid Tick Flick Override Id " . $id;
                return \Response::json($response, $statusCode);
            }

            $original = $item->getOriginal();

            $selection_matrix_id = isset($request['selection_matrix_id']) ? $request['selection_matrix_id'] : null;
            $student_user_id = isset($request['student_user_id']) ? $request['student_user_id'] : null;

            $unique_rule = '';
            if ($item->year_level_id != $year_level_id
                || $item->selection_matrix_id != $selection_matrix_id
                || $item->student_user_id != $student_user_id) {
                $unique_rule = '|unique_with:tick_flick_overrides,selection_matrix_id';
            }

            $validate_rules = array(
                'year_level_id' => 'integer|exists:year_levels,id',
                'selection_matrix_id' => 'integer|exists:selection_matrices,id',
                'student_user_id' => 'integer|exists:users,id' . $unique_rule,
                'override' => 'integer|between:0,1',
                'recommend' => 'integer|between:0,1',
                'tick_flick' => 'integer|between:0,1',
                'requested' => 'integer|between:0,1',
                'eligible' => 'integer|between:0,1',
                'reason' => 'max:255'

            );

            $validator = Validator::make($request->all(), $validate_rules);

            if ($validator->fails()) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $response['errors'][] = $validator->errors();
                return \Response::json($response, $statusCode);
            }
            if (isset($request['recommend']) && $request['recommend'] != $original['recommend']) {
                $item->recommend = $request['recommend'];
            } else {
                //tick_flick_overrides table
                if (isset($request['year_level_id'])) {
                    $item->year_level_id = $request['year_level_id'];
                }
                if (isset($request['selection_matrix_id'])) {
                    $item->selection_matrix_id = $request['selection_matrix_id'];
                }
                if (isset($request['student_user_id'])) {
                    $item->student_user_id = $request['student_user_id'];
                }
                if (isset($request['override'])) {
                    if ($request['override'] != '') {
                        $item->override = $request['override'];
                    }
                }
                if (isset($request['recommend'])) {
                    $item->recommend = $request['recommend'];
                }
                if (isset($request['tick_flick'])) {
                    $item->tick_flick = $request['tick_flick'];
                    $item->student_closed = 0;
                    $item->student_closed_submit = null;
                }
                if (isset($request['reason'])) {
                    $item->reason = $request['reason'];
                }

                if (isset($request['requested'])) {
                    if ($request['requested'] != '') {
                        $item->requested = $request['requested'];
                    }
                }

                if (isset($request['eligible'])) {
                    if ($request['eligible'] != '') {
                        $item->eligible = $request['eligible'];
                    }
                }

                if ($item->eligible == $item->override) {
                    $item->override = null;
                }

                if (isset($request['override'])) {
                    if ($request['override'] == 1 && $item->requested == 1) {
                        $item->tick_flick = 1;
                        $item->student_closed = 0;
                    } else if ($request['override'] == 0 && $item->requested == 1) {
                        $item->tick_flick = 0;
                        $item->student_closed = 0;
                    }
                }

                if (isset($request['tick_flick'])) {
                    if ($item->requested != 1) {
                        $response['warnings'][] = 'Student "' . $item->student->student_code . '-' . $item->student->student_name . '" already decline his request for "'
                            . $item->selection_matrix->course_code . '-' . $item->selection_matrix->course_name . '"';
                        return \Response::json($response, $statusCode);
                    }
                    if (!is_null($item->tick_flick) && $item->tick_flick == 0) {
                        if ($item->override == 1) {
                            $item->override = 0;
                        }
                    } else if ($item->tick_flick == 1) {
                        if ($item->override == 0) {
                            $item->override = 1;
                        }
                    }
                }
            }


            $changes = $item->isDirty() ? $item->getDirty() : false;
            if ($item->save()) {
                $response['status'] = true;

                if ($changes) {
                    $log_msg = 'Tick and flick details has been updated in year level "' . $item->year_level->title
                        . '" for student "' . $item->student->student_code . '-' . $item->student->student_name . '" '
                        . ' and selection matrix "' . $item->selection_matrix->course_code . '-' . $item->selection_matrix->course_name . '"<br>';
                    foreach ($changes as $name => $value) {
                        if (is_null($original[$name]) && $original[$name] != $item->$name) {
                            $original[$name] = (isset($item->$name) && $item->$name == 1) ? 0 : 1;
                        }
                        $log_msg .= $this->get_log_msg($name, $value, $original[$name]);
                    }
                    $this->addLog(array(
                        "log_type_id" => LogType::INFO,
                        "message" => $log_msg,
                        "status_code" => $statusCode,
                        "show_status" => ActionLog::SHOW
                    ));
                }
                $response['tf'] = $item;
                if ($item->tick_flick == 1 && $item->tick_flick != $original['tick_flick']) {
                    $response['origin'] = $original;

                    $response['student_email'] = $this->send_email_to_student($request, $item->selection_matrix, $item->student, 1);
                } elseif ($item->tick_flick == 0 && $item->tick_flick != $original['tick_flick']) {
                    $response['origin'] = $original;

                    $response['student_email'] = $this->send_email_to_student($request, $item->selection_matrix, $item->student, 0);
                }


                $response['update_tick_flick_conditions'] = $this->update_tick_flick_conditions($item, $original);
            } else {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $this->addLog(array(
                    "log_type_id" => LogType::ERROR,
                    "message" => "Something went wrong when updating Tick Flick Override.",
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

    protected function update_tick_flick_conditions(TickFlickOverride $tick_flick, $original = array())
    {
        $selection_matrix = $tick_flick->selection_matrix;


        $response['$original'] = $original;
        $student_user_id = $tick_flick->student_user_id;
        $tick_flick_id = $tick_flick->id;

        if ((!empty($original) && ($tick_flick->override !== $original['override'] || $tick_flick->eligible !== $original['eligible']))
            || empty($original)
        ) {
            try {

                DB::table('allow_tick_flick_selections')
                    ->where('tick_flick_id', '=', $tick_flick_id)
                    ->delete();


                $response['$instructions'] = $instructions = Instruction::where('pre_requisites_action', '=', 1)
                    ->with(array('selections' => function ($q) use ($student_user_id, $tick_flick_id) {
                        $q->with(['selection_matrix' => function ($q) use ($student_user_id, $tick_flick_id) {
                            $q->with(['tick_flicks' => function ($q) use ($student_user_id, $tick_flick_id) {
                                $q->where([
                                    ['student_user_id', '=', $student_user_id],
                                    ['override', '=', 0],
                                    ['eligible', '=', 0],
                                    ['id', '=', $tick_flick_id],
                                ])
                                    ->orWhere([
                                        ['student_user_id', '=', $student_user_id],
                                        ['override', '=', null],
                                        ['eligible', '=', 0],
                                        ['id', '=', $tick_flick_id],
                                    ]);
                            }]); //Important
                        }]);
                    }))->get();


                $disable_selection_ids = [];

                if (!$instructions->isEmpty()) {
                    foreach ($instructions as $instruction) {
                        if (!$instruction->selections->isEmpty()) {
                            foreach ($instruction->selections as $selection) {
                                if (isset($selection->selection_matrix['tick_flicks'])
                                    && count($selection->selection_matrix['tick_flicks'])) {


                                    $response['tick_flicks'][] = $selection->selection_matrix['tick_flicks'];
                                    $disable_selection_ids[] = $selection->id;
                                }
                            }
                        }
                    }
                }

                $response['$disable_selection_ids_1'] = $disable_selection_ids;
                if (($tick_flick->override === 0 && $tick_flick->eligible == 1) || $tick_flick->tick_flick === 0) {

                    if (!empty($tick_flick->selection_matrix)) {


                        if (!$tick_flick->selection_matrix->selections->isEmpty()) {
                            foreach ($tick_flick->selection_matrix->selections as $selection) {
                                if (!in_array($selection->id, $disable_selection_ids)) {
                                    $disable_selection_ids[] = $selection->id;
                                }
                            }
                        }
                    }
                }

                $response['$disable_selection_ids'] = $disable_selection_ids;


                if (!empty($disable_selection_ids)) {

                    $bulk_insert = [];
                    foreach ($disable_selection_ids as $disable_selection_id) {
                        $now = date('Y-m-d H:i:s');
                        $bulk_insert[] = [
                            'tick_flick_id' => $tick_flick_id,
                            'selection_id' => $disable_selection_id,
                            'disabled' => 1,
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    }

                    if (!empty($bulk_insert)) {
                        $response['$bulk_insert'] = $bulk_insert;
                        AllowTickFlickSelection::insert($bulk_insert);
                    }
                }

            } catch (\Exception $er) {

                $response['$er'] = $er->getMessage();

            }
        }

        $response['select_deselect_selection'] = $this->select_deselect_selection($original, $tick_flick, $selection_matrix);

        return $response;
    }

    protected function select_deselect_selection($original, TickFlickOverride $tick_flick, SelectionMatrix $selection_matrix)
    {

        $response = [];
        $year_level_id = $tick_flick->year_level_id;

        if (!empty($original)
            && $tick_flick->tick_flick !== $original['tick_flick']
            && $tick_flick->tick_flick == 1) {

            try {

                $validation_data['year_level_id'] = $year_level_id = $tick_flick->selection_matrix->year_level_id;
                $validation_data['student_user_id'] = $student_user_id = $tick_flick->student_user_id;
                if (!empty($tick_flick->selection)) {

                    //remove disable from allow_tick_flick_selection disable, because tick_flick == 1
                    AllowTickFlickSelection::where(
                        [
                            ['tick_flick_id', '=', $tick_flick->id],
                            ['selection_id', '=', $tick_flick->selection->id]
                        ]
                    )->delete();
                }
            } catch (\Exception $er) {
            }

        } else if (!empty($original)
            && $tick_flick->tick_flick !== $original['tick_flick']
            && $tick_flick->tick_flick == 0) {

            if (!empty($tick_flick->selection_id)) {

                try {
                    $allow_tick_flick_selection = AllowTickFlickSelection::firstOrNew([
                        'tick_flick_id' => $tick_flick->id,
                        'selection_id' => $tick_flick->selection_id
                    ]);
                    $allow_tick_flick_selection->disabled = 1;
                    $allow_tick_flick_selection->save();

                } catch (\Exception $er) {
                }
            }
        } else if (!empty($original)
            && $tick_flick->override !== $original['override']
            && ($tick_flick->override === null || $tick_flick->override === 0)) {

            $selection_ids = Selection::where('selection_matrix_id', '=', $selection_matrix->id)->lists('id')->toArray();

            if (!empty($selection_ids)) {
                $student_selects = StudentSelection::where('student_user_id', '=', $tick_flick->student_user_id)
                    ->whereIn('selection_id', $selection_ids)
                    ->get();

                if (!$student_selects->isEmpty()) {
                    foreach ($student_selects as $student_select) {

                        $student_select->before_pre_req = 1;
                        if ($student_select->save()) {
                            $this->addLog(array(
                                "log_type_id" => LogType::INFO,
                                "message" => "changed before_pre_req = 1 for student selection whith id " . $student_select->id,
                                "status_code" => Response::HTTP_OK
                            ));
                        }
                    }
                }
            }
        } else if (!empty($original)
            && $tick_flick->override !== $original['override']
            && $tick_flick->override === 0) {

        } else if (empty($original)
            && ($tick_flick->override === null || $tick_flick->override === 0)
        ) {
            //When there is no row in tick_flick_overrides and changed Yes to No
            $selection_ids = Selection::where('selection_matrix_id', '=', $selection_matrix->id)->lists('id')->toArray();

            if (!empty($selection_ids)) {
                $student_selects = StudentSelection::where('student_user_id', '=', $tick_flick->student_user_id)
                    ->whereIn('selection_id', $selection_ids)
                    ->get();

                if (!$student_selects->isEmpty()) {
                    foreach ($student_selects as $student_select) {
                        $student_select->before_pre_req = 1;
                        if ($student_select->save()) {
                            $this->addLog(array(
                                "log_type_id" => LogType::INFO,
                                "message" => "Add besfore bre req status to student selection whith id " . $student_select->id,
                                "status_code" => Response::HTTP_OK
                            ));
                        }
                    }
                }
            }

        }

        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Request $request
     * @param  int $id
     * @return Response
     */
    public function destroy(Request $request, $id)
    {

        $response['status'] = false;

        try {
            $statusCode = Response::HTTP_OK;

            if (TickFlickOverride::destroy($id)) {
                $response['status'] = true;
                $this->addLog(array(
                    "log_type_id" => LogType::INFO,
                    "message" => "Tick Flick Override Deleted",
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
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @return Response
     */
    public function store_items(Request $request)
    {
        $response['status'] = false;

        $data = isset($request['data']) ? $request['data'] : null;

        if (!is_array($data) || empty($data)) {
            return $response;
        }


        try {
            $statusCode = Response::HTTP_CREATED;

            $tick_flicks = TickFlickOverride::all();
            $bulk_insert = [];
            $check_data = [];
            foreach ($data as $k => $item) {
                $validation_data['year_level_id'] = isset($item['year_level_id']) ? $item['year_level_id'] : null;
                $validation_data['selection_matrix_id'] = isset($item['selection_matrix_id']) ? $item['selection_matrix_id'] : null;
                $validation_data['student_user_id'] = isset($item['student_user_id']) ? $item['student_user_id'] : null;
                $validation_data['override'] = isset($item['override']) ? $item['override'] : null;
                $validation_data['recommend'] = isset($item['recommend']) ? $item['recommend'] : 0;
                $validation_data['tick_flick'] = isset($item['tick_flick']) ? $item['tick_flick'] : null;
                $validation_data['reason'] = isset($item['reason']) ? $item['reason'] : null;
                $validation_data['requested'] = isset($item['requested']) ? $item['requested'] : 0;
                $validation_data['eligible'] = isset($item['eligible']) ? $item['eligible'] : 0;

                $continue = false;
                if (!$tick_flicks->isEmpty()) {
                    foreach ($tick_flicks as $tick_flick) {
                        if ($tick_flick->year_level_id == $validation_data['year_level_id']
                            && $tick_flick->selection_matrix_id == $validation_data['selection_matrix_id']
                            && $tick_flick->student_user_id == $validation_data['student_user_id']
                        ) {
                            unset($data[$k]);
                            $continue = true;
                            break;
                        }
                    }
                }

                if ($continue) {
                    continue;
                }
                $checked = $validation_data['selection_matrix_id'] . '_' . $validation_data['student_user_id'];

                if (!in_array($checked, $check_data)) {
                    $check_data[] = $checked;
                    $validation_data['created_at'] = date('Y-m-d H:i:s');
                    $bulk_insert[] = $validation_data;
                }

            }

            if (!empty($bulk_insert)) {
                try {
                    TickFlickOverride::insert($bulk_insert);
                    $response['status'] = true;
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
            if ($response['status']) {
                $response['status'] = true;
                $this->addLog(array(
                    "message" => "Success added new Tick Flick Override items.",
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

    protected function send_email_to_student($request, $selection_matrix, $student, $answer)
    {
        $result['status'] = false;
        try {
            $school_logo = isset($request['school_logo']) ? $request['school_logo'] : env('SSO_CLIENT_URL') . '/public/images/sso_logo.png';
            $school_code = $request['X_SCHOOL_CODE'];
            $school_name = isset($request['school_name']) ? $request['school_name'] : '';

            $email_data_params = [
                'student_name' => isset($student->student_name) ? $student->student_code . '-' . $student->student_name : null,
                'selection_name' => isset($selection_matrix->course_name) ? $selection_matrix->course_code . '-' . $selection_matrix->course_name : null,
                'year_level' => isset($selection_matrix->year_level) ? $selection_matrix->year_level->title : null,
                'SSO_CLIENT_URL' => env('SSO_CLIENT_URL'),
                'school_logo' => $school_logo,
                'school_name' => $school_name,
                'school_code' => $school_code
            ];
            $email_template = ($answer === 1 ? 'emails.send_notification_to_student_approved' : 'emails.send_notification_to_student_declined');

            $to_email = $student->student_email;
            $to_name = $email_data_params['student_name'];
            $result['ep'] = $answer;

            $setting = Setting::where('name', '=', 'administered_from')->first();
            $administered_from = !empty($setting) && isset($setting->value) ? $setting->value : 'Australia';
            $suffix = $administered_from == 'UK/Europe' ? 'UK' : 'AU';
            $from = array('email' => env('EMAIL_SUPORT_FROM_' . $suffix), 'name' => 'SSO');

            $result['status'] = Mail::send($email_template, $email_data_params, function ($message) use ($to_email, $to_name, $from) {
                //$message->from set in the config/mail.php file
                $message->from($from['email'], $from['name']);
                $message->to($to_email, $to_name)->subject('Your SSO Override Request');
            });

            if ($result['status']) {
                $this->addLog(array(
                    "log_type_id" => LogType::INFO,
                    "message" => 'Tick and flick ' . ($answer == 1 ? 'approved' : 'declined') . ' message has been sent to student ' . $email_data_params['student_name'],
                    "status_code" => Response::HTTP_OK,
                    "show_status" => ActionLog::SHOW
                ));
            }
        } catch (Exception $e) {
            $result['errors'] = $e->getMessage();
        } finally {
            return $result;
        }

    }


    protected function get_log_msg($name, $value, $original = null)
    {
        $log_msg = '';
        switch ($name) {
            case 'override':
                if ($original) {
                    $log_msg = 'Override status changed from "' . ($original == 1 ? 'Yes' : 'No') . '" to ' . ($value == 1 ? 'Yes' : 'No') . '"<br>';
                } else {
                    $log_msg = 'Override status is "' . ($value == 1 ? 'Yes' : 'No') . '"<br>';
                }
                break;
            case 'recommend':
                if ($original) {
                    $log_msg = 'Recommended status changed from "' . ($original == 1 ? 'Checked' : 'Unchecked') . '" to "' . ($value == 1 ? 'Checked' : 'Unchecked') . '"<br>';
                } else {
                    $log_msg = 'Recommended status is "' . ($value == 1 ? 'Checked' : 'Unchecked') . '"<br>';
                }
                break;
            case 'tick_flick':
                if ($original) {
                    $log_msg = 'Tick and flick reason status changed from "' . ($original == 1 ? 'Allowed' : 'Declined') . '" to ' . ($value == 1 ? 'Allowed' : 'Declined') . '"<br>';
                } else {
                    $log_msg = 'Tick and flick reason is "' . ($value == 1 ? 'Allowed' : 'Declined') . '"<br>';
                }
                break;


        }

        return $log_msg;
    }
}
