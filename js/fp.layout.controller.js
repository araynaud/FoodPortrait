'use strict';

// =========== LayoutController ===========
angular.module('fpControllers')
.controller('LayoutController', ['$scope', '$window', '$state', 'ProfileService', 
function ($scope, $window, $state, ProfileService)
{
    var lc = this;
    $window.LayoutController = this;
    this.state = $state;

    lc.init = function()
    {
        $window.addEventListener("load",   lc.getWindowSize);
        $window.addEventListener("resize", lc.getWindowSize);
        
        lc.showDebug = ProfileService.isDebug();
        lc.userAgent = navigator.userAgent.substringAfter(")", true);
        lc.isMobile = ProfileService.isMobile();

        ProfileService.user = $window.fpUser;
        if(!ProfileService.user)
            $state.go('signin');

         lc.toggleSidebar(lc.isWider(768));
    };

    lc.getWindowSize = function()
    {
        lc.windowWidth  = $window.innerWidth;
        lc.windowHeight = $window.innerHeight;
        $scope.$apply();
    };

    lc.bodyClasses = function()
    {
        var isSmall = lc.isMobile || lc.isSmaller(768);
        return { isMobile: isSmall, isDesktop: !isSmall, aboveFooter: lc.showDebug };
    }

    lc.sidebarWrapperClasses = function()
    {
        return {"toggled": lc.sidebar} ;
    }

    lc.toggleSidebar = function(st)
    {   
        lc.sidebar = valueOrDefault(st, !lc.sidebar);
        //lc.getWindowSize();
        return lc.sidebar;
    };

    lc.width = function()
    {
      return $window.innerWidth;
    };

    lc.height = function()
    {
      return $window.innerHeight;
    };

    lc.isPortrait = function()
    {
      return $window.innerWidth <= $window.innerHeight;      
    };

    lc.isWider = function(min)
    {
      return $window.innerWidth >= min;      
    };

    lc.isSmaller = function(max)
    {
        return $window.innerWidth < max;      
    };

    lc.userFullName = function()
    {
      return ProfileService.userFullName();
    };

    lc.loggedIn = function()
    {
      return !!ProfileService.user;
    };

    lc.logout = function()
    {
        return ProfileService.logout();
    }

    lc.stateIs = function(st)
    {
        return $state.is(st);
    };

    lc.currentState = function()
    {
        return $state.current.name;
    };

    lc.title = function()
    {
      document.title = ProfileService.title ? ProfileService.title + " - " + lc.config.defaultTitle : lc.config.defaultTitle;
      return ProfileService.title || lc.config.defaultTitle;
    };

    lc.init();
}]);
