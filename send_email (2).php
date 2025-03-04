<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $senderEmail = $_POST["sender_email"];
    $senderName = $_POST["sender_name"];
    $toEmails = explode(",", $_POST["to_emails"]); // Convert comma-separated to array
    $ccEmails = !empty($_POST["cc_emails"]) ? explode(",", $_POST["cc_emails"]) : []; // Optional CC
    $subject = $_POST["subject"];
    $htmlMessage = $_POST["message"];
    $pdfPath = "";

    // Handle File Upload
    if (!empty($_FILES["pdf_file"]["name"])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create uploads directory if not exists
        }
        $pdfPath = $uploadDir . basename($_FILES["pdf_file"]["name"]);
        move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $pdfPath);
    }

    function sendEmailWithAttachment($senderEmail, $senderName, $toEmails, $ccEmails, $subject, $htmlMessage, $pdfPath) {
        $boundary = md5(time());

        // Email headers
        $headers = "From: $senderName <$senderEmail>\r\n";
        $headers .= "Reply-To: $senderEmail\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

        // Convert recipients array to string
        $to = implode(", ", array_map('trim', $toEmails));
        $cc = !empty($ccEmails) ? implode(", ", array_map('trim', $ccEmails)) : "";

        // Add CC if provided
        if (!empty($cc)) {
            $headers .= "CC: $cc\r\n";
        }

        // Email body
        $message = "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $htmlMessage . "\r\n\r\n";

        // Attach PDF file if exists
        if (!empty($pdfPath) && file_exists($pdfPath)) {
            $fileName = basename($pdfPath);
            $fileContent = chunk_split(base64_encode(file_get_contents($pdfPath)));

            $message .= "--$boundary\r\n";
            $message .= "Content-Type: application/pdf; name=\"$fileName\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= $fileContent . "\r\n\r\n";
        }

        // End boundary
        $message .= "--$boundary--";

        // Send email
        $sent = mail($to, $subject, $message, $headers);

        return $sent ? "Email sent successfully to: " . implode(", ", $toEmails) : "Failed to send email.";
    }

    // Call function to send email
    echo sendEmailWithAttachment($senderEmail, $senderName, $toEmails, $ccEmails, $subject, $htmlMessage, $pdfPath);
}
?>
