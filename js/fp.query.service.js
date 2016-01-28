'use strict';

//service that handles loading image grid data

angular.module('fpServices')
.service('QueryService', ['$resource', '$q', '$window', 'ProfileService', function($resource, $q, $window, ProfileService) 
{
    var service = this;
    this.init = function()
    {
        $window.QueryService = this;
        this.serviceUrl = 'api/query' + ProfileService.serviceExt();
        this.queryResource = $resource(this.serviceUrl);
    };
    
    this.loadQuery = function(filters)
    {
        var deferred = $q.defer();
        this.queryResource.get(filters, function(response)
        {
            service.results = response.results;
            service.users = response.users;
            deferred.resolve(service.results);
        });
        return deferred.promise;
    };
 
    this.init();
}]);
