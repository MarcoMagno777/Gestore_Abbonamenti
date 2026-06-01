<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController extends BaseController
{
    public function accounts(Request $request, Response $response): Response
    {
        if ($forbidden = $this->requireAdmin($response)) {
            return $forbidden;
        }

        $db = $this->db();
        $result = $db->query('SELECT id, username, email, role FROM account ORDER BY id ASC');

        return $this->json($response, $result->fetch_all(MYSQLI_ASSOC));
    }

    public function abbonamenti(Request $request, Response $response): Response
    {
        if ($forbidden = $this->requireAdmin($response)) {
            return $forbidden;
        }

        $db = $this->db();
        $result = $db->query('SELECT * FROM abbonamento ORDER BY data_scadenza ASC');

        return $this->json($response, $result->fetch_all(MYSQLI_ASSOC));
    }

    public function deleteAccount(Request $request, Response $response, array $args): Response
    {
        if ($forbidden = $this->requireAdmin($response)) {
            return $forbidden;
        }

        $id = (int) $args['id'];
        $db = $this->db();

        if ($this->isAdminAccount($db, $id)) {
            return $this->json($response, ['error' => 'Non puoi cancellare admin'], 422);
        }

        $deleteSubscriptions = $db->prepare('DELETE FROM abbonamento WHERE id_account = ?');
        $deleteSubscriptions->bind_param('i', $id);
        $deleteSubscriptions->execute();

        $deleteAccount = $db->prepare('DELETE FROM account WHERE id = ?');
        $deleteAccount->bind_param('i', $id);
        $deleteAccount->execute();

        return $this->json($response, ['success' => true]);
    }

    public function deleteAbbonamento(Request $request, Response $response, array $args): Response
    {
        if ($forbidden = $this->requireAdmin($response)) {
            return $forbidden;
        }

        $id = (int) $args['id'];
        $db = $this->db();
        $stmt = $db->prepare('DELETE FROM abbonamento WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $this->json($response, ['success' => true]);
    }

    public function countUsers(Request $request, Response $response): Response
    {
        if ($forbidden = $this->requireAdmin($response)) {
            return $forbidden;
        }

        $db = $this->db();
        $result = $db->query('SELECT COUNT(*) AS total FROM account');
        $row = $result->fetch_assoc();

        return $this->json($response, ['total' => (int) $row['total']]);
    }

    public function deleteAllUsers(Request $request, Response $response): Response
    {
        if ($forbidden = $this->requireAdmin($response)) {
            return $forbidden;
        }

        $db = $this->db();
        $db->query("DELETE a FROM abbonamento a INNER JOIN account u ON a.id_account = u.id WHERE u.role <> 'ADMIN'");
        $db->query("DELETE FROM account WHERE role <> 'ADMIN'");

        return $this->json($response, ['success' => true]);
    }

    public function resetPassword(Request $request, Response $response, array $args): Response
    {
        if ($forbidden = $this->requireAdmin($response)) {
            return $forbidden;
        }

        $id = (int) $args['id'];
        $db = $this->db();

        if ($this->isAdminAccount($db, $id)) {
            return $this->json($response, ['error' => 'Non puoi resettare la password admin'], 422);
        }

        $defaultPassword = '1234@';
        $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE account SET password = ? WHERE id = ?');
        $stmt->bind_param('si', $hash, $id);
        $stmt->execute();

        return $this->json($response, ['success' => true, 'defaultPassword' => $defaultPassword]);
    }

    private function isAdminAccount(MySQLi $db, int $id): bool
    {
        $stmt = $db->prepare("SELECT role FROM account WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        return $account && $account['role'] === 'ADMIN';
    }
}
