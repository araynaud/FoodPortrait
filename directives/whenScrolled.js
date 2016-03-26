angular.module('app').directive('whenScrolled', function() 
{
    console.log("whenScrolled directive");

    var tagName = function(el)
    {
        return el[0].nodeName.toLowerCase();
    } 

    return function(scope, el, attr) 
    {
        console.log("whenScrolled " + attr.whenScrolled);
        if(!attr.whenScrolled) return;

        var tag = tagName(el);
        if(tag == "body" || tag == "html")
            angular.element(window).bind('scroll', function(evt) 
            {
                var maxTop = document.documentElement.scrollHeight - window.innerHeight;
                console.log("whenScrolled: " + window.scrollY + " / " + maxTop);
                if (window.scrollY >= maxTop)
                {
                    console.log("whenScrolled bottom");
                    scope.$apply(attr.whenScrolled);
                }
            });
        else
            el.bind('scroll', function(evt) 
            {
                var rect = el[0].getBoundingClientRect();
                var maxTop = el[0].scrollHeight - rect.height;
                console.log("whenScrolled " + el[0].id + ": " + el[0].scrollTop + " / " + maxTop);
                if (el[0].scrollTop >= maxTop)
                    scope.$apply(attr.whenScrolled);
            });
    };
});
