angular.module('app').directive('spinner', [ "$timeout", "ProfileService", function ($timeout, ProfileService) 
{
    return {
        scope: { label: "=", value: "=", min: "=", max: "=", step: "=", loop: "=", mobile: "=", hold: "=", change: '=' },
        templateUrl: 'directives/spinner.html',
        controllerAs: 'vm',
        bindToController: true,
        link: function (scope, element, attr, vm) 
        {
            if(!element || !vm.hold) return;            

            element.on("mouseup",    vm.cancelTimeout);
            if(ProfileService.isMobile())
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
            window.spinner = this;

            vm.value = valueOrDefault(vm.value, 0);
            vm.step = valueOrDefault(vm.step, 1);
            vm.isMobile = vm.mobile || ProfileService.isMobile();
            var timeout = null;

            vm.onInputChange = function()
            {
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
        }         
    };
}]);
