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
        
        lc.showDebug = valueIfDefined("fpConfig.debug.angular");
        lc.userAgent = navigator.userAgent.substringAfter(")", true);
        lc.isMobile = ProfileService.isMobile();

        ProfileService.user = $window.fpUser;
        if(!ProfileService.user)
            $state.go('signin');

    }

    lc.getWindowSize = function()
    {
        lc.windowWidth  = $window.innerWidth;
        lc.windowHeight = $window.innerHeight;
        $scope.$apply();
    };

    lc.bodyClasses = function()
    {
        var classes = { mobile: lc.isMobile, desktop: !lc.isMobile };        
        return classes;
    }

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
