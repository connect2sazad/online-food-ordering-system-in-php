<?php

require '../vendor/autoload.php';

include(__DIR__ . '/../connection/connect.php');

use Aws\S3\S3Client;

$config = parse_ini_file(__DIR__ . '/../connection/db.config'); // FIXED PATH

if ($config === false) {
    die("Unable to load db.config");
}

// Create S3 client (IAM role on EC2)
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => $config['s3_region']
]);

$bucket = $config['s3_bucket'];

include("../connection/connect.php");
error_reporting(0);
session_start();

$error = "";
$success = "";

if (isset($_POST['submit'])) {

    if (
        empty($_POST['c_name']) ||
        empty($_POST['res_name']) ||
        empty($_POST['email']) ||
        empty($_POST['phone']) ||
        empty($_POST['url']) ||
        empty($_POST['o_hr']) ||
        empty($_POST['c_hr']) ||
        empty($_POST['o_days']) ||
        empty($_POST['address'])
    ) {
        $error = '<div class="alert alert-danger">All fields must be filled!</div>';
    } else {

        $fname = $_FILES['file']['name'];
        $temp = $_FILES['file']['tmp_name'];
        $fsize = $_FILES['file']['size'];

        $extension = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'png', 'gif'];

        if (!in_array($extension, $allowed)) {
            $error = '<div class="alert alert-danger">Invalid extension! Only JPG, PNG, GIF allowed.</div>';
        } elseif ($fsize >= 1024 * 1024) {
            $error = '<div class="alert alert-danger">Max image size is 1MB!</div>';
        } else {

            // Generate unique file name
            $fnew = uniqid('res_', true) . '.' . $extension;

            try {

                // Upload to S3
                $result = $s3->putObject([
                    'Bucket' => $bucket,
                    'Key'    => 'restaurants/' . $fnew,
                    'SourceFile' => $temp,
                    'ContentType' => mime_content_type($temp)
                ]);

                $imageUrl = $result['ObjectURL'];

                // Save DB record (store S3 key or URL)
                $sql = "INSERT INTO restaurant 
                (c_id, title, email, phone, url, o_hr, c_hr, o_days, address, image)
                VALUES (
                    '" . $_POST['c_name'] . "',
                    '" . $_POST['res_name'] . "',
                    '" . $_POST['email'] . "',
                    '" . $_POST['phone'] . "',
                    '" . $_POST['url'] . "',
                    '" . $_POST['o_hr'] . "',
                    '" . $_POST['c_hr'] . "',
                    '" . $_POST['o_days'] . "',
                    '" . $_POST['address'] . "',
                    '" . $imageUrl . "'
                )";

                mysqli_query($db, $sql);

                $success = '<div class="alert alert-success">Restaurant added successfully!</div>';
            } catch (Exception $e) {
                $error = '<div class="alert alert-danger">S3 Upload failed: ' . $e->getMessage() . '</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Add Restaurant</title>

    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body class="fix-header">

    <!-- Preloader - style you can find in spinners.css -->
    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>
    <!-- Main wrapper  -->

    <div id="main-wrapper">

        <!-- header header  -->
        <div class="header">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <!-- Logo -->
                <div class="navbar-header">
                    <a class="navbar-brand" href="index.html">
                        <!-- Logo icon -->
                        <b><img src="images/logo.png" alt="homepage" class="dark-logo" /></b>
                        <!--End Logo icon -->
                        <!-- Logo text -->
                        <span><img src="images/logo-text.png" alt="homepage" class="dark-logo" /></span>
                    </a>
                </div>
                <!-- End Logo -->
                <div class="navbar-collapse">
                    <!-- toggle and nav items -->
                    <ul class="navbar-nav mr-auto mt-md-0">
                        <!-- This is  -->
                        <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up text-muted  " href="javascript:void(0)"><i class="mdi mdi-menu"></i></a> </li>
                        <li class="nav-item m-l-10"> <a class="nav-link sidebartoggler hidden-sm-down text-muted  " href="javascript:void(0)"><i class="ti-menu"></i></a> </li>


                    </ul>
                    <!-- User profile and search -->
                    <ul class="navbar-nav my-lg-0">

                        <!-- Search -->
                        <li class="nav-item hidden-sm-down search-box"> <a class="nav-link hidden-sm-down text-muted  " href="javascript:void(0)"><i class="ti-search"></i></a>
                            <form class="app-search">
                                <input type="text" class="form-control" placeholder="Search here"> <a class="srh-btn"><i class="ti-close"></i></a>
                            </form>
                        </li>
                        <!-- Comment -->
                        <li class="nav-item dropdown">

                            <div class="dropdown-menu dropdown-menu-right mailbox animated zoomIn">
                                <ul>
                                    <li>
                                        <div class="drop-title">Notifications</div>
                                    </li>

                                    <li>
                                        <a class="nav-link text-center" href="javascript:void(0);"> <strong>Check all notifications</strong> <i class="fa fa-angle-right"></i> </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <!-- End Comment -->

                        <!-- Profile -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted  " href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="images/users/5.jpg" alt="user" class="profile-pic" /></a>
                            <div class="dropdown-menu dropdown-menu-right animated zoomIn">
                                <ul class="dropdown-user">
                                    <li><a href="logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
        <!-- End header header -->
        <!-- Left Sidebar  -->
        <div class="left-sidebar">
            <!-- Sidebar scroll-->
            <div class="scroll-sidebar">
                <!-- Sidebar navigation-->
                <nav class="sidebar-nav">
                    <ul id="sidebarnav">
                        <li class="nav-devider"></li>
                        <li class="nav-label">Home</li>
                        <li> <a class="has-arrow  " href="#" aria-expanded="false"><i class="fa fa-tachometer"></i><span class="hide-menu">Dashboard</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="dashboard.php">Dashboard</a></li>

                            </ul>
                        </li>
                        <li class="nav-label">Log</li>
                        <li> <a class="has-arrow  " href="#" aria-expanded="false"> <span><i class="fa fa-user f-s-20 "></i></span><span class="hide-menu">Users</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="allusers.php">All Users</a></li>
                                <li><a href="add_users.php">Add Users</a></li>


                            </ul>
                        </li>
                        <li> <a class="has-arrow  " href="#" aria-expanded="false"><i class="fa fa-archive f-s-20 color-warning"></i><span class="hide-menu">Store</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="allrestraunt.php">All Stores</a></li>
                                <li><a href="add_category.php">Add Category</a></li>
                                <li><a href="add_restraunt.php">Add Restaurant</a></li>

                            </ul>
                        </li>
                        <li> <a class="has-arrow  " href="#" aria-expanded="false"><i class="fa fa-cutlery" aria-hidden="true"></i><span class="hide-menu">Menu</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="all_menu.php">All Menues</a></li>
                                <li><a href="add_menu.php">Add Menu</a></li>


                            </ul>
                        </li>
                        <li> <a class="has-arrow  " href="#" aria-expanded="false"><i class="fa fa-shopping-cart" aria-hidden="true"></i><span class="hide-menu">Orders</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="all_orders.php">All Orders</a></li>

                            </ul>
                        </li>

                    </ul>
                </nav>
                <!-- End Sidebar navigation -->
            </div>
            <!-- End Sidebar scroll-->
        </div>
        <!-- End Left Sidebar  -->
        <!-- Page wrapper  -->
        <div class="page-wrapper">
            <!-- Bread crumb -->
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h3 class="text-primary">Dashboard</h3>
                </div>

            </div>
            <!-- End Bread crumb -->
            <!-- Container fluid  -->
            <div class="container-fluid">

                <?php echo $error;
                echo $success; ?>

                <div class="card">
                    <div class="card-header">
                        <h4>Add Restaurant</h4>
                    </div>

                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">

                            <input type="text" name="res_name" placeholder="Restaurant Name" class="form-control mb-2">
                            <input type="text" name="email" placeholder="Email" class="form-control mb-2">
                            <input type="text" name="phone" placeholder="Phone" class="form-control mb-2">
                            <input type="text" name="url" placeholder="Website" class="form-control mb-2">

                            <select name="o_hr" class="form-control mb-2">
                                <option value="">Open Hour</option>
                                <option value="6am">6am</option>
                                <option value="7am">7am</option>
                                <option value="8am">8am</option>
                            </select>

                            <select name="c_hr" class="form-control mb-2">
                                <option value="">Close Hour</option>
                                <option value="3pm">3pm</option>
                                <option value="6pm">6pm</option>
                                <option value="9pm">9pm</option>
                            </select>

                            <select name="o_days" class="form-control mb-2">
                                <option value="">Open Days</option>
                                <option value="mon-fri">Mon-Fri</option>
                                <option value="mon-sat">Mon-Sat</option>
                                <option value="24x7">24x7</option>
                            </select>

                            <select name="c_name" class="form-control mb-2">
                                <?php
                                $res = mysqli_query($db, "SELECT * FROM res_category");
                                while ($row = mysqli_fetch_array($res)) {
                                    echo "<option value='{$row['c_id']}'>{$row['c_name']}</option>";
                                }
                                ?>
                            </select>

                            <textarea name="address" class="form-control mb-2" placeholder="Address"></textarea>

                            <input type="file" name="file" class="form-control mb-3">

                            <button type="submit" name="submit" class="btn btn-success">Save</button>

                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script src="js/lib/datatables/datatables.min.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>
    <script src="js/lib/datatables/cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
    <script src="js/lib/datatables/cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
    <script src="js/lib/datatables/cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>
    <script src="js/lib/datatables/datatables-init.js"></script>
</body>

</html>