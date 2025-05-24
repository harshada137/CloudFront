
<?php 
require 'vendor/autoload.php'; 
  
use Aws\S3\S3Client; 
use Aws\Exception\AwsException; 
  
// Instantiate an Amazon S3 client. 
$s3Client = new S3Client([ 
    'version' => 'latest', 
    'region'  => 'us-east-1', 
    'credentials' => [ 
        'key'    => 'dcbfhksdljhghsdfvhAKIfdsAfdfTOEXfdGfRFAXGZUdfQTGU', // Add your access key here 
        'secret' => 'dsffkxMOxjF22+dfsdWbwRk8HdBdfsdfji4MDzPIwnyzkfdfXD2NiZdf9F' // Add your secret key here 
    ] 
]); 
  
// Check if the form was submitted 
if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    // Check if file was uploaded without errors 
    if (isset($_FILES["anyfile"]) && $_FILES["anyfile"]["error"] == 0) { 
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png"); 
        $filename = $_FILES["anyfile"]["name"]; 
        $filetype = $_FILES["anyfile"]["type"]; 
        $filesize = $_FILES["anyfile"]["size"]; 
  
        // Validate file extension 
        $ext = pathinfo($filename, PATHINFO_EXTENSION); 
        if (!array_key_exists($ext, $allowed)) { 
            die("Error: Please select a valid file format."); 
        } 
  
        // Validate file size - 10MB maximum 
        $maxsize = 10 * 1024 * 1024; 
        if ($filesize > $maxsize) { 
            die("Error: File size is larger than the allowed limit."); 
        } 
  
        // Validate type of the file 
        if (!in_array($filetype, $allowed)) { 
            die("Error: There was a problem with the file type."); 
        } 
  
        // Check whether file exists before uploading it 
        if (file_exists("uploads/" . $filename)) { 
            die($filename . " already exists."); 
        } 
  
        // Move uploaded file to uploads directory 
        if (!move_uploaded_file($_FILES["anyfile"]["tmp_name"], "uploads/" . $filename)) { 
            die("Error: File was not uploaded."); 
        } 
  
        // Upload file to S3 
        $bucket = 'navanava'; // Add your bucket name here 
        $file_Path = __DIR__ . '/uploads/' . $filename; 
        $key = basename($file_Path); 
  
        try { 
            $result = $s3Client->putObject([ 
                'Bucket' => $bucket, 
                'Key'    => $key, 
                'Body'   => fopen($file_Path, 'r'), 
                'ACL'    => 'public-read', // make file 'public' 
            ]); 
            $urls3 = $result->get('ObjectURL'); 
            $cfurl = str_replace("https://navanava.s3.ap-south-1.amazonaws.com", "https://d1g04a21wg9rz.cloudfront.net", $urls3); 
  
            // Database connection 
            $servername = "database-1.cx0iyacc8tzg.ap-south-1.rds.amazonaws.com"; 
            $username = "root"; 
            $password = "Pass1234"; 
            $dbname = "facebook"; 
  
            // Create connection 
            $conn = new mysqli($servername, $username, $password, $dbname); 
            if ($conn->connect_error) { 
                die("Connection failed: " . $conn->connect_error); 
            } 
  
            // Insert record into database 
            $name = $_POST["name"]; 
            $sql = "INSERT INTO posts (name, s3url, cfurl) VALUES ('$name', '$urls3', '$cfurl')"; 
            if ($conn->query($sql) === TRUE) { 
                echo "Image uploaded successfully. Image path is: $urls3 <br>"; 
                echo "CloudFront URL: $cfurl <br>"; 
                echo "Connected successfully to the database. <br>"; 
                echo "New record created successfully. <br>"; 
            } else { 
                echo "Error: " . $sql . "<br>" . $conn->error . "<br>"; 
            } 
  
            // Close database connection 
            $conn->close(); 
        } catch (AwsException $e) { 
            echo "Error: There was an error uploading the file to S3. <br>"; 
            echo $e->getMessage() . "<br>"; 
        } 
    } else { 
        echo "Error: There was a problem uploading your file. Please try again. <br>"; 
    } 
} 
?> 
 

