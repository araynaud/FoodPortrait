angular.module('app').directive('objectForm', function () 
{
    return {
        scope: { actions: '=', questions: '=', formData: '='},
        templateUrl: 'views/objectForm.html',
        controllerAs: 'vm',
        bindToController: true,
        controller: function ()
        {
            var vm = this; 
            window.objectForm = this;

            vm.title="Your Profile";
            vm.showDebug = false;

            vm.toJson = function(data)
            {
                if(!data) return data;
                return angular.toJson(data, true);
            }

            //transform user_answer array into formdata object.
            vm.loadUserAnswers = function() 
            {
                if(vm.actions && vm.actions.loadUserAnswers)
                    return vm.formData = vm.actions.loadUserAnswers();
            }
            
			vm.cancel = function() 
            {
				history.back();
			}
			
            vm.saveForm = function() 
            {
                vm.status = "saveForm\n";
                //if(!vm.isFormComplete()) return false;
                vm.postData = vm.saveUserAnswers();

                //call resource form_Data.php to load / save
                if(vm.actions && vm.actions.saveForm)
                    return vm.actions.saveForm(vm.postData);
            }

            //transform formData into array of user_answers for insert/update
            vm.saveUserAnswers = function() 
            {
                var postData = [];
                for(var qid in vm.formData)
                {
                    var q = vm.questions.byId[qid];
                    if(q.form_answers && !q.faById)
                        q.faById = q.form_answers.indexBy("id");
                    var ans = vm.formData[qid];
                    var answer;
                    if(q.data_type=="multiple")
                    {
                        for(id in ans)
                        {
                            if(ans[id].id)
                            {
                                answer = {question_id: q.id, answer_id: id, answer_text: ans[id].text};
                                postData.push(answer);
                            }
                        }
                    }
                    else if(q.data_type=="single")
                    {
                        var fa=q.faById[ans.id];
                        answer = {question_id: q.id, answer_id: ans.id};
                        if(fa.data_type =="text")
                            answer.answer_text = ans.text;
                        else if(fa.data_type =="number")
                            answer.answer_value = ans.text;
                        postData.push(answer);
                    }
                    else if(ans)
                    {                        
                        answer = {question_id: q.id, answer_id: ans.id};
                        if(angular.isString(ans))
                            answer.answer_text = ans;
                        else
                            answer.answer_value = ans;
                        postData.push(answer);
                    }
                }
                return postData;
            }
        }         
    };
});
