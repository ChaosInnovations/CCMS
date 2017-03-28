<?php

// TODO: groupings
// TODO: templating functions

$TEMPLATES = [

// Always shown
"secure-navbar-start" =>
'
<nav class="navbar navbar-inverse fixed-top secure-nav">
<div class="container-fluid">
<div class="navbar-header">
<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#snavbar-collapse" aria-expanded="false">
<span class="sr-only">Toggle Navigation</span>
<span class="icon-bar"></span>
<span class="icon-bar"></span>
<span class="icon-bar"></span>
</button>
</div>
<div class="collapse navbar-collapse" id="snavbar-collapse">
<ul class="nav navbar-nav">',

// Always shown
"secure-navbar-button-home" =>
'
<li><a href="./" title="Home"><span class="glyphicon glyphicon-home"></span></a></li>',

// Shown if user can edit
"secure-navbar-button-edit" =>
'
<li><a href="#" title="Edit Page" onclick="showDialog(\'edit\');"><span class="glyphicon glyphicon-pencil"></span></a></li>',

// Shown if user can create pages
"secure-navbar-button-create" =>
'
<li><a href="#" title="New Page" onclick="createPage();"><span class="glyphicon glyphicon-plus"></span></a></li>',

// Shown if user can view or create secure pages
"secure-navbar-dropdown-secure-start" =>
'
<li class="dropdown">
<a href="" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Secure Pages <span class="caret"></span></a>
<ul class="dropdown-menu">',

"navbar-separator" =>
'
<li role="separator" class="divider"></li>',

// Shown if user can create secure pages
"secure-navbar-dropdown-secure-button-create" =>
'
<li><a href="#" title="New Secure Page" onclick="createSecurePage();"><span class="glyphicon glyphicon-plus"></span></a></li>',

"navbar-dropdown-end" =>
'
</ul></li>',

// Shown if user can manage modules
"secure-navbar-dropdown-modules-start" =>
'
<li class="dropdown">
<a href="" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Modules <span class="caret"></span></a>
<ul class="dropdown-menu">',

// Shown if user has any admin privileges
"secure-navbar-dropdown-admin-start" =>
'
<li class="dropdown">
<a href="" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Administration <span class="caret"></span></a>
<ul class="dropdown-menu">',

// Shown if user can manage pages
"secure-navbar-dropdown-admin-button-pages" =>
'
<li><a href="#" title="Manage Pages" onclick="showDialog(\'managepages\');">Page Manager</a></li>',

// Shown if user can manage site
"secure-navbar-dropdown-admin-button-site" =>
'
<li><a href="#" title="Manage Site" onclick="showDialog(\'managesite\');">Site Manager</a></li>',

// Shown if user can manage users
"secure-navbar-dropdown-admin-button-users" =>
'
<li><a href="#" title="Manage Users" onclick="showDialog(\'users\');">Users</a></li>',

// Always shown
"secure-navbar-dropdown-account-start" =>
'
<li class="dropdown">
<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Account <span class="caret"></span></a>
<ul class="dropdown-menu">',

// Always shown
"secure-navbar-dropdown-account-button-details" =>
'
<li><a href="#" title="Account Details" onclick="showDialog(\'account\');">Details</a></li>',

// Always shown
"secure-navbar-dropdown-account-button-logout" =>
'
<li><a href="#" title="Log Out" onclick="logout();"><span class="glyphicon glyphicon-log-out"></span></a></li>',

// Always shown
"secure-navbar-button-about" =>
'
<li><a href="#" title="About" onclick="showDialog(\'about\');">About</a></li>',

// Always shown
"secure-navbar-nav-end" =>
'
</ul>',

// Always shown
"secure-navbar-end" =>
'
</div></div></nav>'

];

?>