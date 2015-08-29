angular.module('app').directive('imageGrid', function () 
{
    return {
        scope: { images: '=', title: '=', options: '=', showDebug: '='},
        templateUrl: 'views/imageGrid.html',
        controllerAs: 'vm',
        bindToController: true,
        controller: function ()
        {
            var vm = this; 
            window.imageGrid = this;

            vm.title="Image Grid";
            vm.showDebug = valueIfDefined("fpConfig.debug.angular");
            vm.baseUrl = valueIfDefined("fpConfig.upload.baseUrl");

            vm.imageUrl = function(im, subdir)
            {
                return String.combine(vm.baseUrl, im.username, subdir, im.filename);
            }
        }         
    };
});
