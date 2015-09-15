angular.module('app').directive('fpPopover', function() 
{
    return {
        restrict: 'E',
        template: '<span class="textOutline glyphicon glyphicon-{{icon}}" ng-style="style">{{label}}</span>',
        link: function (scope, el, attr) 
        {
            scope.icon = attr.icon;
            scope.label = attr.label;
            scope.style = { "font-size": attr.size, color: attr.color};
            $(el).popover({
                trigger: 'click',
                html: true,
                title: attr.popTitle || attr.label,
                content: attr.content,
                placement: attr.placement
//                delay: { hide: 1000 }
            });
        }
    };
});
