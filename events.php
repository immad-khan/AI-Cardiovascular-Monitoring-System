<!doctype html>
<html class="no-js " lang="en">

<!-- Mirrored from hms.cognisun.net/oreo/html/light/events.php by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:23 GMT -->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

<title>Appointments - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<!-- Favicon-->
<link rel="stylesheet" href="./assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="./assets/plugins/fullcalendar/fullcalendar.min.css">
<!-- Custom Css -->
<link rel="stylesheet" href="./assets/css/main.css">
<link rel="stylesheet" href="./assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<!-- Page Loader -->
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="./assets/images/logo.svg" width="48" height="48" alt="Oreo"></div>
        <p>Please wait...</p>        
    </div>
</div>
<!-- Overlay For Sidebars -->
<div class="overlay"></div>
<!-- Top Bar -->
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>
<!-- Left Sidebar -->
<aside id="leftsidebar" class="sidebar">
    <ul class="nav nav-tabs">
         <li class="nav-item"><a class="nav-link active" data-toggle="tab" style="background: #00cfd1;
    color: white;" href="#dashboard"><img src="./assets/images/logo.svg" width="30" alt="CUST Digihealth"> &nbsp; CUST  DIGIHEALTH </a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane stretchRight active" id="dashboard">
            <div class="menu">
                <ul class="list">
                   <li>
                        <div class="user-info">
                            <div class="image"><a href="profile.html" class=" waves-effect waves-block"><img src="./assets/images/admin.png" alt="User"></a></div>
                            <div class="detail">
                                <h4>Super Administrator</h4>
                                <small>Waqas</small>                        
                            </div>
                        </div>
                    </li>	
                    <li class="header">MAIN</li>
                    <li><a href="index-2.html"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a></li>            
                    <li class="active open"><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-add"></i><span>Doctors</span> </a>
                        <ul class="ml-menu">
                            <li><a href="doctors.php">All Doctors</a></li>
                            <li><a href="add-doctor.php">Add Doctor</a></li>     
                            <li class="active"><a href="events.php">Doctor Schedule</a></li>
                        </ul>
                    </li>
                    <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-o"></i><span>Patients</span> </a>
                        <ul class="ml-menu">
                            <li><a href="patients.php">All Patients</a></li>
                            <li><a href="add-patient.php">Add Patient</a></li>
                        </ul>
                    </li>
					 <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-swap-alt"></i><span>ECG Devices</span> </a>
                        <ul class="ml-menu">
                            <li><a href="devices.php">All Devices</a></li>
                            <li><a href="add-device.php">Add Device</a></li>         
                        </ul>
                    </li>
                    <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-label-alt"></i><span>Departments</span> </a>
                        <ul class="ml-menu">
                            <li><a href="add-departments.html">Add</a></li>
                            <li><a href="all-Departments.html">All Departments</a></li>
                            <li><a href="javascript:void(0);">Cardiology</a></li>
                            <li><a href="javascript:void(0);">Pulmonology</a></li>
                            <li><a href="javascript:void(0);">Gynecology</a></li>
                            <li><a href="javascript:void(0);">Neurology</a></li>
                            <li><a href="javascript:void(0);">Urology</a></li>
                            <li><a href="javascript:void(0);">Gastrology</a></li>
                            <li><a href="javascript:void(0);">Pediatrician</a></li>
                            <li><a href="javascript:void(0);">Laboratory</a></li>
                        </ul>
                    </li>
                    
                </ul>
            </div>
        </div>
        
    </div>    
</aside>
<!-- Right Sidebar -->

<?php include("rightsidebar.php") ?>
<!-- Chat-launcher -->

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
<script src="./assets/bundles/libscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js --> 
<script src="./assets/bundles/vendorscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js --> 

<script src="./assets/bundles/fullcalendarscripts.bundle.js"></script><!--/ calender javascripts --> 

<script src="./assets/bundles/mainscripts.bundle.js"></script><!-- Custom Js --> 
<script src="./assets/js/pages/calendar/calendar.js"></script>
</body>

<!-- Mirrored from hms.cognisun.net/oreo/html/light/events.php by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:26 GMT -->
</html>