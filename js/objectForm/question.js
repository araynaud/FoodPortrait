angular.module('app').directive('question', function () 
{
    return {
        scope: { vm: '=', q: '='},
        templateUrl: 'views/question.html'
    };
});
