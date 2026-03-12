<?php 
include("../config/DB_Config.php");
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?status=access_denied&type=".($_SESSION['user_type'] ?? 'none'));
    exit();
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Doctors - CUST Digihealth </title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="../assets/images/logo.svg" width="48" height="48" alt="CUST Digihealth"></div>
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

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-12">
                <h2>All Doctors <small>Welcome to CUST Digihealth</small></h2>
            </div>            
            <div class="col-lg-7 col-md-7 col-sm-12 text-right">
                <a href="add-doctor.php" class="btn btn-primary btn-round">Add Doctor</a>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <?php 
                try {
                    // Fetch both doctors WITH profiles and doctors WITHOUT profiles (orphaned)
                    $sql = "SELECT u.\"userID\", u.username, u.email, 
                                   COALESCE(p.full_name, u.username) as full_name, 
                                   COALESCE(p.specialization, 'General Practitioner') as specialization, 
                                   COALESCE(p.phone_number, 'N/A') as phone_number, 
                                   COALESCE(p.profile_picture, '../assets/images/sm/avatar1.jpg') as profile_picture, 
                                   COALESCE(p.description, 'No professional description provided yet.') as description, 
                                   p.website_url
                            FROM users u
                            LEFT JOIN \"doctorProfile\" p ON u.\"userID\" = p.\"userID\"
                            WHERE u.role = 'doctor' AND u.\"isActive\" = TRUE
                            ORDER BY p.full_name ASC NULLS LAST";
                    $stmt = $conn->query($sql);

                    $found = false;
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $found = true;
                        $color = "xl-parpl";
                        if($row['specialization'] == "Cardiologist") $color = "xl-blue";
                        echo '<div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="card ' . $color . ' member-card doctor">
                                <div class="body">
                                    <div class="member-thumb">
                                        <img src="' . htmlspecialchars($row['profile_picture']) . '" class="img-fluid" alt="profile" style="max-height: 200px; width: auto; border-radius: 50%;">                               
                                    </div>
                                    <div class="detail m-t-20">
                                        <h4 class="m-b-0">' . htmlspecialchars($row['full_name']) . '</h4>
                                        <p class="text-muted"><strong>' . htmlspecialchars($row['specialization']) . '</strong></p>
                                        <p class="text-muted">' . htmlspecialchars($row['phone_number']) . '</p>      
                                        <p class="text-muted" style="min-height:60px;">' . htmlspecialchars(substr($row['description'] ?? '', 0, 100)) . '...</p>                           
                                        <div class="m-t-20">
                                            <a href="doctor-profile.php?id=' . $row['userID'] . '" class="btn btn-primary btn-round btn-simple">View Profile</a>
                                            <a href="edit-doctor.php?id=' . $row['userID'] . '" class="btn btn-info btn-round btn-simple">Edit</a>
                                            <a href="delete-doctor.php?id=' . $row['userID'] . '" class="btn btn-danger btn-round btn-simple" onclick="return confirm(\'Are you sure you want to delete this doctor account?\')">Delete</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }

                    if (!$found) {
                        echo '<div class="col-12 text-center"><h3>No doctors found in the database.</h3></div>';
                    }
                } catch (PDOException $e) {
                    echo '<div class="col-12 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            ?>
        </div> 
    </div>
</section>
<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/vendorscripts.bundle.js"></script>
<script src="../assets/bundles/mainscripts.bundle.js"></script>
<script>
    // Hide loader
    $(window).on('load', function() {
        $('.page-loader-wrapper').fadeOut();
    });
</script>
</body>
</html>