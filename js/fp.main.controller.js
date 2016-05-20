'use strict';

// =========== Main Controller ===========
// handles query and gallery display
angular.module('fpControllers')
.controller('MainController', ['$window', '$state', '$timeout', 'ProfileService', 'QueryService', 
function ($window, $state,  $timeout, ProfileService, QueryService)
{
    //TODO:
    //post filters to album.php service
    //query user table where username = user and password = md5
    //return array of uploads to display
//    if(!ProfileService.user && !ProfileService.isOffline())
//        $state.go('home');
    
    var mc = this;
    $window.MainController = this;
    this.state = $state;

    mc.init = function()
    {
        mc.filters = QueryService.filters;
        mc.demographic = true;
        mc.searchResults=[];
        mc.showOptions = ProfileService.getConfig("grid.showOptions");
        mc.options = ProfileService.getConfig("grid.options")
        if(!mc.options)
            mc.options = { columns: 4, rows: 4, margin: 10, border: 1, ratio: 1, shadow: false};

        mc.isMobile =  ProfileService.isMobile();
        mc.isLoggedIn  =  ProfileService.isLoggedIn();
        mc.isAdmin  =  ProfileService.isAdmin();
        mc.showDebug = ProfileService.isDebug();

//date picker options
        mc.dateFormat = 'MM/dd/yyyy';
        mc.today = new Date();
        mc.dateOptions = { formatYear: 'yy', startingDay: 1, maxDate: mc.today };
// end date picker options

        mc.labels = ProfileService.getConfig("labels.filters");
        mc.dropdown = ProfileService.getConfig("dropdown");
        
        var pkey = ProfileService.getRole();
        mc.portrait = mc.dropdown.portrait[pkey];
        if(isString(mc.portrait)) 
            mc.portrait = [mc.portrait]; 
        if(mc.portrait.indexOf(mc.filters.portrait) == -1)
            mc.filters.portrait = mc.portrait[0];

        if(isDefined("dropdown.order_by", mc))
            mc.dropdown.order_by_keys = Object.keys(mc.dropdown.order_by);

        mc.getGroupOptions();

        mc.win = angular.element(window);
        mc.gridContainer = angular.element("#grids");

        mc.win.bind("resize", function() 
        {
            mc.resizeGrids(200);
        });

        mc.getFilters();
        mc.search();
    }

    mc.pickDate = function(id) { mc['datepickerOpen' + id] = true; };
    mc.setMinToday = function() { return mc.filters.date_min = mc.today; };
    mc.setMaxToday = function() { return mc.filters.date_max = mc.today; };

    mc.isFilter = function(q)
    {
        return q.searchable && (q.data_type=='single' || q.data_type=='multiple');
    }

	mc.getFilters = function()
	{
		mc.loading = true;
		ProfileService.loadForm().then(function(response) 
		{
            mc.questions = response.questions;
            mc.fieldNames = mc.questions.mapField("field_name");
            console.log("getFilters", mc.fieldNames);
            mc.getGroupOptions();
			mc.loading = false;    
		});
	};

    mc.orderArrow = function()
    {
        return { 'glyphicon-arrow-up': !mc.filters.reverse, 'glyphicon-arrow-down': mc.filters.reverse };
    };

    mc.setOption =  function (opt,val)
    {
        mc.options[opt] = val;
    };

    mc.addOption =  function (opt,val)
    {
        if(!mc.options[opt]) mc.setOption(opt,val);
        mc.options[opt] += val;
    };

    mc.getCourses = function()
    {
        if(!mc.filters.meal) 
            return ProfileService.getConfig("dropdown.meal.1.courses");
        return mc.filters.meal.courses;
    };

    mc.getGroupOptions = function()
    {
        mc.demographic = mc.filters.portrait == 'demographic';

        var key = mc.isAdmin ? "admin" : mc.filters.portrait;
        var opts = mc.dropdown.group[key];
        if(!angular.isArray(opts)) opts = [opts];

        //add profile options
        if(mc.fieldNames && mc.demographic)
            opts = opts.concat(mc.fieldNames);

        console.log("getGroupOptions", opts);
        return mc.groupOptions = opts;
    }

    mc.gridClasses = function()
    {
        var classes = {};
        if(!mc.multipleGrids()) return classes;
        var nbCols = 12 / mc.searchResults.length;
        var sm = Math.max(nbCols, 6); //small: 2 grids per row
        classes["col-sm-"+sm] = true;
        return classes;
    }

    mc.multipleGrids = function(delay)
    {
        return mc.searchResults && mc.searchResults.length>1;
    }

    mc.resizeGrids = function(delay)
    {
        if(mc.imageGrids)
            for(var i=0; i < mc.imageGrids.length; i++)
                mc.imageGrids[i].resizeAfter(delay);       
        console.log("resizing grid: " + mc.imageGrids.length);
    };

    mc.getGridTitle = function()
    {
        var params = [];
        for(var f in mc.filters)
        {
            if(!mc.filters[f] || mc.filters[f] === true) continue;
            var title = mc.filterTitle(mc.filters[f], f);
            if(title)
                params.push(title);
        }
        return params.join(", ");
    }

    mc.filterTitle = function(filter, key)
    {
        var label = mc.labels[key];
        var prefix = angular.isArray(label) ? label[0] : label || "";
        var suffix = angular.isArray(label) ? label[1] : "";

        var title = filter.label || filter.name || filter;
        if(angular.isArray(filter))
        {
            title = mc.rangeTitle(filter);
            if(!title) return title;
        }
        else if(filter.toLocaleDateString)
            title = filter.toLocaleDateString();
        title = prefix + " " + title + " " + suffix;
        return title.trim();
    };

    mc.rangeTitle = function(filter)
    {
        if(!filter[0] && !filter[1]) return "";
        if(filter.length==1 || filter[0] == filter[1]) return filter[0];
        if(!filter[0]) return mc.labels.range[0] + " " + filter[1];
        if(!filter[1]) return filter[0] + " " + mc.labels.range[2];
        return "{0} {1} {2}".format(filter[0], mc.labels.range[1], filter[1]);
    };

    mc.filterValue = function(filter, key)
    {
        if(filter.toLocaleDateString)
            return filter.toISOString().substringBefore('T');

        if(angular.isArray(filter))
            return filter.join(":");

        //convert dropdown label into value
        var ddValue = valueIfDefined([key, filter], mc.dropdown);

        return ddValue || filter.id || filter.name || filter;
    };

    mc.getSearchParams = function()
    {
        mc.demographic = mc.filters.portrait == 'demographic';
        var params = {};
        if(!mc.filters) return params;

        for(var f in mc.filters)
        {
            var filter = mc.filters[f]; 
            if(f && filter)
            {
                var key = isMissing(filter.question_id) ? f : 'Q_'+filter.question_id;
                params[key] = mc.filterValue(filter, key);
            }
        }

        if(mc.filters.group)
        {
            var questionId = valueIfDefined(["byField", mc.filters.group, "id"], mc.questions);
            if(!isMissing(questionId))
                params.group = "Q_" + questionId;
        }

        if(mc.options.rows && mc.options.columns)
            params.limit = mc.options.rows * mc.options.columns;
        return params;
    };

    mc.search = function()
    {
        mc.loading = true;
        mc.params = mc.getSearchParams();
        mc.title = mc.getGridTitle();
        mc.imageGrids = [];

        QueryService.loadQuery(mc.params).then(function(response) 
        {
            mc.loading = false;
            mc.searchResults = response; 
            mc.maxGrid = mc.searchResults.max("value.length");
            mc.minGrid = mc.searchResults.min("value.length");
            mc.total   = mc.searchResults.sum("value.length");
            mc.time = QueryService.time;
            mc.users = QueryService.users;
            mc.groups = QueryService.groups;
            mc.queries = QueryService.queries;
        }, 
        mc.errorMessage);
    };

    mc.searchMore = function()
    {
        if(!mc.searchResults || mc.maxGrid < mc.options.rows * mc.options.columns)
            mc.search();
        else
            mc.resizeGrids();
    };

    mc.clearSearch = function(refresh)
    {
        mc.filters = { };
        if(refresh)
            mc.search();
    };

    mc.clearFilter = function(key, refresh)
    {
        delete mc.filters[key];
        if(refresh)
            mc.search();
    };

    mc.errorMessage =  function (result)
    {
        mc.loading = false;
        mc.status = "Error: No data returned";
    };

    mc.successMessage =  function (result)
    {
        mc.loading = false;
        mc.status = result;
    };

    mc.init();
}]);