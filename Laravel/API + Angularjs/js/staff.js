ssoApp.controller('adminStaffController',
    ['$scope', '$http', '$timeout', '$parse', '$compile', '$rootScope', '$log', 'DTOptionsBuilder', 'DTColumnDefBuilder', 'toastr', 'toastrService', '$sce', '$uibModal', 'Scopes', '_', 'AlertErrorsWarningsService', function (
        $scope, $http, $timeout, $parse, $compile, $rootScope, $log, DTOptionsBuilder, DTColumnDefBuilder, toastr, toastrService, $sce, $uibModal, Scopes, _, AlertErrorsWarningsService) {
        console.log("adminStaffController");
        toastrService.setToastrOption();

        Scopes.store('adminStaffController', $scope);

        $scope.sr_show_hide = "Show";
        $scope.sr_show_hide_icon = "down";

        $scope.showSpreadsheetRequirements = function () {
            $http({
                method: "POST",
                url: base_url + 'admin/staff/get_spreadsheet_requirements',
                data: {}
            }).then(function (response) {
                var $spreadsheet_requirements = $("#spreadsheet_requirements");
                if ($scope.sr_show_hide === "Show") {
                    $spreadsheet_requirements.html($compile(response.data._html)($scope));
                    $scope.sr_show_hide = "Hide";
                    $scope.sr_show_hide_icon = "up";
                    $spreadsheet_requirements.slideToggle();
                } else {
                    $spreadsheet_requirements.html($compile('')($scope));
                    $scope.sr_show_hide = "Show";
                    $scope.sr_show_hide_icon = "down";
                    $spreadsheet_requirements.slideToggle();
                }
            }, function (response) {
                // console.log('error');
            });
        };

        $scope.staff = _staff;

        if (angular.isDefined($scope.staff) && $scope.staff.length <= 0) {
            // staff are still empty
            $scope.showSpreadsheetRequirements();
        }

        $scope.selectAll = {
            uploaded: false,
            exist: false
        };

        $scope.uploaded_selected_all_disabled = false;
        $scope.selected_all_disabled = false;

        $scope.checkAllRes = function (selected_all) {
            $scope.existSelected = selected_all;
            var selectable_items = [];
            $('[data-id]').each(function () {
                selectable_items.push(parseInt($(this).attr('data-id')));
            });

            angular.forEach($scope.staff, function (item) {
                if (selectable_items.indexOf(item.id) > -1) {
                    item.Selected = selected_all;
                }
                if (item.Selected) {
                    $scope.existSelected = true;
                }
            });
            $scope.selectAll.exist = selected_all;
        };
        $scope.checkAllUploadedRes = function (uploaded_selected_all) {
            $scope.uploadSelected = uploaded_selected_all;
            var selectable_items = [];
            $('[data-up-id]').each(function () {
                selectable_items.push(parseInt($(this).attr('data-up-id')));
            });
            angular.forEach($scope.uploaded_data, function (item) {
                if (selectable_items.indexOf(item.unique_id) > -1) {
                    item.Selected = uploaded_selected_all;
                }
                if (item.Selected) {
                    $scope.uploadSelected = true;
                }
            });
            $scope.selectAll.uploaded = uploaded_selected_all;
        };

        $scope.checkItem = function (staff, uploaded) {
            // console.log("checkItem " + uploaded);
            if (uploaded === true) {
                var keepGoing = true;
                var staff_count = $scope.uploaded_data.length;
                var selected_count = 0;
                angular.forEach($scope.uploaded_data, function (item) {
                    if (item.Selected == true) {
                        selected_count++;
                    }
                    if (keepGoing) {
                        if (item.Selected == true) {
                            keepGoing = false;
                        }
                    }
                });
                if (staff_count === selected_count) {
                    $scope.selectAll.uploaded = true;
                } else if (staff_count > selected_count) {
                    $scope.selectAll.uploaded = false;
                }
                $scope.uploadSelected = !keepGoing;
            } else {
                var keepGoing = true;
                var staff_count = $scope.staff.length;
                var selected_count = 0;
                angular.forEach($scope.staff, function (item) {
                    // console.log(item);
                    if (item.Selected === true) {
                        selected_count++;
                    }
                    if (keepGoing) {
                        if (item.Selected === true) {
                            keepGoing = false;
                        }
                    }
                });
                if (staff_count === selected_count) {
                    $scope.selectAll.exist = true;
                } else if (staff_count > selected_count) {
                    $scope.selectAll.exist = false;
                }
                $scope.existSelected = !keepGoing;
            }
        };

        $scope.uploadSelected = false;
        $scope.uploaded_selected_all = false;
        $scope.uploaded_data = [];
        $scope.uploaded_data_header = [];
        $scope.uploaded_data_section = false;

        var lang = {
            "loadingRecords": "Loading...",
            "processing": "Processing...",
            "search": "",
            "searchPlaceholder": "Search"

        };
        // DataTables configurable options
        $scope.dtOptionsUploaded = DTOptionsBuilder.newOptions()
            .withDisplayLength(10)
            .withOption('bLengthChange', true)
            .withOption('language', lang)
            .withPaginationType('bootstrap_full_number')
            .withOption('order', [1, 'asc'])
            .withOption('bStateSave', true)
            .withOption('fnStateLoadParams', function (oSettings, oData) {
                // oData.search.search = '';
            })
            .withOption('sDom', "<'row'<'col-sm-6'f><'col-sm-6 text-right text_left_xs add_new_section_uploaded '>r><'table-responsive't><'row'<'col-sm-6'l><'text-right col-sm-6 text_left_xs'p>>")
            .withOption('drawCallback', function (settings, json) {
                var search_input = $('#' + settings.sTableId + '_wrapper .dataTables_filter input');
                if (search_input.length > 0) {
                    if (search_input.val() === ' ') {
                        search_input.val($.trim(search_input.val()));
                    }
                    var search_value = search_input.val();
                    if (search_value !== '') {
                        search_input.addClass('border_search_active');
                        if (settings.aiDisplay.length === 0) {
                            search_input.removeClass('border_search_active');
                            search_input.addClass('empty_border_search_active');
                        }
                    } else {
                        search_input.removeClass('border_search_active');
                    }
                    if (settings.aiDisplay.length > 0 || search_value.length === 0) {
                        search_input.removeClass('empty_border_search_active');
                    }

                }
                var add_new_btns = '<a href="javascript:;"  type="button" class="btn red button_margin_right_20 confirm_left_click xs_block" ng-if="uploadSelected == true" confirmed-click="delete_items(\'' + settings.sTableId + '\')"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete</a>';
                $(".add_new_section_uploaded").html($compile(add_new_btns)($scope));

                var remove_not_visible_items = false;
                var selectable_items = [];
                var has_not_selected = false;
                var current_length = parseInt($('[name="uploaded_staff_length"]').val());

                if (current_length < $scope.upDisplayLength) {
                    remove_not_visible_items = true;
                }
                $scope.upDisplayLength = current_length;

                $('[data-up-id]').each(function () {
                    selectable_items.push(parseInt($(this).attr('data-up-id')));
                });

                angular.forEach($scope.uploaded_data, function (item) {
                    if (selectable_items.indexOf(item.unique_id) > -1) {
                        if (!item.Selected) {
                            $scope.selectAll.uploaded = false;
                            has_not_selected = true;
                        }

                    } else if (remove_not_visible_items) {
                        item.Selected = false;
                    }
                    if (item.Selected) {
                        $scope.uploadSelected = true;
                    }
                });

                if (!has_not_selected && settings.aiDisplay.length > 0) {
                    $scope.selectAll.uploaded = true;
                } else {
                    $scope.selectAll.uploaded = false;

                }
                $scope.$digest();
            });

        $scope.dtColumnDefsUploaded = [
            DTColumnDefBuilder.newColumnDef(0).notSortable(),
            DTColumnDefBuilder.newColumnDef(5).notSortable()
        ];
        $scope.displayLength = 10;
        $scope.dtOptionsExist = DTOptionsBuilder.newOptions()
            .withDisplayLength($scope.displayLength)
            .withOption('bLengthChange', true)
            .withOption('language', lang)
            .withPaginationType('bootstrap_full_number')
            .withOption('order', [])
            .withOption('bStateSave', true)
            .withOption('fnStateLoadParams', function (oSettings, oData) {
                // oData.search.search = '';
            })
            .withOption('sDom', "<'row'<'col-sm-6'f><'col-sm-6 text-right text_left_xs add_new_section_exist'>r><'table-responsive't><'row'<'col-sm-6'l><'col-sm-6 text-right text_left_xs 'p>>")
            .withOption('drawCallback', function (settings, json) {
                var search_input = $('#' + settings.sTableId + '_wrapper .dataTables_filter input');
                if (search_input.length > 0) {
                    if (search_input.val() === ' ') {
                        search_input.val($.trim(search_input.val()));
                    }
                    var search_value = search_input.val();
                    if (search_value != '') {
                        search_input.addClass('border_search_active');
                        if (settings.aiDisplay.length === 0) {
                            search_input.removeClass('border_search_active');
                            search_input.addClass('empty_border_search_active');
                        }
                    } else {
                        search_input.removeClass('border_search_active');
                    }
                    if (settings.aiDisplay.length > 0 || search_value.length == 0) {
                        search_input.removeClass('empty_border_search_active');
                    }
                }
                var add_new_btns = '<a href="javascript:;" type="button" class="btn red button_margin_right_20 confirm_left_click confirm_top_click_xs xs_block" ng-if="existSelected == true" confirmed-click="delete_items(\'' + settings.sTableId + '\')"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete</a><button type="button" class="btn blue xs_block " ng-disabled="uploaded_data.length > 0" ng-click="add_new(\'' + settings.sTableId + '\')"><i class="fa fa-plus" aria-hidden="true"></i> Add New</button>';
                // var add_new_btn = '<button type="button" class="btn btn-primary btn-circle pull-right" ng-click="add_new(\''+settings.sTableId+'\')"> Add New</button>';
                $(".add_new_section_exist").html($compile(add_new_btns)($scope));
                if ($scope.existSelected === true) {
                    $('#delBtn').show();
                } else {
                    $('#delBtn').hide();
                }

                var remove_not_visible_items = false;
                var selectable_items = [];
                var has_not_selected = false;
                var current_length = parseInt($('[name="staff_length"]').val());

                if (current_length < $scope.displayLength) {
                    remove_not_visible_items = true;
                }
                $scope.displayLength = current_length;

                $('[data-id]').each(function () {
                    selectable_items.push(parseInt($(this).attr('data-id')));
                });

                angular.forEach($scope.staff, function (item) {
                    if (selectable_items.indexOf(item.id) > -1) {
                        if (!item.Selected) {
                            $scope.selectAll.exist = false;
                            has_not_selected = true;
                        }

                    } else if (remove_not_visible_items) {
                        item.Selected = false;
                    }
                    if (item.Selected) {
                        $scope.existSelected = true;
                    }
                });
                if (!has_not_selected && settings.aiDisplay.length > 0) {
                    $scope.selectAll.exist = true;
                } else {
                    $scope.selectAll.exist = false;

                }
                $scope.$digest();
            });
        $scope.dtColumnDefsExist = [
            DTColumnDefBuilder.newColumnDef(0).notSortable(),
            DTColumnDefBuilder.newColumnDef(5).notSortable()
        ];
        //Dropzone section
        $scope.dzOptions = {
            paramName: 'staff',
            maxFilesize: '10',
            url: '/admin/staff/upload',
            acceptedFiles: ".xls, .xlsx, .csv",
            addRemoveLinks: true,
            dictRemoveFile: 'Remove',
            dictDefaultMessage: "Drop file here or click to upload",
            maxFiles: 1,
            init: function () {
                $scope.dzModel = this;
                this.on("maxfilesexceeded", function (file) {
                    this.removeAllFiles();
                    this.addFile(file);
                })
            }
        };

        $scope.error_uploaded_data = false;
        $scope.warning_uploaded_data = false;
        $scope.what_to_do_save_disabled = false;

        $scope.dzCallbacks = {
            'addedfile': function (file) {
                $scope.error_uploaded_data = false;
                $scope.warning_uploaded_data = false;
                $scope.what_to_do = 1;
                // console.info('File added from dropzone 1.', file);
            },
            'removedfile': function (file) {
                $scope.uploaded_data = [];
                $scope.error_uploaded_data = false;
                $scope.warning_uploaded_data = false;
                $scope.what_to_do_save_disabled = false;
            },
            'success': function (file, response) {
                response = JSON.parse(response);

                if (angular.isDefined(response.warning_headers) && !_.isEmpty(response.warning_headers)) {
                    var warnings = '<p>Invalid column headings detected. Data in these columns will be ignored.</p>';
                    angular.forEach(response.warning_headers, function (head, column) {
                        warnings += '<p> Column ' + column + ' is invalid: ' + head + '</p>';
                    });
                    $scope.warning_uploaded_data = $sce.trustAsHtml(warnings);
                    toastr.warning(warnings, '');
                }
                if (response.errors != null) {
                    var errors = '';
                    angular.forEach(response.errors, function (error, index) {
                        errors += '<p>' + error + '</p>';
                    });
                    $scope.error_uploaded_data = $sce.trustAsHtml(errors);
                    toastr.error(errors, '');
                } else {
                    $("#spreadsheet_requirements").html($compile('')($scope));
                    $scope.sr_show_hide = "Show";
                    $scope.sr_show_hide_icon = "down";

                    var keepGoing = true;
                    angular.forEach(response.file_data, function (item, i) {
                        if (keepGoing) {
                            if (!item.staff_code || !item.staff_name || !item.staff_email) {
                                $scope.what_to_do_save_disabled = true;
                                keepGoing = false;
                            }
                        }
                    });

                    $scope.uploaded_data = response.file_data;
                    $scope.uploaded_data_header = response.header;
                    $scope.uploaded_data_section = true;
                    $scope.what_to_do_save = true;
                }
            }
        };


        $scope.select_all = true;

        // 0 - nothing
        // 1 -  Add New and update existing codes
        // 2 - Purge existing and replace with data in spreadsheet
        $scope.what_to_do = 0;
        $scope.what_to_do_save = false;
        $scope.whatToDo = function (value) {
            $scope.what_to_do = value;
            $scope.what_to_do_save = true;
        };
        $scope.save_new_data = function () {
            $http({
                method: "POST",
                url: base_url + 'admin/staff/addUploadItems',
                data: {
                    what_to_do: $scope.what_to_do,
                    uploaded_data: JSON.stringify($scope.uploaded_data)
                }
            }).then(function (response) {
                AlertErrorsWarningsService.success(response, function () {
                    $scope.getAllStaff();
                    $scope.uploaded_data = [];
                    toastr.success("The data saved successfully.", '');
                    if (angular.isDefined($scope.dzModel)) {
                        $scope.dzModel.removeAllFiles();
                    }
                }).errors().warnings();

            }, function (response) {
                // console.log('error');
                // console.log(response);
            });

        };

        $scope.getAllStaff = function () {
            $http({
                method: "POST",
                url: base_url + 'admin/staff/getAllStaff',
                data: {}
            }).then(function (response) {
                $scope.staff = response.data.result;
                if (!angular.isUndefined(response.data.errors) && response.data.errors != null) {
                    var errors = '';
                    angular.forEach(response.data.errors, function (error, index) {
                        errors += '<p>' + error + '</p>';
                    });
                    toastr.error(errors, '');
                }
            }, function (response) {
            });

        };

        //Add New Staff item
        $scope.add_new = function (tableId) {

            var addNewMatrixInstance = $uibModal.open({
                animation: true,
                ariaLabelledBy: 'modal-title',
                ariaDescribedBy: 'modal-body',
                templateUrl: 'addNewModal',
                controller: 'AddNewModalInstanceCtrl',
                resolve: {
                    param: function () {
                        return {
                            'tableId': tableId
                        };
                    }
                }
            });

            addNewMatrixInstance.result.then(function () {
                if (tableId === "staff") {
                    $scope.getAllStaff();
                }
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });

        };

        //Delete Staff item
        $scope.delete_items = function (tableId) {
            // $log.info(tableId);
            var new_data = [];
            if (tableId === "uploaded_staff") {
                angular.forEach($scope.uploaded_data, function (item, i) {
                    if (item.Selected !== true) {
                        new_data.push(item);
                    }
                });
                $scope.uploaded_data = new_data;
            } else if (tableId === "staff") {
                var delete_item_ids = [];
                angular.forEach($scope.staff, function (item, i) {
                    if (item.Selected !== true) {
                        new_data.push(item);
                    } else {
                        delete_item_ids.push(item.id);
                    }
                });

                if (delete_item_ids.length > 0) {
                    $http({
                        method: "POST",
                        url: base_url + 'admin/staff/destroyItems',
                        data: {ids: delete_item_ids}
                    }).then(function (response) {
                        var errors = '';
                        if (!angular.isUndefined(response.data.errors) && response.data.errors != null) {
                            angular.forEach(response.data.errors, function (error, index) {
                                if (angular.isObject(error)) {
                                    angular.forEach(error, function (e, i) {
                                        errors += '<p>' + e + '</p>';
                                    });
                                } else {
                                    errors += '<p>' + error + '</p>';
                                }
                            });
                            toastr.error(errors, '');
                        } else if (angular.isObject(response.data.result) && !angular.isUndefined(response.data.result.errors)) {
                            angular.forEach(response.data.result.errors, function (error, index) {
                                if (angular.isObject(error)) {
                                    angular.forEach(error, function (e, i) {
                                        errors += '<p>' + e + '</p>';
                                    });
                                } else {
                                    errors += '<p>' + error + '</p>';
                                }
                            });
                            toastr.error(errors, '');
                        }
                        if (angular.isObject(response.data.result) && !angular.isUndefined(response.data.result.status) && response.data.result.status) {
                            toastr.success('Successfully Deleted', '');
                            $scope.getAllStaff();
                        }
                    }, function (response) {
                    });
                } else {
                    toastr.error("No any item selected");
                }

                // $scope.staff = new_data;
            }
        };

        //Edit Staff
        $scope.edit = function (tableId, index, id) {
            var templateUrl = "editStaffModal";
            if (tableId === "staff") {
                templateUrl = "editStaffModal";
            }
            var editMatrixInstance = $uibModal.open({
                animation: true,
                ariaLabelledBy: 'modal-title',
                ariaDescribedBy: 'modal-body',
                templateUrl: templateUrl,
                controller: 'EditStaffModalInstanceCtrl',
                resolve: {
                    param: function () {
                        return {
                            'tableId': tableId,
                            'index': index,
                            'id': id
                        };
                    }
                }
            });

            editMatrixInstance.result.then(function () {
                if (tableId === "uploaded_staff") {
                    $scope.uploaded_data[index] = angular.copy(Scopes.get('EditStaffModalInstanceCtrl').editStaffItemData);

                    $scope.checkGetUploadData($scope.uploaded_data);

                } else if (tableId === "staff") {
                    $scope.getAllStaff();
                }
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });

        };

        $scope.sendEmail = function (tableId, index) {
            if (tableId === "staff") {
                $http({
                    method: "POST",
                    url: base_url + 'admin/staff/sendLoginDetailsEmail',
                    data: {user_id: $scope.staff[index].user_id}
                }).then(function (response) {
                    $("body").append(response.data);
                    if (!angular.isUndefined(response.data.errors) && !_.isEmpty(response.data.errors)) {
                        var errors = '';
                        angular.forEach(response.data.errors, function (error, index) {
                            if (angular.isObject(error)) {
                                angular.forEach(error, function (e, i) {
                                    errors += '<p>' + e + '</p>';
                                });
                            } else {
                                errors += '<p>' + error + '</p>';
                            }
                        });
                        toastr.error(errors, '');
                    }

                    if (!angular.isUndefined(response.data.result) && response.data.result) {
                        toastr.success(response.data.result, '');
                    }

                }, function (response) {
                });

            }
        };

        $scope.checkGetUploadData = function (uploaded_data) {
            $scope.what_to_do_save_disabled = false;
            $scope.duplicate_staff_code_upload_exist = false;
            $scope.duplicate_staff_email_upload_exist = false;
            $scope.required_field_issue = false;
            var data = [];

            var groups_staff_code = _.groupBy(uploaded_data, function (value) {
                return value.staff_code.trim();
            });
            angular.forEach(groups_staff_code, function (group, index) {
                if (group.length > 1) {
                    $scope.what_to_do_save_disabled = true;
                    $scope.duplicate_staff_code_upload_exist = true;
                    angular.forEach(group, function (group_item, i) {
                        var push_data = group_item;
                        push_data.DuplicateStaffCode = true;
                        push_data.required_field_issue = false;
                        if (group_item.staff_code.indexOf(' ') > -1 || group_item.staff_code.indexOf(',') > -1) {
                            $scope.required_field_issue = true;
                            push_data.required_field_issue = true;
                        }
                        if (!group_item.staff_code || !group_item.staff_name || !group_item.staff_email) {
                            $scope.what_to_do_save_disabled = true;
                        }
                        if (group_item.Selected === true) {
                            $scope.uploadSelected = true;
                        }
                        data.push(push_data);
                    });
                } else {
                    var push_data = group[0];
                    push_data.DuplicateStaffCode = false;
                    push_data.required_field_issue = false;
                    if (push_data.staff_code.indexOf(' ') > -1 || push_data.staff_code.indexOf(',') > -1) {
                        $scope.required_field_issue = true;
                        push_data.required_field_issue = true;
                    }

                    if (!push_data.staff_code || !push_data.staff_name || !push_data.staff_email) {
                        $scope.what_to_do_save_disabled = true;
                    }
                    if (push_data.Selected === true) {
                        $scope.uploadSelected = true;
                    }
                    data.push(push_data);
                }
            });

            var groups_staff_email = _.groupBy(data, function (value) {
                return value.staff_email;
            });

            var data2 = [];
            angular.forEach(groups_staff_email, function (group, index) {
                if (group.length > 1) {
                    $scope.what_to_do_save_disabled = true;
                    $scope.duplicate_staff_email_upload_exist = true;
                    angular.forEach(group, function (group_item, i) {
                        var push_data = group_item;
                        push_data.DuplicateStaffEmail = true;

                        if (!group_item.staff_code || !group_item.staff_name || !group_item.staff_email) {
                            $scope.what_to_do_save_disabled = true;
                        }
                        if (group_item.Selected === true) {
                            $scope.uploadSelected = true;
                        }
                        data2.push(push_data);
                    });
                } else {
                    var push_data = group[0];
                    push_data.DuplicateStaffEmail = false;
                    if (!push_data.staff_code || !push_data.staff_name || !push_data.staff_email) {
                        $scope.what_to_do_save_disabled = true;
                    }
                    if (push_data.Selected === true) {
                        $scope.uploadSelected = true;
                    }
                    data2.push(push_data);
                }
            });
            if ($scope.required_field_issue) {
                toastr.warning('StaffCode cannot contain spaces or commas.', 'Please fix');
            }
            return data2;
        };

        $scope.$watch('uploaded_data', function (newValue) {
            // console.log("$watch - 'uploaded_data'");
            $scope.uploaded_selected_all_disabled = false;

            $scope.duplicate_staff_code_upload_exist = false;
            $scope.duplicate_staff_email_upload_exist = false;
            $scope.what_to_do_save_disabled = false;
            $scope.required_field_issue = false;
            $scope.uploadSelected = false;
            if (newValue.length === 0) {
                $scope.uploaded_data_header = [];
                $scope.uploaded_data_section = false;
                $scope.what_to_do_save = false;
                $scope.error_uploaded_data = false;
                $scope.warning_uploaded_data = false;

                $scope.uploaded_selected_all_disabled = true;
                $scope.selectAll.uploaded = false;
            } else {
                angular.forEach(newValue, function (staff, i) {
                    staff.unique_id = i;
                });
                /*newValue = */
                $scope.checkGetUploadData(newValue);
            }
        });

        $scope.$watch('staff', function (newValue) {
            $scope.selected_all_disabled = false;
            if (newValue.length === 0) {
                $scope.selected_all_disabled = true;
                $scope.selectAll.exist = false;
            } else {

            }
        });
        $scope.$watch('existSelected', function (newValue) {
            if (newValue === true) {
                $('#delBtn').show();
            } else {
                $('#delBtn').hide();
            }
        });

    }]);


