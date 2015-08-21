'use strict';

// =========== File Upload Controller ===========
// handles query and gallery display
angular.module('fpControllers')
.controller('UploadController', ['$scope', 'Upload', '$window', '$state', 'ProfileService',
function ($scope, Upload, $window, $state, ProfileService)
{
    var uc = this;
    if(!ProfileService.user && !valueIfDefined("fpConfig.debug.offline"))
        $state.go('home');

    uc.showDebug = valueIfDefined("fpConfig.debug.angular");
    this.scope = $scope;
    $window.UploadController = this;
    this.state = $state;
    uc.fpConfig = $window.fpConfig;

    uc.form = {};
    uc.form.shared=1;
    $scope.log = '';

//date picker options
    uc.datepickerOpen=false;
    uc.dateFormat = 'MMMM dd, yyyy';
    uc.openDatepicker = function($event)
    {
        uc.log = angular.toJson($event, true);
        uc.datepickerOpen = true;   
    }

    uc.today = new Date();
    uc.setToday = function() { return uc.form.dateTaken = uc.today; };
    uc.setToday();

    $scope.status = { opened: false };
    $scope.open = function($event)
    {
        $scope.status.opened = true;
    };

    uc.dateOptions = { formatYear: 'yy', startingDay: 1 };
// end date picker options

    uc.meals = uc.fpConfig.dropdown.meal.distinct("name");

    uc.validate = function(uploadForm)
    {
        return $scope.uploadForm.file.$invalid
        || $scope.uploadForm.description.$invalid
        || $scope.uploadForm.meal.$invalid
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
                uc.form.upload_id = data.upload_id;
                uc.parseDate(data.dateTaken);
                uc.selectMeal(uc.form.dateTaken);

                if(data.description)
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

    uc.parseDate = function(dt)
    {
        if(!dt) return uc.dateTaken = uc.form.dateTaken = null;

        uc.dateTaken = dt.replace(/-/g, '/');
        uc.form.dateTaken = new Date(uc.dateTaken);
        uc.selectMeal(uc.form.dateTaken);
    }

    //select meal based on photo time
    uc.selectMeal = function(dt)
    {
        if(!dt) return;
        var hour = dt.getHours();
        var mealId = 0;
        var list = uc.fpConfig.dropdown.meal;
        for(mealId = 0; mealId < list.length; mealId++)
            if(!list[mealId].start || hour >= list[mealId].start && hour < list[mealId].end) break;
        uc.mealId = mealId;
        return uc.form.meal = uc.fpConfig.dropdown.meal[mealId].name;
    }

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