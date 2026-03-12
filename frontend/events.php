<?php
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "admin") {
    header("Location: index.php?status=access_denied&type=error");
    exit();
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Doctor Schedule - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/plugins/fullcalendar/fullcalendar.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="../assets/images/logo.svg" width="48" height="48" alt="Oreo"></div>
        <p>Please wait...</p>        
    </div>
</div>
<div class="overlay"></div>
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>

<aside id="leftsidebar" class="sidebar">
    <?php include("left_sidebar.php") ?>
</aside>

<?php include("rightsidebar.php") ?>

<section class="content page-calendar">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Calendar
                <small>Welcome to CUST Digihealth</small>
                </h2>
            </div>
            <div class="col-lg-5 col-md-7 col-sm-12">
                <ul class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="index-2.html"><i class="zmdi zmdi-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">App</a></li>
                    <li class="breadcrumb-item active">Calendar</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container-fluid">        
        <div class="row">
            <div class="col-md-12 col-lg-4 col-xl-4">
                <div class="card">
                    <div class="body">
                        <button type="button" class="btn btn-sm btn-round btn-success waves-effect" data-toggle="modal" data-target="#addevent">Add Events</button>
                        <button class="btn btn-simple btn-sm btn-primary btn-round d-xl-none m-t-0 float-right" data-toggle="collapse" data-target="#open-events" aria-expanded="false" aria-controls="collapseExample"><i class="zmdi zmdi-chevron-down"></i></button>                        
                    </div>
                </div>
                <div class="collapse-xs collapse-sm collapse" id="open-events">
                    <div class="card">
                        <div class="header">
                            <h2><strong>Repeating</strong> Event</h2>
                            <ul class="header-dropdown">
                                <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                    <ul class="dropdown-menu dropdown-menu-right slideUp">
                                        <li><a href="javascript:void(0);">Action</a></li>
                                        <li><a href="javascript:void(0);">Another action</a></li>
                                        <li><a href="javascript:void(0);">Something else</a></li>
                                    </ul>
                                </li>
                                <li class="remove">
                                    <a role="button" class="boxs-close"><i class="zmdi zmdi-close"></i></a>
                                </li>
                            </ul>                        
                        </div>
                        <div class="body">
                            <div class="event-name b-lightred row">
                                <div class="col-2 text-center">
                                    <h4>09<span>Dec</span><span>2024</span></h4>
                                </div>
                                <div class="col-10">
                                    <h6>Repeating Event</h6>
                                    <p>It is a long established fact that a reader will be distracted</p>
                                    <address><i class="zmdi zmdi-pin"></i> 123 6th St. Melbourne, FL 32904</address>
                                </div>
                            </div>
                            <div class="event-name b-greensea row">
                                <div class="col-2 text-center">
                                    <h4>16<span>Dec</span><span>2024</span></h4>
                                </div>
                                <div class="col-10">
                                    <h6>Repeating Event</h6>
                                    <p>It is a long established fact that a reader will be distracted</p>
                                    <address><i class="zmdi zmdi-pin"></i> 123 6th St. Melbourne, FL 32904</address>
                                </div>
                            </div>
                            <div class="event-name b-primary row">
                                <div class="col-2 text-center">
                                    <h4>11<span>Dec</span><span>2024</span></h4>
                                </div>
                                <div class="col-10">
                                    <h6>Conference</h6>
                                    <p>It is a long established fact that a reader will be distracted</p>
                                    <address><i class="zmdi zmdi-pin"></i> 123 6th St. Melbourne, FL 32904</address>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="header">
                            <h2><strong>Activity</strong></h2>
                            <ul class="header-dropdown">
                                <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                    <ul class="dropdown-menu dropdown-menu-right slideUp">
                                        <li><a href="javascript:void(0);">Action</a></li>
                                        <li><a href="javascript:void(0);">Another action</a></li>
                                        <li><a href="javascript:void(0);">Something else</a></li>
                                    </ul>
                                </li>
                                <li class="remove">
                                    <a role="button" class="boxs-close"><i class="zmdi zmdi-close"></i></a>
                                </li>
                            </ul>                        
                        </div>
                        <div class="body">                                                    
                            <div class="event-name b-primary row">
                                <div class="col-2 text-center">
                                    <h4>13<span>Dec</span><span>2024</span></h4>
                                </div>
                                <div class="col-10">
                                    <h6>Birthday</h6>
                                    <p>It is a long established fact that a reader will be distracted</p>
                                    <address><i class="zmdi zmdi-pin"></i> 123 6th St. Melbourne, FL 32904</address>
                                </div>
                            </div>
                        </div>
                    </div>                
                </div>
            </div>
            <div class="col-md-12 col-lg-8 col-xl-8">
                <div class="card">
                    <div class="body">
                        <button class="btn btn-primary btn-sm btn-round waves-effect" id="change-view-today">today</button>
                        <button class="btn btn-default btn-sm btn-simple btn-round waves-effect" id="change-view-day" >Day</button>
                        <button class="btn btn-default btn-sm btn-simple btn-round waves-effect" id="change-view-week">Week</button>
                        <button class="btn btn-default btn-sm btn-simple btn-round waves-effect" id="change-view-month">Month</button>                        
                    </div>
                </div>
                <div class="card">
                    <div class="body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>        
    </div>
</section>
<!-- Default Size -->
<div class="modal fade" id="addevent" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="title" id="defaultModalLabel">Add Event</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="form-line">
                        <input type="number" class="form-control" placeholder="Event Date">
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-line">
                        <input type="text" class="form-control" placeholder="Event Title">
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-line">
                        <textarea class="form-control no-resize" placeholder="Event Description..."></textarea>
                    </div>
                </div>       
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-round waves-effect">Add</button>
                <button type="button" class="btn btn-simple btn-round waves-effect" data-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>
<!-- Jquery Core Js --> 
<script src="../assets/bundles/libscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js --> 
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js --> 

<script src="../assets/bundles/fullcalendarscripts.bundle.js"></script><!--/ calender javascripts --> 

<script src="../assets/bundles/mainscripts.bundle.js"></script><!-- Custom Js --> 
<script src="../assets/js/pages/calendar/calendar.js"></script>
</body>

<!-- Mirrored from hms.cognisun.net/oreo/html/light/events.php by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:26 GMT -->
</html>