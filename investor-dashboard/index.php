<?php
/**
 * Created by PhpStorm.
 * User: AlexBoey
 * Date: 4/24/2017
 * Time: 5:55 PM
 */

include 'includes/DB.php';
include 'helpers/Response.php';
include 'helpers/ConfirmationCode.php';
include "helpers/AfricasTalkingGateway.php";
include 'helpers/fpdf/fpdf.php';
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

date_default_timezone_set("Africa/Nairobi");
header("Access-Control-Allow-Origin: *");

$function = $_REQUEST['function'];

function formatPhoneNumber($phoneNumber)
{
    $phoneNumber = preg_replace('/[^\dxX]/', '', $phoneNumber);
    $phoneNumber = preg_replace('/^0/', '254', $phoneNumber);

    $phoneNumber = $phone = preg_replace('/\D+/', '', $phoneNumber);

    return $phoneNumber;
}
function sendSMS($phoneNumber, $message, $senderID)
{


    $curl = curl_init();
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://payme.nouveta.co.ke/api/sendsms.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"phoneNumber\"\r\n\r\n$phoneNumber\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"message\"\r\n\r\n$message\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"senderID\"\r\n\r\n$senderID\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
        CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache",
            "Postman-Token: f5486d10-3009-4007-87fd-b52d250142e0",
            "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

}
function sendEmail($replyTo,$to,$subject,$message){

    $headers = 'From: ZAKA' . "\r\n" .
        'Reply-To: '.$replyTo . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);
}
function generateTransaction($accountNumber, $amount, $TransactionType, $transactionCode, $description, $type)
{
    //Type 0 investor 1 Entrepreneur

    $sql = "INSERT INTO `transactions`(`accountNumber`, `amount`, `TransactionType`, `transactionCode`,`description`,`type`) 
                                VALUES ('$accountNumber','$amount','$TransactionType','$transactionCode','$description','$type')";

    $result = DB::instance()->executeSQL($sql);
    /* if($result){

         $response = new Response();
         $response->status = Response::STATUS_SUCCESS;
         $response->message = 'successful';
         $response->success = true;
         echo json_encode($response);

     }else{

         $response = new Response();
         $response->status = Response::STATUS_SUCCESS;
         $response->message = 'Failed';
         $response->success = false;
         echo json_encode($response);
     }*/

}
function code()
{
    $today = date("d");
    $rand = strtoupper(substr(uniqid(sha1(time())), 0, 4));
    $order_number_sale = $today . $rand;

    return $order_number_sale;
}
function accountBalance($accountNumber){
    $sqlDr = "SELECT SUM(amount) FROM `transactions` WHERE `TransactionType` ='Dr' AND `accountNumber` ='$accountNumber'";
    $resDr = DB::instance()->executeSQL($sqlDr)->fetch_array()[0];

   $sqlCr = "SELECT SUM(amount) FROM `transactions` WHERE `TransactionType` ='Cr'  AND `accountNumber` ='$accountNumber'";
    $resCr = DB::instance()->executeSQL($sqlCr)->fetch_array()[0];


    return $resCr-$resDr;

}
function getPost($entrepreneur_id){

    $sql ="SELECT * FROM `posts` WHERE `entrepreneur_id` ='$entrepreneur_id'";
    $result = DB::instance()->executeSQL($sql);

    while( $row = $result->fetch_assoc()){
        $new_array[] = array("CampaignPost"=>$row,"CampaignOffers"=>getOffer($row['id']),"CampaignTotalAmount"=>totalOfferAccepted($row['id']));
    }


    return $new_array;

}
function getOffer($post_id){

    $sql ="SELECT * FROM `offers` WHERE `post_id` ='$post_id'";
    $result = DB::instance()->executeSQL($sql);

    while( $row = $result->fetch_assoc()){
        $new_array[] =array("Offers"=>$row,"Negotiation"=>getNegotiation($row['id']));
    }

    return  $new_array;

}
function getNegotiation($offer_id){

    $sql ="SELECT * FROM `negotiation` WHERE `offer_id`='$offer_id'";
    $result = DB::instance()->executeSQL($sql);

    while( $row = $result->fetch_assoc()){
        $new_array[] =$row;
    }

    return  $new_array;

}
function pushPayments($Amount,$PhoneNumber,$AccountReference){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://payme.nouveta.co.ke/api/index.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"TransactionType\"\r\n\r\nCustomerPayBillOnline\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"PayBillNumber\"\r\n\r\n256666\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"Amount\"\r\n\r\n$Amount\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"PhoneNumber\"\r\n\r\n$PhoneNumber\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"AccountReference\"\r\n\r\n$AccountReference\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"TransactionDesc\"\r\n\r\nZAKA\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer UVZoQk9rcFhDcWxlR1RPSnJBa1RaRkZQSjdZdlVSRzdZTThHVldLUU1jZz06MTIzNDU6UTIydkozVll4RzFMWUV2MkViSDl5UVN3NFFRanZrNVJoVThQM0pXTXRIRT06MTk3LjI0OC4xNDkuNjI6MDQvMDAvMTcgMTIwMA==",
            "Cache-Control: no-cache",
            "Postman-Token: 496bc8ee-896e-302d-31c4-54d106910842",
            "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        //  echo "cURL Error #:" . $err;
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =$err;
        $response->success = false;
        echo json_encode($response);
    } else {
        // echo $response;
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Send";
        $response->success = true;
        echo json_encode($response);
    }
}
function totalOfferAccepted($post_id){
    $sql = "SELECT SUM(amount) FROM `offers` WHERE `status` ='1' AND `post_id` ='$post_id'";
    $res = DB::instance()->executeSQL($sql)->fetch_array()[0];

    return $res;
}


if($function=="registerEntrepreneur"){
    $name = $_REQUEST['name'];
    $phoneNumber = formatPhoneNumber($_REQUEST['phoneNumber']);
    $location = $_REQUEST['location'];
    $email = $_REQUEST['email'];
    $amount = $_REQUEST['amount'];
    $reason= $_REQUEST['reason'];
    $userName = $_REQUEST['userName'];
    $password = $_REQUEST['password'];
    $birthYeah = $_REQUEST['birthYear'];

    $sql ="INSERT INTO `entrepreneur`(`name`, `phoneNumber`, `location`, `email`, `password`, `birthYear`)
                              VALUES ('$name','$phoneNumber','$location','$email','$password','$birthYeah')";

    $result = DB::instance()->executeSQL($sql);
    if($result)
        $message ="Welcome to Zaka. Your account has been setup as Entrepreneur, Please login and continue";
        $message2="$name $phoneNumber $location $email $amount $reason ";

        sendSMS ($phoneNumber,$message,'NOUVETA');
        sendEmail($email,'info@zaka.co.ke',$subject,$message2);//To Zaka
        sendEmail('info@zaka.co.ke',$email,$subject,$message);//To Entrepreneur


        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Registration successful';
        $response->success = true;
        echo json_encode($response);

    }
