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
        scope: { label: "@", id: "@",
            items: "=", itemValue: "@", itemLabel: "@", 
            minValue: "=", maxValue: "=", min: "=", max: "=", step: "=", array: "=", 
            change: '=', showDebug: '=' },
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
                if(vm.id) vm.ids = "#" + vm.id;
                vm.isMobile = vm.mobile || app.isMobile();
                vm.step = valueOrDefault(vm.step, 1);
                vm.checkBounds();
                vm.minX = vm.getPercent(valueOrDefault(vm.minValue, vm.min));
                vm.maxX = vm.getPercent(valueOrDefault(vm.maxValue, vm.max));
            };

            vm.checkBounds = function()
            {
                vm.min = valueOrDefault(vm.min, 0);
                if(vm.items)
                    vm.max = valueOrDefault(vm.max, vm.items.length-1);
            };

            vm.minMaxArray = function()
            {
                return vm.array = (vm.minValue == vm.maxValue) ? [vm.minValue] : [vm.minValue, vm.maxValue];
            };

            vm.getItemLabel = function(value)
            {
                if(isMissing(value)) return "";
                return vm.items && vm.itemLabel ? vm.items[value][vm.itemLabel] : value;
            };

            vm.getItemValue = function(value)
            {
                if(isMissing(value)) return value;
                return vm.items && vm.itemValue ? vm.items[value][vm.itemValue] : value;
            };

            vm.range = function()
            {
                vm.checkBounds();
                if(!vm.hasMinValue() && !vm.hasMaxValue()) return "-";
                if(!vm.hasMinValue()) return "up to " + vm.getItemLabel(vm.maxValue);
                if(!vm.hasMaxValue()) return vm.getItemLabel(vm.minValue) + " and over";
                if(vm.maxValue == vm.minValue) return vm.getItemLabel(vm.minValue);
                return String.append( vm.getItemLabel(vm.minValue), " to ", vm.getItemLabel(vm.maxValue));
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
                vm.checkBounds();
                if(!event)
                { 
                    vm.minX = 0;
                    vm.minValue = vm.min;
                    return vm.onChange();
                }

                vm.minValue = vm.getValue(event.pageX);
                if(vm.hasMaxValue())    vm.minValue = Math.min(vm.val, vm.maxValue);
                if(vm.hasMinValue())    vm.minValue = Math.max(vm.minValue, vm.min);
                vm.minX = vm.getPercent(vm.minValue);
                vm.onChange();
            };

            vm.setMaxValue = function(event)
            {
                vm.checkBounds();
                if(!event)
                { 
                    vm.maxX = 100;
                    vm.maxValue = vm.max;
                    return vm.onChange();
                }

                vm.maxValue = vm.getValue(event.pageX);
                if(vm.hasMinValue())    vm.maxValue = Math.max(vm.val, vm.minValue);
                if(vm.hasMaxValue())    vm.maxValue = Math.min(vm.maxValue, vm.max);
                vm.maxX = vm.getPercent(vm.maxValue);
                vm.onChange();
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
                vm.onChange();
            }

            vm.clearMaxValue = function() 
            {
                delete vm.maxValue;
                vm.maxX = 100;
                vm.onChange();
            }

            vm.onChange = function()
            {
                vm.minMaxArray();
                if(!vm.selectMax && !vm.selectMin && angular.isFunction(vm.change))
                    $timeout(vm.change, 0);
            };

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
