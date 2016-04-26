angular.module('app').directive('imageGridPc', function () 
{ return {
    scope: { key: '=', images: '=', title: '=', options: '=', showDebug: '=', main: '=',
    columns: '@', rows: '@', ratio: '@', border: '@',  borderColor: '@' , margin: '@', shadow: '@'},
    templateUrl: 'directives/imageGridPc.html',
    controllerAs: 'vm',
    bindToController: true,
    link: function (scope, element, attr, vm) 
    {
        vm.grid = element.children("div.imageGrid");
        vm.parent = element.parent(); //.parent();
        console.log("link grid: {0} / parent: {1}".format(vm.grid.length, vm.parent.length));
        vm.resizeGrid();
    },    
    controller: function ($timeout, $uibModal, ProfileService)
    {
        var vm = this; 

        vm.init = function()
        {
            vm.addGrid();
            vm.count=0;
            vm.win = angular.element(window);
            vm.opts="title,columns,rows,ratio,border,borderColor,margin,shadow".split(",");
            vm.initOptions(vm.opts);
            if(vm.key) 
                vm.id = "grid-" + vm.key.replace(/ /g, "-");
            vm.gridSelector = vm.id ? ("#"+vm.id) : ".imageGrid";
            vm.selector = vm.gridSelector  + " .cell";

            vm.showDebug = ProfileService.isDebug();
            vm.isAdmin = ProfileService.isAdmin();
            vm.api = ProfileService.getConfig("api.foodportrait.url");
            vm.isExternalApi = String.isExternalUrl(vm.api);

            vm.baseUrl = ProfileService.getConfig("upload.baseUrl");
            vm.baseServer = ProfileService.getConfig("upload.server");
            vm.isIE = ProfileService.clientIsIE();
            vm.isMobile = ProfileService.isMobile();

            vm.thumbnails = ProfileService.getConfig("thumbnails");
            if(vm.thumbnails.keep)
                delete vm.thumbnails.sizes[vm.thumbnails.keep];
            vm.tnsizes = Object.toArray(vm.thumbnails.sizes);

            if(vm.options.ratio)
            {
                vm.ratioClass = 'ratio-' + vm.options.ratio.toString().replace(/\//g,"-").replace(/\./g,"-");
                vm.pad = vm.roundRatio(100 / vm.options.ratio);
            }

        
/*          vm.win.bind("resize", function() 
            {
                vm.resizeGrid();
            });
*/
        };

        vm.addGrid = function()
        {
            if(!vm.main) return;

            if(!vm.main.imageGrids) 
                vm.main.imageGrids = [];
            vm.main.imageGrids.push(vm);
            if(vm.key) 
                vm.main.imageGrids[vm.key] = vm;
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

            if(vm.options.ratio && vm.ratioClass)
                classes[vm.ratioClass] = true;

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
            if(subdir == ".ss" && im.noss) subdir="";
            var url = String.combine(vm.baseUrl, im.username, subdir, im.filename);
            if(vm.isExternalApi || !im.exists && vm.baseServer)
                url = vm.baseServer + url;
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
            if(vm.showDebug || vm.isAdmin)
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


        vm.resizeAfter = function(delay)
        {
            if(angular.isNumber(delay) && delay>0)
                $timeout(vm.resizeGrid, delay);
            else
                vm.resizeGrid();
        };

        vm.roundRatio = function(r)
        {
            return Math.roundDigits(r, 2);
        };

        //fit grid in containing element, keep aspect ratio
        vm.imageWidthPercent = function(n)
        {
            n = n || 1;
            var width = 100; 
            if(n < vm.options.columns)
                width  = width * n / vm.options.columns;

            width -= vm.options.margin;
            return Math.floor(width);
        };

        vm.imageHeightPercent = function(n)
        {
            n = n || 1;
            var height = 100; 
            if(n < vm.options.rows)
                height = height * n / vm.options.rows;

            height -= vm.options.margin;
            return Math.floor(height);
        };

        vm.resizeGrid = function()
        {
            console.log("vm.resizeGrid " + vm.id + " " + vm.count++);
            vm.prevWidth = vm.gridWidth;

            vm.availableWidth  = vm.parent.width();
//            if(vm.main.multipleGrids() && !vm.isMobile)
//                vm.availableWidth /= 2;

            vm.firstGrid = vm.main.imageGrids[0].grid;
            vm.availableHeight = vm.win.height() - vm.firstGrid.offset().top;
            vm.availableRatio = vm.availableWidth / vm.availableHeight;
            vm.gridRatio = vm.options.ratio * vm.options.columns / vm.options.rows;
            vm.fit = vm.availableRatio > vm.gridRatio ? "height" : "width";

            vm.gridWidth = 100;
//            if(vm.fit == "height")
//                vm.gridWidth = vm.roundRatio(100 * vm.gridRatio / vm.availableRatio);

            vm.width = vm.imageWidthPercent();
            vm.height = vm.imageHeightPercent();
            vm.subdir = vm.selectImageSize();

            if(!vm.options.borderColor) 
                vm.options.borderColor = 'black';
            if(vm.options.border)
                vm.options.borderStyle = "{0}px solid {1}".format(vm.options.border, vm.options.borderColor);
            else 
                vm.options.borderStyle = "none";

            vm.addedCss ="";
            vm.addedCss += "{0} { width:{1}%; padding:{2}%; }\n".format(vm.gridSelector, vm.gridWidth, vm.options.margin/2);
            vm.addedCss += "{0} { width:{1}%; border:{2}; margin:{3}%; }\n".format(vm.selector, vm.width, vm.options.borderStyle, vm.options.margin/2);
            vm.addedCss += ".ratio-wrapper.{0}:after { padding-top:{1}% }\n".format(vm.ratioClass, vm.pad); 

            return vm.addedCss;
        }

        vm.init();
    }         
}});
