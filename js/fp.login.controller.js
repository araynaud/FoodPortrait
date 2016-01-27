'use strict';

// =========== LoginController ===========
// handles login (sign in), register (sign up), logout, 
angular.module('fpControllers')
.controller('LoginController', ['$window', '$state', 'ProfileService', 
function ($window, $state, ProfileService)
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

    lc.post = function(postData)
    {
      lc.loading = true;
      ProfileService.login(postData).then(function(response) 
      {
          lc.loading = false;
          lc.user = response.user; 
          lc.loggedIn = response.success;
          lc.message = response.message;
          if(lc.user && lc.user.hasProfile)
            $state.go('main');
          else if(lc.user)
            $state.go('profile');
      });
    };

}]);
