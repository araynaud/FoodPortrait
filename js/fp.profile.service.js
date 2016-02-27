'use strict';

angular.module('fpServices', ['ngResource'])
.service('ProfileService', ['$window', '$http', '$resource', '$q', function($window, $http, $resource, $q) 
{
    var service = this;
    this.init = function()
    {
//        $window.ProfileService = this;
        this.config = $window.fpConfig;

        this.questions = [];
        this.configResource = this.getResource("foodportrait", "config.php");
        this.formResource =   this.getResource("foodportrait", "form_data" + this.serviceExt());
        this.loginResource =  this.getResource("foodportrait", "login" + this.serviceExt());
        this.loadCountries();
    };

    this.getResourceUrl = function(api, url, qs)
    {
        if(this.offline)
        {
            var svcName = url.substringBefore("/:")
            svcName = svcName.substringAfter("/", false, true);
            return "api/" + svcName + ".json";
        }

        var baseUrl = this.getConfig("api."+api+".url");
        var proxy = String.isExternalUrl(baseUrl) ? this.getConfig("api.proxy") : null;
        var url = String.combine(proxy, baseUrl, url);
        if(qs) url += "?" + qs;
        return url;
    };

    this.getResource = function(api, url, qs, defaults)
    {
        url = this.getResourceUrl(api, url, qs);
        return $resource(url, defaults);
    };

    this.getConfig = function(key)
    {
        return valueIfDefined(key, service.config);
    }

    this.isDebug = function()
    {
        return service.getConfig("debug.angular");
    };
    
    this.isOffline = function()
    {
        return service.getConfig("debug.offline");
    };

    this.serviceExt = function()
    {
        return this.isOffline() ? '.json' : '.php';
    };

    this.loadCountries = function()
    {
        var deferred = $q.defer();

        $http.get("api/countries.csv").then(function(response) 
        {
            service.countries = String.parseCsv(response.data, true);
            service.countries.byCode = service.countries.indexBy("country_code");
            deferred.resolve(service.countries);
        });
        return deferred.promise;
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

    this.currentUsername = function()
    {
        return this.user ? this.user.username : null;
    };

    this.userFullName = function()
    {
        if(!this.user) return "nobody";
        if(!this.user.first_name && !this.user.last_name)   return this.user.username;
        if(!this.user.first_name)   return this.user.last_name;
        if(!this.user.last_name)    return this.user.first_name;
        return this.user.first_name + " " + this.user.last_name;
    };

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


    this.isMobile = function() 
    { 
        return !!navigator.userAgent.match(/Android|webOS|iPhone|iPad|iPod|BlackBerry|Phone|mobile/i);
    }

    this.clientIs = function(str) 
    { 
        var reg = new RegExp(str, "i");
        return !!navigator.userAgent.match(reg);
    }

    this.clientIsIE = function() 
    { 
        return this.clientIs("MSIE|Trident");
    }

    this.init();
}]);
