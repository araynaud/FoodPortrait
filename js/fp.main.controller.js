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
        mc.filters = { portrait: "demographic", reverse: true, order_by: "image_date_taken" };
        mc.searchResults=[];
        mc.showOptions = ProfileService.getConfig("grid.showOptions");
        mc.options = ProfileService.getConfig("grid.options")
        if(!mc.options)
            mc.options = { columns: 4, rows: 4, margin: 10, border: 1, ratio: 1, shadow: false};

        mc.isMobile =  ProfileService.isMobile();
        mc.isAdmin  =  ProfileService.isAdmin();
        mc.showDebug = ProfileService.isDebug();

//date picker options
        mc.dateFormat = 'MM/dd/yyyy';
        mc.today = new Date();
        mc.dateOptions = { formatYear: 'yy', startingDay: 1, maxDate: mc.today };
        mc.pickDate = function(id) { mc['datepickerOpen' + id] = true; };
        mc.setMinToday = function() { return mc.filters.date_min = mc.today; };
        mc.setMaxToday = function() { return mc.filters.date_max = mc.today; };
// end date picker options

        mc.dropdown = ProfileService.getConfig("dropdown");
        mc.getFilters();
        mc.search();
    }

    mc.isFilter = function(q)
    {
        return q.searchable && (q.data_type=='single' || q.data_type=='multiple');
    }

	mc.getFilters = function()
	{
		mc.loading = true;
		ProfileService.loadForm().then(function(response) 
		{
			mc.loading = false;    
		    mc.questions = response.questions;
		    if(!mc.questions) return;
            mc.questions.byId = mc.questions.indexBy("id");
		  	mc.questions.forEach(function (q) 
		  	{
		  		q.title = q.field_name.makeTitle(true);
		  	});
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
        var key = mc.isAdmin ? "admin" : mc.filters.portrait;
        var opts = mc.dropdown.group[key];
        return angular.isArray(opts) ? opts : [opts];
    }

    mc.gridClasses = function()
    {
        var classes = {};
        if(!mc.searchResults || mc.searchResults.length <= 1) return classes;
        var nbCols = 12 / mc.searchResults.length;
        var lg = Math.max(nbCols, 3); //large: 4 grids per row
        var sm = Math.max(nbCols, 6); //small: 2 grids per row

        classes["col-sm-"+sm] = true;
        //classes["col-lg-"+lg] = true;

        return classes;
    }

    mc.resizeGrid = function(delay, last)
    {
        if(window.imageGrid)
            imageGrid.resizeInterval(delay, last);       
    };

    mc.getGridTitle = function()
    {
        var params = [];
        for(var f in mc.filters)
        {
            if(!mc.filters[f] || mc.filters[f] === true) continue;
            params.push(mc.filterTitle(mc.filters[f]));
        }
        return params.join(", ");
    }

    mc.filterTitle = function(filter)
    {
        if(filter.toLocaleDateString)
            return filter.toLocaleDateString();
        return filter.label || filter.name || filter;
    };

    mc.filterValue = function(filter)
    {
        if(filter.toLocaleDateString)
            return filter.toISOString().substringBefore('T');
        return filter.id || filter.name || filter;
    };

    mc.getSearchParams = function()
    {
        var params = {};
        for(var f in mc.filters)
        {
            var filter = mc.filters[f]; 
            if(f && filter)
            {
                var key = isMissing(filter.question_id) ? f : 'Q_'+filter.question_id;
                params[key] = mc.filterValue(filter);
            }
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
        QueryService.loadQuery(mc.params).then(function(response) 
        {
            mc.searchResults = response; 
            mc.users = QueryService.users;
            mc.queries = QueryService.queries;
        }, 
        mc.errorMessage);
    };

    mc.searchMore = function()
    {
         $timeout(function() { 
            if(!mc.searchResults || mc.searchResults.length < mc.options.rows * mc.options.columns)
                mc.search();
          }, 0);
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