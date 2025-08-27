<?php
include '../mailer/PHPMailerAutoload.php';

$name = trim(ucwords($_POST['name']));
$email = trim($_POST['email']);
$job = trim(ucwords($_POST['job']));
$industry = trim(ucwords($_POST['industry']));
$country = trim(ucwords($_POST['country']));
$company = trim(ucwords($_POST['company']));
$phone = trim($_POST['phone']);
$msg = trim($_POST['msg']);

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->Mailer = "smtp";
$mail->SMTPDebug = 0;
$mail->SMTPAuth = TRUE;
$mail->SMTPSecure = "ssl";
$mail->Port = 465;
$mail->Host = "p3plzcpnl487229.prod.phx3.secureserver.net";
$mail->Username = "contactus@igoalzero.com";
$mail->Password = "kSj}.7}~o=iU";
$mail->IsHTML(true);

$content = '<html>
<head>
    <style>
        .mail-area {
            width: 100%;
            height: auto;
            background-color: #FFF;
            position: absolute;
            top: 0;
            left: 0;
            font-family: arial;
        }
        .mail-logo {
            text-align: center;
            margin-top: 1em;
            font-weight: 700;
            color: #002E5B;
        }
        table {
            table-layout: fixed;
        }
        th {
            background-color: #002E5B;
            padding: 1em 0;
            color: #FFF;
        }
        td {
            padding: 1em 0;
            border-bottom: 1px dotted #000;
            overflow: hidden;
            text-align: justify;
            color: #000;
        }
        .mail-footer{
            padding: 1em 0;
            text-align: center;
            color:#002E5B;
            font-weight: 600;
            font-size: 1em;
        }
    </style>
</head>
<body>
    <div class="mail-area">
        <div class="mail-logo">iGoalZERO LOGO</div>
        <table style="margin-top:2em;" align="center" width="700px" border="0" cellpadding="0" cellspacing="0">
            <thead>
                <th colspan="2">Customer Contact Info</th>
            </thead>
            <tbody>
                <tr>
                    <td>Customer Name:</td>
                    <td>' . $name . '</td>
                </tr>
                <tr>
                    <td>Email ID:</td>
                    <td>' . $email . '</td>
                </tr>
                <tr>
                    <td>Job:</td>
                    <td>' . $job . '</td>
                </tr>
                <tr>
                    <td>Industry:</td>
                    <td>' . $industry . '</td>
                </tr>
                <tr>
                    <td>Company:</td>
                    <td>' . $company . '</td>
                </tr>
                <tr>
                    <td>Country:</td>
                    <td>' . $country . '</td>
                </tr>
                <tr>
                    <td>Phone Number:</td>
                    <td>' . $phone . '</td>
                </tr>
                <tr>
                    <td>Message:</td>
                    <td>' . $msg . '</td>
                </tr>
            </tbody>
        </table>
        <div class="mail-footer">
            iGoalZERO &copy; 2021
        </div>
    </div>
</body>
</html>';

$mail->AddAddress("support@igoalzero.com", "support@igoalzero.com");
$mail->AddAddress("walter.pinto@pennpetchem.com", "walter.pinto@pennpetchem.com");
$mail->SetFrom("contactus@igoalzero.com", "contactus@igoalzero.com");
$mail->AddReplyTo("contactus@igoalzero.com", "contactus@igoalzero.com");
$mail->Subject = 'A Customer has contacted you through the website';
$mail->AltBody = "To view the message, please use an HTML compatible email viewer!";
$mail->MsgHTML($content);
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= 'From: contactus@igoalzero.com';
if (!$mail->Send()) {
    $sentmail = "not sent";
} else {
    $sentmail = "sent";
}

echo json_encode($sentmail);