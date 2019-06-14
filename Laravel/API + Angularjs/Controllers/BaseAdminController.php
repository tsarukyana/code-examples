<?php


namespace App\Http\Controllers;
use LucaDegasperi\OAuth2Server\Authorizer;
use App\ActionLog;
use App\LogType;
use App\User;
use App\Role;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BaseAdminController extends Controller {

    public $authorizer;

    public  $user_id;

    protected $_access;

    public function __construct(Authorizer $authorizer)
    {
        $this->middleware('oauth');
        $this->middleware('oauth-user');
        $this->authorizer = $authorizer;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  array  $log_data
     * @return Response
     */
    public function addLog($log_data = array()){
        $currentPath = request()->path();
        $logged_user = User::find($this->authorizer->getResourceOwnerId());
        $user_code = isset($logged_user->username) ? $logged_user->username : null;
        $user_role = 'Guest';
        if(!empty($logged_user)){
            if($logged_user->hasRole(Role::$role_name[Role::SSOADMIN])){
                $user_role = "SSO Admin";
            }else if($logged_user->hasRole(Role::$role_name[Role::COORDINATOR])){
                $user_role = "Coordinator";
            }else if($logged_user->hasRole(Role::$role_name[Role::HOLA])){
                $user_role = "HoLA";
            }else if($logged_user->hasRole(Role::$role_name[Role::COURSECOUNSELLOR])){
                $user_role = "Course Counsellor";
            }else if($logged_user->hasRole(Role::$role_name[Role::HOMETEACHER])){
                $user_role = "Home Teacher";
            }else if($logged_user->hasRole(Role::$role_name[Role::STUDENT])){
                $user_role = "Student";
            }else if($logged_user->hasRole(Role::$role_name[Role::STAFF])){
                $user_role = "Staff";
            }
        }
        $log_type = LogType::$log_types[LogType::INFO];
        if(isset($log_data['log_type_id'])){
            if(isset(LogType::$log_types[$log_data['log_type_id']])){
                $log_type = LogType::$log_types[$log_data['log_type_id']];
            }
        }
        $ip_address = isset(request()->X_CLIENT_IP_ADDRESS) ? request()->X_CLIENT_IP_ADDRESS : \Request::ip();
        try{
            $log              = new ActionLog();
            $log->log_type_id = isset($log_data['log_type_id']) ? $log_data['log_type_id']: LogType::INFO; //default 1 info
            $log->log_type = $log_type; //default INFO
            $log->viewed      = isset($log_data['viewed']) ? $log_data['viewed']: 0; //default 0 no view
            $log->ip_address  = isset($log_data['ip_address']) ? $log_data['ip_address']: $ip_address; //default client ip
            $log->message     = isset($log_data['message']) ? $log_data['message']: $currentPath;
            $log->user_id     = isset($log_data['user_id']) ? $log_data['user_id'] : $this->authorizer->getResourceOwnerId();
            $log->user_code   = $user_code;
            $log->user_role   = $user_role;
            $log->current_path = isset($log_data['current_path']) ? $log_data['current_path'] : $currentPath;
            $log->status_code = isset($log_data['status_code']) ? $log_data['status_code'] : Response::HTTP_NOT_FOUND;
            $log->show_status = isset($log_data['show_status']) ? $log_data['show_status'] : ActionLog::DONT_SHOW; //default don't show 0
            $log->save();
        }catch (\Exception $e){}

    }

    //check  SSO Admin or SSO Co-Ordinator (admin)
    protected function checkAccess($user_id = null){
        $user = User::find($user_id);// get the user data from database
        if($user->role->id != Role::SSOADMIN  //SSO Admin
            && $user->role->id != Role::COORDINATOR){//SSO Co-Ordinator (admin)

            $statusCode = Response::HTTP_NOT_ACCEPTABLE;
            $response['errors'] = new \stdClass();
            $response['errors']->access_denied = array(
                "The resource owner or authorization server denied the request."
            );
            $this->addLog(array(
                "log_type_id"        => LogType::ERROR,
                "message"     => "The resource owner or authorization server denied the request.",
                "status_code" => $statusCode
            ));
            return \Response::json($response, $statusCode);
        }
        return true;
    }

    protected function getAccess(){
        $this->user_id = $this->authorizer->getResourceOwnerId();
        return $this->checkAccess($this->user_id);
    }
// push logs in bulk_logs
    public function pushBulkLogs($log_data = array()){
        $currentPath = request()->path();
        $logged_user = User::find($this->authorizer->getResourceOwnerId());
        $user_code = isset($logged_user->username) ? $logged_user->username : null;
        $user_role = null;
        if(!empty($logged_user)){
            if($logged_user->hasRole(Role::$role_name[Role::STUDENT])){
                $user_role = "Student";
            }else if($logged_user->hasRole(Role::$role_name[Role::SSOADMIN])){
                $user_role = "SSO Admin";
            }else if($logged_user->hasRole(Role::$role_name[Role::COORDINATOR])){
                $user_role = "Coordinator";
            }else if($logged_user->hasRole(Role::$role_name[Role::HOLA])){
                $user_role = "HoLA";
            }else if($logged_user->hasRole(Role::$role_name[Role::COURSECOUNSELLOR])){
                $user_role = "Course Counsellor";
            }else if($logged_user->hasRole(Role::$role_name[Role::HOMETEACHER])){
                $user_role = "Home Teacher";
            }else if($logged_user->hasRole(Role::$role_name[Role::STAFF])){
                $user_role = "Staff";
            }
        }

        $log_type = LogType::$log_types[LogType::INFO];
        if(isset($log_data['log_type_id'])){
            if(isset(LogType::$log_types[$log_data['log_type_id']])){
                $log_type = LogType::$log_types[$log_data['log_type_id']];
            }
        }
        $ip_address = isset(request()->X_CLIENT_IP_ADDRESS) ? request()->X_CLIENT_IP_ADDRESS : \Request::ip();
        $now = date('Y-m-d H:i:s');
        try{
            $log              = [];
            $log['log_type_id'] = isset($log_data['log_type_id']) ? $log_data['log_type_id']: LogType::INFO; //default 1 info
            $log['log_type'] = $log_type; //default INFO
            $log['viewed']      = isset($log_data['viewed']) ? $log_data['viewed']: 0; //default 0 no view
            $log['ip_address']  = isset($log_data['ip_address']) ? $log_data['ip_address']: $ip_address; //default client ip
            $log['message']     = isset($log_data['message']) ? $log_data['message']: $currentPath;
            $log['user_id']     = isset($log_data['user_id']) ? $log_data['user_id'] : $this->authorizer->getResourceOwnerId();
            $log['user_code']   = $user_code;
            $log['user_role']   = $user_role;
            $log['current_path'] = isset($log_data['current_path']) ? $log_data['current_path'] : $currentPath;
            $log['status_code'] = isset($log_data['status_code']) ? $log_data['status_code'] : Response::HTTP_NOT_FOUND;
            $log['show_status'] = isset($log_data['show_status']) ? $log_data['show_status'] : ActionLog::DONT_SHOW; //default don't show 0
            $log['created_at'] = $now;
            $log['updated_at'] = $now;
            if(!empty($log_data)) {
                $this->bulk_logs[] = $log;
            }
        }catch (\Exception $e){}
    }
    // get bulk_logs
    public function getBulkLogs(){
        return $this->bulk_logs;
    }
    // Save all logs pushed in bulk_logs
    public function saveBulkLogs(){
        try{
            if(!empty($this->bulk_logs)) {
                $status = ActionLog::insert($this->bulk_logs);
                if($status){
                    $this->bulk_logs = [];
                }
                return $status;
            } else {
                return Response::HTTP_NO_CONTENT;
            }
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }
}
