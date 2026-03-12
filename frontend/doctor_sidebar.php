<div class="menu">
    <ul class="list">
        <li>
            <div class="user-info">
                <div class="image"><a href="javascript:void(0);" class="waves-effect waves-block"><img src="../assets/images/profile_av.jpg" alt="User"></a></div>
                <div class="detail">
                    <h4><?php echo $_SESSION['username'] ?? 'Doctor'; ?></h4>
                    <small>Doctor Account</small>                        
                </div>
            </div>
        </li>	
        <li class="header">MAIN</li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'Doctor-Dashboard.php') ? 'active open' : ''; ?>">
            <a href="Doctor-Dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a>
        </li>            
        <!-- More links will be added here step by step -->
    </ul>
</div>