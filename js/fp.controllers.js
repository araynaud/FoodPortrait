'use strict';

// =========== ProfileController ===========
angular.module('fpControllers', [])
// =========== LoginController ===========
// handles login (sign in), register (sign up), logout, 
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
      if($window.md5)
        postData.password = md5(lc.form.password);
      lc.post(postData);
    };

    lc.signup = function()
    {
      var postData = {action: "signup"};
      angular.merge(postData, lc.form);
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
          if(ProfileService.user)
            $state.go('profile');
      });
    };

}])

// =========== LayoutController ===========
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

}])

.controller('ProfileController', ['$window', '$state', 'ProfileService',  
function ($window, $state, ProfileService)
{
  var pc = this;
  $window.ProfileController = this;
  //this.scope = $scope;
  this.state = $state;
  pc.plural = plural;
  pc.loading = false;
  pc.config = $window.fpConfig;
  pc.form = {};

  pc.init = function()
  {
    if(!ProfileService.user)
      $state.go('signin');

    if(!pc.config)
      pc.actions.getConfig();
    pc.actions.getForm();
  } 

  pc.actions = {};
  pc.actions.getConfig = function()
  {
    ProfileService.loadConfig().then(function(response) 
    {
        pc.config = response; 
        pc.successMessage();
    }, 
    pc.errorMessage);    
  }

  pc.actions.getForm = function()
  {
    pc.loading = true;
    ProfileService.loadForm().then(function(response) 
    {
        pc.form = response;
        pc.questions = response.questions;
        pc.questions.byId = pc.questions.indexBy("id");
        pc.user_answers = response.user_answers; 
        pc.formData = pc.actions.loadUserAnswers();
        pc.successMessage();
    }, 
    pc.errorMessage);
  };

  //transform answer array into formdata object.
  pc.actions.loadUserAnswers = function() 
  {
      var formData = {};
      if(!pc.user_answers) return formData;

      for(var i=0; i < pc.user_answers.length; i++)
      {
          var ans = pc.user_answers[i];
          var q = pc.questions.byId[ans.question_id];
          if(q.data_type=="multiple")
          {
              if(!formData[q.id])
                formData[q.id] = {};
              formData[q.id][ans.answer_id] = {id: true, text: ans.answer_text, value: ans.answer_value};
          }
          else if(q.data_type=="single")
              formData[q.id] = {id: ans.answer_id, text: ans.answer_text, value: ans.answer_value};
          else
              formData[q.id] = ans.answer_text || ans.answer_value;
      }
      return formData;
  }

  pc.actions.saveForm = function(postData)
  {
    pc.loading = true;
    ProfileService.saveForm(postData).then(function(response) 
    {
        pc.saveStatus = response; 
        pc.successMessage();
    }, 
    pc.errorMessage);
  };

  pc.errorMessage =  function (result)
  {
    pc.loading = false;
    pc.status = "Error: No data returned";
  };

  pc.successMessage =  function (result)
  {
    pc.loading = false;
    pc.status = result;
  };

  pc.title = function()
  {
    document.title = ProfileService.title ? ProfileService.title + " - " + pc.config.defaultTitle : pc.config.defaultTitle;
    return ProfileService.title || pc.config.defaultTitle;
  };

  pc.init();
}]);

