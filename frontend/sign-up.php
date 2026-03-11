<?php
session_start();
include("../config/DB_Config.php");

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate the input
    $user = htmlspecialchars(trim($_POST['username']));
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $pass = htmlspecialchars(trim($_POST['password']));
    $accType = htmlspecialchars(trim($_POST['accType']));

    // Ensure no fields are empty
    if (empty($user) || empty($email) || empty($pass) || empty($accType)) {
        header("Location: sign-up.php?status=All fields are required");
        exit();
    } else {
        // Hash the password for security
        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

        try {
            // Prepare a PDO statement to insert the user data
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user, $email, $hashed_password, $accType]);

            // Auto-login: fetch the new user and set session
            $stmt2 = $conn->prepare('SELECT "userID", username, role FROM users WHERE username = ?');
            $stmt2->execute([$user]);
            $newUser = $stmt2->fetch(PDO::FETCH_ASSOC);

            if ($newUser) {
                $_SESSION['user_id']   = $newUser['userID'];
                $_SESSION['username']  = $newUser['username'];
                $_SESSION['user_type'] = $newUser['role'];

                // Role-based redirect after signup
                if ($newUser['role'] === 'patient') {
                    header("Location: Patient-Dashboard.php");
                } elseif ($newUser['role'] === 'doctor') {
                    header("Location: /complete-doctor-profile.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            }

            header("Location: sign-up.php?status=Registration+successful.+Please+sign+in&type=success");
            exit();

        } catch (PDOException $e) {
            $errMsg = $e->getMessage();
            if (strpos($errMsg, 'unique') !== false || strpos($errMsg, 'duplicate') !== false) {
                header("Location: sign-up.php?status=Username+or+email+already+exists&type=error");
            } else {
                header("Location: sign-up.php?status=Registration+failed.+Please+try+again&type=error");
            }
            exit();
        }
    }
}
?>
<!doctype html>
<html class="no-js " lang="en">

<!-- Mirrored from hms.cognisun.net/oreo/html/light/sign-up.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:31 GMT -->
<head>
<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

    <title>CUST-Digihealth - Sign up</title>
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
                    <a class="nav-link" title="Follow us on Linkedin" href="https://pk.linkedin.com/school/capital-university-of-science-and-technology/" target="_blank">
                        <i class="zmdi zmdi-linkedin"></i>
                        <p class="d-lg-none d-xl-none">Linkedin</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" title="Like us on Facebook" href="https://www.facebook.com/capitaluniversityislamabad" target="_blank">
                        <i class="zmdi zmdi-facebook"></i>
                        <p class="d-lg-none d-xl-none">Facebook</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" title="Follow us on Instagram" href="https://www.instagram.com/capital_university/?hl=en" target="_blank">                        
                        <i class="zmdi zmdi-instagram"></i>
                        <p class="d-lg-none d-xl-none">Instagram</p>
                    </a>
                </li>                
                <li class="nav-item">
                    <a class="nav-link btn btn-white btn-round" href="index.php">Sign in</a>
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
                <form class="form" method="post" action="#">
                    <div class="header">
                        <div class="logo-container">
                            <img src="../assets/images/logo.svg" alt="">
                        </div>
                        <h5>Sign Up</h5>
                        <span>Register a new membership</span>
                        <?php if (isset($_GET['status'])): ?>
                            <div class="alert alert-<?php echo (isset($_GET['type']) && $_GET['type'] == 'error') ? 'danger' : 'success'; ?>" style="margin-top: 10px;">
                                <?php echo htmlspecialchars($_GET['status']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="content">                                                
                        <div class="input-group">
                            <input type="text" class="form-control" name="username" required placeholder="Enter User Name">
                            <span class="input-group-addon">
                                <i class="zmdi zmdi-account-circle"></i>
                            </span>
                        </div>
                        <div class="input-group">
                            <input type="text" class="form-control" name="email" required placeholder="Enter Email">
                            <span class="input-group-addon">
                                <i class="zmdi zmdi-email"></i>
                            </span>
                        </div>
                        <div class="input-group">
                            <select type="text" class="form-control" name="accType" required>
								<option value="" style="color:black;">- Account Type -</option>
								<option value="doctor" style="color:black;">Doctor</option>
								<option value="patient" style="color:black;">Patient</option>
							</select>
                            <span class="input-group-addon">
                                <i class="zmdi zmdi-account"></i>
                            </span>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" required placeholder="Password" class="form-control" />
                            <span class="input-group-addon">
                                <i class="zmdi zmdi-lock"></i>
                            </span>
                        </div>                        
                    </div>
                    <div class="footer text-center">
                        <button type="submit" class="btn btn-primary btn-round btn-lg btn-block waves-effect waves-light">SIGN UP</button>
                        <h5><a class="link" href="index.php">You already have a membership?</a></h5>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <footer class="footer">
        <div class="container">
            <nav>
                <ul>
                    <li><a href="http://cust.edu.pk/contact/" target="_blank">Contact Us</a></li>
                    <li><a href="https://cust.edu.pk/about/" target="_blank">About Us</a></li>
                    <li><a href="https://cust.edu.pk/faqs/">FAQ</a></li>
                </ul>
            </nav>
            <div class="copyright">
                <span>Capital University of Science and Technology, Islamabad</span>
				
                &copy;
                <script>
                    document.write(new Date().getFullYear())
                </script>
            </div>
        </div>
    </footer>
</div>

<!-- Jquery Core Js -->
<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js --> 
<script>
   $(".navbar-toggler").on('click',function() {
    $("html").toggleClass("nav-open");
});
</script>
</body>

<!-- Mirrored from hms.cognisun.net/oreo/html/light/sign-up.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:31 GMT -->
</html>
