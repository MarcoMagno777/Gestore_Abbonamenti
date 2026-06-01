<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController extends BaseController
{
    public function login(Request $request, Response $response): Response
    {
        $data = $this->body($request);
        $username = strtolower(trim((string) ($data['username'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->json($response, ['error' => 'Username e password obbligatori'], 422);
        }

        $db = $this->db();
        $stmt = $db->prepare('SELECT id, username, email, password, role FROM account WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        if (!$account || !password_verify($password, $account['password'])) {
            return $this->json($response, ['error' => 'Credenziali non valide'], 401);
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $this->publicUser($account);

        return $this->json($response, $_SESSION['user']);
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $this->body($request);
        $username = strtolower(trim((string) ($data['username'] ?? '')));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            return $this->json($response, ['error' => 'Compila tutti i campi'], 422);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'USER';
        $db = $this->db();

        try {
            $stmt = $db->prepare('INSERT INTO account (username, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $username, $email, $hash, $role);
            $stmt->execute();
            $accountId = $stmt->insert_id;
        } catch (mysqli_sql_exception $exception) {
            if ((int) $exception->getCode() === 1062) {
                return $this->json($response, ['error' => 'Username o email già registrati'], 409);
            }

            throw $exception;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (string) $accountId,
            'accountId' => (int) $accountId,
            'role' => $role,
            'name' => $username,
            'email' => $email,
        ];

        return $this->json($response, $_SESSION['user'], 201);
    }

    public function me(Request $request, Response $response): Response
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->json($response, ['authenticated' => false], 401);
        }

        return $this->json($response, $user);
    }

    public function logout(Request $request, Response $response): Response
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        return $this->json($response, ['success' => true]);
    }

    private function publicUser(array $account): array
    {
        return [
            'id' => (string) $account['id'],
            'accountId' => (int) $account['id'],
            'role' => $account['role'],
            'name' => $account['username'],
            'email' => $account['email'],
        ];
    }
}
