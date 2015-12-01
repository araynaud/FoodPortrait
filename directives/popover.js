angular.module('app').directive('fpPopover', function() 
{
    return {
        restrict: 'E',
        template: '<i class="textOutline glyphicon" ng-class="{{classes}}" ng-style="{{style}}">{{label}}</i>',
        link: function (scope, el, attr) 
        {
            scope.icon = attr.icon;
            scope.label = attr.label;
            scope.classes = {};
            if(!attr.placement) 
                attr.placement="auto bottom";
            if(attr.icon)
                scope.classes.glyphicon = scope.classes["glyphicon-" + attr.icon] = attr.icon;
            
            scope.style = { width: attr.size, height: attr.size, "font-size": attr.size, color: attr.color};

            el.parent().popover({
                trigger: 'hover',
                html: true,
                title: attr.popTitle || attr.label,
                content: attr.popContent,
                placement: attr.placement
            });
        }
    };
});