if($function=="getEntrepreneur"){
    $keyword = $_REQUEST['keyword'];
    if(empty($keyword)) {
        $sql = "SELECT * FROM `entrepreneur`";
    }else{
        $sql ="SELECT * FROM `entrepreneur` WHERE  `phoneNumber` = '%$keyword%' ";
    }
    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows >0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = array("Entrepreneur"=>$row,"CampaignPost"=>getPost($row['id']));
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Entrepreneur" ;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Entrepreneur" ;
        $response->data = [];
        $response->success = false;
        echo json_encode($response);


    }

}
if($function=="registerInvestors"){
    $name = $_REQUEST['name'];
    $phoneNumber = formatPhoneNumber($_REQUEST['phoneNumber']);
    $location = $_REQUEST['location'];
    $email = $_REQUEST['email'];
    $userName = $_REQUEST['userName'];
    $password = $_REQUEST['password'];
    $birthYeah = $_REQUEST['birthYear'];

    $sql ="INSERT INTO `investors`(`name`, `phoneNumber`, `location`, `email`, `userName`, `password`, `birthYear`) 
                                VALUES ('$name','$phoneNumber','$location','$email','$userName','$password','$birthYeah')";

    $result = DB::instance()->executeSQL($sql);
    if($result){

        $message ="Welcome to Zaka. Your account has been setup as Investor, Please login and continue";

        sendSMS ($phoneNumber,$message,'NOUVETA');
        sendEmail('info@zaka.co.ke',$email,$subject,$message);


        //debit account with Registration fee
        $amount = '1000';
        $transactionCode = code();

        generateTransaction($phoneNumber,$amount,'Dr',$transactionCode,'Registration Fee','0');
        generateTransaction("ZakaAccount",$amount,'Cr',$transactionCode,'Registration Fee','0');
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Registration successful';
        $response->success = true;
        echo json_encode($response);

    }else{

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Failed to Register';
        $response->success = false;
        echo json_encode($response);
    }
}
if($function=="getInvestors"){
    $keyword = $_REQUEST['keyword'];
    if(empty($keyword))
        $sql ="SELECT * FROM `entrepreneur`";
    else   $sql ="SELECT * FROM `entrepreneur` WHERE `name` LIKE '%keyword%' OR `phoneNumber` LIKE '%keyword%' OR `location` LIKE '%keyword%' OR `email` LIKE '%keyword%' OR  `userName` LIKE '%keyword%' OR `birthYear` LIKE '%keyword%'  ";

    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows >0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Investors" ;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Entrepreneur" ;
        $response->data = [];
        $response->success = false;
        echo json_encode($response);


    }

}
if($function=="transactions") {

    if (empty($_REQUEST['keyword'])) {
        $sql = "SELECT * FROM `transactions`";
    } else {
        $keyword = $_REQUEST['keyword'];
        $sql = "SELECT * FROM `transactions` WHERE `accountNumber` LIKE '%$keyword%' OR  `amount`  LIKE '%$keyword%' OR  `TransactionType`  LIKE '%$keyword%' OR `transactionCode`  LIKE '%$keyword%' OR  `date`  LIKE '%$keyword%' OR `description`  LIKE '%$keyword%' ";

    }

    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows > 0) {

        while ($row = $result->fetch_assoc()) {
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = " $result->num_rows Transactions";
        $response->data = $new_array;
        $response->success = true;
        echo json_encode($response);


    } else {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = " $result->num_rows Transactions";
        $response->data = [];
        $response->success = false;
        echo json_encode($response);


    }

}
if($function==="zakaCallback"){

    $accountNumber = $_POST['AccountReference'];
    $MpesaReceiptNumber =$_POST['MpesaReceiptNumber'];
    $amount_paid = $_POST['amount'];

    if($MpesaReceiptNumber==="FAILED"){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="$MpesaReceiptNumber";
        $response->success = false;
        echo json_encode($response);

    }else{
        $sql ="SELECT * FROM `transactions` WHERE `transactionCode` ='$accountNumber'";
        $result = DB::instance()->executeSQL($sql);

        if($result){
            $accountNumber = $result->fetch_assoc()['accountNumber'];
            $description = $result->fetch_assoc()['description'];
            $type =  $result->fetch_assoc()['type'];

            generateTransaction($accountNumber,$amount_paid,'Cr',$MpesaReceiptNumber,$description,$type);

        }


    }

}
if($function==="pay"){


    $Amount = $_POST['amount'];
    $PhoneNumber = $_POST['phoneNumber'];
    $AccountReference = $_POST['transactionCode'];

    pushPayments($Amount,$PhoneNumber,$AccountReference);

}
if($function=="loginInvestors"){

    $userName = $_REQUEST['userName'];
    $password = $_REQUEST['password'];

    $sql ="SELECT * FROM `investors` WHERE `userName` ='$userName' AND `password` ='$password'";

    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows >0){

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data =$result->fetch_assoc();
        $response->message ="Login Success" ;
        $response->success = true;
        echo json_encode($response);




    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" Failed to Login" ;
        $response->success = false;
        echo json_encode($response);

    }


}
if($function=="dashboardData"){

    //totalInvestors
    $totalInvestors = DB::instance()->executeSQL("SELECT * FROM `investors` ")->num_rows;
    //totalSumInvestorsWallet
    $totalSumInvestorsWallet = DB::instance()->executeSQL("SELECT SUM(amount) FROM `transactions` WHERE `account_type` ='1' AND `TransactionType` ='Cr'")->fetch_array()[0];

    //totalEntrepreneurs
    $totalEntrepreneurs = DB::instance()->executeSQL("SELECT * FROM `entrepreneur`")->num_rows;
    //totalCampaignPostAmount
    $totalCampaignPostAmount = DB::instance()->executeSQL("SELECT SUM(goal) FROM `posts` WHERE `status` ='1'")->fetch_array()[0];

    //totalOfferAcceptedSumAmount
    $totalOfferAcceptedSumAmount = DB::instance()->executeSQL("SELECT SUM(amount) FROM `offers` WHERE `status` ='1'")->fetch_array()[0];
    //totalRepayment

    //totalRegistrationFee
    $totalRegistrationFee = DB::instance()->executeSQL("SELECT SUM(amount) FROM `transactions` WHERE `account_type` ='0' AND `description`='Registration Fee'")->fetch_array()[0];

    $response = new Response();
    $response->status = Response::STATUS_SUCCESS;
    $response->message ="Dashboard Data" ;
    $response->data = array("totalInvestors"=>$totalInvestors,"totalSumInvestorsWallet"=>$totalSumInvestorsWallet,"totalEntrepreneurs"=>$totalEntrepreneurs,"totalCampaignPostAmount"=>$totalCampaignPostAmount,
        "totalOfferAcceptedSumAmount"=>$totalOfferAcceptedSumAmount,"totalRegistrationFee"=>$totalRegistrationFee);
    $response->success = true;
    echo json_encode($response);


}
if($function=="post"){


    //GET IMAGE AND SAVE TO THE FOLDER UPLOADS
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            echo "File is an image - " . $check["mime"] . ".";
            $uploadOk = 1;
        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message= "File is not an image.";
            $response->success = false;
            echo json_encode($response);
            $uploadOk = 0;
        }
    }
    // Check if file already exists
    if (file_exists($target_file)) {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Sorry, file already exists.";
        $response->success = false;
        echo json_encode($response);
        $uploadOk = 0;
        exit();
    }
    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 500000) {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Sorry, your file is too large.";
        $response->success = false;
        echo json_encode($response);
        $uploadOk = 0;
        exit();
    }
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $response->success = false;
        echo json_encode($response);
        $uploadOk = 0;
        exit();
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Sorry, your Images was not uploaded.";
        $response->success = false;
        echo json_encode($response);
        exit();
        // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {

            $phoneNumber = formatPhoneNumber($_REQUEST['phoneNumber']);
            $title = $_REQUEST['title'];
            $category = $_REQUEST['category'];
            $goal = $_REQUEST['goal'];
            $location = $_REQUEST['location'];
            $description = $_REQUEST['description'];
            $image =basename( $_FILES["fileToUpload"]["name"]);

            $sql ="SELECT * FROM `entrepreneur` WHERE `phoneNumber` ='$phoneNumber'";
            $result = DB::instance()->executeSQL($sql);

            if($result->num_rows>0){
                $entrepreneur_id = $result->fetch_assoc()['id'];
                $code = 'LOAN'.code();
                //status{0=GOAL NOT REACHED 1=GOAL REACHED, 2=DISBURSED}
                $status ="0";

                $sql ="INSERT INTO `posts`(`entrepreneur_id`, `title`, `category`, `goal`, `location`, `description`, `image`,`code`,`status`) 
                       VALUES ('$entrepreneur_id','$title','$category','$goal','$location','$description','$image','$code','$status')";

                $result = DB::instance()->executeSQL($sql);

                if($result){
                    $response = new Response();
                    $response->status = Response::STATUS_SUCCESS;
                    $response->message ="Posted Success" ;
                    $response->success = true;
                    echo json_encode($response);


                }else{
                    $response = new Response();
                    $response->status = Response::STATUS_SUCCESS;
                    $response->message =" Failed to post" ;
                    $response->success = false;
                    echo json_encode($response);

                }

            }else{

                $response = new Response();
                $response->status = Response::STATUS_SUCCESS;
                $response->message =" $phoneNumber not found please ensure you enter the correct mobile number" ;
                $response->success = false;
                echo json_encode($response);

            }

        } else {

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message= "Sorry, there was an error uploading your file.";
            $response->success = false;
            echo json_encode($response);
            exit();
        }
    }


}
if($function=="createOffer"){

    $investor_id = $_REQUEST['investor_id'];
    $post_id = $_REQUEST['post_id'];
    $amount = $_REQUEST['amount'];
    $interest_rate = $_REQUEST['interest'];
    $comments = $_REQUEST['comments'];
    $status ='0';


    $sql ="INSERT INTO `offers`(`investor_id`, `post_id`, `amount`, `interest_rate`, `comments`, `status`) 
                        VALUES ('$investor_id','$post_id','$amount','$interest_rate','$comments','$status')";

    $result = DB::instance()->executeSQL($sql);
    if($result){

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Offer Posted";
        $response->success = true;
        echo json_encode($response);
    }else{

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Failed to Post Offer";
        $response->success = false;
        echo json_encode($response);

    }


}
if($function=="offerRespond"){
    $status = $_REQUEST['status'];
    $offer_id = $_REQUEST['offer_id'];
    if($status==="3"){
        $offer_id = $_REQUEST['offer_id'];
        $user = $_REQUEST['user'];
        $comments = $_REQUEST['comments'];

        //tODO SendSMS

        $sql ="INSERT INTO `negotiation`(`offer_id`, `user`, `comments`) 
                             VALUES ('$offer_id','$user','$comments')";
        $result = DB::instance()->executeSQL($sql);

        $sql ="UPDATE `offers` SET `status`='$status' WHERE `id`='$offer_id'";
        $result = DB::instance()->executeSQL($sql);

        if($result){
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message= "Posted";
            $response->success = true;
            echo json_encode($response);

        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message= "Failed to Post";
            $response->success = false;
            echo json_encode($response);

        }
    }else{
        $sql ="UPDATE `offers` SET `status`='$status' WHERE `id`='$offer_id'";
        $result = DB::instance()->executeSQL($sql);

        if($result){
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message= "Posted";
            $response->success = true;
            echo json_encode($response);

        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message= "Failed to Post";
            $response->success = false;
            echo json_encode($response);

        }
    }

}
if($function=="loginEntrepreneur"){

    $email = $_REQUEST['email'];
    $password = $_REQUEST['password'];

    $sql ="SELECT * FROM `entrepreneur` WHERE `email` ='$email' AND `password` ='$password'";

    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows >0){

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data =$result->fetch_assoc();
        $response->message ="Login Success" ;
        $response->success = true;
        echo json_encode($response);




    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" Failed to Login to your account" ;
        $response->success = false;
        echo json_encode($response);

    }


}

