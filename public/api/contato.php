<?php
declare(strict_types=1);
use PHPMailer\PHPMailer\PHPMailer;
ini_set('display_errors', '0');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
function respond(int $code, bool $ok, string $message): never
{
    http_response_code($code);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Contato IMPTEC</title><body><h1>Contato IMPTEC</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p><a href="/#contato">Voltar ao site</a></body></html>';
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, false, 'Utilize o formulário do site.');
}
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384)
    respond(413, false, 'Solicitação muito grande.');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !in_array($origin, ['https://imp-tec.com', 'https://www.imp-tec.com'], true))
    respond(403, false, 'Origem não permitida.');
function field(string $key): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
}
if (field('website') !== '')
    respond(200, true, 'Solicitação recebida.');
$name = field('name');
$email = field('email');
$phone = field('phone');
$type = field('projectType');
$description = field('description');
$types = ['Automação Jurídica', 'Automação Contábil', 'Automação Bancária', 'Cálculos Trabalhistas', 'Integração via API', 'Desenvolvimento de Sistema', 'Outro'];
function length(string $s): int
{
    return preg_match_all('/./us', $s) ?: 0;
}
if (length($name) < 2 || length($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || preg_match('/[\r\n]/', $name . $email) || length($phone) < 6 || length($phone) > 30 || !in_array($type, $types, true) || length($description) < 10 || length($description) > 500)
    respond(422, false, 'Confira nome, e-mail, telefone, tipo de projeto e descrição (10 a 500 caracteres).');
// Limite por IP, sem confiar em cabeçalhos enviados pelo visitante.
$rateFile = sys_get_temp_dir() . '/imptec-contact-' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown') . '.json';
$handle = fopen($rateFile, 'c+');
if (!$handle || !flock($handle, LOCK_EX))
    respond(503, false, 'Serviço temporariamente indisponível.');
$history = json_decode(stream_get_contents($handle), true);
$history = array_values(array_filter(is_array($history) ? $history : [], fn($time) => is_int($time) && $time > time() - 600));
if (count($history) >= 5) {
    flock($handle, LOCK_UN);
    fclose($handle);
    respond(429, false, 'Aguarde alguns minutos antes de enviar novamente.');
}
$history[] = time();
rewind($handle);
ftruncate($handle, 0);
fwrite($handle, json_encode($history));
flock($handle, LOCK_UN);
fclose($handle);
try {
    $base = dirname(__DIR__, 2);
    if (!is_file($base . '/vendor/autoload.php') || !is_file($base . '/config/email.php'))
        throw new RuntimeException('Instale as dependências e configure config/email.php.');
    require $base . '/vendor/autoload.php';
    $config = require $base . '/config/email.php';
    if (empty($config['password']) || $config['password'] === 'PREENCHA_A_SENHA_DA_CAIXA')
        throw new RuntimeException('SMTP sem senha configurada.');
    $create = function () use ($config): PHPMailer {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = $config['host'];
        $mail->Port = (int) $config['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout = 20;
        $mail->setFrom($config['username'], $config['sender_name']);
        return $mail;
    };
    $mail = $create();
    $mail->addAddress($config['recipient']);
    $mail->addReplyTo($email, $name);
    $mail->Subject = 'Novo contato pelo site IMPTEC';
    $mail->Body = "Nome: $name\nE-mail: $email\nTelefone: $phone\nTipo de projeto: $type\n\nDescrição:\n$description";
    $mail->send();
} catch (Throwable $e) {
    error_log('IMPTEC contato: ' . get_class($e) . ' ' . $e->getMessage());
    respond(502, false, 'Não foi possível enviar agora. Tente novamente ou fale pelo WhatsApp.');
}
// A confirmação é secundária: uma falha não deve pedir reenvio de um lead já recebido.
try {
    $confirmation = $create();
    $confirmation->addAddress($email, $name);
    $confirmation->Subject = 'Recebemos sua solicitação | IMPTEC';
    $confirmation->Body = "Olá, $name!\n\nRecebemos sua solicitação sobre $type. Nossa equipe entrará em contato em breve.\n\nAtenciosamente,\nEquipe IMPTEC";
    $confirmation->send();
} catch (Throwable $e) {
    error_log('IMPTEC confirmação: ' . get_class($e) . ' ' . $e->getMessage());
}
respond(200, true, 'Solicitação enviada com sucesso! Nossa equipe entrará em contato em breve.');
