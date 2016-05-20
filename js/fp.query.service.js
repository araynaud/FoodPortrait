'use strict';

//service that handles loading image grid data

angular.module('fpServices')
.service('QueryService', ['$resource', '$q', 'ProfileService', function($resource, $q, ProfileService) 
{
    var svc = this;
    window.QueryService = this;
    
    svc.init = function()
    {
        svc.queryResource = ProfileService.getResource("foodportrait", "query" + ProfileService.serviceExt());
        svc.filters = ProfileService.getConfig("filters"); 
    };
    
    svc.loadQuery = function(filters)
    {
        var deferred = $q.defer();
        svc.queryResource.get(filters, function(response)
        {
            svc.results = Object.toArray(response.results);
            for(var i=0; i<svc.results.length; i++)
            {
                var group = svc.results[i];
                group.title = response.groupTitles ? response.groupTitles[group.key] : group.key.substringAfter("group_", false, true);
            }

            svc.users = response.users;
            svc.groups = response.groups;

            svc.time = response.time;
            svc.queries = response.queries;
            deferred.resolve(svc.results);
        });
        return deferred.promise;
    };
 
    svc.init();
}]);
