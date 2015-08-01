'use strict';

// =========== File Upload Controller ===========
// handles query and gallery display
angular.module('fpControllers')
.controller('UploadController', ['$scope', 'Upload', '$window', '$state', 'ProfileService',
function ($scope, Upload, $window, $state, ProfileService)
{
    if(!ProfileService.user)    $state.go('home');
    var uc = this;
    this.scope = $scope;
    $window.UploadController = this;
    this.state = $state;

    uc.form = {};
    uc.form.shared=1;
/*
    $scope.$watch('files', function () 
    {
        $scope.upload($scope.files);
    });
    $scope.$watch('file', function () 
    {
        $scope.upload([$scope.file]);
    });
*/
    $scope.log = '';

    uc.validate = function(uploadForm)
    {
        return $scope.uploadForm.file.$invalid
        || $scope.uploadForm.description.$invalid
//        || $scope.uploadForm.context.$invalid
        || $scope.uploadForm.shared.$invalid;
    }

    $scope.upload = function (files) 
    {
        if (!files || !files.length) return false;

        for (var i = 0; i < files.length; i++) 
        {
            var file = files[i];
            Upload.upload({
                url: 'api/upload.php',
                fields: uc.form,
                file: file
            })
            .progress(function (evt) 
            {
                var progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
                $scope.log = 'progress: ' + i +' ' + progressPercentage + '% '
                    + (evt.config && evt.config.file? evt.config.file.name : "(none)")
                    + '\n' + $scope.log;
            })
            .success(function (data, status, headers, config) 
            {
                $scope.uploadUrl = data.uploadUrl;
                uc.form.dateTaken = data.dateTaken;
                var dataLog = data;
                if(angular.isObject(data))
                    dataLog = angular.toJson(data, true);

                $scope.log = 'uploaded file: ' + (config.file ? config.file.name : "(none)")
                + ', status: '+ status + ', Response: ' + dataLog + '\n' + $scope.log;
            });
        }
    };
}]);