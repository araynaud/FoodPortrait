app.directive('onLoad', function() 
{
    return {
        restrict: 'A',
        link: function(scope, element, attrs) 
        {
            if(attrs.onLoad)
                element.bind('load', function()
                {
                    var callback = scope.$eval(attrs.onLoad);
                    scope.$apply(function() { callback(element); });
                });
        }
    };
})
.directive('onError', function() 
{
    return {
        restrict: 'A',
        link: function(scope, element, attrs) 
        {
            if(attrs.onError)
                element.bind('error', function()
                {
                    var callback = scope.$eval(attrs.onError);
                    scope.$apply(function() { callback(element); });
                });
        }
    };
});
