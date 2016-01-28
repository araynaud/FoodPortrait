angular.module('app').directive('imageGrid', function () 
{ return {
    scope: { images: '=', title: '=', options: '=', showDebug: '=', 
    columns: '@', rows: '@', ratio: '@', border: '@',  borderColor: '@' , margin: '@', shadow: '@'},
    templateUrl: 'directives/imageGrid.html',
    controllerAs: 'vm',
    bindToController: true,
    controller: function ($timeout, $uibModal)
    {
        var vm = this; 
        window.imageGrid = this;

        vm.init = function()
        {
            vm.win = angular.element(window);
            vm.opts="title,columns,rows,ratio,border,borderColor,margin,shadow".split(",");
            vm.initOptions(vm.opts);
            vm.gridSelector = ".imageGrid";
            vm.grid = angular.element(vm.gridSelector);
            vm.parent = vm.grid.parent().parent();
            vm.selector = vm.gridSelector  + " .cell";

            vm.showDebug = ProfileService.isDebug();
            vm.baseUrl = ProfileService.getConfig("upload.baseUrl");
            vm.baseServer = ProfileService.getConfig("upload.server");
            vm.isIE = ProfileService.clientIsIE();
            vm.isMobile = ProfileService.isMobile();

            vm.thumbnails = ProfileService.getConfig("thumbnails");
            if(vm.thumbnails.keep)
                delete vm.thumbnails.sizes[vm.thumbnails.keep];
            vm.tnsizes = Object.toArray(vm.thumbnails.sizes);
        
            vm.resizeInterval(400, 800);

            vm.win.bind("resize", function() 
            {
                vm.resizeInterval(400, 800);
            });

        };

        vm.initOptions = function(opts)
        {
            if(!vm.options) vm.options = {};
            opts.forEach(vm.initOption);
            if(!vm.options.borderColor) 
                vm.options.borderColor = 'black';
            return vm.options;
        };

        vm.initOption = function(name)
        {            
            if(vm[name]) 
                vm.options[name] = vm[name];
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
        };

        vm.imageStyle = function(im)
        {
            if(!im || !vm.isIE) return null;
            var bgImage = "url('{0}')".format(vm.imageUrl(im));
            return { "background-image": bgImage};
        };

        //use the right thumbnail based on vm.width / height
        //get first size larger than vm.maxStretch
        vm.selectImageSize = function()
        {
            var tn = "";
            var maxSize = Math.min(vm.width, vm.height);
            for(var i=0; i<vm.tnsizes.length; i++)
                if(maxSize <= vm.tnsizes[i].value)
                    tn = "." + vm.tnsizes[i].key;
            return tn;
        };

        vm.isMine = function(im)
        {
            return im.username == ProfileService.currentUsername();
        };

        vm.imageUrl = function(im, subdir)
        {
            if(!im) return;

            subdir = valueOrDefault(subdir, vm.subdir);
            var url = String.combine(vm.baseUrl, im.username, subdir, im.filename);
            if(!im.exists && vm.baseServer) url = vm.baseServer + url;
            return url;
        };
 
        vm.imageTitle = function(im)
        {
            var title = "";
            if(!im) return title;
            var dt = im.image_date_taken;
            if(dt)
            {
                dt = dt.replace(/-/g,"/");
                title = new Date(dt).toLocaleDateString();
            }
            if(im.meal) title = title.append(" ", im.meal);
            if(im.course) title = title.append(", ", im.course);
            if(vm.showDebug)
                title = title.append(" ", im.upload_id);
            return title.toString();
        };

        vm.imageDescription = function(im, html)
        {
            var title = "";
            if(!im) return title;
            var sep = html ? "<br/>" : "\n";
            //if(html)    title = title.append(sep, "<img src='{0}'/>".format(vm.imageUrl(im)));
            title = title.append(sep, im.caption);
            title = title.append(sep, im.context);
            //if(vm.showDebug)
            title = title.append(sep, "by @" + im.username);

            return title;
        };

        vm.popoverPlacement = function(index)
        {
            return index < vm.options.columns ? "bottom" : "top";
        };

        vm.openImage = function(im)
        {
            var modalInstance = $uibModal.open({
                animation: true,
//                backdrop: false,
                templateUrl: 'directives/imageModal.html',
                controller: 'ImageModalController',
                controllerAs: 'm',
                bindToController: true,
                size: 'lg',
                resolve: {
                    parent:   function() { return vm; },
                    image:    function() { return im; }, 
                    imageUrl: function() { return vm.imageUrl(im, '.ss'); }
                }
            });

//            modalInstance.result.then(function (result) { vm.selected = result; }, 
//            function() { console.log('Modal dismissed at: ' + new Date()); });
        };

        vm.imageWidth = function(n)
        {
            n = n || 1;
            var width = vm.gridWidth - vm.options.margin; 
            if(n < vm.options.columns)
                width  = width * n / vm.options.columns;

            width -= vm.options.margin;
            return Math.floor(width);
        };

        vm.imageHeight = function(n)
        {
            n = n || 1;
            var height = vm.gridHeight - vm.options.margin; 
            if(n < vm.options.rows)
                height = height * n / vm.options.rows;

            height -= vm.options.margin;
            return Math.floor(height);
        };

        vm.colspanCss = function(n)
        {
            var width = vm.imageWidth(n);
            return "{0}.colspan{1} { width: {2}px; }\n".format(vm.selector, n, width);
        };

        vm.rowspanCss = function(n)
        {
            var height = vm.imageHeight(n); 
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

            for(var t=delay; t<=last; t+=delay)
                vm.resizeAfter(t);        
        };

        vm.resizeAfter = function(delay)
        {
            if(angular.isNumber(delay) && delay>0)
                $timeout(vm.resizeGrid, delay);
            else
                vm.resizeGrid();
        };

        vm.roundRatio = function()
        {
            return Math.roundDigits(vm.availableRatio, 2);
        };

        //fit grid in containing element, keep aspect ratio
        vm.resizeGrid = function()
        {
            vm.prevWidth  = vm.gridWidth;
            vm.gridWidth  = vm.availableWidth  = vm.parent.width();
            vm.gridHeight = vm.availableHeight = vm.win.height() - vm.grid.offset().top;
            vm.availableRatio = vm.availableWidth / vm.availableHeight;
            vm.gridRatio = vm.options.ratio * vm.options.columns / vm.options.rows;
            vm.fit = vm.availableRatio > vm.gridRatio ? "height" : "width";
            if(vm.fit == "height")
                vm.gridWidth = Math.floor(vm.gridHeight * vm.gridRatio);
            else
                vm.gridHeight = Math.floor(vm.gridWidth / vm.gridRatio);

            vm.width = vm.imageWidth();
            vm.height = vm.imageHeight();
            vm.subdir = vm.selectImageSize();

            if(!vm.options.borderColor) 
                vm.options.borderColor = 'black';
            if(vm.options.border)
                vm.options.borderStyle = "{0}px solid {1}".format(vm.options.border, vm.options.borderColor);
            else 
                vm.options.borderStyle = "none";
            vm.addedCss ="";
            vm.addedCss += "{0} { width:{1}px; height:{2}px; padding:{3}px; }\n".format(vm.gridSelector, vm.gridWidth, vm.gridHeight, vm.options.margin/2);
            vm.addedCss += "{0} { width:{1}px; height:{2}px; border:{3}; margin:{4}px; }\n".format(vm.selector, vm.width, vm.height, vm.options.borderStyle, vm.options.margin/2);

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
