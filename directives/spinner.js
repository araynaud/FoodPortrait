angular.module('app').directive('spinner', [ "$timeout", function ($timeout) 
{
    return {
        scope: { label: "@", value: "=", min: "=", max: "=", step: "=", loop: "=", mobile: "=", hold: "=", change: '=' },
        templateUrl: '../foodportrait/directives/spinner.html',
        controllerAs: 'vm',
        bindToController: true,
        link: function (scope, element, attr, vm) 
        {
            if(!element || !vm.hold) return;            

            element.on("mouseup",    vm.cancelTimeout);
            if(app.isMobile())
            {
                element.on("touchcancel", vm.cancelTimeout);
                element.on("touchend",    vm.cancelTimeout);
            }
            else
                element.on("mouseleave",  vm.cancelTimeout);
        },
        controller: function ()
        {
            var vm = this;
            var timeout = null;
            vm.init = function()
            {
                vm.isMobile = vm.mobile || app.isMobile();
                vm.numValue();
                vm.step = valueOrDefault(vm.step, 1);
            };

            //ensure numeric value;
            vm.numValue = function()
            {
                vm.value = valueOrDefault(vm.value, 0);
                vm.value = parseFloat(vm.value);
                if(isNaN(vm.value))
                    vm.value = vm.min || 0;
                return vm.value;
            };

            vm.onInputChange = function()
            {
                vm.numValue();
                vm.oldValue = vm.value;
                if(vm.value > vm.max)
                    vm.value = vm.loop ? vm.min : vm.max;
                else if(vm.value < vm.min)
                    vm.value = vm.loop ? vm.max : vm.min;
                if(angular.isFunction(vm.onchange))
                    vm.onchange(vm.value, vm.oldValue);
                return vm.value;
            };

            vm.addValue = function(incr)
            {
                vm.numValue();
                vm.oldValue = vm.value;
                if(incr > 0 && vm.value == vm.max) 
                    vm.value = vm.loop ? vm.min : vm.value;
                else if(incr < 0 && vm.value == vm.min) 
                    vm.value = vm.loop ? vm.max : vm.value;
                else
                {
                    vm.value += incr * vm.step;
                    vm.value = Math.roundDigits(vm.value, 2);
                }
                if(angular.isFunction(vm.change))
                    vm.change(vm.value, vm.oldValue);

                if(vm.hold)
                    timeout = $timeout(function() { vm.addValue(incr); }, 200);
                return vm.value;
            };

            vm.cancelTimeout = function (e) 
            {
                if (timeout)    $timeout.cancel(timeout);
                timeout = null;
            };

            vm.init();
        }         
    };
}]);
