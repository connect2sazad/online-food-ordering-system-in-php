<?php

require '../vendor/autoload.php';

include(__DIR__ . '/../connection/connect.php');

use Aws\S3\S3Client;

$config = parse_ini_file(__DIR__ . '/../connection/db.config');

if ($config === false) {
    die("Unable to load db.config");
}

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

if(isset($_POST['submit']))
{
    if(empty($_POST['d_name'])||empty($_POST['about'])||$_POST['price']==''||$_POST['res_name']=='')
    {
        $error = '<div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong>All fields Must be Fillup!</strong>
        </div>';
    }
    else
    {
        $fname = $_FILES['file']['name'];
        $temp  = $_FILES['file']['tmp_name'];
        $fsize = $_FILES['file']['size'];

        $extension = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
        $fnew = uniqid().'.'.$extension;

        if($extension == 'jpg' || $extension == 'png' || $extension == 'gif')
        {
            if($fsize >= 1000000)
            {
                $error = '<div class="alert alert-danger">Max Image Size is 1MB!</div>';
            }
            else
            {
                try {

                    // ✅ S3 UPLOAD
                    $s3->putObject([
                        'Bucket'      => $bucket,
                        'Key'         => 'dishes/'.$fnew,
                        'SourceFile'  => $temp,
                        'ACL'         => 'public-read',
                        'ContentType' => mime_content_type($temp)
                    ]);

                    // save DB
                    $sql = "INSERT INTO dishes(rs_id,title,slogan,price,img)
                    VALUES(
                        '".$_POST['res_name']."',
                        '".$_POST['d_name']."',
                        '".$_POST['about']."',
                        '".$_POST['price']."',
                        '".$fnew."'
                    )";

                    mysqli_query($db,$sql);

                    $success = '<div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Success!</strong> New Dish Added Successfully.
                    </div>';

                } catch(Exception $e) {
                    $error = '<div class="alert alert-danger">'.$e->getMessage().'</div>';
                }
            }
        }
        elseif($extension == '')
        {
            $error = '<div class="alert alert-danger">Select image</div>';
        }
        else
        {
            $error = '<div class="alert alert-danger">Invalid extension! Only jpg, png, gif allowed.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Menu</title>

    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body class="fix-header">

<div id="main-wrapper">

    <!-- HEADER (UNCHANGED) -->
    <div class="header">
        <nav class="navbar top-navbar navbar-expand-md navbar-light">
            <div class="navbar-header">
                <a class="navbar-brand" href="index.html">
                    <b><img src="images/logo.png" alt=""></b>
                    <span><img src="images/logo-text.png" alt=""></span>
                </a>
            </div>
        </nav>
    </div>

    <!-- SIDEBAR (UNCHANGED) -->
    <div class="left-sidebar">
        <div class="scroll-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="add_restraunt.php">Add Restaurant</a></li>
                    <li><a href="add_menu.php">Add Menu</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- PAGE CONTENT -->
    <div class="page-wrapper">

        <div class="container-fluid">

            <?php echo $error; echo $success; ?>

            <div class="card">

                <div class="card-header">
                    <h4>Add Menu to Restaurant</h4>
                </div>

                <div class="card-body">

                    <form method="post" enctype="multipart/form-data">

                        <input type="text" name="d_name" class="form-control mb-2" placeholder="Dish Name">
                        <input type="text" name="about" class="form-control mb-2" placeholder="About">
                        <input type="text" name="price" class="form-control mb-2" placeholder="Price">

                        <input type="file" name="file" class="form-control mb-2">

                        <select name="res_name" class="form-control mb-3">
                            <option value="">Select Restaurant</option>
                            <?php
                            $res = mysqli_query($db,"select * from restaurant");
                            while($row=mysqli_fetch_array($res)){
                                echo '<option value="'.$row['rs_id'].'">'.$row['title'].'</option>';
                            }
                            ?>
                        </select>

                        <button type="submit" name="submit" class="btn btn-success">
                            Save
                        </button>

                    </form>

                </div>
            </div>

        </div>
    </div>

</div>

</body>
</html>