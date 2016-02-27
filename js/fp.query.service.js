'use strict';

//service that handles loading image grid data

angular.module('fpServices')
.service('QueryService', ['$resource', '$q', '$window', 'ProfileService', function($resource, $q, $window, ProfileService) 
{
    var service = this;
    this.init = function()
    {
        //$window.QueryService = this;
        this.queryResource = ProfileService.getResource("foodportrait", "query" + ProfileService.serviceExt());
    };
    
    this.loadQuery = function(filters)
    {
        var deferred = $q.defer();
        this.queryResource.get(filters, function(response)
        {
            service.results = Object.toArray(response.results);
            service.users = response.users;
            service.queries = response.queries;
            deferred.resolve(service.results);
        });
        return deferred.promise;
    };
 
    this.init();
}]);
