angular.module('app').directive('spinner', function () 
{
    return {
        scope: { label: '=', value: '=', min: '=', max: '=', step: '=', loop: "=" },
        templateUrl: 'directives/spinner.html',
        controllerAs: 'vm',
        bindToController: true,
        controller: function ()
        {
            var vm = this;
            window.spinner = this;

            vm.value = valueOrDefault(vm.value, 0);
            vm.step = valueOrDefault(vm.step, 1);
            vm.isMobile = true; //valueIfDefined("fpConfig.isMobile");
            vm.isMobile = ProfileService.isMobile();

            vm.addValue =  function (incr)
            {
                if(incr > 0 && vm.value == vm.max) 
                    return vm.value = vm.loop ? vm.min : vm.value;
                if(incr < 0 && vm.value == vm.min) 
                    return vm.value = vm.loop ? vm.max : vm.value;

                vm.value += incr * vm.step;
                vm.value = Math.roundDigits(vm.value, 2);
                return vm.value;
            }
        }         
    };
});

