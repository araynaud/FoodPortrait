'use strict';

// =========== Main Controller ===========
// handles query and gallery display
angular.module('fpControllers')
.controller('MainController', ['$window', '$state', 'ProfileService', function ($window, $state, ProfileService)
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
    mc.filters = {};
    mc.fpConfig = $window.fpConfig; 
//date picker options
    //mc.datepickerOpen=false;
    mc.dateFormat = 'MM/dd/yyyy';
    mc.dateOptions = { formatYear: 'yy', startingDay: 1 };
    mc.today = new Date();
    mc.pickDate = function(id) { mc['datepickerOpen' + id] = true; };
    mc.setMinToday = function() { return mc.filters.date_min = mc.today; };
    mc.setMaxToday = function() { return mc.filters.date_max = mc.today; };
//    mc.setToday();
// end date picker options

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

	mc.getFilters();

}]);