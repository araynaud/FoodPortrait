'use strict';

// =========== File Upload Controller ===========
// handles query and gallery display
angular.module('fpControllers')
.controller('UploadController', ['$scope', 'Upload', '$window', '$state', 'ProfileService',
function ($scope, Upload, $window, $state, ProfileService)
{
    if(!ProfileService.user)    $state.go('home');
    var uc = this;
    $window.UploadController = this;
    this.state = $state;

    uc.form = {};

    $scope.$watch('files', function () 
    {
        $scope.upload($scope.files);
    });
    $scope.$watch('file', function () 
    {
        $scope.upload([$scope.file]);
    });
    $scope.log = '';

    $scope.upload = function (files) 
    {
        if (files && files.length) 
        {
            for (var i = 0; i < files.length; i++) 
            {
                var file = files[i];
                Upload.upload({
                    url: 'api/upload.php',
                    //url: 'https://angular-file-upload-cors-srv.appspot.com/upload',
                    fields: uc.form, //{ username: ProfileService.user.username },
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
                    if(angular.isObject(data))
                        data = JSON.stringify(data);
                    $scope.log = 'uploaded file: ' + (config.file ? config.file.name : "(none)")
                    + ', Response: ' + data + '\n' + $scope.log;
                });
            }
        }
    };
}]);