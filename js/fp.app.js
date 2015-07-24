'use strict';

/* App Module */

// Define new module for our application
var app = angular.module('app', ['ui.router', 'fpControllers', 'fpServices']);

app.config(function($stateProvider, $urlRouterProvider)
{  
  // For any unmatched url, send to /route1
  $urlRouterProvider.otherwise("/");
  $stateProvider
  	.state('home',    { url: "/",        template: '<h2 class="form-signin-heading">Welcome home to Food Portrait</h2>' })
  	.state('about',   { url: "/about",   template: '<h2 class="form-signin-heading">About Food Portrait</h2>' })
  	.state('signin',  { url: "/signin",  controller: 'LoginController', controllerAs: 'lc', templateUrl: "views/signin.html" })
  	.state('signup',  { url: "/signup",  controller: 'LoginController', controllerAs: 'lc', templateUrl: "views/signup.html" })
  	.state('profile', { url: "/profile",
  		controller: 'ProfileController', controllerAs: 'pc', 
  		template: '<object-form actions="pc.actions" questions="pc.questions" form-data="pc.formData"/>'});
});