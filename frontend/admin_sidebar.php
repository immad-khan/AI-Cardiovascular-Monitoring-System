<div class="menu">
    <ul class="list">
        <li>
            <div class="user-info">
                <div class="image">
                    <?php
                    $admin_avatar = '../assets/images/admin.png';
                    if (isset($conn) && isset($_SESSION['user_id'])) {
                        try {
                            $avatar_stmt = $conn->prepare('SELECT COALESCE(profile_picture, \'\') as profile_picture FROM users WHERE "userID" = ?');
                            $avatar_stmt->execute([$_SESSION['user_id']]);
                            $avatar_row = $avatar_stmt->fetch(PDO::FETCH_ASSOC);
                            if ($avatar_row && !empty($avatar_row['profile_picture'])) {
                                $admin_avatar = htmlspecialchars($avatar_row['profile_picture']);
                            }
                        } catch (Exception $e) {}
                    }
                    ?>
                    <a href="Admin-Profile.php" class="waves-effect waves-block"><img src="<?php echo $admin_avatar; ?>" alt="User"></a>
                </div>
                <div class="detail">
                    <h4>Administrator</h4>
                    <small><?php echo $_SESSION['username'] ?? 'Admin'; ?></small>                        
                </div>
            </div>
        </li>	
        <li class="header">MAIN</li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active open' : ''; ?>">
            <a href="dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a>
        </li>            
        <li class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['doctors.php', 'add-doctor.php', 'edit-doctor.php'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-add"></i><span>Doctors</span> </a>
            <ul class="ml-menu">
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'doctors.php') ? 'active' : ''; ?>"><a href="doctors.php">All Doctors</a></li>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'add-doctor.php') ? 'active' : ''; ?>"><a href="add-doctor.php">Add Doctor</a></li>  
                <li><a href="events.php">Doctor Schedule</a></li>
            </ul>
        </li>
        <li class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['patients.php', 'add-patient.php'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-o"></i><span>Patients</span> </a>
            <ul class="ml-menu">
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'patients.php') ? 'active' : ''; ?>"><a href="patients.php">All Patients</a></li>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'add-patient.php') ? 'active' : ''; ?>"><a href="add-patient.php">Add Patient</a></li>   
            </ul>
        </li>
        <li class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['devices.php', 'add-device.php'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-swap-alt"></i><span>ECG Devices</span> </a>
            <ul class="ml-menu">
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'devices.php') ? 'active' : ''; ?>"><a href="devices.php">All Devices</a></li>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'add-device.php') ? 'active' : ''; ?>"><a href="add-device.php">Add Device</a></li>         
            </ul>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'all-Departments.php') ? 'active' : ''; ?>">
            <a href="all-Departments.php"><i class="zmdi zmdi-city"></i><span>Manage Departments</span></a>
        </li>
        <li>
            <a href="Clinical-Predictions.php"><i class="zmdi zmdi-chart"></i><span>AI Predictions</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'device-monitor.php') ? 'active open' : ''; ?>">
            <a href="device-monitor.php"><i class="zmdi zmdi-cast-connected"></i><span>IoT Device Monitor</span></a>
        </li>
    </ul>
</div>