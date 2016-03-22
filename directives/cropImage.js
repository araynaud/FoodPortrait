angular.module('app').directive('cropImage', function () 
{ return {
    scope: { src: '@', title: '@' },
    templateUrl: '../foodportrait/directives/cropImage.html',
    controllerAs: 'vm',
    bindToController: true,
    replace: true,
    controller: function (ConfigService)
    {
        var vm = this; 
        vm.isIE = ConfigService.clientIsIE();

        vm.imageStyle = function()
        {
            if(!vm.isIE || !vm.src) return null;
            var bgImage = "url('{0}')".format(vm.src);
            return { "background-image": bgImage};
        };

        vm.imageClasses = function()
        {
            if(!vm.isIE)
                return "stretch cover";
            return "stretchW visible-print-block";
        };

        vm.remove = function(element)
        {
//            console.log("removing " + vm.src);
            element.parent().remove();
        };
    }         
}});
