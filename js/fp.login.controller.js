'use strict';

// =========== LoginController ===========
// handles login (sign in), register (sign up), logout, 
angular.module('fpControllers')
.controller('LoginController', ['$window', '$state', '$stateParams', 'ProfileService', 
function ($window, $state, $stateParams, ProfileService)
{
    //TODO:
    //post login, md5(password)
    //to PHP login.php service
    //query user table where username = user and password = md5
    //if success: store session["username"]
    //else : unset session["username"]
    //return user object with name
    var lc = this;
    $window.LoginController = this;
    this.state = $state;
    lc.form = { };
    lc.success = true;
    if($state.is("reset2") && $stateParams.email)
      lc.form.email = $stateParams.email;

    lc.login = function()
    {
      var postData = {action: "login"};
      angular.merge(postData, lc.form);
      if(!lc.form.username || !lc.form.password) 
        return false;
      if($window.md5)
        postData.password = md5(lc.form.password);
      lc.post(postData);
    };

    lc.signup = function()
    {
      var postData = {action: "signup"};
      angular.merge(postData, lc.form);
      if(!lc.form.username || !lc.form.password || !lc.form.password2 || !lc.form.email) 
        return false;

      if(lc.form.password != lc.form.password2)
        return lc.message = "Passwords do not match.";

      if($window.md5)
        postData.password = md5(lc.form.password);
      delete postData.password2;
      lc.post(postData);
    };

    lc.sendResetEmail = function()
    {
      var postData = {action: "sendResetEmail"};
      angular.merge(postData, lc.form);
      if(!lc.form.email) 
        return false;

      lc.message = "Sending email...";
      lc.post(postData);
    };


    lc.resetPassword = function()
    {
      var postData = {action: "resetPassword"};
      angular.merge(postData, lc.form);
      if(!lc.form.password || !lc.form.password2 || !lc.form.email) 
        return false;

      if(lc.form.password != lc.form.password2)
        return lc.message = "Passwords do not match.";

      if($window.md5)
        postData.password = md5(lc.form.password);
      delete postData.password2;
      lc.post(postData);
    };

    lc.post = function(postData)
    {
      lc.loading = true;
      ProfileService.login(postData).then(function(response) 
      {
          lc.loading = false;
          lc.user = response.user; 
          lc.success = !!response.success;
          lc.loggedIn = lc.success && !!lc.user;
          lc.message = response.message;
          if(lc.user && lc.user.hasProfile)
            $state.go('main');
          else if(lc.user)
            $state.go('profile');
      });
    };

    lc.returnToMain = function(delay)
    {
        if(!delay)
            return $state.go('main');

        $timeout(function() { $state.go('main'); }, delay);
    };

}]);
