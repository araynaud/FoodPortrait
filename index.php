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

<link rel="stylesheet" href="../bootstrap/css/bootstrap.css">
<link rel="stylesheet" href="style/signin.css">
<link rel="stylesheet" href="style/objectForm.css">
<link rel="stylesheet" href="style/sticky-footer.css">

<link rel="icon" href="images/FoodPortrait128.png">
<link rel="icon" sizes="192x192" href="images/FoodPortrait192.png">
<link rel="icon" sizes="128x128" href="images/FoodPortrait128.png">
<link rel="apple-touch-icon" sizes="128x128" href="images/FoodPortrait128.png">
<link rel="apple-touch-icon-precomposed" sizes="128x128" href="images/FoodPortrait128.png">

<script type="text/javascript" src="js/ng14/angular.min.js"></script>
<script type="text/javascript" src="js/ng14/angular-resource.min.js"></script>
<script type="text/javascript" src="js/ng14/angular-sanitize.min.js"></script>
<script type="text/javascript" src="js/ng14/angular-animate.min.js"></script>
<script type="text/javascript" src="js/ng14/angular-ui-router.min.js"></script>

<script type="text/javascript" src="js/md5.min.js"></script>
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="../bootstrap/js/bootstrap.min.js"></script>

<script type="text/javascript" src="/mt/js/mt.extensions.js"></script>
<!--script type="text/javascript" src="/mt/js/mt.extensions.jquery.js"></script>
<script type="text/javascript" src="/mt/js/mt.user.js"></script>
<script type="text/javascript" src="/mt/js/mt.mediafile.js"></script>
<script type="text/javascript" src="/mt/js/mt.album.js"></script>
<script type="text/javascript" src="/mt/js/mt.transition.js"></script>
<script type="text/javascript" src="/mt/js/mt.slideshow.js"></script>
<script type="text/javascript" src="/mt/js/mt.html5player.js"></script-->

<script type="text/javascript" src="js/fp.app.js"></script>
<script type="text/javascript" src="js/fp.services.js"></script>
<script type="text/javascript" src="js/fp.controllers.js"></script>
<script type="text/javascript" src="js/objectForm/objectForm.js"></script>
<script type="text/javascript" src="js/objectForm/question.js"></script>

<script type="text/javascript">
<?php echoJsVar("fpConfig"); echoJsVar("fpUser"); ?>
</script>

</head>
<body class="container-fluid" ng-controller="LayoutController as lc">

    <!-- Static navbar -->
    <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container">
          <a class="navbar-brand textOutline" ng-class="{active: lc.stateIs('home')}" style="font-size: 34px; color: #F44;" href="#/">
            FOOD
            <div style="font-size: 19px; color: #FE0;">PORTRAIT</div>
          </a>

        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
        </div>

        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
            <li ng-show="lc.loggedIn()" ng-class="{active: lc.stateIs('profile')}"><a href="#/profile">Profile</a></li>
            <li ng-class="{active: lc.stateIs('about')}"><a href="#/about">About</a></li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li ng-hide="lc.loggedIn()" ng-class="{active: lc.stateIs('signin')}"><a href="#/signin">Sign in</span></a></li>
            <li ng-hide="lc.loggedIn()" ng-class="{active: lc.stateIs('signup')}"><a href="#/signup">Register</a></li>
            <li ng-show="lc.loggedIn()"><a href="#/profile">{{lc.userFullName()}}</a></li>
            <li ng-show="lc.loggedIn()"><a href="#/login" ng-click="lc.logout()">Sign out</a></li>
          </ul>
        </div>
      </div>
    </nav>

  <a class="navbar-brand navbar-collapse collapse" href="#/" >
    <img class="logo" src="images/FoodPortrait192.png" alt="logo" ng-hide0="true"/>
  </a>

  <div id="main" class="container" ui-view>
  </div>
  <footer class="footer container">
    <div class="text-muted"> {{lc.currentState()}} {{lc.windowWidth}} x {{lc.windowHeight}} {{lc.userAgent}}</div>
  </footer>
</body>
</html>