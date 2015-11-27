'use strict';

// =========== File Upload Controller ===========
// handles query and gallery display
angular.module('fpControllers')
.controller('UploadController', ['$scope', 'Upload', '$window', '$state', '$timeout', 'ProfileService',
function ($scope, Upload, $window, $state, $timeout, ProfileService)
{
    var uc = this;
    $window.UploadController = this;
    if(!ProfileService.user && !ProfileService.isOffline())
        $state.go('home');

    uc.init = function()
    {
        uc.showDebug = valueIfDefined("fpConfig.debug.angular");
        uc.queued = true;
        this.scope = $scope;
        this.state = $state;
        uc.fpConfig = $window.fpConfig;
        uc.logReverse = false;
        uc.form = {};
        uc.form.shared_with=1;
        uc.resetLog();

    //date picker options
        uc.datepickerOpen=false;
        uc.dateFormat = 'MMMM dd, yyyy';
        uc.dateOptions = { formatYear: 'yy', startingDay: 1 };
        uc.today = new Date();
        uc.pickDate = function() { uc.datepickerOpen = true; };
        uc.setToday = function() { return uc.form.image_date_taken = uc.today; };
        uc.setToday();
    // end date picker options

        uc.meals = uc.fpConfig.dropdown.meal.distinct("name");
        uc.meals.byName = uc.fpConfig.dropdown.meal.indexBy("name");
    };

    uc.validate = function()
    {
        return $scope.uploadForm.file.$invalid
        || $scope.uploadForm.image_date_taken.$invalid
        || $scope.uploadForm.caption.$invalid
        || $scope.uploadForm.meal.$invalid
        || $scope.uploadForm.shared_with.$invalid;
    };

    //post file
    //insert db record
    //return file metadata and upload id to form
    uc.upload = function()
    {
        uc.index=0;
        uc.resetLog();
        if(isEmpty(uc.files)) return false;

        if(!angular.isArray(uc.files))
            return uc.uploadFile(uc.files);

        if(uc.queued)
            return uc.uploadFile(uc.files[0]);

        for(var i = 0; i < uc.files.length; i++) 
            uc.uploadFile(uc.files[i]);
    };

    uc.uploadFile = function(file) 
    {
        uc.progressPercentage="";
        uc.uploadUrl="";
        Upload.upload({ url: 'api/upload.php', fields: uc.form, file: file })
        .progress(function (evt) 
        {
            uc.progressPercentage = parseInt(100.0 * evt.loaded / evt.total) + '%';
            if(!uc.showDebug) return;

            var fname = (evt.config && evt.config.file? evt.config.file.name : "(none)");
            uc.addLog('progress: {0} {1}'.format(uc.progressPercentage, fname));
        })
        .success(function (data, status, headers, config) 
        {
            uc.progressPercentage="";
//            uc.uploadUrl = data.uploadUrl;
            uc.uploadUrl = uc.getImageUrl(data, ".tn");
            uc.form.upload_id = data.upload_id;
            uc.parseDate(data.dateTaken);
            uc.message=data.message;
            //TODO: message ng-class depending on data.success
            if(data.description)
                uc.form.caption = data.description;

            if(uc.showDebug)
            {
                var dataLog = data;
                if(angular.isObject(data))
                    dataLog = angular.toJson(data, true);
                var fname = (file ? file.name : "(none)");
                uc.addLog('uploaded file: {0}, status: {1}, Response: {2}'.format(fname, status, dataLog));
            }

            if(uc.queued)
                uc.uploadNextFile();
        });
    };

    uc.getImageUrl = function (data, subdir)
    {
        return String.combine(fpConfig.upload.baseUrl, ProfileService.user.username, subdir, data.filename);
    };

    uc.resetLog = function (message)
    {
        return uc.log = "";
    };

    uc.addLog = function (message, append)
    {
        if(!uc.showDebug) return;
        if(!message) message = "";
        if(uc.logReverse)
            uc.log = message + "\n" + uc.log;
        else
            uc.log = uc.log + "\n" + message;
        return uc.log;
    };

    uc.uploadNextFile = function()
    {
        uc.index++;
        if(isEmpty(uc.files) || uc.index >= uc.files.length) return;
        var file = uc.files[uc.index];
        uc.addLog();
        uc.addLog("uploadNextFile {0}/{1}: {2} ".format(uc.index+1, uc.files.length, file.name));
        delete uc.form.upload_id;
        uc.uploadFile(file);
    };

    uc.parseDate = function(dt)
    {
        if(!dt) return; // uc.dateTaken = uc.form.image_date_taken = null;

        uc.dateTaken = dt.replace(/-/g, '/');
        uc.form.image_date_taken = new Date(uc.dateTaken);
        uc.selectMeal(uc.form.image_date_taken);
        return uc.form.image_date_taken;
    };

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
    };

    uc.getCourses = function()
    {
        var mealId = uc.form.meal; 
        if(!mealId) return [];
        return uc.meals.byName[mealId].courses || [];
    };

    //post details
    //update db record
    uc.saveUpload = function () 
    {
        //file is already uploaded: post only form data to upload api
        Upload.upload({ url: 'api/upload.php', fields: uc.form }).success(function (data, status, headers, config) 
        {
            uc.message=data.message;
            if(data.success)
                uc.returnToMain(500);

            if(!uc.showDebug) return;

            var dataLog = data;
            if(angular.isObject(data))
                dataLog = angular.toJson(data, true);
            uc.addLog(dataLog);
        });
    };

    uc.returnToMain = function(delay)
    {
        if(!delay)
            return $state.go('main');

        $timeout(function() { $state.go('main'); }, delay);
    };

    //if upload canceled: delete details
    //delete db record and uploaded file
    uc.cancelUpload = function() 
    {
        uc.returnToMain();
    };

    uc.init();
}]);