ssoApp.controller('AddNewModalInstanceCtrl',
    ['$scope', '$http', 'toastr', 'toastrService', '$uibModalInstance', '$rootScope', 'param', 'Scopes', function (
        $scope, $http, toastr, toastrService, $uibModalInstance, $rootScope, param, Scopes) {
        console.log("AddNewModalInstanceCtrl");

        toastrService.setToastrOption();
        Scopes.store('AddNewModalInstanceCtrl', $scope);

        $scope.addStaffItemData = {};
        $scope.ok = function () {
            if (param.tableId === "uploaded_staff") {
                var keepGoing = true;
                angular.forEach(Scopes.get('adminStaffController').uploaded_data, function (item, i) {
                    if (keepGoing) {
                        if (item.staff_code === $scope.addStaffItemData.staff_code) {
                            toastr.error("Duplicate StaffCode: " + item.staff_code);
                            keepGoing = false;
                        }
                        if (item.staff_email === $scope.addStaffItemData.staff_email) {
                            toastr.error("Duplicate StaffEmail: " + item.staff_email);
                            keepGoing = false;
                        }
                    }
                });
                if (keepGoing) {
                    Scopes.get('adminStaffController').uploaded_data.push($scope.addStaffItemData);
                    $uibModalInstance.close();
                }
            } else if (param.tableId === "staff") {
                // console.log($scope.addStaffItemData);
                // return false;
                $http({
                    method: "POST",
                    url: base_url + 'admin/staff/store',
                    data: $scope.addStaffItemData
                }).then(function (response) {
                    var errors = '';
                    if (!angular.isUndefined(response.data.errors) && response.data.errors != null) {
                        angular.forEach(response.data.errors, function (error, index) {
                            if (angular.isObject(error)) {
                                angular.forEach(error, function (e, i) {
                                    errors += '<p>' + e + '</p>';
                                });
                            } else {
                                errors += '<p>' + error + '</p>';
                            }
                        });
                        toastr.error(errors, '');
                    } else if (angular.isObject(response.data.result) && !angular.isUndefined(response.data.result.errors)) {
                        angular.forEach(response.data.result.errors, function (error, index) {
                            if (angular.isObject(error)) {
                                angular.forEach(error, function (e, i) {
                                    errors += '<p>' + e + '</p>';
                                });
                            } else {
                                errors += '<p>' + error + '</p>';
                            }
                        });
                        toastr.error(errors, '');
                    }
                    if (angular.isObject(response.data.result) && !angular.isUndefined(response.data.result.status) && response.data.result.status) {
                        toastr.success('Successfully Added', '');
                        $uibModalInstance.close();
                    }
                }, function (response) {
                });
            }
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };
    }]);

