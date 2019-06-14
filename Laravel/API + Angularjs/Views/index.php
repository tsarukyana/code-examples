<!-- BEGIN CONTAINER -->
<div class="page-container" ng-controller="adminStaffController">
    <!-- BEGIN CONTENT -->
    <div class="page-content-wrapper">
        <!-- BEGIN CONTENT BODY -->

        <!-- BEGIN PAGE HEAD-->

        <!-- END PAGE HEAD-->

        <div class="page-head">
            <div class="container">
                <!-- BEGIN PAGE TITLE -->
                <div class="page-title">
                    <ul class="page-breadcrumb breadcrumb">
                        <li>
                            Staff
                        </li>
                    </ul>
                </div>
                <div class="text-right help_button_wrapper">
                    <md-button get-help url="admin/staff" class="btn btn-xs blue btn-outline help_button hidden">
                        help ?
                    </md-button>
                </div>
                <!-- END PAGE TITLE -->
            </div>
        </div>

        <!-- BEGIN PAGE CONTENT BODY -->
        <div class="page-content">
            <div class="container">
                <div class="page-content-inner">
                    <div class="portlet light full-height-content">
                        <div class="portlet-title">
                            <div class="caption">
                                <span class="caption-subject font-blue bold uppercase">Staff</span>
                            </div>
                        </div>
                        <div class="portlet-body" ng-cloak>
                            <div class="staff_upload_section">
                                <div class="full-height-content-body">

                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="row">
                                                <div class="col-md-8 col-lg-9">
                                                    <h4 class=" margin-bottom-20 spreadsheet_format inline_block">
                                                        You’ll need to upload staff details using a spreadsheet (either
                                                        .xls, .xlsx, or .csv format)
                                                    </h4>
                                                </div>
                                                <div class=" col-md-4 col-lg-3 spreadsheet_requirements margin-top-10 ">
                                                    <a href="javascript:void(0);"
                                                       ng-click="showSpreadsheetRequirements()">{{sr_show_hide}}
                                                        spreadsheet requirements <i
                                                                class="fa fa-angle-{{sr_show_hide_icon}} fa-lg margin-left-5"
                                                                aria-hidden="true"></i></a>
                                                </div>
                                            </div>
                                            <div id="spreadsheet_requirements" ng-bind-html="data_spreadsheet_desc"
                                                 compile-template>

                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="dropzone_section">
                                                <form class="dropzone margin-top-20 dropzone-file-area dz-clickable"
                                                      options="dzOptions" callbacks="dzCallbacks" ng-dropzone>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div ng-show="warning_uploaded_data" class="alert alert-warning margin-top-10"
                                         ng-bind-html="warning_uploaded_data" compile-template></div>
                                    <div ng-show="error_uploaded_data" class="alert alert-danger margin-top-10"
                                         ng-bind-html="error_uploaded_data" compile-template></div>
                                    <div ng-show="uploaded_data_section" class="margin-top-10 ">
                                        <h3>New data for adding</h3>
                                        <div class="alert alert-danger"
                                             ng-show="what_to_do_save_disabled || duplicate_staff_code_upload_exist || duplicate_staff_email_upload_exist || required_field_issue">
                                            <p ng-show="duplicate_staff_code_upload_exist"><i class="fa fa-caret-right"
                                                                                              aria-hidden="true"></i>
                                                Only one result per StudentCode combination is permitted.</p>
                                            <p ng-show="duplicate_staff_email_upload_exist"><i class="fa fa-caret-right"
                                                                                               aria-hidden="true"></i>
                                                Only one result per StudentEmail combination is permitted.</p>
                                            <p ng-show="required_field_issue"><i class="fa fa-caret-right"
                                                                                 aria-hidden="true"></i> Staff codes
                                                cannot contain spaces or commas.</p>
                                            <p ng-show="what_to_do_save_disabled"><i class="fa fa-caret-right"
                                                                                     aria-hidden="true"></i> The fields
                                                in red require correction before proceeding.</p>
                                            <br/>
                                            <p ng-show="what_to_do_save_disabled || duplicate_staff_code_upload_exist || duplicate_staff_email_upload_exist || required_field_issue">
                                                Click the checkbox for the option to delete a row, or click Edit to
                                                amend the details.<br/>
                                                Please correct each of the marked fields as required, then click Save.
                                            </p>
                                        </div>
                                        <p class="font-blue-madison bold margin-top-20 "
                                           ng-show="uploaded_data_section">
                                            Choose what you would like to do:
                                        </p>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="icheck-list margin-bottom-20"
                                                     ng-show="uploaded_data_section">
                                                    <label for="what_to_do">
                                                        <input type="radio" icheck ng-model="what_to_do"
                                                               radioClass="iradio_square-blue" value="1"
                                                               ng-change="whatToDo(1)" name="what_to_do">
                                                        Add new and update existing staff (based on StaffCode)
                                                    </label>
                                                    <label for="what_to_do">
                                                        <input type="radio" icheck ng-model="what_to_do"
                                                               radioClass="iradio_square-red" value="2"
                                                               ng-change="whatToDo(2)" name="what_to_do">
                                                        Purge existing and replace with data in spreadsheet
                                                    </label>
                                                    <a href="javascript:;" ng-show="what_to_do_save"
                                                       class="btn blue confirm_right_click"
                                                       confirmed-click="save_new_data()"
                                                       ng-disabled="what_to_do_save_disabled || duplicate_staff_code_upload_exist || required_field_issue">
                                                        <i class="fa fa-check-square-o" aria-hidden="true"></i> Save
                                                        Data</a>
                                                </div>
                                            </div>
                                        </div>

                                        <table datatable="ng" dt-options="dtOptionsUploaded"
                                               dt-column-defs="dtColumnDefsUploaded" class="table table-bordered "
                                               id="uploaded_staff">
                                            <thead>
                                            <tr>
                                                <th class="text-center">
                                                    <md-checkbox aria-label="checkAllUploadedRes"
                                                                 ng-change="checkAllUploadedRes(selectAll.uploaded)"
                                                                 ng-model="selectAll.uploaded"
                                                                 ng-disabled="uploaded_selected_all_disabled"
                                                                 class="md-primary"
                                                                 name="checkAllUploadedRes"></md-checkbox>
                                                </th>
                                                <th ng-if="uploaded_data_header.A">{{uploaded_data_header.A}}</th>
                                                <th ng-if="!uploaded_data_header.A"> StaffCode</th>
                                                <th ng-if="uploaded_data_header.B">{{uploaded_data_header.B}}</th>
                                                <th ng-if="!uploaded_data_header.B"> StaffName</th>
                                                <th ng-if="uploaded_data_header.C">{{uploaded_data_header.C}}</th>
                                                <th ng-if="!uploaded_data_header.C"> StaffEmail</th>
                                                <th ng-if="uploaded_data_header.D">{{uploaded_data_header.D}}</th>
                                                <th ng-if="!uploaded_data_header.D"> StaffPIN</th>
                                                <th> Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr ng-repeat="uploaded_item in uploaded_data track by $index"
                                                ng-class="!uploaded_item.staff_code || !uploaded_item.staff_name || !uploaded_item.staff_email  ? 'required_row': ''">
                                                <td class="minicheck">
                                                    <md-checkbox ng-model="uploaded_item.Selected"
                                                                 aria-label="{{$index}}"
                                                                 data-up-id="{{uploaded_item.unique_id}}"
                                                                 ng-change="checkItem(uploaded_item.Selected, true)"
                                                                 class="md-primary"></md-checkbox>
                                                </td>
                                                <td ng-class="!uploaded_item.staff_code ? 'required_field': (uploaded_item.DuplicateStaffCode == true ? 'required_field' : (uploaded_item.required_field_issue == true ? 'required_field' : ''))">
                                                    {{uploaded_item.staff_code}}
                                                </td>
                                                <td ng-class="!uploaded_item.staff_name ? 'required_field': ''">
                                                    {{uploaded_item.staff_name}}
                                                </td>
                                                <td ng-class="!uploaded_item.staff_email ? 'required_field': (uploaded_item.DuplicateStaffEmail == true ? 'required_field' : '')">
                                                    {{uploaded_item.staff_email}}
                                                </td>
                                                <td> {{uploaded_item.staff_pin}}</td>
                                                <td><a href="javascript:void(0);" class="btn btn-xs green-jungle"
                                                       ng-click="edit('uploaded_staff', $index, uploaded_item.id)"><i
                                                                class="fa fa-pencil-square-o" aria-hidden="true"></i>
                                                        Edit</a></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <h3>Existing Staff</h3>
                                    <div class=" margin-top-10">
                                        <table datatable="ng" dt-options="dtOptionsExist"
                                               dt-column-defs="dtColumnDefsExist" class="table table-bordered "
                                               id="staff">
                                            <thead>
                                            <tr>
                                                <th class="text-center">
                                                    <md-checkbox aria-label="checkAllRes"
                                                                 ng-change="checkAllRes(selectAll.exist)"
                                                                 ng-disabled="selected_all_disabled"
                                                                 ng-model="selectAll.exist"
                                                                 class="md-primary"></md-checkbox>
                                                </th>
                                                <th> StaffCode</th>
                                                <th> StaffName</th>
                                                <th> StaffEmail</th>
                                                <th> StaffPIN</th>
                                                <th> Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr ng-repeat="staff_item in  staff | orderBy:'is_coordinator' track by $index"
                                                ng-class="!staff_item.staff_code || !staff_item.staff_name ? 'required_row': ''">
                                                <td class="minicheck">
                                                    <md-checkbox ng-model="staff_item.Selected" aria-label="{{$index}}"
                                                                 data-id="{{staff_item.id}}"
                                                                 ng-change="checkItem(staff_item.Selected)"
                                                                 class="md-primary"
                                                                 ng-if="!(staff_item.is_coordinator > 0)"
                                                    ></md-checkbox>
                                                </td>
                                                <td ng-class="!staff_item.staff_code ? 'required_field': (staff_item.DuplicateStaffCode == true ? 'required_field' : '')">
                                                    {{staff_item.staff_code}}
                                                </td>
                                                <td ng-class="!staff_item.staff_name ? 'required_field': ''">
                                                    {{staff_item.staff_name}}
                                                </td>
                                                <td ng-class="!staff_item.user.email ? 'required_field': (staff_item.DuplicateStaffEmail == true ? 'required_field' : '')">
                                                    {{staff_item.user.email}}
                                                </td>
                                                <td ng-if="staff_item.staff_pin_type == '1' ">
                                                    Specified
                                                </td>
                                                <td ng-if="staff_item.staff_pin_type == '0' ">
                                                    Random
                                                </td>
                                                <td ng-if="!(staff_item.is_coordinator > 0)">
                                                    <a href="javascript:void(0);" ng-click="sendEmail('staff', $index)"
                                                       class="tooltips btn btn-xs green-jungle"
                                                       data-original-title="Send login details via email"
                                                       data-placement="top" ng-click=""><i class="fa fa-envelope-o"
                                                                                           aria-hidden="true"></i> Send</a>
                                                    <a href="javascript:void(0);"
                                                       ng-click="edit('staff', $index, staff_item.id)"
                                                       class="btn btn-xs green-jungle"
                                                       ng-if="!(staff_item.is_coordinator > 0)"> <i
                                                                class="fa fa-pencil-square-o" aria-hidden="true"></i>
                                                        Edit</a>
                                                </td>
                                                <td ng-if="staff_item.is_coordinator > 0">
                                                        <span class="tooltips btn btn-xs blue"
                                                              uib-tooltip="{{staff_item.is_coordinator > 0 ? staff_item.staff_name + ' is coordinator' : ''}}"
                                                              tooltip-placement="top"
                                                              tooltip-append-to-body="true">Info</span>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- END PAGE CONTENT INNER -->
                    </div>
                </div>
            </div>
            <script type="text/ng-template" class="modal" id="addNewModal">
                <div class="modal-content type-primary">
                    <form action="#" class="horizontal-form" name="addNewForm" novalidate>
                        <div class="modal-header">
                            <button type="button" class="close" ng-click="cancel()"></button>
                            <h3 class="modal-title">Add new Staff</h3>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="staff_code">StaffCode: <i class="text-danger">*</i> </label>
                                        <input type="text" class="form-control" ng-model="addStaffItemData.staff_code"
                                               name="staff_code" ng-keyup="$event.keyCode == 13 && ok()" required>
                                        <span class="help-block text-danger"
                                              ng-show="addNewForm.staff_email.$dirty && addNewForm.staff_code.$touched && addNewForm.staff_code.$invalid">The StaffCode is required.</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="staff_name">StaffName: <i class="text-danger">*</i> </label>
                                        <input type="text" class="form-control" ng-model="addStaffItemData.staff_name"
                                               name="staff_name" ng-keyup="$event.keyCode == 13 && ok()" required>
                                        <span class="help-block text-danger"
                                              ng-show="addNewForm.staff_name.$touched && addNewForm.staff_name.$invalid">The StaffName is required.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="staff_email">StaffEmail:<i class="text-danger">*</i></label>
                                        <input type="email" class="form-control" ng-model="addStaffItemData.staff_email"
                                               name="staff_email" ng-keyup="$event.keyCode == 13 && ok()" required>
                                        <span class="text-danger"
                                              ng-show="addNewForm.staff_email.$dirty && addNewForm.staff_email.$invalid">
                                    <span ng-show="addNewForm.staff_email.$touched && !addNewForm.staff_email.$error.email">The StaffEmail is required.</span>
                                    <span ng-show="addNewForm.staff_email.$error.email">Invalid email address.</span>
                                </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="staff_pin">StaffPIN: </label>
                                        <input type="text" class="form-control" ng-model="addStaffItemData.staff_pin"
                                               ng-keyup="$event.keyCode == 13 && ok()" name="staff_pin">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-default" type="button" ng-click="cancel()">Cancel</button>
                            <button class="btn btn-primary" type="button" ng-click="ok()"
                                    ng-disabled="addNewForm.$invalid">Save
                            </button>
                        </div>
                    </form>
                </div>
            </script>

            <script type="text/ng-template" class="modal" id="editStaffModal">
                <div class="modal-content type-primary">
                    <form action="#" class="horizontal-form" name="editStaffForm" novalidate>
                        <div class="modal-header">
                            <button type="button" class="close" ng-click="cancel()"></button>
                            <h3 class="modal-title">Edit Staff</h3>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="staff_code">StaffCode: <i class="text-danger">*</i> </label>
                                        <input type="text" class="form-control" ng-model="editStaffItemData.staff_code"
                                               name="staff_code" ng-keyup="$event.keyCode == 13 && ok()" required>
                                        <span class="help-block text-danger"
                                              ng-show="editStaffForm.staff_code.$dirty && editStaffForm.staff_code.$touched && editStaffForm.staff_code.$invalid">The StaffCode is required.</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="staff_name">StaffName: <i class="text-danger">*</i> </label>
                                        <input type="text" class="form-control" ng-model="editStaffItemData.staff_name"
                                               name="staff_name" ng-keyup="$event.keyCode == 13 && ok()" required>
                                        <span class="help-block text-danger"
                                              ng-show="editStaffForm.staff_name.$touched && editStaffForm.staff_name.$invalid">The StaffName is required.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="staff_email">StaffEmail:<i class="text-danger">*</i></label>
                                        <input type="email" class="form-control"
                                               ng-model="editStaffItemData.staff_email" name="staff_email"
                                               ng-keyup="$event.keyCode == 13 && ok()" required>
                                        <span class="text-danger"
                                              ng-show="editStaffForm.staff_email.$dirty && editStaffForm.staff_email.$invalid">
                                    <span ng-show="editStaffForm.staff_email.$touched && !editStaffForm.staff_email.$error.email">The StaffEmail is required.</span>
                                    <span ng-show="editStaffForm.staff_email.$error.email">Invalid email address.</span>
                                </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="staff_pin">StaffPIN: </label>
                                        <input type="text" class="form-control" ng-model="editStaffItemData.staff_pin"
                                               name="staff_pin" ng-keyup="$event.keyCode == 13 && ok()"
                                               placeholder="{{editStaffItemData.staff_pin_type_text}}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-default" type="button" ng-click="cancel()">Cancel</button>
                            <button class="btn btn-primary" type="button" ng-click="ok()"
                                    ng-disabled="editStaffForm.$invalid">Save
                            </button>
                        </div>
                    </form>
                </div>
            </script>
        </div>
    </div>
</div>
<!-- END CONTAINER -->
<script>
    var _staff = <?php echo isset($staff) ? json_encode($staff) : json_encode(array())?>;
</script>
