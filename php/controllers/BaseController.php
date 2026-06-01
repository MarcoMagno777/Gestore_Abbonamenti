<?php

use Psr\Http\Message\ResponseInterface as Response;

class BaseController
{
    protected function db(): MySQLi
    {
        $host = getenv('DB_HOST') ?: 'db';
        $database = getenv('DB_DATABASE') ?: 'scuola';
        $username = getenv('DB_USERNAME') ?: 'scuola';
        $password = getenv('DB_PASSWORD') ?: 'scuola';

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = new MySQLi($host, $username, $password, $database);
        $connection->set_charset('utf8mb4');
        $this->migrate($connection);

        return $connection;
    }

    protected function json(Response $response, mixed $payload, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    protected function body($request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed)) {
            return $parsed;
        }

        $raw = (string) $request->getBody();
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function ensureAccount(MySQLi $db, int $id): void
    {
        $stmt = $db->prepare('SELECT id FROM account WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($stmt->get_result()->fetch_assoc()) {
            return;
        }

        $username = 'utente-' . $id;
        $email = 'utente-' . $id . '@submanager.local';
        $password = password_hash('password', PASSWORD_DEFAULT);

        $insert = $db->prepare('INSERT IGNORE INTO account (id, username, email, password) VALUES (?, ?, ?, ?)');
        $insert->bind_param('isss', $id, $username, $email, $password);
        $insert->execute();
    }

    protected function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function requireUser(Response $response): ?Response
    {
        if ($this->currentUser()) {
            return null;
        }

        return $this->json($response, ['error' => 'Login richiesto'], 401);
    }

    protected function requireAdmin(Response $response): ?Response
    {
        $user = $this->currentUser();
        if ($user && ($user['role'] ?? '') === 'ADMIN') {
            return null;
        }

        return $this->json($response, ['error' => 'Accesso admin richiesto'], 403);
    }

    private function migrate(MySQLi $db): void
    {
        $db->query(
            "CREATE TABLE IF NOT EXISTS account (
                id INT(11) NOT NULL AUTO_INCREMENT,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'USER',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $column = $db->query("SHOW COLUMNS FROM account LIKE 'role'");
        if ($column->num_rows === 0) {
            $db->query("ALTER TABLE account ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'USER'");
        }

        $db->query(
            "CREATE TABLE IF NOT EXISTS abbonamento (
                id INT(11) NOT NULL AUTO_INCREMENT,
                nome VARCHAR(255) NOT NULL,
                descrizione VARCHAR(255),
                data_sottoscrizione DATE NOT NULL,
                data_scadenza DATE NOT NULL,
                costo DECIMAL(6,2) NOT NULL,
                id_account INT(11) NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_abbonamento_account (id_account),
                CONSTRAINT fk_abbonamento_account
                    FOREIGN KEY (id_account) REFERENCES account(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            "INSERT INTO account (username, email, password, role)
             VALUES ('admin', 'admin@submanager.local', ?, 'ADMIN')
             ON DUPLICATE KEY UPDATE password = VALUES(password), role = 'ADMIN'"
        );
        $stmt->bind_param('s', $adminPassword);
        $stmt->execute();
    }
}
