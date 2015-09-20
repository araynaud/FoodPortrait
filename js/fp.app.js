'use strict';

/* App Module */

// Define new module for our application
var app = angular.module('app', ['ui.router', 'ui.bootstrap', 'ngFileUpload', 'fpControllers', 'fpServices']);

app.config(function($stateProvider, $urlRouterProvider)
{  
  // For any unmatched url, send to /route1
  $urlRouterProvider.otherwise("/");
  $stateProvider
  	.state('home',    { url: "/",        templateUrl: 'views/about.html' })
//  	.state('about',   { url: "/about",   templateUrl: 'views/about.html' })
    .state('main',    { url: "/main",    controller: 'MainController',   controllerAs: 'mc', templateUrl: 'views/main.html' })
    .state('upload',  { url: "/upload",  controller: 'UploadController', controllerAs: 'uc', templateUrl: 'views/upload.html' })
  	.state('signin',  { url: "/signin",  controller: 'LoginController',  controllerAs: 'lc', templateUrl: "views/signin.html" })
  	.state('signup',  { url: "/signup",  controller: 'LoginController',  controllerAs: 'lc', templateUrl: "views/signup.html" })
  	.state('profile', { url: "/profile",
  		controller: 'ProfileController', controllerAs: 'pc', 
  		template: '<object-form actions="pc.actions" questions="pc.questions" form-data="pc.formData" showDebug="pc.showDebug"/>'});
});

angular.module('fpServices', ['ngResource']);
angular.module('fpControllers', []);

app.filter('toJson', function() 
{

  // In the return function, we must pass in a single parameter which will be the data we will work on.
  // We have the ability to support multiple other parameters that can be passed into the filter optionally
  return function(data, loop)
  {
    if(!data) return data;

    var result = '';
    if(loop && angular.isArray(data))
    {
      data.forEach(function(el)
      {
        result += angular.toJson(el) + '\n';
      });
      return result;
    }

    if(loop && angular.isObject(data))
    {
      for(key in data)
        result += key + ": " + angular.toJson(data[key]) + '\n';
      return result;
    }
    
    return angular.toJson(data, true);
  }

});