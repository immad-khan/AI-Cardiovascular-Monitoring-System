<?php
// Fetch pending task count for this doctor
$pending_count = 0;
try {
    $t_stmt = $conn->prepare("SELECT COUNT(*) FROM doctor_tasks WHERE \"doctorID\" = ? AND status = 'Pending'");
    $t_stmt->execute([$_SESSION['user_id']]);
    $pending_count = (int)$t_stmt->fetchColumn();
} catch(PDOException $e) {}
?>
<div class="menu">
    <ul class="list">
        <li>
            <div class="user-info">
                <div class="image">
                    <?php
                    $doc_avatar = '../assets/images/profile_av.jpg';
                    if (isset($conn) && isset($_SESSION['user_id'])) {
                        try {
                            $avatar_stmt = $conn->prepare('SELECT COALESCE(profile_picture, \'\') as profile_picture FROM "doctorProfile" WHERE "userID" = ?');
                            $avatar_stmt->execute([$_SESSION['user_id']]);
                            $avatar_row = $avatar_stmt->fetch(PDO::FETCH_ASSOC);
                            if ($avatar_row && !empty($avatar_row['profile_picture'])) {
                                $doc_avatar = htmlspecialchars($avatar_row['profile_picture']);
                            }
                        } catch (Exception $e) {}
                    }
                    ?>
                    <a href="javascript:void(0);" class="waves-effect waves-block"><img src="<?php echo $doc_avatar; ?>" alt="User"></a>
                </div>
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
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'patients.php') ? 'active open' : ''; ?>">
            <a href="patients.php"><i class="zmdi zmdi-account-o"></i><span>My Patients</span></a>
        </li>
        <li class="header">CLINICAL</li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'Doctor-Dashboard.php') ? 'active open' : ''; ?>">
            <a href="Doctor-Dashboard.php#pending-tasks">
                <i class="zmdi zmdi-assignment"></i>
                <span>Pending Tasks</span>
                <?php if ($pending_count > 0): ?>
                    <span class="badge badge-danger ml-2" style="font-size:11px;"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'device-monitor.php') ? 'active open' : ''; ?>">
            <a href="device-monitor.php">
                <i class="zmdi zmdi-cast-connected"></i>
                <span>IoT Device Monitor</span>
            </a>
        </li>
    </ul>
</div>