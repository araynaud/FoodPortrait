<?php
//Main page. Initialize variables used in HTML
require_once("include/includes.php");
session_start(); 
//get recipe id and parameters from query string, 
//site title
$fpUser = arrayGet($_SESSION, "fp_user");
$title = getConfig("defaultTitle");
//default meta tags
$meta = array();
$meta["og:title"] = $title;
$meta["og:site_name"] = $title; //get root dir title	
$meta["description"] = $meta["og:description"] = getConfig("description");
$meta["og:url"] = currentUrlDir(); 

?>
<!doctype html>
<html ng-app="app">
<head>
<title><?php echo $title?></title>

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, target-densitydpi=device-dpi" />
<meta name="mobile-web-app-capable" content="yes" />
<?php echo metaTagArray($meta); ?>

<link rel="stylesheet" href="../bootstrap/css/bootstrap.css"/>
<link rel="stylesheet" href="style/common.css"/>
<link rel="stylesheet" href="style/sidebar.css"/>
<link rel="stylesheet" href="style/signin.css"/>
<link rel="stylesheet" href="style/sticky-footer.css"/>
<link rel="stylesheet" href="style/fileUpload.css"/>
<link rel="stylesheet" href="directives/objectForm.css"/>
<link rel="stylesheet" href="directives/imageGrid.css"/>
<link rel="stylesheet" href="directives/spinner.css"/>

<link rel="icon" href="images/icon128.png"/>
<link rel="icon" sizes="192x192" href="images/icon192.png"/>
<link rel="icon" sizes="128x128" href="images/icon128.png"/>
<link rel="apple-touch-icon" sizes="128x128" href="images/icon128.png"/>
<link rel="apple-touch-icon-precomposed" sizes="128x128" href="images/icon128.png"/>

<script type="text/javascript" src="js/lib/jquery.min.js"></script>
<script type="text/javascript" src="../bootstrap/js/bootstrap.min.js"></script>

<script type="text/javascript" src="js/ng14/angular.js"></script>
<script type="text/javascript" src="js/ng14/angular-resource.min.js"></script>
<script type="text/javascript" src="js/ng14/angular-sanitize.min.js"></script>
<script type="text/javascript" src="js/ng14/angular-animate.min.js"></script>
<script type="text/javascript" src="js/ng14/angular-ui-router.min.js"></script>

<script type="text/javascript" src="js/lib/ui-bootstrap-tpls-0.14.3.min.js"></script>
<script type="text/javascript" src="js/lib/ng-file-upload.js"></script>
<script type="text/javascript" src="js/lib/md5.min.js"></script>
<script type="text/javascript" src="/mt/js/mt.extensions.js"></script>

<script type="text/javascript" src="js/fp.app.js"></script>
<script type="text/javascript" src="js/fp.profile.service.js"></script>
<script type="text/javascript" src="js/fp.query.service.js"></script>

<script type="text/javascript" src="js/fp.layout.controller.js"></script>
<script type="text/javascript" src="js/fp.login.controller.js"></script>
<script type="text/javascript" src="js/fp.profile.controller.js"></script>
<script type="text/javascript" src="js/fp.main.controller.js"></script>
<script type="text/javascript" src="js/fp.fileupload.controller.js"></script>

<script type="text/javascript" src="directives/objectForm.js"></script>
<script type="text/javascript" src="directives/spinner.js"></script>
<script type="text/javascript" src="directives/popover.js"></script>
<script type="text/javascript" src="directives/imageGrid.js"></script>
<script type="text/javascript" src="directives/imageModal.js"></script>

<script type="text/javascript">
<?php echoJsVar("fpConfig"); echoJsVar("fpUser"); ?>
</script>

</head>
<body ng-controller="LayoutController as lc" ng-class="lc.bodyClasses()">

  <!-- Static navbar -->
  <nav class="navbar navbar-default navbar-fixed-top">
    <div class="container">
        <a class="navbar-brand textOutline" ng-class="{active: lc.stateIs('main')}" href="#/main">
          <img src="images/logo40.png" alt="FOOD PORTRAIT"/>
        </a>

      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".mobile #navbar" aria-expanded="false" aria-controls="navbar">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
      </div>

      <div id="navbar" class="navbar-collapse collapse">
        <ul class="nav navbar-nav">
          <li ng-class="{active: lc.stateIs('about')}" data-toggle="collapse" data-target=".mobile #navbar"><a href="#/about">About</a></li>
          <li ng-show="lc.loggedIn()" ng-class="{active: lc.stateIs('profile')}" data-toggle="collapse" data-target=".mobile #navbar"><a href="#/profile">Profile</a></li>
          <li ng-show="lc.loggedIn()" ng-class="{active: lc.stateIs('upload')}"  data-toggle="collapse" data-target=".mobile #navbar"><a href="#/upload/">Upload</a></li>
        </ul>
        <ul class="nav navbar-nav navbar-right">
          <li ng-hide="lc.loggedIn()" ng-class="{active: lc.stateIs('signin')}" data-toggle="collapse" data-target=".mobile #navbar"><a href="#/signin">Log in</a></li>
          <li ng-hide="lc.loggedIn()" ng-class="{active: lc.stateIs('signup')}" data-toggle="collapse" data-target=".mobile #navbar"><a href="#/signup">Sign up</a></li>
          <li ng-show="lc.loggedIn()" ng-class="{active: lc.stateIs('user')}"   data-toggle="collapse" data-target=".mobile #navbar"><a href="#/main">{{lc.userFullName()}}</a></li>
          <li ng-show="lc.loggedIn()" data-toggle="collapse" data-target=".mobile #navbar"><a href="#/login" ng-click="lc.logout()">Sign out</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <a class="navbar-brand navbar-collapse collapse" href="#/">
    <img class="logo" src="images/FoodPortrait192.png" alt="logo" ng-hide="true || lc.stateIs('home')" />
  </a>

  <div id="main" ui-view></div>
  
  <footer class="footer container nowrap" ng-if="lc.showDebug">
    <div class="text-muted"> {{lc.currentState()}} {{lc.windowWidth}} x {{lc.windowHeight}} {{lc.userAgent}}</div>
  </footer>
</body>
</html>