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

            vm.init = function()
            {
                if(vm.id) vm.ids = "#" + vm.id;
                
                vm.itemValue = valueOrDefault(vm.itemValue, "value");
                vm.itemLabel = valueOrDefault(vm.itemLabel, "label");
                vm.step = valueOrDefault(vm.step, 1);
                vm.isMobile = vm.mobile || app.isMobile();

                vm.checkBounds();
                vm.minX = vm.getPercent(valueOrDefault(vm.minValue, vm.min));
                vm.maxX = isMissing(vm.max) ? 100 : vm.getPercent(valueOrDefault(vm.maxValue, vm.max));
                if(vm.array)
                {
                    vm.minValue = vm.getItemIndex(vm.array[0]);
                    vm.maxValue = vm.getItemIndex(vm.array[1]);
                    vm.updateMinValue(vm.minValue);
                    vm.updateMaxValue(vm.maxValue);
                }
            };


            vm.getItemIndex = function(val)
            {
                if(!angular.isArray(vm.items)) return val;
                return vm.items.findIndex(function(a) { return a[vm.itemValue] == val; });
            }

            vm.checkBounds = function()
            {
                vm.min = valueOrDefault(vm.min, 0);
                if(vm.items)
                    vm.max = valueOrDefault(vm.max, vm.items.length-1);
            };

            vm.minMaxArray = function()
            {
                return vm.array = [ vm.getItemValue(vm.minValue), vm.getItemValue(vm.maxValue) ];
            };

            vm.getItemLabel = function(value)
            {
                if(isMissing(value)) return "";
                if(vm.items && vm.items[value] && vm.itemLabel)
                    return vm.items[value][vm.itemLabel];
                return value;
            };

            vm.getItemValue = function(value)
            {
                if(isMissing(value)) return value;
                if(vm.items && vm.items[value] && vm.itemValue)
                    return vm.items[value][vm.itemValue];
                return value;
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

                var val = vm.getValue(event.pageX);
                vm.updateMinValue(val);
                vm.onChange();
            };

            vm.updateMinValue = function(val)
            {
                if(isMissing(val)) return;
                vm.minValue = setBetween(val, vm.min, vm.maxValue);
                vm.minX = vm.getPercent(vm.minValue);                
                return vm.minValue;
            }

            vm.setMaxValue = function(event)
            {
                vm.checkBounds();
                if(!event)
                { 
                    vm.maxX = 100;
                    vm.maxValue = vm.max;
                    return vm.onChange();
                }

                var val = vm.getValue(event.pageX);
                vm.updateMaxValue(val);
                vm.onChange();
            };

            vm.updateMaxValue = function(val)
            {
                if(isMissing(val)) return;
                vm.maxValue = setBetween(val, vm.minValue, vm.max);
                vm.maxX = vm.getPercent(vm.maxValue);
                return vm.maxValue;
            }

            function setBetween(val, min, max)
            {
                if(isMissing(val)) return val;
                if(!isMissing(min) && val < min) val = min;
                if(!isMissing(max) && val > max) val = max;
                return val;
            }

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
                var val = vm.roundStep(vm.min + (vm.max - vm.min) * x / width);
                return val;
            };

            vm.getPercent = function(val)
            {                
                if(val == vm.min) return vm.percent = 0;
                return vm.percent = Math.roundDigits(100 * (val - vm.min) / (vm.max - vm.min), 2);
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
