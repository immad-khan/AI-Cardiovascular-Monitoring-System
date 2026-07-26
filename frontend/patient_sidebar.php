<div class="menu">
    <ul class="list">
        <li>
            <div class="user-info">
                <div class="image">
                    <?php
                    $patient_avatar = '../assets/images/profile_av.jpg';
                    if (isset($conn) && isset($_SESSION['email'])) {
                        try {
                            $avatar_stmt = $conn->prepare('SELECT COALESCE(profile_picture, \'\') as profile_picture FROM patients WHERE email = ?');
                            $avatar_stmt->execute([$_SESSION['email']]);
                            $avatar_row = $avatar_stmt->fetch(PDO::FETCH_ASSOC);
                            if ($avatar_row && !empty($avatar_row['profile_picture'])) {
                                $patient_avatar = htmlspecialchars($avatar_row['profile_picture']);
                            }
                        } catch (Exception $e) {}
                    }
                    ?>
                    <a href="Patient-MyProfile.php" class="waves-effect waves-block"><img src="<?php echo $patient_avatar; ?>" alt="User"></a>
                </div>
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
            <a href="Patient-Profile.php?patientId=<?php echo $_SESSION['username'] ?? ''; ?>"><i class="zmdi zmdi-heart-pulse"></i><span>My Health Profile</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'Patient-AI-Assistant.php') ? 'active open' : ''; ?>">
            <a href="Patient-AI-Assistant.php"><i class="zmdi zmdi-robot"></i><span>AI Assistant</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'Patient-MyProfile.php') ? 'active open' : ''; ?>">
            <a href="Patient-MyProfile.php"><i class="zmdi zmdi-account-circle"></i><span>My Profile</span></a>
        </li>
    </ul>
</div>
