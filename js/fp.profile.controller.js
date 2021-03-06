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
  pc.showDebug = ProfileService.isDebug();
  pc.form = {};

  pc.init = function()
  {
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
          if(!q) continue;
          
          if(q.data_type=="multiple")
          {
              if(!formData[q.id])
                formData[q.id] = {};
              formData[q.id][ans.answer_id] = {id: true, text: ans.answer_text, value: ans.answer_value};
          }
          else if(q.data_type=="single")
              formData[q.id] = {id: ans.answer_id, text: ans.answer_text, value: ans.answer_value};
          else if(q.data_type=="country")
              formData[q.id] = pc.questions.countries.byCode[ans.answer_text];
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
    var defaultTitle = ProfileService.getConfig("defaultTitle");
    return document.title = String.append(ProfileService.title, " - ", defaultTitle);
  };

  pc.init();
}]);
