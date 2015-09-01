'use strict';

// =========== LoginController ===========
// handles login (sign in), register (sign up), logout, 
angular.module('fpControllers')
// =========== ProfileController ===========
.controller('ProfileController', ['$window', '$state', 'ProfileService',  
function ($window, $state, ProfileService)
{
  if(!ProfileService.user && !ProfileService.isOffline())
      $state.go('home');

  var pc = this;
  $window.ProfileController = this;
  //this.scope = $scope;
  this.state = $state;

  pc.plural = plural;
  pc.loading = false;
  pc.config = $window.fpConfig;
  pc.showDebug = valueIfDefined("fpConfig.debug.angular");
  pc.form = {};

  pc.init = function()
  {
    if(!pc.config)
      pc.actions.getConfig();
    pc.config.isMobile = ProfileService.isMobile();
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
        if(pc.questions)
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
      if(!pc.user_answers || !pc.questions) return formData;

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
        $state.go("main");
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
