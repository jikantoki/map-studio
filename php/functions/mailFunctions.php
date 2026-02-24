<?php
// require_once DIR_ROOT . '/env.php';

//メール関係

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 本当はここenvから取得したい
define('MailHeader', "
<div style=\"text-align: center;\">
<img src=\"https://map.enoki.xyz/icon.png\" alt=\"Map Studio\" height=\"64px\">
</div>
<br>
<p>
いつも Map Studio をご利用いただきありがとうございます。
</p>
<hr>");
define('MailFooter', "
<hr>
<p>このメールに返信することはできません。</p>
<p>また、このメールに身に覚えのない場合は、エノキ電気までお問い合わせください。</p>
<p>
<a href=\"https://map.enoki.xyz\">Map Studio</a> by <a href=\"https://enoki.xyz\">エノキ電気</a>
</p>
<p>お問い合わせはこちら: <a href=\"mailto:info@enoki.xyz\">info@enoki.xyz</a></p>
<p>&copy; 2024 エノキ電気</p>
");

function sendMail($to, $title, $message)
{
  try {
    //全メール共通設定
    $mail = new PHPMailer(true);
    $mail->CharSet = 'utf-8';
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->Host = SMTP_Server;
    $mail->Username = SMTP_Username;
    $mail->Password = SMTP_Password;
    $mail->Port = SMTP_Port;
    $mail->setFrom(SMTP_Mailaddress);
    $mail->FromName = SMTP_Name;
    $mail->isHTML(true);

    //メールによる設定
    $mail->addAddress($to);
    $mail->Subject = $title;
    $mail->Body = MailHeader . "<br>{$message}<br>" . MailFooter;

    //送信
    $mail->send();
  } catch (Exception $e) {
    echo $e;
  }
}
