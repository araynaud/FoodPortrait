angular.module('app').directive('spinner', [ "$timeout", "ProfileService", function ($timeout, ProfileService) 
{
    return {
        scope: { label: "=", value: "=", min: "=", max: "=", step: "=", loop: "=", mobile: "=", hold: "=" },
        templateUrl: 'directives/spinner.html',
        controllerAs: 'vm',
        bindToController: true,
        link: function (scope, element, attr, vm) 
        {
            vm.element = element;
            if(!vm.element && !vm.hold) return;            

            vm.element.on("mouseup",    vm.cancelTimeout);
            if(ProfileService.isMobile())
            {
                vm.element.on("touchcancel", vm.cancelTimeout);
                vm.element.on("touchend",    vm.cancelTimeout);
            }
            else
                vm.element.on("mouseleave",  vm.cancelTimeout);
        },
        controller: function ()
        {
            var vm = this;
            window.spinner = this;

            vm.value = valueOrDefault(vm.value, 0);
            vm.step = valueOrDefault(vm.step, 1);
            vm.isMobile = vm.mobile || ProfileService.isMobile();
            var timeout = null;

            vm.onChange = function()
            {
                if(vm.value > vm.max)
                    vm.value = vm.loop ? vm.min : vm.max;
                else if(vm.value < vm.min)
                    vm.value = vm.loop ? vm.max : vm.min;
                return vm.value;                
            };

            vm.addValue = function(incr)
            {
                if(incr > 0 && vm.value == vm.max) 
                    vm.value = vm.loop ? vm.min : vm.value;
                else if(incr < 0 && vm.value == vm.min) 
                    vm.value = vm.loop ? vm.max : vm.value;
                else
                {
                    vm.value += incr * vm.step;
                    vm.value = Math.roundDigits(vm.value, 2);
                }
                if(vm.hold)
                    timeout = $timeout( function () { vm.addValue(incr); } , 200);
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
