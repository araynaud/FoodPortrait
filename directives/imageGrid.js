angular.module('app').directive('imageGrid', function () 
{ return {
    scope: { images: '=', title: '=', options: '=', showDebug: '=', 
    columns: '@', rows: '@', ratio: '@', border: '@',  borderColor: '@' , margin: '@', shadow: '@'},
    templateUrl: 'directives/imageGrid.html',
    controllerAs: 'vm',
    bindToController: true,
    controller: function ($timeout)
    {
        var vm = this; 
        window.imageGrid = this;

        vm.init = function()
        {
            vm.win = angular.element(window);
            vm.opts="title,columns,rows,ratio,border,borderColor,margin,shadow".split(",");
            vm.initOptions(vm.opts);
            vm.grid = angular.element(".imageGrid");
            vm.selector = ".imageGrid .cell";
            vm.showDebug = valueIfDefined("fpConfig.debug.angular");
            vm.baseUrl = valueIfDefined("fpConfig.upload.baseUrl");
            vm.baseServer = valueIfDefined("fpConfig.upload.server");
            if(!vm.options.borderColor) vm.options.borderColor = 'black';
        
            vm.resizeInterval(400, 800);

            vm.win.bind("resize", function() 
            {
                imageGrid.resizeInterval(400, 800);
            });
        };

        vm.initOptions = function(opts)
        {
            if(!vm.options) vm.options = {};
            opts.forEach(vm.initOption);
            return vm.options;
        };

        vm.initOption = function(name)
        {            
            if(vm[name]) vm.options[name] = vm[name];
            return vm.options[name];
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
            var url = String.combine(vm.baseUrl, im.username, subdir, im.filename);
            if(!im.exists && vm.baseServer) url = vm.baseServer + url;
            return url;
        };

        vm.imageTitle = function(im)
        {
            var title = "";
            var dt = im.image_date_taken;
            if(dt)
            {
                dt = dt.replace(/-/g,"/");
                title = new Date(dt).toLocaleDateString();
            }
            if(im.meal) title = title.append(" ", im.meal);
            if(im.course) title = title.append(" ", im.course);
            if(vm.showDebug)
                title = title.append(" ", im.upload_id);
            return title.toString();
        };

        vm.imageDescription = function(im)
        {
            var title = "";
            title = title.append(im.caption);
            title = title.append("<br/>", im.context);
            if(vm.showDebug)
                title = title.append("<br/>", "by @" + im.username);

            return title;
        };

        vm.popoverPlacement = function(index)
        {
            return index < vm.options.columns ? "bottom" : "top";
        };

        vm.imageDetails = function(im)
        {
        //    vm.options.title = im.caption;
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
            //if(!delta && vm.addedCss) return vm.addedCss;
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

String.append = function(str1, sep, str2)
{
    str1 = valueOrDefault(str1, "");
    return str1.toString().append(sep,str2).toString();
}

String.prototype.append = function(sep, str)
{
    if(arguments.length==1)
    {
        str=sep;
        sep="";
    }
    if(!sep) sep="";
    if(!str) return this;

    if(this.length)
        return this + sep + str;
    return str.toString();
}