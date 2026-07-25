<div class="menu">
    <ul class="list">
        <li>
            <div class="user-info">
                <div class="image"><a href="javascript:void(0);" class="waves-effect waves-block"><img src="../assets/images/profile_av.jpg" alt="User"></a></div>
                <div class="detail">
                    <h4><?php echo $_SESSION['username'] ?? 'Patient'; ?></h4>
                    <small>Patient Account</small>                        
                </div>
            </div>
        </li>	
        <li class="header">MAIN</li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'Patient-Dashboard.php') ? 'active open' : ''; ?>">
            <a href="Patient-Dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'Patient-Profile.php') ? 'active open' : ''; ?>">
            <a href="Patient-Profile.php?patientId=<?php echo $_SESSION['username'] ?? ''; ?>"><i class="zmdi zmdi-favorite"></i><span>My Health Profile</span></a>
        </li>
    </ul>
</div>