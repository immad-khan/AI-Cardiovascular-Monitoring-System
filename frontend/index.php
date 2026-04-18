<?php
session_start();
include("../config/DB_Config.php");

// If already logged in, redirect to respective dashboard
if (isset($_SESSION["user_type"])) {
    if ($_SESSION["user_type"] === "admin") {
        header("Location: dashboard.php");
        exit();
    } elseif ($_SESSION["user_type"] === "doctor") {
        header("Location: Doctor-Dashboard.php");
        exit();
    } elseif ($_SESSION["user_type"] === "patient") {
        header("Location: Patient-Dashboard.php");
        exit();
    }
}

// Handle Login Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = htmlspecialchars(trim($_POST['username']));
    $pass = htmlspecialchars(trim($_POST['password']));

    if (empty($user) || empty($pass)) {
        header("Location: index.php?status=All fields are required&type=error");
        exit();
    }

    try {
        $stmt = $conn->prepare('SELECT "userID", username, email, password, role, "isActive" FROM users WHERE username = ?');
        $stmt->execute([$user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if (password_verify($pass, $row['password'])) {
                if ($row['isActive'] == false) {
                    header("Location: index.php?status=Account is not active&type=error");
                    exit();
                }

                $_SESSION['user_id'] = $row['userID'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_type'] = $row['role'];

                if ($row['role'] === 'admin') {
                    header("Location: dashboard.php");
                } elseif ($row['role'] === 'doctor') {
                    header("Location: Doctor-Dashboard.php");
                } elseif ($row['role'] === 'patient') {
                    header("Location: Patient-Dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            }
            header("Location: index.php?status=Invalid Password&type=error");
            exit();
        }
        header("Location: index.php?status=User Not Found&type=error");
        exit();
    } catch (PDOException $e) {
        header("Location: index.php?status=Database Error&type=error");
        exit();
    }
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

    <title>CUST-Digihealth - Sign In</title>
    <!-- Favicon-->
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <!-- Custom Css -->
    <link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/authentication.css">
    <link rel="stylesheet" href="../assets/css/color_skins.css">
</head>

<body class="theme-cyan authentication sidebar-collapse">
<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top navbar-transparent">
    <div class="container">        
        <div class="navbar-translate n_logo">
            <a class="navbar-brand" href="javascript:void(0);" title="" target="_blank">CUST Digihealth</a>
            <button class="navbar-toggler" type="button">
                <span class="navbar-toggler-bar bar1"></span>
                <span class="navbar-toggler-bar bar2"></span>
                <span class="navbar-toggler-bar bar3"></span>
            </button>
        </div>
        <div class="navbar-collapse">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link btn btn-white btn-round" href="sign-up.php">SIGN UP</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- End Navbar -->
<div class="page-header">
    <div class="page-header-image" style="background-image:url(../assets/images/login.jpg)"></div>
    <div class="container">
        <div class="col-md-12 content-center">
            <div class="card-plain">
                <form class="form" method="post" action="index.php">
                    <div class="header">
                        <div class="logo-container">
                            <img src="../assets/images/logo.svg" alt="">
                        </div>
                        <h5>Sign In</h5>
                        <p class="text-muted">Personalized Portal for Admin, Doctor & Patients</p>
                        <?php if (isset($_GET['status'])): ?>
                            <div class="alert alert-<?php echo (isset($_GET['type']) && $_GET['type'] == 'error') ? 'danger' : 'success'; ?>" style="margin-top: 10px;">
                                <?php echo htmlspecialchars($_GET['status']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="content">                                                
                        <div class="input-group">
                            <input type="text" class="form-control" name="username" required placeholder="User Name">
                            <span class="input-group-addon">
                                <i class="zmdi zmdi-account-circle"></i>
                            </span>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" required placeholder="Password" class="form-control" />
                            <span class="input-group-addon">
                                <i class="zmdi zmdi-lock"></i>
                            </span>
                        </div>
                        <div class="m-t-20 text-center">
                            <small class="text-white-50">Authorized access only for: <br>
                            <span class="badge badge-primary">Admins</span> 
                            <span class="badge badge-info">Doctors</span> 
                            <span class="badge badge-success">Patients</span></small>
                        </div>
                    </div>
                    <div class="footer text-center">
                        <button type="submit" class="btn btn-primary btn-round btn-lg btn-block waves-effect waves-light">SIGN IN</button>
                        <h5><a class="link" href="sign-up.php">Don't have an account? Sign Up</a></h5>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <footer class="footer">
        <div class="container">
            <div class="copyright">
                <span>Capital University of Science and Technology, Islamabad</span>
                &copy; <script>document.write(new Date().getFullYear())</script>
            </div>
        </div>
    </footer>
</div>

<!-- Jquery Core Js -->
<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/mainscripts.bundle.js"></script>
</body>
</html>
