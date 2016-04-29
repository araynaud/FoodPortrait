angular.module('app').directive('minMax', [ "$timeout", function ($timeout) 
{

/*
horizontal bar that allows selection of a min and max value

                      [350]
100 ----+---------------+---- 400
      [134]

values= array of possible values
passed directly or computed via min,max,step

*/

    return {
        scope: { label: "@", values: "=", minValue: "=", maxValue: "=", min: "=", max: "=", step: "=", change: '=' },
        templateUrl: '../foodportrait/directives/minmax.html',
        controllerAs: 'vm',
        bindToController: true,
        controller: function ()
        {
            var vm = this;
            window.minmax = vm;
            var timeout = null;
            vm.init = function()
            {
                vm.isMobile = vm.mobile || app.isMobile();
                vm.step = valueOrDefault(vm.step, 1);

                if(!vm.values || !vm.values.length)
                {
                    vm.values = [];
                    var i = vm.min;
                    for(i = vm.min; i <= vm.max; i+= vm.step)
                        vm.values.push(i);
                    i -= vm.step;
                    if(i<vm.max)
                        vm.values.push(vm.max);
                }
            };

            vm.minMaxArray = function()
            {
                return [vm.minValue, vm.maxValue];
            };

            vm.selectMinValue = function(val) 
            {
                if(vm.selectMin)
                    vm.setMinValue(val); 
            };

            vm.setMinValue = function(val) 
            {
                if(isMissing(vm.maxValue) ||  val <= vm.maxValue)
                    vm.minValue = val;
            };

            vm.selectMaxValue = function(val) 
            {
                if(vm.selectMax)
                    vm.setMaxValue(val); 
            };

            vm.setMaxValue = function(val) 
            {
                if(isMissing(vm.minValue) || val >= vm.minValue)
                    vm.maxValue = val;
            };

            vm.hasMaxValue = function()
            {
                return !isMissing(vm.maxValue);
            }

            vm.hasMinValue = function()
            {
                return !isMissing(vm.minValue);
            }

            vm.clearMinValue = function() 
            {
                delete vm.minValue;
            }

            vm.clearMaxValue = function() 
            {
                delete vm.maxValue;
            }

            vm.cancelTimeout = function (e) 
            {
                if (timeout)    $timeout.cancel(timeout);
                timeout = null;
            };

            vm.init();
        }         
    };
}]);