/*
    if ($function == "uploadExcelMpesaSafaricom") {
        $temp = explode(".", $_FILES["excel"]["name"]);
        $extension = end($temp); // For getting Extension of selected file
        $allowed_extension = array("xls", "xlsx", "csv"); //allowed extension
        if (in_array($extension, $allowed_extension)) //check selected file extension is present in allowed extension array
        {
            $file = $_FILES["excel"]["tmp_name"]; // getting temporary source of excel file
            include("PHPExcel.php"); // Add PHPExcel Library in this code
            include("PHPExcel/IOFactory.php"); // Add PHPExcel Library in this code
            $objPHPExcel = PHPExcel_IOFactory::load($file); // create object of PHPExcel library by using load() method and in load method define path of selected file

            foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
                $highestRow = $worksheet->getHighestRow();
                for ($row = 2; $row <= $highestRow; $row++) {
                    $ReceiptNo = $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                    $CompletionTime = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                    $InitiationTime = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                    $Details = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                    $TransactionStatus = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
                    $PaidIn = toInt($worksheet->getCellByColumnAndRow(5, $row)->getValue());
                    $Withdrawn = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
                    $Balance = toInt($worksheet->getCellByColumnAndRow(7, $row)->getValue());
                    $BalanceConfirmed = $worksheet->getCellByColumnAndRow(8, $row)->getValue();
                    $ReasonType = $worksheet->getCellByColumnAndRow(9, $row)->getValue();
                    $OtherPartyInfo = $worksheet->getCellByColumnAndRow(10, $row)->getValue();
                    $LinkedTransactionID = $worksheet->getCellByColumnAndRow(11, $row)->getValue();
                    $ACNo = $worksheet->getCellByColumnAndRow(12, $row)->getValue();


                    $sql = "INSERT INTO `mpesa_transaction`(`ReceiptNo`, `CompletionTime`, `InitiationTime`, `Details`, `TransactionStatus`, `PaidIn`, `Withdrawn`, `Balance`, `BalanceConfirmed`, `ReasonType`, `OtherPartyInfo`, `LinkedTransactionID`, `ACNo`)
                                                VALUES ('$ReceiptNo','$CompletionTime','$InitiationTime','$Details','$TransactionStatus','$PaidIn','$Withdrawn','$Balance','$BalanceConfirmed','$ReasonType','$OtherPartyInfo','$LinkedTransactionID','$ACNo')";
                    $result = DB::instance()->executeSQL($sql);
                }
            }


            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = " $result->num_rows Transactions uploaded";
            $response->success = true;
            echo json_encode($response);


        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Invalid file";
            $response->success = false;
            echo json_encode($response);
        }

    }
    if ($function == "uploadExcelMpesaCoreBanking") {
        $temp = explode(".", $_FILES["excel"]["name"]);
        $extension = end($temp); // For getting Extension of selected file
        $allowed_extension = array("xls", "xlsx", "csv"); //allowed extension
        if (in_array($extension, $allowed_extension)) //check selected file extension is present in allowed extension array
        {
            $file = $_FILES["excel"]["tmp_name"]; // getting temporary source of excel file
            include("PHPExcel.php"); // Add PHPExcel Library in this code
            include("PHPExcel/IOFactory.php"); // Add PHPExcel Library in this code
            $objPHPExcel = PHPExcel_IOFactory::load($file); // create object of PHPExcel library by using load() method and in load method define path of selected file

            foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
                $highestRow = $worksheet->getHighestRow();
                for ($row = 2; $row <= $highestRow; $row++) {
                    $LineNo = $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                    $TrxDate = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                    $TrxSN = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                    $TUNInternalSN = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                    $Timestamp = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
                    $Code = $worksheet->getCellByColumnAndRow(5, $row)->getValue();
                    $TransactionName = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
                    $Comments = $worksheet->getCellByColumnAndRow(7, $row)->getValue();
                    $JustificationCode = $worksheet->getCellByColumnAndRow(8, $row)->getValue();
                    $JustificationName = $worksheet->getCellByColumnAndRow(9, $row)->getValue();
                    $AccountNumber = $worksheet->getCellByColumnAndRow(10, $row)->getValue();
                    $BeneficiariesName = $worksheet->getCellByColumnAndRow(11, $row)->getValue();
                    $Currency = $worksheet->getCellByColumnAndRow(12, $row)->getValue();
                    $ChequeNumber = $worksheet->getCellByColumnAndRow(13, $row)->getValue();
                    $RV = $worksheet->getCellByColumnAndRow(14, $row)->getValue();
                    $Debit = $worksheet->getCellByColumnAndRow(15, $row)->getValue();
                    $Credit = toInt($worksheet->getCellByColumnAndRow(16, $row)->getValue());
                    $ChargesAmt = $worksheet->getCellByColumnAndRow(17, $row)->getValue();
                    $Authorizer1 = $worksheet->getCellByColumnAndRow(18, $row)->getValue();
                    $Authorizer2 = $worksheet->getCellByColumnAndRow(19, $row)->getValue();


                    $sql = "INSERT INTO `core_banking_mpesa`(`LineNo`, `TrxDate`, `TrxSN`, `TUNInternalSN`, `Timestamp`, `Code`, `TransactionName`, `Comments`, `JustificationCode`,`JustificationName`, `AccountNumber`, `BeneficiariesName`, `Currency`, `ChequeNumber`, `RV`, `Debit`, `Credit`, `ChargesAmt`, `Authorizer1`, `Authorizer2`)
                                                 VALUES ('$LineNo','$TrxDate','$TrxSN','$TUNInternalSN','$Timestamp','$Code','$TransactionName','$Comments','$JustificationCode','$JustificationName','$AccountNumber','$BeneficiariesName','$Currency','$ChequeNumber','$RV','$Debit','$Credit','$ChargesAmt','$Authorizer1','$Authorizer2')";
                    $result = DB::instance()->executeSQL($sql);
                }
            }


            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = " $result->num_rows Transactions uploaded";
            $response->success = true;
            echo json_encode($response);


        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Invalid file";
            $response->success = false;
            echo json_encode($response);
        }

    }

    if ($function == "uploadExcelAtm") {
        $temp = explode(".", $_FILES["excel"]["name"]);
        $extension = end($temp); // For getting Extension of selected file
        $allowed_extension = array("xls", "xlsx", "csv"); //allowed extension
        if (in_array($extension, $allowed_extension)) //check selected file extension is present in allowed extension array
        {
            $file = $_FILES["excel"]["tmp_name"]; // getting temporary source of excel file
            include("PHPExcel.php"); // Add PHPExcel Library in this code
            include("PHPExcel/IOFactory.php"); // Add PHPExcel Library in this code
            $objPHPExcel = PHPExcel_IOFactory::load($file); // create object of PHPExcel library by using load() method and in load method define path of selected file

            foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
                $highestRow = $worksheet->getHighestRow();
                for ($row = 2; $row <= $highestRow; $row++) {
                    $cardNumber = $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                    $amount = toInt($worksheet->getCellByColumnAndRow(1, $row)->getValue());
                    $RRN = ltrim($worksheet->getCellByColumnAndRow(2, $row)->getValue(), '0');
                    $terminal = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                    $account = ltrim($worksheet->getCellByColumnAndRow(4, $row)->getValue(), '0');;
                    $timestamp = $worksheet->getCellByColumnAndRow(5, $row)->getValue();

                    $sql = "INSERT INTO `atm_transaction`(`cardNumber`, `amount`, `RRN`, `terminal`, `account`, `timestamp`)
                                               VALUES ('$cardNumber','$amount','$RRN','$terminal','$account','$timestamp')";
                    $result = DB::instance()->executeSQL($sql);
                }
            }


            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = " $result->num_rows Transactions Uploaded";
            $response->success = true;
            echo json_encode($response);


        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Invalid file";
            $response->success = false;
            echo json_encode($response);
        }

    }
    if ($function == "uploadExcelAtmCoreBanking") {
        $temp = explode(".", $_FILES["excel"]["name"]);
        $extension = end($temp); // For getting Extension of selected file
        $allowed_extension = array("xls", "xlsx", "csv"); //allowed extension
        if (in_array($extension, $allowed_extension)) //check selected file extension is present in allowed extension array
        {
            $file = $_FILES["excel"]["tmp_name"]; // getting temporary source of excel file
            include("PHPExcel.php"); // Add PHPExcel Library in this code
            include("PHPExcel/IOFactory.php"); // Add PHPExcel Library in this code
            $objPHPExcel = PHPExcel_IOFactory::load($file); // create object of PHPExcel library by using load() method and in load method define path of selected file

            foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
                $highestRow = $worksheet->getHighestRow();
                for ($row = 2; $row <= $highestRow; $row++) {
                    $LineNo = $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                    $TrxDate = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                    $TrxSN = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                    $TUNInternalSN = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                    $Timestamp = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
                    $Code = $worksheet->getCellByColumnAndRow(5, $row)->getValue();
                    $TransactionName = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
                    $Comments = $worksheet->getCellByColumnAndRow(7, $row)->getValue();
                    $JustificationCode = $worksheet->getCellByColumnAndRow(8, $row)->getValue();
                    $JustificationName = $worksheet->getCellByColumnAndRow(9, $row)->getValue();
                    $AccountNumber = $worksheet->getCellByColumnAndRow(10, $row)->getValue();
                    $BeneficiariesName = $worksheet->getCellByColumnAndRow(11, $row)->getValue();
                    $Currency = $worksheet->getCellByColumnAndRow(12, $row)->getValue();
                    $ChequeNumber = $worksheet->getCellByColumnAndRow(13, $row)->getValue();
                    $RV = $worksheet->getCellByColumnAndRow(14, $row)->getValue();
                    $Debit = toInt($worksheet->getCellByColumnAndRow(15, $row)->getValue());
                    $Credit = toInt($worksheet->getCellByColumnAndRow(16, $row)->getValue());
                    $ChargesAmt = $worksheet->getCellByColumnAndRow(17, $row)->getValue();
                    $Authorizer1 = $worksheet->getCellByColumnAndRow(18, $row)->getValue();
                    $Authorizer2 = $worksheet->getCellByColumnAndRow(19, $row)->getValue();

                    $CommentsArray = explode(" | ", $Comments);
                    $RRN = ltrim($CommentsArray[1], '0');;

                    $Comments = $CommentsArray[0] . " | " . $RRN;


                    $sql = "INSERT INTO `core_banking_atm`(`LineNo`, `TrxDate`, `TrxSN`, `TUNInternalSN`, `Timestamp`, `Code`, `TransactionName`, `Comments`, `JustificationCode`,`JustificationName`, `AccountNumber`, `BeneficiariesName`, `Currency`, `ChequeNumber`, `RV`, `Debit`, `Credit`, `ChargesAmt`, `Authorizer1`, `Authorizer2`)
                                                 VALUES ('$LineNo','$TrxDate','$TrxSN','$TUNInternalSN','$Timestamp','$Code','$TransactionName','$Comments','$JustificationCode','$JustificationName','$AccountNumber','$BeneficiariesName','$Currency','$ChequeNumber','$RV','$Debit','$Credit','$ChargesAmt','$Authorizer1','$Authorizer2')";
                    $result = DB::instance()->executeSQL($sql);
                }
            }


            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = " $result->num_rows Transactions uploaded";
            $response->success = true;
            echo json_encode($response);


        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Invalid file";
            $response->success = false;
            echo json_encode($response);
        }

    }

    if ($function == "get_core_banking_mpesa") {
        $keyword = $_POST['keyword'];

        if (empty($keyword)) {
            $sql = "SELECT * FROM `core_banking_mpesa` ";
        } else {

            $sql = "SELECT * FROM `core_banking_mpesa` WHERE `LineNo` LIKE '%$keyword%' OR `TrxDate` LIKE '%%' OR `TrxSN` LIKE '%%' OR `TUNInternalSN` LIKE '%%' OR `Timestamp` LIKE '%$keyword%' OR
`Code` LIKE '%$keyword%' OR`TransactionName` LIKE '%$keyword%' OR`Comments`LIKE '%$keyword%' OR`JustificationCode`LIKE '%$keyword%' OR`AccountNumber`LIKE '%$keyword%' OR`BeneficiariesName`LIKE '%$keyword%' OR`Currency`LIKE '%$keyword%' OR`ChequeNumber`LIKE '%$keyword%' OR `RV` LIKE '%$keyword%' OR `Debit` LIKE '%$keyword%' OR`Credit` LIKE '%$keyword%' OR `ChargesAmt` LIKE '%$keyword%' OR `Authorizer1` LIKE '%$keyword%' OR`Authorizer2` LIKE '%$keyword%' OR`des` = '$keyword' OR `reconcile` LIKE '%$keyword%'  ";
        }

        $result = DB::instance()->executeSQL($sql);

        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {
                $new_array[] = $row; // Inside while loop
            }

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->data = $new_array;
            $response->message = " $result->num_rows Transactions";
            $response->success = true;
            echo json_encode($response);


        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = " $result->num_rows Transactions";
            $response->data = [];
            $response->success = false;
            echo json_encode($response);


        }

    }
    if ($function == "get_mpesa_transaction") {
        $keyword = $_POST['keyword'];

        if (empty($keyword)) {
            $sql = "SELECT * FROM `mpesa_transaction` ";
        } else {

            $sql = "SELECT * FROM `mpesa_transaction` WHERE `ReceiptNo` LIKE '%$keyword%' OR `CompletionTime` LIKE '%$keyword%'
                OR `InitiationTime` LIKE '%$keyword%' OR `Details` LIKE '%$keyword%' OR`TransactionStatus` LIKE '%$keyword%' OR `PaidIn` LIKE '%$keyword%'
                OR `Withdrawn` LIKE '%$keyword%' OR `Balance` LIKE '%$keyword%' OR `BalanceConfirmed` LIKE '%$keyword%' OR `ReasonType` LIKE '%$keyword%' OR `OtherPartyInfo`
                LIKE '%$keyword%' OR `LinkedTransactionID` LIKE '%$keyword%' OR `ACNo` LIKE '%$keyword%' OR `date` LIKE '%$keyword%' OR `reconcile` LIKE '%$keyword%'";

        }

        $result = DB::instance()->executeSQL($sql);

        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {
                $new_array[] = $row; // Inside while loop
            }

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = " $result->num_rows Transactions";
            $response->data = $new_array;
            $response->success = true;
            echo json_encode($response);


        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = " $result->num_rows Transactions";
            $response->data = [];
            $response->success = false;
            echo json_encode($response);


        }

    }

    if ($function == "get_core_banking_atm") {
        $keyword = $_POST['keyword'];

        if (empty($keyword)) {
            $sql = "SELECT * FROM `core_banking_atm` ";
        } else {

            $sql = "SELECT * FROM `core_banking_atm` WHERE `LineNo` LIKE '%$keyword%' OR `TrxDate` LIKE '%$keyword%' OR `TrxSN` LIKE '%$keyword%' OR `TUNInternalSN` LIKE '%$keyword%' OR `Timestamp` LIKE '%$keyword%' OR `Code` LIKE '%$keyword%' OR `TransactionName` LIKE '%$keyword%' OR `Comments` LIKE '%$keyword%' OR `JustificationCode` LIKE '%$keyword%' OR `JustificationName` LIKE '%$keyword%' OR `AccountNumber` LIKE '%$keyword%' OR `BeneficiariesName` LIKE '%$keyword%' OR `Currency` LIKE '%$keyword%' OR `ChequeNumber` LIKE '%$keyword%' OR `RV` LIKE '%$keyword%' OR `Debit` LIKE '%$keyword%' OR `Credit` LIKE '%$keyword%' OR `ChargesAmt` LIKE '%$keyword%' OR `Authorizer1` LIKE '%$keyword%' OR `Authorizer2` LIKE '%$keyword%' OR `des` = '$keyword' OR `reconcile` LIKE '%$keyword%'";
        }

        $result = DB::instance()->executeSQL($sql);

        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {
                $new_array[] = $row; // Inside while loop
            }

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = " $result->num_rows Transactions";
            $response->data = $new_array;
            $response->success = true;
            echo json_encode($response);


        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = " $result->num_rows Transactions";
            $response->data = [];
            $response->success = false;
            echo json_encode($response);


        }

    }
    if ($function == "get_atm_transaction") {
        $keyword = $_POST['keyword'];

        if (empty($keyword)) {
            $sql = "SELECT * FROM `atm_transaction` ";
        } else {

            $sql = "SELECT * FROM `atm_transaction` WHERE `cardNumber` LIKE '%$keyword%' OR `amount` LIKE '%$keyword%' OR `RRN` LIKE '%$keyword%' OR `terminal` LIKE '%$keyword%' OR `account` LIKE '%$keyword%' OR `timestamp` LIKE '%$keyword%' OR `date` LIKE '%$keyword%' OR `reconcile`='%keyword%'";
        }

        $result = DB::instance()->executeSQL($sql);

        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {
                $new_array[] = $row; // Inside while loop
            }

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = " $result->num_rows Transactions";
            $response->data = $new_array;
            $response->success = true;
            echo json_encode($response);


        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = " $result->num_rows Transactions";
            $response->data = [];
            $response->success = false;
            echo json_encode($response);


        }

    }

    if ($function == "dashboardDataCoreBanking") {

        //INTERNAL REPORTS
        //core banking Mpesa
        $sqlRecMpesaCB = "SELECT SUM(Credit)
                       FROM `core_banking_mpesa` WHERE `reconcile` ='1'
                    ";
        $sumReconcileMpesaCB = DB::instance()->executeSQL($sqlRecMpesaCB)->fetch_array()[0];

        $sqlNotRecMpesaCB = "SELECT SUM(Credit)
                       FROM `core_banking_mpesa` WHERE `reconcile` ='0'
                    ";
        $sumNotReconcileMpesaCB = DB::instance()->executeSQL($sqlNotRecMpesaCB)->fetch_array()[0];

        $sqlFundInAccountMpesaCB = "SELECT SUM(Credit)
                       FROM `core_banking_mpesa`
                    ";
        $fundInAccountMpesaCB = DB::instance()->executeSQL($sqlFundInAccountMpesaCB)->fetch_array()[0];


        //core banking atms
        $sqlRecAtmCB = "SELECT SUM(Debit)
                       FROM `core_banking_atm` WHERE `reconcile` ='1'
                    ";
        $sumReconcileAtmCB = DB::instance()->executeSQL($sqlRecAtmCB)->fetch_array()[0];

        $sqlNotRecAtmCB = "SELECT SUM(Debit)
                       FROM `core_banking_atm` WHERE `reconcile` ='0'
                    ";
        $sumNotReconcileAtmCB = DB::instance()->executeSQL($sqlNotRecAtmCB)->fetch_array()[0];

        $sqlFundInAccountAtmCB = "SELECT SUM(Debit)
                       FROM `core_banking_atm`
                    ";
        $fundInAccountATmCB = DB::instance()->executeSQL($sqlFundInAccountAtmCB)->fetch_array()[0];

        if ($fundInAccountMpesaCB == null)
            $fundInAccountMpesaCB = 0;

        if ($sumReconcileMpesaCB == null)
            $sumReconcileMpesaCB = "0";

        if ($sumNotReconcileMpesaCB == null)
            $sumNotReconcileMpesaCB = "0";

        if ($fundInAccountATmCB == null)
            $fundInAccountATmCB = "0";

        if ($sumReconcileAtmCB == null)
            $sumReconcileAtmCB = "0";

        if ($sumNotReconcileAtmCB == null)
            $sumNotReconcileAtmCB = "0";


        //EXTERNAL REPORTS
        //MPESA reports
        $sqlRecMpesaExternal = "SELECT SUM(PaidIn)
                       FROM `mpesa_transaction` WHERE `reconcile` ='1'
                    ";
        $sumReconcileMpesaExternal = DB::instance()->executeSQL($sqlRecMpesaExternal)->fetch_array()[0];

        $sqlNotRecMpesaExternal = "SELECT SUM(PaidIn)
                       FROM `mpesa_transaction` WHERE `reconcile` ='0'
                    ";
        $sumNotReconcileMpesaExternal = DB::instance()->executeSQL($sqlNotRecMpesaExternal)->fetch_array()[0];

        $sqlFundInAccountMpesaExternal = "SELECT SUM(PaidIn)
                       FROM `mpesa_transaction`
                    ";
        $fundInAccountMpesaExternal = DB::instance()->executeSQL($sqlFundInAccountMpesaExternal)->fetch_array()[0];


        //ATM reports
        $sqlRecAtmExternal = "SELECT SUM(amount)
                       FROM `atm_transaction` WHERE `reconcile` ='1'
                    ";
        $sumReconcileAtmExternal = DB::instance()->executeSQL($sqlRecAtmExternal)->fetch_array()[0];

        $sqlNotRecAtmExternal = "SELECT SUM(amount)
                       FROM `atm_transaction` WHERE `reconcile` ='0'
                    ";
        $sumNotReconcileAtmExternal = DB::instance()->executeSQL($sqlNotRecAtmExternal)->fetch_array()[0];

        $sqlFundInAccountAtmExternal = "SELECT SUM(amount)
                       FROM `atm_transaction`
                    ";
        $fundInAccountATmExternal = DB::instance()->executeSQL($sqlFundInAccountAtmExternal)->fetch_array()[0];


        if ($sumReconcileMpesaExternal == null)
            $sumReconcileMpesaExternal = "0";

        if ($sumNotReconcileMpesaExternal == null)
            $sumNotReconcileMpesaExternal = "0";

        if ($fundInAccountMpesaExternal == null)
            $fundInAccountMpesaExternal = "0";

        if ($sumReconcileAtmExternal == null)
            $sumReconcileAtmExternal = "0";

        if ($sumNotReconcileAtmExternal == null)
            $sumNotReconcileAtmExternal = "0";

        if ($fundInAccountATmExternal == null)
            $fundInAccountATmExternal = "0";


        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data = array("fundInAccountMpesaCB" => formatcurrency($fundInAccountMpesaCB), "reconciledMpesaCB" => formatcurrency($sumReconcileMpesaCB), 'notReconciledMpesaCB' => formatcurrency($sumNotReconcileMpesaCB),
            "fundInAccountMpesaExternal" => formatcurrency($fundInAccountMpesaExternal), "reconciledMpesaExternal" => formatcurrency($sumReconcileMpesaExternal), 'notReconciledMpesaExternal' => formatcurrency($sumNotReconcileMpesaExternal),
            "fundInAccountATMCB" => formatcurrency($fundInAccountATmCB), "reconciledATMCB" => formatcurrency($sumReconcileAtmCB), 'notReconciledATMCB' => formatcurrency($sumNotReconcileAtmCB),
            "fundInAccountATMExternal" => formatcurrency($fundInAccountATmExternal), "reconciledATMExternal" => formatcurrency($sumReconcileAtmExternal), 'notReconciledATMExternal' => formatcurrency($sumNotReconcileAtmExternal));
        $response->success = true;
        echo json_encode($response);
        exit();

    }
    if ($function == "clear") {
        $action = $_REQUEST['action'];
        if ($action == "MPESA-CB")
            DB::instance()->executeSQL(" DELETE FROM `core_banking_mpesa`");
        if ($action == "MPESA-EXTERNAL")
            DB::instance()->executeSQL(" DELETE FROM  `mpesa_transaction`");

        if ($action == "ATM-CB")
            DB::instance()->executeSQL(" DELETE FROM `core_banking_atm`");
        if ($action == "ATM-EXTERNAL")
            DB::instance()->executeSQL(" DELETE FROM  `atm_transaction`");

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = "Transactions cleared";
        $response->success = true;
        echo json_encode($response);

    }

    function reconcileMpesa()
    {
        $sql = "SELECT * FROM `core_banking_mpesa`";

        $res = DB::instance()->executeSQL($sql);

        if ($res->num_rows > 0) {

            while ($row = $res->fetch_assoc()) {
                $id = $row['id'];
                $Credit = $row['Credit'];

                $Comments = $row['Comments'];
                $CommentsArray = explode("-", $Comments);
                $CbReceiptNo = $CommentsArray[0];


                $sql = "SELECT * FROM `mpesa_transaction` WHERE `ReceiptNo`='$CbReceiptNo'";
                $result = DB::instance()->executeSQL($sql);
                if ($result->num_rows > 0) {
                    $paidIn = $result->fetch_assoc()['PaidIn'];

                    if ($Credit == $paidIn) {
                        DB::instance()->executeSQL("UPDATE `core_banking_mpesa` SET `reconcile`='1' ,`des`='matched',`reconcileDesc`='Reconciled' WHERE `id` ='$id' ");
                        DB::instance()->executeSQL("UPDATE `mpesa_transaction` SET `reconcile`='1' ,`des`='matched' ,`reconcileDesc`='Reconciled' WHERE `ReceiptNo`='$CbReceiptNo'");


                    } else {
                        $message = "MPESA=$paidIn CB=$Credit DIF = " . diff($Credit, $paidIn);
                        DB::instance()->executeSQL("UPDATE `core_banking_mpesa` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled' WHERE `id` LIKE '$id' ");
                        DB::instance()->executeSQL("UPDATE `mpesa_transaction` SET `reconcile`='0' ,`des`='$message'  ,`reconcileDesc`='Not Reconciled' WHERE `ReceiptNo`='$CbReceiptNo'");

                    }

                } else {
                    $message = "unmatched";
                    DB::instance()->executeSQL("UPDATE `core_banking_mpesa` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled'   WHERE `id` LIKE '$id' ");
                    DB::instance()->executeSQL("UPDATE `mpesa_transaction` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled'  WHERE `ReceiptNo`='$CbReceiptNo'");
                }

            }

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = 'Reconciliation successful';
            $response->success = true;
            echo json_encode($response);

        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = 'Nothing to Reconcile now';
            $response->success = false;
            echo json_encode($response);
        }
    }

    function reconcileATM()
    {
        $sql = "SELECT * FROM `core_banking_atm`";

        $res = DB::instance()->executeSQL($sql);

        if ($res->num_rows > 0) {

            while ($row = $res->fetch_assoc()) {
                $id = $row['id'];
                $Debit = $row['Debit'];

                $Comments = $row['Comments'];
                $CommentsArray = explode(" | ", $Comments);
                $RRN = ltrim($CommentsArray[1], '0');;


                $sql_atm = "SELECT * FROM `atm_transaction` WHERE `RRN`='$RRN'";
                $result_atm = DB::instance()->executeSQL($sql_atm);
                if ($result_atm->num_rows > 0) {
                    $amount = $result_atm->fetch_assoc()['amount'];

                    if ($Debit == $amount) {
                        DB::instance()->executeSQL("UPDATE `core_banking_atm` SET `reconcile`='1' ,`des`='matched' ,`reconcileDesc`='Reconciled'   WHERE `id` ='$id' ");
                        DB::instance()->executeSQL("UPDATE `atm_transaction` SET `reconcile`='1',`des`='matched',`reconcileDesc`='Reconciled'  WHERE `RRN`='$RRN'");


                    } else {
                        $message = "ATM=$amount CB=$Debit DIF = " . diff($amount, $Debit);
                        DB::instance()->executeSQL("UPDATE `core_banking_atm` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled'   WHERE `id` = '$id' ");
                        DB::instance()->executeSQL("UPDATE `atm_transaction` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled'  WHERE `ReceiptNo`='$RRN'");

                    }

                } else {
                    $message = "unmatched";
                    DB::instance()->executeSQL("UPDATE `core_banking_atm` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled'  WHERE `id` = '$id' ");
                    DB::instance()->executeSQL("UPDATE `atm_transaction` SET `reconcile`='0',`des`='$message' ,`reconcileDesc`='Not Reconciled'  WHERE `ReceiptNo`='$RRN'");
                }

            }

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = 'Reconciliation successful';
            $response->success = true;
            echo json_encode($response);

        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = 'Nothing to Reconcile now';
            $response->success = false;
            echo json_encode($response);
        }
    }


    if ($function == "reconcileMpesa") {
        reconcileMpesa();
    }
    if ($function == "reconcileATM") {
        reconcileATM();
    }


    class PDF extends FPDF
    {
// Page header
        function Header()
        {
            // Logo
            $this->Image('http://biasharaleo.co.ke/wp-content/uploads/2017/11/DK6bxmVf.png', 10, -1, 70);
            $this->SetFont('Arial', 'B', 13);
            // Move to the right
            $this->Cell(80);
            // Title
            $this->Cell(80, 10, 'BANK RECONCILIATION REPORT', 1, 0, 'C');
            // Line break
            $this->Ln(50);
        }

// Page footer
        function Footer()
        {
            // Position at 1.5 cm from bottom
            $this->SetY(-15);
            // Arial italic 8
            $this->SetFont('Arial', 'I', 8);
            // Page number
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
    }

//CORE BANKING REPORTS
    if ($function == "generatePDFmPESACB") {
        $reconciled = $_REQUEST['reconciled'];

        if ($reconciled == '2') {
            $sql = "SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_mpesa`";
        } else {
            $sql = "SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_mpesa` WHERE `reconcile` ='$reconciled' ";
        }


        $result = DB::instance()->executeSQL($sql);

        $header = DB::instance()->executeSQL("SHOW COLUMNS FROM core_banking_mpesa
                                       WHERE FIELD IN('Comments','TransactionName','AccountNumber','Credit','des','reconcileDesc')");


        $display_heading = array('Comments' => 'Comments', 'TransactionName' => 'Transaction Name', 'AccountNumber' => 'Account Number', 'Credit' => 'Credit',
            'des' => 'Des', 'reconcileDesc' => 'Reconcile',);

        $pdf = new PDF();
//header
        $pdf->AddPage('Horizontal');
//foter page
        $pdf->AliasNbPages();
        $pdf->SetFont('Arial', 'B', 8);
        foreach ($header as $heading) {
            $pdf->Cell(47, 12, $display_heading[$heading['Field']], 1);
        }
        foreach ($result as $row) {
            $pdf->Ln();
            foreach ($row as $column)
                $pdf->Cell(47, 12, $column, 1);
        }
        $pdf->Output();

    }
    if ($function == "generatePDFaTMCB") {

        $reconciled = $_REQUEST['reconciled'];
        if ($reconciled == 2) {
            $sql = "SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_atm` ";

        } else {
            $sql = "SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_atm` WHERE  `reconcile` =$reconciled";
        }


        $result = DB::instance()->executeSQL($sql);

        $header = DB::instance()->executeSQL("SHOW COLUMNS FROM core_banking_mpesa
                                       WHERE FIELD IN('Comments','TransactionName','AccountNumber','Credit','des','reconcileDesc')");


        $display_heading = array('Comments' => 'Comments', 'TransactionName' => 'TransactionName', 'AccountNumber' => 'AccountNumber', 'Credit' => 'Credit',
            'des' => 'des', 'reconcileDesc' => 'Reconcile');

        $pdf = new PDF();
//header
        $pdf->AddPage('Horizontal');
//foter page
        $pdf->AliasNbPages();
        $pdf->SetFont('Arial', 'B', 8);
        foreach ($header as $heading) {
            $pdf->Cell(47, 12, $display_heading[$heading['Field']], 1);
        }
        foreach ($result as $row) {
            $pdf->Ln();
            foreach ($row as $column)
                $pdf->Cell(47, 12, $column, 1);
        }
        $pdf->Output();

    }

    if ($function == "generateSpreadSheetMpesaCB") {

        header('Content-type: application/vnd.ms-excel');

// It will be called file.xls
        header('Content-Disposition: attachment; filename="BankReconciliationReport.xlsx"');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Transaction Name');
        $sheet->setCellValue('B1', 'Comments');
        $sheet->setCellValue('C1', 'Account Number');
        $sheet->setCellValue('D1', 'Credit');
        $sheet->setCellValue('E1', 'des');
        $sheet->setCellValue('F1', 'Reconcile');


        $reconciled = $_REQUEST['reconciled'];
        if ($reconciled == 2) {
            $sql = "SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_mpesa`";

        } else {
            $sql = "SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_mpesa` WHERE  `reconcile` =$reconciled";
        }

        $result = DB::instance()->executeSQL($sql);
        $x = 1;
        while ($row = $result->fetch_assoc()) {
            $x++;
            $sheet->setCellValue('A' . $x, $row['TransactionName']);
            $sheet->setCellValue('B' . $x, $row['Comments']);
            $sheet->setCellValue('C' . $x, $row['AccountNumber']);
            $sheet->setCellValue('D' . $x, $row['Credit']);
            $sheet->setCellValue('E' . $x, $row['des']);
            $sheet->setCellValue('F' . $x, $row['reconcileDesc']);

        }


        $writer = new Xlsx($spreadsheet);
        $writer->save("php://output");

    }
    if ($function == "generateSpreadSheetATMCB") {

        header('Content-type: application/vnd.ms-excel');

// It will be called file.xls
        header('Content-Disposition: attachment; filename="BankReconciliationReport.xlsx"');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Transaction Name');
        $sheet->setCellValue('B1', 'Comments');
        $sheet->setCellValue('C1', 'Account Number');
        $sheet->setCellValue('D1', 'Credit');
        $sheet->setCellValue('E1', 'des');
        $sheet->setCellValue('F1', 'Reconcile');


        $reconciled = $_REQUEST['reconciled'];
        if ($reconciled == 2) {
            $sql = "SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_atm`";

        } else {
            $sql = "SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_atm` WHERE  `reconcile` =$reconciled";
        }


        $result = DB::instance()->executeSQL($sql);
        $x = 1;
        while ($row = $result->fetch_assoc()) {
            $x++;
            $sheet->setCellValue('A' . $x, $row['TransactionName']);
            $sheet->setCellValue('B' . $x, $row['Comments']);
            $sheet->setCellValue('C' . $x, $row['AccountNumber']);
            $sheet->setCellValue('D' . $x, $row['Credit']);
            $sheet->setCellValue('E' . $x, $row['des']);
            $sheet->setCellValue('F' . $x, $row['reconcileDesc']);

        }


        $writer = new Xlsx($spreadsheet);
        $writer->save("php://output");

    }


//EXTERNAL REPORT
    if ($function == "generatePDFmPESAExternal") {
        $reconciled = $_REQUEST['reconciled'];

        if ($reconciled == 2) {
            $sql = "SELECT `CompletionTime`,`ReceiptNo`,`PaidIn`,`Details`,`des`,`reconcileDesc` FROM `mpesa_transaction`";
        } else {
            $sql = "SELECT `CompletionTime`,`ReceiptNo`,`PaidIn`,`Details`,`des`,`reconcileDesc` FROM `mpesa_transaction` WHERE `reconcile` ='$reconciled' ";
        }


        $result = DB::instance()->executeSQL($sql);

        $header = DB::instance()->executeSQL("SHOW COLUMNS FROM mpesa_transaction
                                       WHERE FIELD IN('CompletionTime','ReceiptNo','PaidIn','Details','des','reconcileDesc')");


        $display_heading = array('CompletionTime' => 'CompletionTime', 'ReceiptNo' => 'ReceiptNo', 'Details' => 'Details', 'des' => 'des', 'reconcileDesc' => 'Reconcile',);

        $pdf = new PDF();
//header
        $pdf->AddPage('Horizontal');
//foter page
        $pdf->AliasNbPages();
        $pdf->SetFont('Arial', 'B', 8);
        foreach ($header as $heading) {
            $pdf->Cell(47, 12, $display_heading[$heading['Field']], 1);
        }
        foreach ($result as $row) {
            $pdf->Ln();
            foreach ($row as $column)
                $pdf->Cell(47, 12, $column, 1);
        }
        $pdf->Output();

    }
    if ($function == "generatePDFaTMCExternal") {

        $reconciled = $_REQUEST['reconciled'];
        if ($reconciled == 2) {
            $sql = "SELECT `cardNumber`,`amount`,`RRN`,`account`,`des`,`reconcileDesc` FROM `atm_transaction` ";

        } else {
            $sql = "SELECT `cardNumber`,`amount`,`RRN`,`account`,`des`,`reconcileDesc` FROM `atm_transaction` WHERE  `reconcile` =$reconciled";
        }


        $result = DB::instance()->executeSQL($sql);

        $header = DB::instance()->executeSQL("SHOW COLUMNS FROM atm_transaction
                                       WHERE FIELD IN('cardNumber','amount','RRN','account','des','reconcileDesc')");


        $display_heading = array('cardNumber' => 'cardNumber', 'amount' => 'amount', 'RRN' => 'RRN', 'account' => 'account', 'des' => 'des',
            'reconcileDesc' => 'reconcile');

        $pdf = new PDF();
//header
        $pdf->AddPage('Horizontal');
//foter page
        $pdf->AliasNbPages();
        $pdf->SetFont('Arial', 'B', 8);
        foreach ($header as $heading) {
            $pdf->Cell(47, 12, $display_heading[$heading['Field']], 1);
        }
        foreach ($result as $row) {
            $pdf->Ln();
            foreach ($row as $column)
                $pdf->Cell(47, 12, $column, 1);
        }
        $pdf->Output();

    }

    if ($function == "generateSpreadSheetMpesaExternal") {

        header('Content-type: application/vnd.ms-excel');

// It will be called file.xls
        header('Content-Disposition: attachment; filename="BankReconciliationReport.xlsx"');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'CompletionTime');
        $sheet->setCellValue('B1', 'Details');
        $sheet->setCellValue('C1', 'ReceiptNo');
        $sheet->setCellValue('D1', 'PaidIn');
        $sheet->setCellValue('E1', 'des');
        $sheet->setCellValue('F1', 'reconcile');


        $reconciled = $_REQUEST['reconciled'];
        if ($reconciled == 2) {
            $sql = "SELECT `CompletionTime`,`Details`,`ReceiptNo`,`PaidIn`,`des`,`reconcileDesc` FROM `mpesa_transaction`";

        } else {
            $sql = "SELECT `CompletionTime`,`Details`,`ReceiptNo`,`PaidIn`,`des`,`reconcileDesc` FROM `mpesa_transaction` WHERE  `reconcile` =$reconciled";
        }

        $result = DB::instance()->executeSQL($sql);
        $x = 1;
        while ($row = $result->fetch_assoc()) {
            $x++;
            $sheet->setCellValue('A' . $x, $row['CompletionTime']);
            $sheet->setCellValue('B' . $x, $row['Details']);
            $sheet->setCellValue('C' . $x, $row['ReceiptNo']);
            $sheet->setCellValue('D' . $x, $row['PaidIn']);
            $sheet->setCellValue('E' . $x, $row['des']);
            $sheet->setCellValue('F' . $x, $row['reconcileDesc']);

        }


        $writer = new Xlsx($spreadsheet);
        $writer->save("php://output");

    }
    if ($function == "generateSpreadSheetATMExternal") {

        header('Content-type: application/vnd.ms-excel');

// It will be called file.xls
        header('Content-Disposition: attachment; filename="BankReconciliationReport.xlsx"');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'cardNumber');
        $sheet->setCellValue('B1', 'amount');
        $sheet->setCellValue('C1', 'RRN');
        $sheet->setCellValue('D1', 'account');
        $sheet->setCellValue('E1', 'des');
        $sheet->setCellValue('F1', 'Reconcile');


        $reconciled = $_REQUEST['reconciled'];
        if ($reconciled == 2) {
            $sql = "SELECT `cardNumber`,`amount`,`RRN`,`account`,`des`,`reconcileDesc` FROM `atm_transaction`";

        } else {
            $sql = "SELECT `cardNumber`,`amount`,`RRN`,`account`,`des`,`reconcileDesc` FROM `atm_transaction` WHERE  `reconcile` =$reconciled";
        }


        $result = DB::instance()->executeSQL($sql);
        $x = 1;
        while ($row = $result->fetch_assoc()) {
            $x++;
            $sheet->setCellValue('A' . $x, $row['cardNumber']);
            $sheet->setCellValue('B' . $x, $row['amount']);
            $sheet->setCellValue('C' . $x, $row['RRN']);
            $sheet->setCellValue('D' . $x, $row['account']);
            $sheet->setCellValue('E' . $x, $row['des']);
            $sheet->setCellValue('F' . $x, $row['reconcileDesc']);

        }


        $writer = new Xlsx($spreadsheet);
        $writer->save("php://output");

    }

    function toInt($str)
    {
        return (int)preg_replace("/\..+$/i", "", preg_replace("/[^0-9\.]/i", "", $str));
    }

    function diff($v1, $v2)
    {
        return ($v1 - $v2) < 0 ? (-1) * ($v1 - $v2) : ($v1 - $v2);
    }

    function formatcurrency($input)
    {
        setlocale(LC_MONETARY, "en_US");
        return str_replace("USD", "KES", money_format("%i", $input));


    }
*/