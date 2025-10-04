<?php
namespace controllers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController {

    public function enviarConfirmacion() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar que el usuario esté autenticado
        if (!isset($_SESSION['estudiante_id'])) {
            // Manejar el caso de que no haya sesión
            echo "Error: Sesión no iniciada.";
            return;
        }

        // Datos del estudiante desde la sesión
        $nombre_completo = $_SESSION['nombre_completo'] ?? 'Estudiante';
        $correo_estudiante = $_SESSION['correo'] ?? ''; // Necesitaremos el correo del estudiante
        $nombre_personero = $_SESSION['nombre_personero'] ?? 'No registrado';
        $nombre_representante = $_SESSION['nombre_representante'] ?? 'No registrado';
        $grado = $_SESSION['grado'] ?? '';
        $id_verificacion = strtoupper(substr(md5($_SESSION['estudiante_id'] . time()), 0, 12));

        if (empty($correo_estudiante)) {
            $_SESSION['mensaje_correo'] = "Error: No se encontró el correo en la sesión. Por favor, cierre la sesión y vuelva a iniciarla.";
            $_SESSION['tipo_correo'] = 'danger';
            header("Location: /Login/views/confirmacion.php");
            exit();
        }

        // Contenido del correo
        $body = "<h1>¡Gracias por tu voto, " . htmlspecialchars($nombre_completo) . "!</h1>";
        $body .= "<p>Tu voto ha sido registrado correctamente en nuestro sistema.</p>";
        $body .= "<h2>Resumen de tu votación:</h2>";
        $body .= "<ul>";
        $body .= "<li><b>Personero:</b> " . htmlspecialchars($nombre_personero) . "</li>";
        $body .= "<li><b>Representante (Grado " . htmlspecialchars($grado) . "):</b> " . htmlspecialchars($nombre_representante) . "</li>";
        $body .= "</ul>";
        $body .= "<p><b>ID de Verificación:</b> " . $id_verificacion . "</p>";
        $body .= "<p>Gracias por participar en este proceso democrático.</p>";

        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // CAMBIAR: Tu servidor SMTP
            $mail->SMTPAuth   = true;
            $mail->Username   = 'wilmer.andres.romero1991@gmail.com'; // CAMBIAR: Tu usuario SMTP
            $mail->Password   = 'advvkowyotibynec';           // CAMBIAR: Tu contraseña SMTP
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Opciones de SMTPOptions para entornos de desarrollo locales (XAMPP)
            // Esto soluciona el error "certificate verify failed"
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Remitente y destinatario
            $mail->setFrom('no-reply@example.com', 'Sistema de Votación');
            $mail->addAddress($correo_estudiante, $nombre_completo);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Confirmación de tu Voto';
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            $_SESSION['mensaje_correo'] = 'Confirmación enviada a tu correo electrónico.';
            $_SESSION['tipo_correo'] = 'success';
        } catch (Exception $e) {
            $_SESSION['mensaje_correo'] = "No se pudo enviar el correo. Mailer Error: {$mail->ErrorInfo}";
            $_SESSION['tipo_correo'] = 'danger';
        }

        header("Location: /Login/views/confirmacion.php");
        exit();
    }
}