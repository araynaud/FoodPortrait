'use strict';

// =========== Main Controller ===========
// handles query and gallery display
angular.module('fpControllers')
.controller('MainController', ['$window', '$state', 'ProfileService', 
function ($window, $state, ProfileService)
{
    //TODO:
    //post filters to album.php service
    //query user table where username = user and password = md5
    //return array of uploads to display
    if(!ProfileService.user)
      $state.go('home');

    var mc = this;
    $window.MainController = this;
    this.state = $state;

	mc.getFilters = function()
	{
		mc.loading = true;
		ProfileService.loadForm().then(function(response) 
		{
			mc.loading = false;    
		    mc.filters = response.questions;
		    if(mc.filters)
		      mc.filters.byId = mc.filters.indexBy("id");
		});
	};

	mc.getFilters();

}]);