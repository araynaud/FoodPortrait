angular.module('app').directive('fpPopover', function() 
{
    return {
        restrict: 'AE',
        template: '<a class="textOutline glyphicon" ng-classes="{{classes}}" ng-style="style">{{label}}</a>',
        link: function (scope, el, attr) 
        {
            scope.icon = attr.icon;
            scope.label = attr.label;
            scope.classes = {};
            if(attr.icon)
                scope.classes["glyphicon-" + attr.icon] = attr.icon;
            scope.style = { "font-size": attr.size, color: attr.color};
            $(el).popover({
                trigger: 'hover',
                html: true,
                title: attr.popTitle || attr.label,
                content: attr.popContent,
                placement: attr.placement
            });
        }
    };
});
