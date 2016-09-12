'use strict';

/* App Module */

// Define new module for our application
var app = angular.module('app', ['ui.router', 'ui.bootstrap', 'fpControllers', 'fpServices']);

app.config(function($stateProvider, $urlRouterProvider)
{  
  // For any unmatched url, send to /route1
  $urlRouterProvider.otherwise("/");
  $stateProvider
  	.state('home',    { url: "/",        templateUrl: 'views/about.html' })
    .state('main',    { url: "/main",    controller: 'MainController',   controllerAs: 'mc', templateUrl: 'views/main.html' })
    .state('upload',  { url: "/upload/:uploadId",  controller: 'UploadController', controllerAs: 'uc', templateUrl: 'views/upload.html' })
  	.state('signin',  { url: "/signin",  controller: 'LoginController',  controllerAs: 'lc', templateUrl: "views/signin.html" })
  	.state('signup',  { url: "/signup",  controller: 'LoginController',  controllerAs: 'lc', templateUrl: "views/signup.html" })
    .state('reset1',  { url: "/reset1",  controller: 'LoginController',  controllerAs: 'lc', templateUrl: "views/reset1.html" })
    .state('reset2',  { url: "/reset2",  controller: 'LoginController',  controllerAs: 'lc', templateUrl: "views/reset2.html" })
  	.state('profile', { url: "/profile",
  		controller: 'ProfileController', controllerAs: 'pc', 
  		template: '<object-form actions="pc.actions" questions="pc.questions" form-data="pc.formData" showDebug="pc.showDebug"/>'});
});

angular.module('fpServices', ['ngResource']);
angular.module('fpControllers', ['ngAnimate', 'ngFileUpload']);

app.isMobile = function() 
{ 
    return !!navigator.userAgent.match(/Android|webOS|iPhone|iPad|iPod|BlackBerry|Phone|mobile/i);
};

app.toJson = function(data, loop)
{
  if(!data || angular.isString(data)) return data;
  var result = '';
  if(angular.isString(data)) return result + data;

  if(angular.isArray(data) && !angular.isObject(data[0]))
  {
    result += "[" + data.join(", ") + "]";
    return result;
  }

  if(loop && angular.isArray(data))
  {
    data.forEach(function(el)
    {
      result += app.toJson(el, loop) + '\n';
    });
    return result;
  }

  if(loop && angular.isObject(data))
  {
    for(var key in data)
      result += key + ": " + app.toJson(data[key]) + '\n';
    return result;
  }
  
  return angular.toJson(data, !loop);
};

app.filter('toJson', function() { return app.toJson; });