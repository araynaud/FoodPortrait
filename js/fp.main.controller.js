'use strict';

// =========== Main Controller ===========
// handles query and gallery display
angular.module('fpControllers')
.controller('MainController', ['$window', '$state', 'ProfileService', 'QueryService', 
function ($window, $state, ProfileService, QueryService)
{
    //TODO:
    //post filters to album.php service
    //query user table where username = user and password = md5
    //return array of uploads to display
    if(!ProfileService.user && !ProfileService.isOffline())
        $state.go('home');
    
    var mc = this;
    $window.MainController = this;
    this.state = $state;

    mc.init = function()
    {
        mc.filters = {};
        mc.searchResults=[];
        mc.fpConfig = $window.fpConfig; 
        
        mc.showOptions = mc.fpConfig.grid.showOptions;
        mc.options = mc.fpConfig.grid.options;
        if(!mc.options)
            mc.options = { columns: 4, rows: 4, margin: 10, border: 1, ratio: 1, shadow: false};

        mc.showDebug = ProfileService.isDebug();

//date picker options
        mc.dateFormat = 'MM/dd/yyyy';
        mc.dateOptions = { formatYear: 'yy', startingDay: 1 };
        mc.today = new Date();
        mc.pickDate = function(id) { mc['datepickerOpen' + id] = true; };
        mc.setMinToday = function() { return mc.filters.date_min = mc.today; };
        mc.setMaxToday = function() { return mc.filters.date_max = mc.today; };
// end date picker options

        mc.getFilters();
        mc.search();
    }

	mc.getFilters = function()
	{
		mc.loading = true;
		ProfileService.loadForm().then(function(response) 
		{
			mc.loading = false;    
		    mc.questions = response.questions;
		    if(mc.questions)
		      mc.questions.byId = mc.questions.indexBy("id");
		  	mc.questions.forEach(function (q) 
		  	{
		  		q.title = q.field_name.makeTitle();
		  	});
		});
	};

    mc.toggleSidebar = function()
    {
        angular.element("#wrapper").toggleClass("toggled");
        mc.resizeGrid(200, 800);
    };

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
            if(!mc.filters[f]) continue;
            params.push(mc.filters[f].label || mc.filters[f].name|| mc.filters[f]);
        }
        return mc.title = params.join(", ");
    }

    mc.getSearchParams = function()
    {
        var params = {};
        for(var f in mc.filters)
        {
            var filter = mc.filters[f]; 
            if(f && filter)
            {
                var key = isMissing(filter.question_id) ? f : 'Q_'+filter.question_id;
                params[key] = filter.id || filter.name || filter;
            }
        }
        return params;
    }

    mc.search = function()
    {
        mc.loading = true;
        mc.params = mc.getSearchParams();
        QueryService.loadQuery(mc.params).then(function(response) 
        {
            mc.searchResults = response; 
        }, 
        mc.errorMessage);
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