ssoApp.controller('EditStaffModalInstanceCtrl',
    ['$scope', '$http', 'toastr', 'toastrService', '$uibModalInstance', '$rootScope', 'param', 'Scopes', function (
        $scope, $http, toastr, toastrService, $uibModalInstance, $rootScope, param, Scopes) {
        console.log("EditStaffModalInstanceCtrl");
        toastrService.setToastrOption();

        Scopes.store('EditStaffModalInstanceCtrl', $scope);
        $scope.editStaffItemData = {};
        if (param.tableId === "uploaded_staff") {
            $scope.editStaffItemData = angular.copy(Scopes.get('adminStaffController').uploaded_data[param.index]);
        } else if (param.tableId === "staff") {
            $scope.editStaffItemData = angular.copy(Scopes.get('adminStaffController').staff[param.index]);
            if ($scope.editStaffItemData.staff_pin_type === "1") {
                $scope.editStaffItemData.staff_pin_type_text = "Specified";
            } else if ($scope.editStaffItemData.staff_pin_type === "0") {
                $scope.editStaffItemData.staff_pin_type_text = "Random";
            }
            $scope.editStaffItemData.staff_email = $scope.editStaffItemData.user.email;
        }

        // console.log($scope.editStaffItemData);

        $scope.ok = function () {
            if (param.tableId === "uploaded_staff") {
                var keepGoing = true;
                angular.forEach(Scopes.get('adminStaffController').uploaded_data, function (item, i) {
                    if (keepGoing) {
                        if (item.staff_code === $scope.editStaffItemData.staff_code && param.index !== i) {
                            toastr.error("Duplicate StaffCode: " + item.staff_code);
                            keepGoing = false;
                        }
                        if (item.staff_email === $scope.editStaffItemData.staff_email && param.index !== i) {
                            toastr.error("Duplicate StaffEmail: " + item.staff_email);
                            keepGoing = false;
                        }
                    }
                });
                if (keepGoing) {
                    $uibModalInstance.close();
                }

                // $uibModalInstance.close();
            } else if (param.tableId === "staff") {

                var keepGoing = true;
                angular.forEach(Scopes.get('adminStaffController').staff, function (item, i) {
                    if (keepGoing) {
                        if (item.staff_code === $scope.editStaffItemData.staff_code && param.index !== i) {
                            toastr.error("Duplicate StaffCode: " + item.staff_code);
                            keepGoing = false;
                        }
                        if (item.staff_email === $scope.editStaffItemData.staff_email && param.index !== i) {
                            toastr.error("Duplicate StaffEmail: " + item.staff_email);
                            keepGoing = false;
                        }
                    }
                });
                if (keepGoing) {
                    $http({
                        method: "POST",
                        url: base_url + 'admin/staff/update',
                        data: $scope.editStaffItemData
                    }).then(function (response) {
                        var errors = '';
                        if (!angular.isUndefined(response.data.errors) && response.data.errors != null) {
                            angular.forEach(response.data.errors, function (error, index) {
                                if (angular.isObject(error)) {
                                    angular.forEach(error, function (e, i) {
                                        errors += '<p>' + e + '</p>';
                                    });
                                } else {
                                    errors += '<p>' + error + '</p>';
                                }
                            });
                            toastr.error(errors, '');
                        } else if (angular.isObject(response.data.result) && !angular.isUndefined(response.data.result.errors)) {
                            angular.forEach(response.data.result.errors, function (error, index) {
                                if (angular.isObject(error)) {
                                    angular.forEach(error, function (e, i) {
                                        errors += '<p>' + e + '</p>';
                                    });
                                } else {
                                    errors += '<p>' + error + '</p>';
                                }
                            });
                            toastr.error(errors, '');
                        }
                        if (angular.isObject(response.data.result) && !angular.isUndefined(response.data.result.status) && response.data.result.status) {
                            toastr.success('Successfully Updated', '');
                            $uibModalInstance.close();
                        }
                    }, function (response) {
                    });
                }
            }
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };
    }]);