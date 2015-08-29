'use strict';

//service that handles loading image grid data

angular.module('fpServices')
.service('QueryService', ['$resource', '$q', '$window', 'ProfileService', function($resource, $q, $window, ProfileService) 
{
    var service = this;
    this.init = function()
    {
        $window.QueryService = this;
        this.config = $window.fpConfig;
        this.queryResource = $resource('api/query.php'); // + ProfileService.serviceExt());
    };
    
    this.loadQuery = function(filters)
    {
        var deferred = $q.defer();
        this.queryResource.query(filters, function(response)
        {
            service.results = response;
            deferred.resolve(response);
        });
        return deferred.promise;
    };
 
    this.init();
}]);
