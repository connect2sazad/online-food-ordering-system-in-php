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
                    'ACL'    => 'public-read',
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
                    '" . $fnew . "'
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

<div id="main-wrapper">

    <div class="container-fluid">

        <?php echo $error; echo $success; ?>

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

</body>
</html>