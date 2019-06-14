ssoApp.requires.push('ngMaterialDatePicker');
ssoApp.controller('adminAccessController',
    ['$scope', '$http', '$log', 'toastr', 'toastrService', 'DTOptionsBuilder', 'DTColumnBuilder', '$timeout', '$compile', '$uibModal', '$mdDialog', '$q', 'AlertErrorsWarningsService',
        function ($scope, $http, $log, toastr, toastrService, DTOptionsBuilder, DTColumnBuilder, $timeout, $compile, $uibModal, $mdDialog, $q, AlertErrorsWarningsService) {

            toastrService.setToastrOption();

            $scope.onText = 'ON';
            $scope.offText = 'OFF';
            $scope.isActive = true;
            $scope.size = 'small';
            $scope.onColor = 'success';
            $scope.animate = true;
            $scope.radioOff = true;
            $scope.handleWidth = "auto";
            $scope.labelWidth = "auto";
            $scope.inverse = true;
            $scope.start_date_required = false;
            $scope.years_key_ids = [];
            $scope.date_time_now = window._date_time_now;

            $scope.isSelectedCoOrdinator = '1';
            $scope.isSelectedHoLA = '1';
            $scope.isSelectedCourseCounsellor = '1';
            $scope.isSelectedHomeTeacher = '1';

            $scope.accessCoOrdinatorId = null;
            $scope.accessHoLAId = null;
            $scope.accessCounsellorId = null;
            $scope.accessHomeTeacherId = null;

            $scope.moment_format = 'DD-MM-YYYY HH:mm';

            //copy HTML text button
            $scope.copy_html_text = function () {
                var copyDiv = document.querySelector('.textarea_text');
                copyDiv.style.display = 'block';
                copyDiv.select();
                try {
                    var successful = document.execCommand('copy', false, null);
                    copyDiv.style.display = 'none';
                    var msg = successful ? 'successful' : 'unsuccessful';
                    toastr.success('HTML link successfully copied to your clipboard', '');
                } catch (err) {
                    toastr.success('Unable to copy', '');
                }
            };


            // Display date and clock
            $scope.tickInterval = 1000; //ms

            var tick = function (first_time) {

                if (first_time) {
                    $scope.clock = moment($scope.date_time_now, 'DD-MM-YYYY HH:mm:ss').format('HH:mm:ss DD/MM/YYYY'); // get the current time
                } else {
                    $scope.clock = moment($scope.clock, 'HH:mm:ss DD/MM/YYYY').add(1, 'seconds').format('HH:mm:ss DD/MM/YYYY');
                }
                // $scope.clock = moment().format('HH:mm:ss DD/MM/YYYY');
                $timeout(tick, $scope.tickInterval); // reset the timer
            };

            // Start the timer
            tick(true);

            $scope.toggleStaff = function (role_id) {

                switch (role_id) {
                    case 6:
                        $scope.updateAccess($scope.accessCoOrdinatorId, role_id, $scope.isSelectedCoOrdinator);
                        $log.info('CoOrdinator: ' + $scope.isSelectedCoOrdinator);
                        break;
                    case 5:

                        $scope.updateAccess($scope.accessHoLAId, role_id, $scope.isSelectedHoLA);
                        $log.info('HoLA: ' + $scope.isSelectedHoLA);
                        break;
                    case 4:
                        $scope.updateAccess($scope.accessCounsellorId, role_id, $scope.isSelectedCourseCounsellor);
                        $log.info('CourseCounsellor: ' + $scope.isSelectedCourseCounsellor);
                        break;
                    case 3:
                        $scope.updateAccess($scope.accessHomeTeacherId, role_id, $scope.isSelectedHomeTeacher);
                        $log.info('HomeTeacher: ' + $scope.isSelectedHomeTeacher);
                        break;
                    default:
                        $log.info('Nothing: ' + role_id);
                }
            };
            $scope.updateAccess = function (access_id, role_id, status) {

                $http({
                    method: "POST",
                    url: base_url + 'admin/access/update',
                    data: {
                        id: access_id,
                        role_id: role_id,
                        status: status
                    }
                }).then(function (response) {
                    AlertErrorsWarningsService.success(response, function () {
                        toastr.success('Successfully Updated', '');
                    }).errors().warnings();
                }, function (response) {

                });
            };

            $scope.getAllAccess = function () {
                $http({
                    method: "POST",
                    url: base_url + 'admin/access/getAllAccess',
                    data: {}
                }).then(function (response) {
                    angular.forEach(response.data, function (item, index) {
                        switch (item.role_id) {
                            case 6:
                                $scope.isSelectedCoOrdinator = item.status;
                                $scope.accessCoOrdinatorId = item.id;
                                break;
                            case 5:
                                $scope.isSelectedHoLA = item.status;
                                $scope.accessHoLAId = item.id;
                                break;
                            case 4:
                                $scope.isSelectedCourseCounsellor = item.status;
                                $scope.accessCounsellorId = item.id;
                                break;
                            case 3:
                                $scope.isSelectedHomeTeacher = item.status;
                                $scope.accessHomeTeacherId = item.id;
                                break;
                        }
                    });

                }, function (response) {

                });
            };
            $scope.getAllAccess();


            var lang = {
                "decimal": "",
                "emptyTable": "No data available in table",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Show _MENU_",
                "loadingRecords": "Loading...",
                "processing": "Processing...",
                "search": "",
                "searchPlaceholder": "Search",
                "zeroRecords": "No matching records found",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                },
                "aria": {
                    "sortAscending": ": activate to sort column ascending",
                    "sortDescending": ": activate to sort column descending"
                }
            };


            // help popap
            $scope.customFullscreen = true;


            DTColumnBuilder.newColumn('end_time').withTitle('Schedule start-end'),

                $scope.years = [];
            $scope.dtOptions = [];
            $scope.dtColumns = [];
            var compile_one_time = false;
            $scope.WithPromiseCtrl = function () {
                // for ajax from server side instead of 'fromFnPromise' function  use 'fromSource' example -> DTOptionsBuilder.fromSource(base_url + 'admin/access/getAllYearsWithAccessStatusesTable'). withOption('serverSide', true) .withDisplayLength(1). ...
                $scope.dtOptions = DTOptionsBuilder.fromFnPromise(function () {
                    var defer = $q.defer();
                    $http({
                        method: "POST",
                        url: base_url + 'admin/access/getAllYearsWithAccessStatusesTable',
                        data: {}
                    }).then(function (response) {

                        defer.resolve(response.data);
                    }, function (response) {

                    });
                    return defer.promise;
                }).withDisplayLength(10)
                    .withOption('bLengthChange', true)
                    .withOption('language', lang)
                    .withPaginationType('bootstrap_full_number')
                    .withOption('order', [0, 'asc'])
                    .withOption('bStateSave', true)
                    .withOption('sDom', "<'row'<'col-md-6 col-sm-12'f><'col-md-6 text-right col-sm-12 save_changes_section'>r><'table-responsive't><'row'<'col-md-5 col-sm-12'l><'col-md-7 text-right col-sm-12'p>>")
                    .withOption('createdRow', function (row) {
                        $compile(angular.element(row).contents())($scope);
                    })
                    .withOption('initComplete',
                        function (settings) {
                            compile_one_time = true;
                        })
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
                    });

                $scope.dtColumns = [
                    DTColumnBuilder.newColumn('title').withTitle('Year').withOption('type', 'years').renderWith(function (data, type, full) {
                        if ($scope.years.length === 0 || !$scope.customInArray($scope.years_key_ids, full.id)) {
                            $scope.years.push(full);
                            $scope.years_key_ids.push(full.id);
                        }
                        return full.title;
                    }),
                    DTColumnBuilder.newColumn('status').withTitle('Status').renderWith(function (data, type, full) {

                        var last_key = ($scope.years.length - 1);


                        return '<span ng-if="years[' + last_key + '].access_status.status == 1" class="label label-success">On</span>'
                            + '<span ng-if="years[' + last_key + '].access_status.status == 2" class="label label-warning">Set</span>'
                            + '<span ng-if="years[' + last_key + '].access_status.status != 1 && years[' + last_key + '].access_status.status != 2" class="label label-default">Off</span>';
                    }).notSortable(),
                    DTColumnBuilder.newColumn('schedule_start_end').withTitle('Schedule start-end').renderWith(function (data, type, full) {

                        var last_key = ($scope.years.length - 1); // Getting last key of $scope.years
                        if ($scope.years[last_key].access_status && !_.isUndefined($scope.years[last_key].access_status.schedule_start)) {
                            $scope.years[last_key].access_status.schedule_start = moment($scope.years[last_key].access_status.schedule_start);
                        }
                        if ($scope.years[last_key].access_status && !_.isUndefined($scope.years[last_key].access_status.schedule_end) && $scope.years[last_key].access_status.schedule_end) {
                            $scope.years[last_key].access_status.schedule_end = moment($scope.years[last_key].access_status.schedule_end);
                        }
                        if ($scope.years[last_key].access_status) {
                            if ((moment($scope.years[last_key].access_status.schedule_start) <= moment(moment($scope.date_time_now, 'DD-MM-YYYY HH:mm:ss').format())
                                && ($scope.years[last_key].access_status.schedule_end
                                    && moment($scope.years[last_key].access_status.schedule_end) > moment(moment($scope.date_time_now, 'DD-MM-YYYY HH:mm:ss').format())))
                                || (moment($scope.years[last_key].access_status.schedule_start) <= moment(moment($scope.date_time_now, 'DD-MM-YYYY HH:mm:ss').format()) && !$scope.years[last_key].access_status.schedule_end)
                            ) {

                                $scope.years[last_key].access_status.status = 1; // state on
                            } else if (moment($scope.years[last_key].access_status.schedule_start) > moment(moment($scope.date_time_now, 'DD-MM-YYYY HH:mm:ss').format())) {
                                $scope.years[last_key].access_status.status = 2;  // state set
                            } else {
                                $scope.years[last_key].access_status.status = 0; // state off
                            }
                        }
                        return '<div class="row">'
                            + '<div class="col-xs-6">' // statr datetime part
                            + '<div class="form-group margin-bottom-0 text-left">'
                            + '<md-input-container class="md-input-has-placeholder">'
                            + '<label>Start</label>'                          // custom-change attr used as an event of change a date time picker in the  public/js/angular-material-datetimepicker.js  file -> line 413
                            + '<div class="input-group">'                                  // change_statr_end_date is a function() from this file line
                            + '<i class="fa fa-calendar" aria-hidden="true"></i>'
                            + '<input mdc-datetime-picker="" date="true"  format="{{moment_format}}" custom-change="updateAccessData" customchange-params="' + last_key + ',start" time="true" type="text"'
                            + 'placeholder="Date" max-date="years[' + last_key + '].access_status.schedule_end" ng-model="years[' + last_key + '].access_status.schedule_start"  ng-class="[{hasError: start_date_required}]" class=" md-input"'
                            + 'id="input_start_' + full.id + '">'
                            + '</div>'
                            + '</md-input-container>'
                            + '</div>'
                            + '</div>'

                            + '<div class="col-xs-6">' // end datetime part
                            + '<div class="form-group margin-bottom-0 text-left">'
                            + '<md-input-container class="md-input-has-placeholder">'
                            + '<label>End</label>'                                        // custom-change attr used as an event of change a date time picker in the  public/js/angular-material-datetimepicker.js  file -> line 413
                            + '<div class="input-group" tooltip-append-to-body="true" uib-tooltip="{{ !years[' + last_key + '].access_status ?  \'Please at first select start date\' : \'\'}}" >'                                                // change_statr_end_date is a function() from this file -> line
                            + '<i class="fa fa-calendar" aria-hidden="true"></i>'
                            + '<input mdc-datetime-picker="" date="true" time="true"   format="{{moment_format}}" custom-change="updateAccessData" customchange-params="' + last_key + ',end" type="text" '
                            + 'placeholder="Date" min-date="years[' + last_key + '].access_status.schedule_start"="years[' + last_key + '].access_status.schedule_end" ng-model="years[' + last_key + '].access_status.schedule_end" class=" md-input" id="input_end_' + full.id + '" ng-disabled="!years[' + last_key + '].access_status" >'
                            + '</div>'
                            + '</md-input-container>'
                            + '</div>'
                            + '</div>'
                            + '</div>';
                    }).withClass('schedule_column').notSortable(),
                    DTColumnBuilder.newColumn('view_only').withTitle('View only').renderWith(function (data, type, full) {
                        var last_key = ($scope.years.length - 1); // Getting last key of $scope.years
                        if (full.access_status && !_.isUndefined(full.access_status.view_only) && full.access_status.view_only === '1') {
                            full.access_status.view_only = true;
                        } else if (full.access_status && !_.isUndefined(full.access_status.view_only)) {
                            full.access_status.view_only = false;
                        }
                        return '<div class="disabled_tooltip" tooltip-append-to-body="true" uib-tooltip="{{ !years[' + last_key + '].access_status ?  \'Please at first select start date\' : \'\'}}" > <md-checkbox  aria-label="give_access_students" ng-change="updateAccessData(' + last_key + ',' + true + ')" ng-model="years[' + last_key + '].access_status.view_only"  ng-disabled="!years[' + last_key + '].access_status"  class="md-primary" name="give_access_students"></md-checkbox></div>';

                    }).notSortable()
                ];
                $scope.InfoAboutAccess = function ($event, access_status) {
                    console.log(access_status);
                    $scope.current_element = $event.currentTarget;
                    $event.stopPropagation();
                };
            };
            $scope.WithPromiseCtrl();

            $scope.updateAccessData = function (params, _view_only) { // type of params (arguments of the function) is a string, params must be concatenated with ','
                $scope.start_date_required = false;
                var params_ = [];
                if (params && typeof params === 'string' && _.isUndefined(_view_only)) {
                    params_ = params.split(',');
                } else if (!_.isUndefined(_view_only) && _view_only) {
                    params_[0] = params;
                }

                if ((params_.length > 0 && $scope.years[params_[0]] && _view_only) ||
                    (params_.length > 0 && $scope.years[params_[0]] && !_.isUndefined(params_[1]) && !_.isUndefined(params_[2]))) {

                    var start_datetime = $scope.years[params_[0]].access_status && !_.isUndefined($scope.years[params_[0]].access_status.schedule_start) && $scope.years[params_[0]].access_status.schedule_start ? moment($scope.years[params_[0]].access_status.schedule_start).format($scope.moment_format) : null;
                    var end_datetime = $scope.years[params_[0]].access_status && !_.isUndefined($scope.years[params_[0]].access_status.schedule_end) && $scope.years[params_[0]].access_status.schedule_end ? moment($scope.years[params_[0]].access_status.schedule_end).format($scope.moment_format) : null;
                    var _start_datetime = $scope.years[params_[0]].access_status && !_.isUndefined($scope.years[params_[0]].access_status.schedule_start) ? $scope.years[params_[0]].access_status.schedule_start : null;
                    var _end_datetime = $scope.years[params_[0]].access_status && !_.isUndefined($scope.years[params_[0]].access_status.schedule_end) ? $scope.years[params_[0]].access_status.schedule_end : null;

                    if (!_.isUndefined(params_[1]) && !_.isUndefined(params_[2])) {
                        if (params_[1] === 'start') {
                            start_datetime = moment(params_[2]).format($scope.moment_format);
                            _start_datetime = params_[2];
                        } else {
                            end_datetime = moment(params_[2]).format($scope.moment_format);
                            _end_datetime = params_[2];
                        }
                    }
                    var year_level_id = $scope.years[params_[0]].access_status && !_.isUndefined($scope.years[params_[0]].access_status.year_level_id) ? $scope.years[params_[0]].access_status.year_level_id : null;
                    var view_only = $scope.years[params_[0]].access_status && !_.isUndefined($scope.years[params_[0]].access_status.view_only) ? $scope.years[params_[0]].access_status.view_only : false;
                    var year_id = $scope.years[params_[0]].id;
                    var access_status_id = $scope.years[params_[0]].access_status && !_.isUndefined($scope.years[params_[0]].access_status.id) ? $scope.years[params_[0]].access_status.id : null;
                    if (start_datetime) {
                        $http({
                            method: "POST",
                            url: base_url + 'admin/access/update',
                            data: {
                                role_id: 1,
                                year_level_id: year_level_id,
                                year_id: year_id,
                                schedule_start: start_datetime,
                                schedule_end: end_datetime,
                                view_only: view_only,
                                id: access_status_id
                            }
                        }).then(function (response) {
                            AlertErrorsWarningsService.success(response, function () {
                                if (!_.isUndefined(response.data.date_time_now)) {
                                    $scope.date_time_now = response.data.date_time_now;
                                }
                                if (angular.isObject(response.data.result) && !angular.isUndefined(response.data.result.status) && response.data.result.status) {
                                    if ((moment(_start_datetime) <= moment(moment($scope.date_time_now, 'DD-MM-YYYY HH:mm:ss').format())
                                        && (_end_datetime
                                            && moment(_end_datetime) > moment(moment($scope.date_time_now, 'DD-MM-YYYY HH:mm:ss').format())))
                                        || (moment(_start_datetime) <= moment(moment($scope.date_time_now, 'DD-MM-YYYY HH:mm:ss').format()) && !_end_datetime)
                                    ) {

                                        $scope.years[params_[0]].access_status.status = 1; // state on
                                    } else if (moment(_start_datetime) > moment(moment($scope.date_time_now, 'DD-MM-YYYY HH:mm:ss').format())) {
                                        $scope.years[params_[0]].access_status.status = 2;  // state set
                                    } else {
                                        $scope.years[params_[0]].access_status.status = 0; // state off
                                    }

                                    if (!angular.isUndefined(response.data.result.inserted_id)) {
                                        $scope.years[params_[0]].access_status.id = response.data.result.inserted_id;
                                    }
                                    toastr.success('Successfully Updated', '');
                                } else {
                                    if (_view_only) {
                                        if ($scope.years[params_[0]].access_status.view_only) {
                                            $scope.years[params_[0]].access_status.view_only = false;
                                        } else {
                                            $scope.years[params_[0]].access_status.view_only = true
                                        }
                                    }
                                }
                            }).errors().warnings();

                        }, function (response) {

                        });
                    } else {
                        if (_view_only) {
                            if ($scope.years[params_[0]].access_status && !angular.isUndefined($scope.years[params_[0]].access_status.view_only)) {
                                if ($scope.years[params_[0]].access_status.view_only) {
                                    $scope.years[params_[0]].access_status.view_only = false;
                                } else {
                                    $scope.years[params_[0]].access_status.view_only = true
                                }
                            }
                        }
                        $scope.start_date_required = true;
                        toastr.error('A start date and time for selections is required before changing the \'View Only\' option', '');
                    }


                }
            };


            $scope.customInArray = function (data, value) {
                return data.indexOf(value) !== -1;
            };

            $scope.openCalendar = function (ev, index, start_end) {
                ev.preventDefault();
                ev.stopPropagation();

                if (start_end === "start") {
                    $scope.years[index].end_open = false;
                    $scope.years[index].start_open = true;
                } else if (start_end === "end") {
                    $scope.years[index].start_open = false;
                    $scope.years[index].end_open = true;
                }
            };

        }]);