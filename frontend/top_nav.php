<?php
include_once(__DIR__ . "/../backend/alert_logic.php");
$active_alerts = getActiveAlerts($conn);
$alert_count = count($active_alerts);
?>
<ul class="nav navbar-nav navbar-left">
		
        <li><a href="javascript:void(0);" class="ls-toggle-btn toggle-sidebar" data-close="true"><i class="zmdi zmdi-swap"></i></a></li>
        
        <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle notification-box" data-toggle="dropdown" role="button"><i class="zmdi zmdi-notifications"></i>
            <?php if ($alert_count > 0) { ?>
                <div class="notify"><span class="heartbit"></span><span class="point"></span></div>
            <?php } ?>
            </a>
            <ul class="dropdown-menu pullDown">
                <li class="header">CRITICAL ALERTS</li>
                <li class="body">
                    <ul class="menu list-unstyled">
                        <?php if ($alert_count > 0) { 
                            foreach ($active_alerts as $alert) { ?>
                            <li>
                                <a href="Patient-Profile.php?patientId=<?php echo $alert['patient_id']; ?>">
                                    <div class="media">
                                        <div class="media-body">
                                            <span class="name">Patient: <?php echo $alert['patient_id']; ?> <span class="time"><?php echo date('H:i', strtotime($alert['timestamp'])); ?></span></span>
                                            <span class="message text-danger font-weight-bold"><?php echo $alert['message']; ?></span>                                        
                                        </div>
                                    </div>
                                </a>
                            </li>
                        <?php } 
                        } else { ?>
                            <li><a href="javascript:void(0);"><div class="media-body text-center p-t-10">No active alerts</div></a></li>
                        <?php } ?>
                    </ul>
                </li>
                <li class="footer"> <a href="javascript:void(0);">View All History</a> </li>
            </ul>
        </li>
        
            
        <li class="float-right">
            <a href="logout.php" class="mega-menu" data-close="true"><i class="zmdi zmdi-power"></i></a>
            <a href="javascript:void(0);" class="js-right-sidebar" data-close="true"><i class="zmdi zmdi-settings zmdi-hc-spin"></i></a>
        </li>
        <li class="float-right">
			<a href="#" class="mega-menu" data-close="true"> <?php echo strtoupper($_SESSION['username']) ." (".strtoupper($_SESSION['user_type']).")" ?></a>
        </li>
    </ul>
