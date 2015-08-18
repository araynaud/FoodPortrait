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
    uc.fpConfig = $window.fpConfig;
    uc.showDebug = valueIfDefined("fpConfig.debug.angular", uc);
    uc.showDebug = $window.fpConfig && $window.fpConfig.debug ? $window.fpConfig.debug.angular : false;

    uc.form = {};
    uc.form.shared=1;
    $scope.log = '';

    uc.validate = function(uploadForm)
    {
        return $scope.uploadForm.file.$invalid
//        || $scope.uploadForm.description.$invalid
//        || $scope.uploadForm.meal.$invalid
//        || $scope.uploadForm.context.$invalid
//        || $scope.uploadForm.mood.$invalid
        || $scope.uploadForm.shared.$invalid;
    }

    uc.showFileChanged = function()
    {
        uc.log = uc.file;
//        alert(angular.toJson(uc.file));
    }

    //post file
    //insert db record
    //return file metadata and upload id to form
    uc.upload = function (files) 
    {
        if (!files || !files.length) return false;

        for (var i = 0; i < files.length; i++) 
        {
            var file = files[i];
            uc.progressPercentage="";
            uc.uploadUrl="";
            uc.log="";
            Upload.upload({ url: 'api/upload.php', fields: uc.form, file: file })
            .progress(function (evt) 
            {
                uc.progressPercentage = parseInt(100.0 * evt.loaded / evt.total) + '%';
                if(!uc.showDebug) return;

                var fname = (evt.config && evt.config.file? evt.config.file.name : "(none)");
                uc.log = 'progress: {0} {1}\n'.format(uc.progressPercentage, fname) + uc.log;
            })
            .success(function (data, status, headers, config) 
            {
                uc.progressPercentage="";
                uc.uploadUrl = data.uploadUrl;
                uc.form.dateTaken = data.dateTaken;
                uc.form.description = data.description;
                if(!uc.showDebug) return;

                var dataLog = data;
                if(angular.isObject(data))
                    dataLog = angular.toJson(data, true);
                var fname = (config.file ? config.file.name : "(none)");
                uc.log = 'uploaded file: {0}, status: {1}, Response: {2}\n'.format(fname, status, dataLog) + uc.log;
            });
        }
    };

    //post details
    //update db record
    uc.saveUpload = function () 
    {
        //file is already uploaded: post only form data to upload api
        Upload.upload({ url: 'api/upload.php', fields: uc.form }).success(function (data, status, headers, config) 
        {
                if(!uc.showDebug) return;

                var dataLog = data;
                if(angular.isObject(data))
                    dataLog = angular.toJson(data, true);
                uc.log = dataLog;
        });
    }

    //if upload canceled: delete details
    //delete db record and uploaded file
    uc.cancelUpload = function () 
    {
        $state.go('main');
    }


}]);