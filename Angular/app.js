function () {
    'use strict';

    var underscore = angular.module('underscore', []);
    underscore.factory('_', ['$window', function ($window) {
        return $window._; // assumes underscore has already been loaded on the page
    }]);

    var toastr = angular.module('toastr', []);
    toastr.factory('toastr', ['$window', function ($window) {
        return $window.toastr; // assumes underscore has already been loaded on the page
    }]);
})();

function azure_image_url(image) {

    if (image !== 'default_school_logo.png' && image !== 'school_logo.png') {
        return _azure_image_url_path + 'images/school/' + _school_id + '/' + image;
    } else {
        return base_url + 'public/images/' + image;
    }
}

Dropzone.autoDiscover = false;

'use strict';
var ssoApp = angular.module('ssoApp', ['ngIdle', 'ngResource', 'thatisuday.dropzone', 'underscore', 'toastr', 'ui.bootstrap', 'ui.select', 'purplefox.numeric', 'frapontillo.bootstrap-switch', 'datatables', 'ngMaterial', 'ngMessages', 'ngSanitize', 'puElasticInput', 'ng.deviceDetector', 'angular.css.injector', 'treeGrid', 'io-barcode'], function ($httpProvider) {
    // Use x-www-form-urlencoded Content-Type
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
    $httpProvider.defaults.headers.post['X-Requested-With'] = 'XMLHttpRequest';
    /**
     * The workhorse; converts an object to x-www-form-urlencoded serialization.
     * @param {Object} obj
     * @return {String}
     */
    var param = function (obj) {
        var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

        for (name in obj) {
            value = obj[name];

            if (value instanceof Array) {
                for (i = 0; i < value.length; ++i) {
                    subValue = value[i];
                    fullSubName = name + '[' + i + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += param(innerObj) + '&';
                }
            }
            else if (value instanceof Object) {
                for (subName in value) {
                    subValue = value[subName];
                    fullSubName = name + '[' + subName + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += param(innerObj) + '&';
                }
            }
            else if (value !== undefined && value !== null)
                query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
            else if (value == null)
                query += encodeURIComponent(name) + '=' + '&';
        }

        return query.length ? query.substr(0, query.length - 1) : query;
    };

    // Override $http service's default transformRequest
    $httpProvider.defaults.transformRequest = [function (data) {
        return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
    }];
})

    .config(function ($provide, $httpProvider, $mdDateLocaleProvider) {


        $mdDateLocaleProvider.formatDate = function (date) {
            return moment(date).format('DD-MM-YYYY');
        };

        // Intercept http calls.
        $provide.factory('newHttpInterceptor', function ($q) {
            var blockUIOption = {animate: true}; // default option
            return {
                // On request success
                request: function (config) {
                    App.blockUI(blockUIOption);
                    // console.log(config); // Contains the data about the request before it is sent.

                    // Return the config or wrap it in a promise if blank.
                    return config || $q.when(config);
                },

                // On request failure
                requestError: function (rejection) {
                    App.unblockUI();



                    // Return the promise rejection.
                    return $q.reject(rejection);
                },

                // On response success
                response: function (response) {
                    App.unblockUI();
                    // console.log(response); // Contains the data from the response.

                    // Return the response or promise.
                    return response || $q.when(response);
                },

                // On response failture
                responseError: function (rejection) {
                    App.unblockUI();
                    // console.log(rejection); // Contains the data about the error.

                    // Return the promise rejection.
                    return $q.reject(rejection);
                },
                setBlockUIOption: function (params) {
                    blockUIOption = params;
                }
            };
        });

        // Add the interceptor to the $httpProvider.
        $httpProvider.interceptors.push('newHttpInterceptor');

    }).config(function (dropzoneOpsProvider) {
        dropzoneOpsProvider.setOptions({
            url: '/',
            acceptedFiles: '*',
            addRemoveLinks: true,
            // dictDefaultMessage : 'Click to add or drop files',
            dictRemoveFile: 'Remove File',
            // dictResponseError : 'Could not upload this file'
        });
    }).config(function (uiSelectConfig) {
        uiSelectConfig.theme = 'select2';
        uiSelectConfig.resetSearchInput = true;
        uiSelectConfig.appendToBody = true;
    }).factory('toastrService', function () {
        return {
            setToastrOption: function () {
                toastr.options = {
                    "closeButton": true,
                    "debug": false,
                    "positionClass": "toast-bottom-left",
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "4000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                };
            }
        };
    }).factory('Scopes', function ($rootScope) {
        var mem = {};

        return {
            store: function (key, value) {
                $rootScope.$emit('scope.stored', key);
                mem[key] = value;
            },
            get: function (key) {
                return mem[key];
            }
        };
    }).factory('AddCss', ['deviceDetector', 'cssInjector', function (deviceDetector, cssInjector) {
        this.forFirefox = function () {
            if (deviceDetector.browser === "firefox") {
                cssInjector.add(base_url + "public/css/custom_firefox.css");
            }
            return this;
        };
        this.forIE = function () {
            if (deviceDetector.browser === "ie") {
                cssInjector.add(base_url + "public/css/custom_ie.css");
            }
            return this;
        };
        return this;
    }]).filter('propsFilter', function () {
        return function (items, props) {
            var out = [];

            if (angular.isArray(items)) {
                items.forEach(function (item) {
                    var itemMatches = false;

                    var keys = Object.keys(props);
                    for (var i = 0; i < keys.length; i++) {
                        var prop = keys[i];
                        var text = props[prop].toLowerCase();
                        if (item[prop].toString().toLowerCase().indexOf(text) !== -1) {
                            itemMatches = true;
                            break;
                        }
                    }

                    if (itemMatches) {
                        out.push(item);
                    }
                });
            } else {
                // Let the output be the input untouched
                out = items;
            }

            return out;
        }
    }).factory('AlertErrorsWarningsService', ['$rootScope', 'toastrService', function ($rootScope, toastrService) {

        return {
            success: function (response, successCallBack) {
                toastrService.setToastrOption();

                if (angular.isObject(response.data.result) && !angular.isUndefined(response.data.result.status) && response.data.result.status) {
                    if (typeof  successCallBack === 'function') {
                        successCallBack();
                    } else {
                        toastr.success('Success', '');
                    }
                }

                return {
                    errors: function (errorCallBack) {
                        if ((angular.isObject(response.data.result) && !angular.isUndefined(response.data.result.status) && !response.data.result.status) || !response.data.result) {
                            if (typeof  errorCallBack === 'function') {
                                errorCallBack();
                            }
                        }
                        var errors = '';
                        if (!angular.isUndefined(response.data.errors) && !_.isEmpty(response.data.errors)) {

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
                        } else if (angular.isObject(response.data.result) && !angular.isUndefined(response.data.result.errors) && !_.isEmpty(response.data.result.errors)) {
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


                        return {
                            warnings: function (warningCallBack) {
                                if (angular.isObject(response.data.result) && !angular.isUndefined(response.data.result.warnings)
                                    && response.data.result.warnings.length > 0) {
                                    var warnings = '';
                                    angular.forEach(response.data.result.warnings, function (warning, index) {
                                        if (angular.isObject(warning)) {
                                            angular.forEach(warning, function (e, i) {
                                                warnings += '<p>' + e + '</p>';
                                            });
                                        } else {
                                            warnings += '<p>' + warning + '</p>';
                                        }
                                    });
                                    if (typeof warningCallBack === 'function') {
                                        warningCallBack();
                                    }
                                    toastr.warning(warnings, '');
                                } else {

                                }
                            }
                        }
                    },
                    warnings: function (warningCallBack) {
                        if (angular.isObject(response.data.result)
                            && !angular.isUndefined(response.data.result.warnings)
                            && response.data.result.warnings.length > 0) {
                            var warnings = '';
                            angular.forEach(response.data.result.warnings, function (warning, index) {
                                if (angular.isObject(warning)) {
                                    angular.forEach(warning, function (e, i) {
                                        warnings += '<p>' + e + '</p>';
                                    });
                                } else {
                                    warnings += '<p>' + warning + '</p>';
                                }
                            });
                            toastr.warning(warnings, '');
                            if (typeof warningCallBack === 'function') {
                                warningCallBack();
                            }
                        } else {

                        }
                    }
                }
            }

        };
    }]).service('anchorSmoothScroll', function () {

        this.scrollTo = function (eID) {
            // This scrolling function
            // is from http://www.itnewb.com/tutorial/Creating-the-Smooth-Scroll-Effect-with-JavaScript
            var startY = currentYPosition();
            var stopY = elmYPosition(eID);
            var distance = stopY > startY ? stopY - startY : startY - stopY;
            if (distance < 100) {
                scrollTo(0, stopY);
                return;
            }
            var speed = Math.round(distance / 100);
            if (speed >= 20) speed = 20;
            var step = Math.round(distance / 25);
            var leapY = stopY > startY ? startY + step : startY - step;
            var timer = 0;
            if (stopY > startY) {
                for (var i = startY; i < stopY; i += step) {
                    setTimeout("window.scrollTo(0, " + leapY + ")", timer * speed);
                    leapY += step;
                    if (leapY > stopY) leapY = stopY;
                    timer++;
                }
                return;
            }
            for (var i = startY; i > stopY; i -= step) {
                setTimeout("window.scrollTo(0, " + leapY + ")", timer * speed);
                leapY -= step;
                if (leapY < stopY) leapY = stopY;
                timer++;
            }

            function currentYPosition() {
                // Firefox, Chrome, Opera, Safari
                if (self.pageYOffset) return self.pageYOffset;
                // Internet Explorer 6 - standards mode
                if (document.documentElement && document.documentElement.scrollTop)
                    return document.documentElement.scrollTop;
                // Internet Explorer 6, 7 and 8
                if (document.body.scrollTop) return document.body.scrollTop;
                return 0;
            }

            function elmYPosition(eID) {
                var elm = document.getElementById(eID);
                var y = elm.offsetTop;
                var node = elm;
                while (node.offsetParent && node.offsetParent !== document.body) {
                    node = node.offsetParent;
                    y += node.offsetTop;
                }
                return y;
            }

        };

    }).directive('getHelp', ['AlertErrorsWarningsService', '$mdDialog', '$http', '$sce', function (AlertErrorsWarningsService, $mdDialog, $http, $sce) {
        return {
            restrict: 'A',
            scope: {
                url: "@"
            },
            link: function (scope, element, attr) {
                function helpDialogController(scope, $mdDialog) {
                    scope.helpEditorOptions = {
                        language: 'en',
                        uiColor: '#cfd1cf',
                        extraPlugins: 'imagebrowser',
                        // customConfig: '/public/app/controllers/config_help_editor.js',
                        filebrowserBrowseUrl: '/assets/global/plugins/ckfinder/ckfinder.html',
                        filebrowserImageBrowseUrl: '/assets/global/plugins/ckfinder/ckfinder.html?type=Images',
                        // filebrowserImageBrowseUrl : 'ssss/ckfinder/ckfinder.html?type=Images',
                        filebrowserFlashBrowseUrl: 'ckfinder/ckfinder.html?type=Flash',
                        filebrowserUploadUrl: '/assets/global/plugins/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
                        filebrowserImageUploadUrl: '/assets/global/plugins/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
                        filebrowserFlashUploadUrl: '/assets/global/plugins/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash'
                    };
                    scope.closePopup = function () {
                        $mdDialog.cancel();
                    };
                    scope.editBodyText = function () {
                        var el = document.getElementsByClassName('admin_dialog_content_height');
                        var el_2 = document.getElementsByClassName('cke_contents');
                        el_2[0].style.height = el[0] ? el[0].offsetHeight - 48 + 'px' : 'auto';
                        scope.edit_clicked = true;
                        if (scope.response_body_text) {
                            scope.body_text_local = scope.response_body_text;
                        }
                    };

                    scope.saveBodyText = function () {
                        $http({
                            method: "POST",
                            url: base_url + 'help/saveHelpBodyText',
                            data: {page_url: scope.url, body_text: scope.response_body_text}
                        }).then(function (response) {
                            AlertErrorsWarningsService.success(response, function () {
                                toastr.success('Success', '');
                                scope.body_text = $sce.trustAsHtml(scope.response_body_text);
                                scope.edit_clicked = false;
                            }).errors().warnings();
                        }, function (response) {
                            console.log('error');
                        });
                    };
                    scope.is_changed = function () {
                        if (scope.response_body_text) {
                            return $sce.getTrustedHtml(scope.body_text_local) === $sce.getTrustedHtml(scope.response_body_text);
                        } else {
                            return false;
                        }
                    }
                }

                element.on('click', function (ev) {
                    $http({
                        method: "POST",
                        url: base_url + 'help/getHelp',
                        data: {page_url: scope.url}
                    }).then(function (response) {
                        AlertErrorsWarningsService.success(response, function () {
                            scope.response_body_text = response.data.result.body_text;
                            scope.body_text = $sce.trustAsHtml(scope.response_body_text);
                            scope.is_super_admin = is_super_admin ? true : false;
                            $mdDialog.show({
                                controller: helpDialogController,
                                templateUrl: scope.is_super_admin ? base_url + 'public/app/view/helpPopupAdminView.html' : base_url + 'public/app/view/helpPopupView.html',
                                parent: angular.element(document.body),
                                // targetEvent: event,
                                bindToController: true,
                                scope: scope,
                                clickOutsideToClose: true,
                                preserveScope: true,
                                fullscreen: true // Only for -xs, -sm breakpoints.
                            })
                                .then(function (answer) {
                                    // here your code
                                    scope.edit_clicked = false;


                                }, function () {
                                    // here your code
                                    scope.edit_clicked = false;
                                });
                        }).errors().warnings();
                    }, function (response) {
                        console.log('error');
                    });

                });
                scope.checkingBodyText = function () {
                    $http({
                        method: "POST",
                        url: base_url + 'help/getHelp',
                        data: {page_url: scope.url}
                    }).then(function (response) {
                        AlertErrorsWarningsService.success(response, function () {
                            scope.response_body_text = response.data.result.body_text;
                            if (scope.response_body_text && scope.response_body_text.trim().length > 0) {
                                element.removeClass('hidden');
                            }
                        }).errors().warnings();
                    }, function (response) {
                        console.log('error');
                    });
                };
                scope.checkingBodyText();

            }
        };
    }])
    .config(function (IdleProvider, KeepaliveProvider) {
        IdleProvider.idle(3300); // 55 min
        IdleProvider.timeout(60);
        KeepaliveProvider.interval(600); // heartbeat every 10 min
        KeepaliveProvider.http('/index/is_alive'); // URL that makes sure session is alive
    })
    .run(function ($rootScope, Idle, $window, $uibModal) {

        function closeIdleModals() {
            if ($rootScope.idleWarning) {
                $rootScope.idleWarning.dismiss('cancel');
                $rootScope.idleWarning = null;
            }

            if ($rootScope.idleTimedout) {
                $rootScope.idleTimedout.dismiss('cancel');
                $rootScope.idleTimedout = null;
            }
        }

        Idle.watch();
        $rootScope.$on('IdleStart', function () { /* Display modal warning or sth */
            closeIdleModals();
            $rootScope.idleWarning = $uibModal.open({
                templateUrl: 'warning-dialog.html',
                windowClass: 'modal-danger'
            });
        });
        $rootScope.$on('IdleTimeout', function () { /* Logout user */

            closeIdleModals();
            $rootScope.idleTimedout = $uibModal.open({
                templateUrl: 'timedout-dialog.html',
                windowClass: 'modal-danger'
            });

            $rootScope.idleTimedout.result.then(function () {
                //success
            }, function () {
                $window.location.href = base_url + '/auth/logout';
            });
        });

        $rootScope.$on('IdleEnd', function () {
            closeIdleModals();
        });

        $rootScope.idleModalClose = function () {

            $rootScope.idleTimedout.dismiss('cancel');

        };
    });

if (is_super_admin) {
    ssoApp.requires.push('ngCkeditor');
}