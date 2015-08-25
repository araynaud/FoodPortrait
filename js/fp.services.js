'use strict';

angular.module('fpServices', ['ngResource'])
.service('ProfileService', ['$resource', '$q', function($resource, $q) 
{
    var service = this;
    this.init = function()
    {
        window.ProfileService = this;
        this.config = window.fpConfig;

        this.questions = [];
        this.configResource = $resource('api/config.php');
        this.formResource = $resource('api/form_data' + this.serviceExt());
        this.loginResource = $resource('api/login' + this.serviceExt());
    };
    
    this.isOffline = function()
    {
        return valueIfDefined('config.debug.offline', service);
    };

    this.serviceExt = function()
    {
        return this.isOffline() ? '.json' : '.php';
    };

    this.loadConfig = function()
    {
        var deferred = $q.defer();
        this.configResource.get(function(response)
        {
            service.config = response;
            deferred.resolve(response);
        });
        return deferred.promise;
    };

//User login / logout
//POST to login.php service
    this.logout = function()
    {
        return this.login({action: "logout"});    
    }
    
    //POST to login.php service
    this.login = function(formData)
    {
        var deferred = $q.defer();
        //formData.action = "login"; //or register or logout
        this.loginResource.save(formData, function(response) 
        {
            service.user = response.user;
            deferred.resolve(response);
        });
        return deferred.promise;
    };

    this.userFullName = function()
    {
        if(!this.user) return "nobody";
        return this.user.first_name + " " + this.user.last_name 
    }

//Profile form
	this.loadForm = function()
	{
        var deferred = $q.defer();
        //optional: pass username? pass form_id. section_id
	    this.formResource.get(function(response)
        {
            service.form = response;
            deferred.resolve(response);
        }); 		
        return deferred.promise;
	};

    //save array of user answers
    this.saveForm = function(formData)
    {
        var deferred = $q.defer();
        this.formResource.save({action: "saveForm", formData: formData}, function(response) 
        {
            deferred.resolve(response);
        });
        return deferred.promise;
    };

    this.init();
}]);
