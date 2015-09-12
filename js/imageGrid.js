angular.module('app').directive('imageGrid', function () 
{ return {
    scope: { images: '=', title: '=', options: '=', showDebug: '=', 
    columns: '@', rows: '@', ratio: '@', border: '@',  borderColor: '@' , margin: '@', shadow: '@'},
    templateUrl: 'views/imageGrid.html',
    controllerAs: 'vm',
    bindToController: true,
    controller: function ($timeout)
    {
        var vm = this; 
        window.imageGrid = this;

        vm.initOptions = function(opts)
        {
            if(!vm.options) vm.options = {};
            opts.forEach(vm.initOption);
            return vm.options;
        }

        vm.initOption = function(name)
        {            
            if(vm[name]) vm.options[name] = vm[name];
            return vm.options[name];
        }

        vm.init = function()
        {
            vm.win = angular.element(window);
            vm.opts="title,columns,rows,ratio,border,borderColor,margin,shadow".split(",");
            vm.initOptions(vm.opts);
            vm.grid = angular.element(".imageGrid");
            vm.selector = ".imageGrid .cell";
            vm.showDebug = valueIfDefined("fpConfig.debug.angular");
            vm.baseUrl = valueIfDefined("fpConfig.upload.baseUrl");
            if(!vm.options.borderColor) vm.options.borderColor = 'black';
        
            vm.resizeInterval(0, 600);

            vm.win.bind("resize", function() 
            {
                imageGrid.resizeInterval(0, 600);
            });
        };

        vm.imageClasses = function(im)
        {
            var classes= {shadow: vm.options.shadow};
            if(im.colspan>=vm.options.columns) classes['colspan'+ vm.options.columns] = true;
            else if(im.colspan>1) classes['colspan'+ im.colspan] = true;
            
            if(im.rowspan>=vm.options.rows) classes['rowspan'+ vm.options.rows] = true;
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
            if(vm.options.columns > n)
            {
                width *= n;
                width /= vm.options.columns;
            }

            if(vm.options.margin)  width -= 2 * vm.options.margin;

            return Math.floor(width);
        };

        vm.colspanCss = function(n)
        {
            var width = vm.imageWidth(n);
            return "{0}.colspan{1} { width: {2}px; }\n".format(vm.selector, n, width);
        };

        vm.rowspanCss = function(n)
        {
            var height = vm.imageWidth(n); 
            return "{0}.rowspan{1} { height: {2}px; }\n".format(vm.selector, n, height);
        };

        vm.resizeInterval = function(delay, last)
        {
            if(!last)
                return vm.resizeAfter(delay);        

            if(!delay)
            {
                vm.resizeGrid(); 
                delay=100;
            }

            for(var t=delay; t<=last ; t+=delay)
                vm.resizeAfter(t);        
        };


        vm.resizeAfter = function(delay)
        {
            if(angular.isNumber(delay) && delay>0)
                $timeout(vm.resizeGrid, delay);
            else
                vm.resizeGrid();
        };


        vm.delta = function()
        {
            if(!vm.prevWidth) return 0;
            return vm.grid.width() - vm.prevWidth;
        }

        //fit grid in containing element, keep aspect ratio
        vm.resizeGrid = function()
        {
            vm.prevWidth = vm.totalWidth;
            vm.totalWidth = vm.grid.width();
            var delta = vm.delta(); 
            vm.width = vm.imageWidth();

            if(delta<0) vm.width += delta;
            vm.height = vm.options.ratio ? vm.width / vm.options.ratio : vm.width;
            vm.width = Math.floor(vm.width);
            vm.height = Math.floor(vm.height);
            if(!vm.options.borderColor) 
                vm.options.borderColor = 'black';
            if(vm.options.border)
                vm.options.borderStyle = "{0}px solid {1}".format(vm.options.border, vm.options.borderColor);
            else 
                vm.options.borderStyle = "none";

            vm.addedCss = "{0} { width:{1}px; height:{2}px; border:{3}; margin:{4}px; }\n".format(vm.selector, vm.width, vm.height, vm.options.borderStyle, vm.options.margin);

            for(var i=2; i<=vm.options.columns; i++)
                vm.addedCss += vm.colspanCss(i);
            for(var i=2; i<=vm.options.rows; i++)
                vm.addedCss += vm.rowspanCss(i);

            return vm.addedCss;
        }

        vm.dynamicCss = function()
        {
            return vm.resizeGrid();
        }

        vm.init();
    }         
}});
