angular.module('app').directive('imageGrid', function () 
{ return {
    scope: { images: '=', title: '=', options: '=', showDebug: '=', columns: '@', rows: '@', ratio: '@', border: '@',  borderColor: '@' , margin: '@', shadow: '@'},
    templateUrl: 'views/imageGrid.html',
    controllerAs: 'vm',
    bindToController: true,
    controller: function ($timeout)
    {
        var vm = this; 
        Object.merge(vm, vm.options);
        window.imageGrid = this;
        vm.win = angular.element(window);
        vm.grid = angular.element(".imageGrid");
        var selector = ".imageGrid .cell";

        vm.title="Image Grid";
        vm.showDebug = valueIfDefined("fpConfig.debug.angular");
        vm.baseUrl = valueIfDefined("fpConfig.upload.baseUrl");
        if(!vm.borderColor) vm.borderColor = 'black';
        
        vm.init = function()
        {
            vm.win.bind("resize", function() { vm.resizeGrid(); vm.resizeAfter(200); });
            vm.resizeGrid();
            vm.resizeAfter(400);
        };

        vm.imageClasses = function(im)
        {
            var classes= {shadow: vm.shadow};
            if(im.colspan>=vm.columns) classes['colspan'+ vm.columns] = true;
            else if(im.colspan>1) classes['colspan'+ im.colspan] = true;
            
            if(im.rowspan>=vm.rows) classes['rowspan'+ vm.rows] = true;
            else if(im.rowspan) classes['rowspan'+ im.rowspan] = true;
            
            return classes;
        }

        vm.imageStyle = function(im, subdir)
        {
            var bgImage = "url('{0}')".format(vm.imageUrl(im,subdir));
            return { "background-image": bgImage};
        };

        vm.imageUrl = function(im, subdir)
        {
            return String.combine(vm.baseUrl, im.username, subdir, im.filename);
        };

        vm.imageTitle = function(im)
        {
            var title = im.caption || '';
            if(title && im.context) title += ' ';
            if(im.context) title += im.context;
            if(vm.showDebug) title = im.upload_id + ' ' + title;
            return title;
        };

        vm.imageDetails = function(im)
        {
            vm.title = im.caption;
        };

        vm.imageWidth = function(n)
        {
            n = n || 1;
            var width = vm.totalWidth = vm.grid.width();
            if(vm.columns > n)
            {
                width *= n;
                width /= vm.columns;
            }

            if(vm.margin)  width -= 2 * vm.margin;

            return Math.floor(width);
        };

        vm.colspanCss = function(n)
        {
            var width = vm.imageWidth(n);
            return "{0}.colspan{1} { width: {2}px; }\n".format(selector, n, width);
        };

        vm.rowspanCss = function(n)
        {
            var height = vm.imageWidth(n); 
            return "{0}.rowspan{1} { height: {2}px; }\n".format(selector, n, height);
        };

        //keep aspect ratio
        vm.resizeAfter = function(delay)
        {
            if(isNumber(delay) && delay>0)
                $timeout(vm.resizeGrid, delay);
            else
                vm.resizeGrid();
        };

        vm.resizeGrid = function()
        {
            vm.width = vm.grid.width();
            vm.delta = vm.width - vm.totalWidth;
            vm.totalWidth = vm.width;
            vm.width = vm.imageWidth();
            vm.height = vm.ratio ? vm.width / vm.ratio : vm.width;
            vm.width = Math.floor(vm.width);
            vm.height = Math.floor(vm.height);
            if(!vm.borderColor) 
                vm.borderColor = 'black';
            if(vm.border)
                vm.borderStyle = "{0}px solid {1}".format(vm.border, vm.borderColor);
            else 
                vm.borderStyle = "none";

            vm.addedCss = "{0} { width:{1}px; height:{2}px; border:{3}; margin:{4}px; }\n".format(selector, vm.width, vm.height, vm.borderStyle, vm.margin);

            for(var i=2; i<=vm.columns; i++)
                vm.addedCss += vm.colspanCss(i);
            for(var i=2; i<=vm.rows; i++)
                vm.addedCss += vm.rowspanCss(i);

            //$scope.$apply();
        }

        vm.init();
    }         
}});
