<?php
require_once("include/includes.php");
session_start(); 
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
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta name="mobile-web-app-capable" content="yes" />
<?php echo metaTagArray($meta); ?>

<link rel="icon" href="images/icon128.png"/>
<link rel="icon" sizes="192x192" href="images/icon192.png"/>
<link rel="icon" sizes="128x128" href="images/icon128.png"/>
<link rel="apple-touch-icon" sizes="128x128" href="images/icon128.png"/>
<link rel="apple-touch-icon-precomposed" sizes="128x128" href="images/icon128.png"/>

<?php addCssFromConfig("lib.bootstrap"); 
      addAllCss("style");
      addAllCss("directives"); 
      addScriptFromConfig("lib", "jquery.min.js");
      addScriptFromConfig("lib.bootstrap");
      addScriptFromConfig("lib.angular"); 
      addScriptFromConfig("lib"); 
      addScriptFromConfig("MediaThingy", "mt.extensions.js");
      addAllScripts("js");
      addAllScripts("directives"); 
?>

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