ssoApp.directive('compileTemplate', function ($compile, $parse) {
    return {
        restrict: 'A',
        link: function (scope, element, attr) {
            var parsed = $parse(attr.ngBindHtml);

            //Recompile if the template changes
            scope.$watch(
                function () {
                    return (parsed(scope) || '').toString();
                },
                function () {
                    $compile(element, null, -9999)(scope);  //The -9999 makes it skip directives so that we do not recompile ourselves
                }
            );
        }
    };
}).directive('preventDefault', function () {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            element.bind('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
            });
        }
    };
}).directive('icheck', ['$timeout', '$parse', '$compile', function ($timeout, $parse, $compile) {
    return {
        restrict: 'A',
        require: '?ngModel',
        link: function ($scope, element, $attrs, ngModel) {
            return $timeout(function () {
                var value;
                value = $attrs['value'];

                $scope.$watch($attrs.icheckUibTooltip, function (newValue, old_value) {
                    if (angular.isDefined(newValue)) {
                        var parent = $(element).parent();
                        var ins = parent.children('ins');
                        ins.attr('uib-tooltip', newValue);
                        ins.attr('tooltip-append-to-body', true);
                        ins.attr('tooltip-placement', 'top');
                        parent.append('<span></span>');
                        parent.children('span').append(ins);
                        $compile(parent.children('span'))($scope);

                    }
                });
                $scope.$watch($attrs['ngModel'], function (newValue) {
                    $(element).iCheck('update');
                });

                $scope.$watch($attrs['ngDisabled'], function (newValue) {
                    $(element).iCheck(newValue ? 'disable' : 'enable');
                    $(element).iCheck('update');
                });

                return $(element).iCheck({
                    checkboxClass: $attrs['checkboxclass'] || 'icheckbox_square-blue',
                    radioClass: $attrs['radioclass'] || 'iradio_square-blue'

                }).on('ifChanged', function (event) {
                    if ($(element).attr('type') === 'checkbox' && $attrs['ngModel']) {
                        $scope.$apply(function () {
                            return ngModel.$setViewValue(event.target.checked);
                        })
                    }
                }).on('ifClicked', function (event) {
                    if ($(element).attr('type') === 'radio' && $attrs['ngModel']) {
                        return $scope.$apply(function () {
                            if (ngModel.$viewValue !== value) {
                                return ngModel.$setViewValue(value);
                            } else {
                                return ngModel.$render();
                            }
                        });
                    }
                });
            });
        }
    }
}]).directive('newDatePicker', ['$timeout', function ($timeout) {
    return {
        restrict: 'A',
        link: function (scope, elem, attrs) {
            // timeout internals are called once directive rendering is complete
            $timeout(function () {
                $(elem).datepicker();
            });
        }
    };
}]).directive('confirmedClick', function ($parse, $q, $compile, $rootScope) {

    var box = '<div class="box">' +
        '<div class="arrow"></div>' +
        '<h3>Are you sure?</h3> ' +
        '<div class="popover-content text-center">' +
        '<div class="btn-group">' +
        '<button class="btn btn-sm btn-success" ng-click="close($event, true)">' +
        '<i class="glyphicon glyphicon-ok"></i> Yes</button>' +
        '<button class="btn btn-sm btn-danger" ng-click="close($event)">' +
        '<i class="glyphicon glyphicon-remove"></i> No</button></div></div>' +
        '</div>';

    return {

        link: function (scope, element, attrs) {

            element.on('click', function () {
                if (element.attr("disabled") === 'disabled') {
                    return false;
                }
                var fn = $parse(attrs.confirmedClick);
                var boxScope = $rootScope.$new();
                var boxElement = $compile(box)(boxScope);

                element.attr('disabled', 'disabled');
                element.append(boxElement);

                boxScope.close = function (event, execute) {
                    event.stopPropagation();
                    element.removeAttr('disabled');
                    boxElement.remove();
                    if (execute) {
                        fn(scope, {$event: event});
                    }
                };
            });
        }
    };
}).directive("passwordVerify", function () {
    return {
        require: "ngModel",
        scope: {
            passwordVerify: '='
        },
        link: function (scope, element, attrs, ctrl) {
            scope.$watch(function () {
                var combined;

                if (scope.passwordVerify || ctrl.$viewValue) {
                    combined = scope.passwordVerify + '_' + ctrl.$viewValue;
                }
                return combined;
            }, function (value) {
                if (value) {
                    ctrl.$parsers.unshift(function (viewValue) {
                        var origin = scope.passwordVerify;
                        if (origin !== viewValue) {
                            ctrl.$setValidity("passwordVerify", false);
                            return undefined;
                        } else {
                            ctrl.$setValidity("passwordVerify", true);
                            return viewValue;
                        }
                    });
                }
            });
        }
    };
}).directive('stringToNumber', function () {
    return {
        require: 'ngModel',
        link: function (scope, element, attrs, ngModel) {
            ngModel.$parsers.push(function (value) {
                return '' + value;
            });
            ngModel.$formatters.push(function (value) {
                return parseFloat(value);
            });
        }
    };
});
