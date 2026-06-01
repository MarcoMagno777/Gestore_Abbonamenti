<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    public function index(Request $request, Response $response): Response
    {
        if ($unauthorized = $this->requireUser($response)) {
            return $unauthorized;
        }

        $user = $this->currentUser();
        $userId = (int) $user['accountId'];
        $db = $this->db();

        $count = $this->scalar($db, 'SELECT COUNT(*) FROM abbonamento WHERE id_account = ?', $userId);
        $monthlyTotal = $this->scalar($db, 'SELECT COALESCE(SUM(costo), 0) FROM abbonamento WHERE id_account = ?', $userId);

        $stmt = $db->prepare('SELECT * FROM abbonamento WHERE id_account = ? ORDER BY data_scadenza ASC LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $nextRenewal = $stmt->get_result()->fetch_assoc() ?: null;

        return $this->json($response, [
            'activeSubscriptions' => (int) $count,
            'monthlyTotal' => (float) $monthlyTotal,
            'nextRenewal' => $nextRenewal,
        ]);
    }

    private function scalar(MySQLi $db, string $sql, int $userId): int|float
    {
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();

        return $row ? (float) $row[0] : 0;
    }
}
