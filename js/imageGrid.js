angular.module('app').directive('imageGrid', function () 
{ return {
    scope: { images: '=', title: '=', options: '=', showDebug: '=', columns: '@', rows: '@', ratio: '@', border: '@',  borderColor: '@' , margin: '@'},
    templateUrl: 'views/imageGrid.html',
    controllerAs: 'vm',
    bindToController: true,
    controller: function ($scope, $timeout)
    {
        var vm = this; 
        window.imageGrid = this;
        vm.win = angular.element(window);
        vm.grid = angular.element(".imageGrid");
        var selector = ".imageGrid .cell";

        vm.title="Image Grid";
        vm.showDebug = valueIfDefined("fpConfig.debug.angular");
        vm.baseUrl = valueIfDefined("fpConfig.upload.baseUrl");

        vm.init = function()
        {
            vm.win.bind("resize", vm.resizeThumbnails);
            vm.resizeThumbnails();
        }

        vm.imageUrl = function(im, subdir)
        {
            return String.combine(vm.baseUrl, im.username, subdir, im.filename);
        };

        vm.imageDetails = function(im)
        {
            vm.title = im.caption;
        };

        //keep aspect ratio
        vm.resizeThumbnails = function()
        {
            $timeout(function() {
                vm.width = vm.grid.width();
                if(vm.columns > 1)   
                    vm.width /= vm.columns;
                if(vm.border)   vm.width -= 2 * vm.border;
                if(vm.margin)   vm.width -= 2 * vm.margin;
                vm.width = Math.floor(vm.width);
                vm.height = vm.ratio ? vm.width / vm.ratio : vm.width;
                vm.height = Math.floor(vm.height);
                if(vm.border && vm.borderColor)
                    vm.borderStyle = "{0}px solid {1}".format(vm.border, vm.borderColor);
                else 
                    vm.borderStyle = "none";
                vm.addedCss = "{0} { width: {1}px; height: {2}px; border: {3}; margin: {4}px; }".format(selector, vm.width, vm.height, vm.borderStyle, vm.margin);
            }, 10);
        };

        vm.init();
    }         
}});
