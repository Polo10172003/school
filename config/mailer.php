<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('mailer_default_config')) {
    /**
     * Base mailer configuration with sane defaults for Hostinger SMTP.
     *
     * @return array<string,mixed>
     */
    function mailer_default_config(): array
    {
        $base = [
            'host'        => getenv('SMTP_HOST') ?: 'smtp.hostinger.com',
            'username'    => getenv('SMTP_USERNAME') ?: 'no-reply@rosariodigital.site',
            'password'    => getenv('SMTP_PASSWORD') ?: 'Dan@65933',
            'from_email'  => getenv('SMTP_FROM') ?: 'no-reply@rosariodigital.site',
            'from_name'   => getenv('SMTP_FROM_NAME') ?: 'Escuela De Sto. Rosario',
            'charset'     => 'UTF-8',
            'encoding'    => 'base64',
            'timeout'     => 20,
            'smtp_options' => [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ],
            'fallback_to_mail' => filter_var(
                getenv('SMTP_ALLOW_MAIL_FALLBACK'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ),
        ];

        if ($base['fallback_to_mail'] === null) {
            $base['fallback_to_mail'] = true;
        }

        $debugLevel = getenv('SMTP_DEBUG_LEVEL');
        if ($debugLevel !== false) {
            $base['debug_level'] = (int) $debugLevel;
        }

        $debugLog = getenv('SMTP_DEBUG_LOG');
        if ($debugLog !== false) {
            $base['debug_log'] = $debugLog;
        }

        return $base;
    }

    /**
     * Apply reusable defaults to a PHPMailer instance.
     *
     * @param PHPMailer             $mail
     * @param array<string,mixed>   $overrides
     *
     * @return array<string,mixed>  The effective configuration.
     */
    function mailer_apply_defaults(PHPMailer $mail, array $overrides = []): array
    {
        $config = array_merge(mailer_default_config(), $overrides);

        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPAutoTLS = true;
        $mail->CharSet = (string) $config['charset'];
        $mail->Encoding = (string) $config['encoding'];
        $mail->Host = (string) $config['host'];
        $mail->Username = (string) $config['username'];
        $mail->Password = (string) $config['password'];
        $mail->Timeout = (int) $config['timeout'];
        $mail->SMTPOptions = $config['smtp_options'] ?? [];

        if (!empty($config['from_email'])) {
            $mail->setFrom((string) $config['from_email'], (string) ($config['from_name'] ?? ''));
        }

        if (!empty($config['debug_level'])) {
            $mail->SMTPDebug = (int) $config['debug_level'];
        }

        if (!empty($config['debug_log'])) {
            $logPath = (string) $config['debug_log'];
            $mail->Debugoutput = static function (string $str, int $level) use ($logPath): void {
                @file_put_contents(
                    $logPath,
                    sprintf("[%s][lvl:%d] %s\n", date('c'), $level, $str),
                    FILE_APPEND
                );
            };
        }

        return $config;
    }

    /**
     * Default transport attempts ordered by preference.
     *
     * @return array<int,array<string,mixed>>
     */
    function mailer_default_transports(): array
    {
        return [
            [
                'label'  => 'starttls://587',
                'secure' => PHPMailer::ENCRYPTION_STARTTLS,
                'port'   => (int) (getenv('SMTP_PORT_TLS') ?: 587),
                'smtp_auto_tls' => true,
            ],
            [
                'label'  => 'smtps://465',
                'secure' => PHPMailer::ENCRYPTION_SMTPS,
                'port'   => (int) (getenv('SMTP_PORT_SSL') ?: 465),
                'smtp_auto_tls' => false,
            ],
        ];
    }

    /**
     * Attempt to send the message trying multiple SMTP transports when needed.
     *
     * @param PHPMailer                      $mail
     * @param array<int,array<string,mixed>> $transports
     * @param callable|null                  $logger Receives log lines for diagnostics.
     *
     * @throws Exception
     */
    function mailer_send_with_fallback(
        PHPMailer $mail,
        array $transports = [],
        ?callable $logger = null,
        bool $fallbackToMail = false
    ): void {
        $attempts = $transports ?: mailer_default_transports();
        $lastException = null;

        foreach ($attempts as $transport) {
            $label = $transport['label'] ?? sprintf(
                '%s:%s',
                (string) ($transport['secure'] ?? ''),
                (string) ($transport['port'] ?? '')
            );

            $mail->SMTPSecure = $transport['secure'] ?? PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = (int) ($transport['port'] ?? 465);
            $mail->SMTPAutoTLS = isset($transport['smtp_auto_tls'])
                ? (bool) $transport['smtp_auto_tls']
                : ($mail->SMTPSecure === PHPMailer::ENCRYPTION_STARTTLS);

            if ($logger) {
                $logger(sprintf('Attempting SMTP via %s', $label));
            }

            try {
                $mail->send();
                if ($logger) {
                    $logger(sprintf('SMTP via %s succeeded', $label));
                }
                return;
            } catch (Exception $exception) {
                $lastException = $exception;
                $mail->smtpClose();

                if ($logger) {
                    $logger(sprintf(
                        'SMTP via %s failed: %s',
                        $label,
                        $exception->getMessage()
                    ));
                }
            }
        }

        if ($fallbackToMail) {
            if ($logger) {
                $logger('All SMTP attempts failed, falling back to PHP mail() transport.');
            }
            $mail->isMail();
            $mail->SMTPDebug = 0;
            $mail->Debugoutput = 'error_log';
            $mail->send();
            return;
        }

        if ($lastException instanceof Exception) {
            throw $lastException;
        }

        throw new Exception('Mailer encountered an unknown error.');
    }
}
