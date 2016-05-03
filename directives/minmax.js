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
        scope: { label: "@", minValue: "=", maxValue: "=", min: "=", max: "=", step: "=", values: "=", change: '=', showDebug: '=' },
        templateUrl: '../foodportrait/directives/minmax.html',
        controllerAs: 'vm',
        bindToController: true,
        link: function (scope, element, attr, vm)
        {
            vm.bar = element.find("div.bar");
        },
        controller: function ()
        {
            var vm = this;
            window.minmax = vm;
            var timeout = null;
            vm.init = function()
            {
                vm.isMobile = vm.mobile || app.isMobile();
                vm.step = valueOrDefault(vm.step, 1);
                vm.minX = vm.getPercent(valueOrDefault(vm.minValue, vm.min));
                vm.maxX = vm.getPercent(valueOrDefault(vm.maxValue, vm.max));
            };

            vm.minMaxArray = function()
            {
                return [vm.minValue, vm.maxValue];
            };

            vm.sortMinMax = function()
            {
                var mm = vm.minMaxArray();
                if(isMissing(mm[0]) || isMissing(mm[1])) return;
                mm.sortObjectsBy("");
                vm.minValue = mm[0];
                vm.maxValue = mm[1];

                mm=[vm.minX, vm.maxX].sortObjectsBy("");
                vm.minX = mm[0];
                vm.maxX = mm[1];
            };

            vm.range = function()
            {
                if(!vm.hasMinValue() && !vm.hasMaxValue()) return "-";
                if(!vm.hasMinValue()) return "up to " + vm.maxValue;
                if(!vm.hasMaxValue()) return vm.minValue + " and over";
                if(vm.maxValue == vm.minValue) return vm.minValue;
                return String.append(vm.minValue, " to ", vm.maxValue);
            }

            vm.selectMinValue = function(event) 
            {
                if(vm.selectMin)
                    vm.setMinValue(event); 
            };

            vm.selectMaxValue = function(event) 
            {
                if(vm.selectMax)
                    vm.setMaxValue(event); 
            };

            vm.setMinValue = function(event) 
            {
                if(!event)
                { 
                    vm.minX = 0;
                    return vm.minValue = vm.min;
                }

                vm.minValue = vm.getValue(event.pageX);
                if(vm.hasMaxValue())    vm.minValue = Math.min(vm.val, vm.maxValue);
                if(vm.hasMinValue())    vm.minValue = Math.max(vm.minValue, vm.min);
                vm.minX = vm.getPercent(vm.minValue);
            };

            vm.setMaxValue = function(event)
            {
                if(!event)
                { 
                    vm.maxX = 100;
                    return vm.maxValue = vm.max;
                }

                vm.maxValue = vm.getValue(event.pageX);
                if(vm.hasMinValue())    vm.maxValue = Math.max(vm.val, vm.minValue);
                if(vm.hasMaxValue())    vm.maxValue = Math.min(vm.maxValue, vm.max);
                vm.maxX = vm.getPercent(vm.maxValue);
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
                vm.minX = 0;
            }

            vm.clearMaxValue = function() 
            {
                delete vm.maxValue;
                vm.maxX = 100;
            }

            vm.getValue = function(x)
            {
                x -= vm.bar.offset().left;
                vm.x = Math.round(x);
                var width = vm.bar.width();                
                vm.val = vm.roundStep(vm.min + (vm.max - vm.min) * x / width);
                return vm.val;
            };

            vm.getPercent = function(val)
            {
                val = valueOrDefault(val, vm.val);
                vm.percent = Math.roundDigits(100 * (val - vm.min) / (vm.max - vm.min), 2);
                //console.log(val, vm.percent + '%');
                return vm.percent;
            }

            vm.roundStep = function(value)
            {
                if(!vm.step || vm.step==1) return Math.round(value);
                return vm.step * Math.round(value/vm.step);
            }

            vm.init();
        }         
    };
}]);
