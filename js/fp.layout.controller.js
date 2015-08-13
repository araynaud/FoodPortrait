'use strict';

// =========== LayoutController ===========
angular.module('fpControllers')
.controller('LayoutController', ['$window', '$state', 'ProfileService', 
function ($window, $state, ProfileService)
{
    var lc = this;
    $window.LayoutController = this;
    this.state = $state;

    lc.getWindowSize = function()
    {
        lc.windowWidth  = $window.innerWidth;
        lc.windowHeight = $window.innerHeight;
    };

    $window.addEventListener("load",   lc.getWindowSize);
    $window.addEventListener("resize", lc.getWindowSize);

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

    lc.userAgent = navigator.userAgent.substringAfter(")", true);
    ProfileService.user = $window.fpUser;

    if(!ProfileService.user)
      $state.go('signin');

}]);
