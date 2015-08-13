'use strict';

/* App Module */

// Define new module for our application
var app = angular.module('app', ['ui.router', 'ngFileUpload', 'fpControllers', 'fpServices']);

app.config(function($stateProvider, $urlRouterProvider)
{  
  // For any unmatched url, send to /route1
  $urlRouterProvider.otherwise("/");
  $stateProvider
  	.state('home',    { url: "/",        templateUrl: 'views/home.html' })
  	.state('about',   { url: "/about",   templateUrl: 'views/about.html' })
    .state('main',    { url: "/main",    controller: 'MainController',   controllerAs: 'mc', templateUrl: 'views/main.html' })
    .state('upload',  { url: "/upload",  controller: 'UploadController', controllerAs: 'uc', templateUrl: 'views/upload.html' })
  	.state('signin',  { url: "/signin",  controller: 'LoginController',  controllerAs: 'lc', templateUrl: "views/signin.html" })
  	.state('signup',  { url: "/signup",  controller: 'LoginController',  controllerAs: 'lc', templateUrl: "views/signup.html" })
  	.state('profile', { url: "/profile",
  		controller: 'ProfileController', controllerAs: 'pc', 
  		template: '<object-form actions="pc.actions" questions="pc.questions" form-data="pc.formData"/>'});
});

angular.module('fpControllers', []);
