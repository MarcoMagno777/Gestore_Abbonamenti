<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AbbonamentiController extends BaseController
{
    public function index(Request $request, Response $response): Response
    {
        if ($unauthorized = $this->requireUser($response)) {
            return $unauthorized;
        }

        $db = $this->db();
        $user = $this->currentUser();
        $userId = (int) $user['accountId'];

        $stmt = $db->prepare('SELECT * FROM abbonamento WHERE id_account = ? ORDER BY data_scadenza ASC');
        $stmt->bind_param('i', $userId);

        $stmt->execute();

        return $this->json($response, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function create(Request $request, Response $response): Response
    {
        if ($unauthorized = $this->requireUser($response)) {
            return $unauthorized;
        }

        $data = $this->body($request);
        $required = ['nome', 'data_sottoscrizione', 'data_scadenza', 'costo'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return $this->json($response, ['error' => "Campo obbligatorio mancante: $field"], 422);
            }
        }

        $db = $this->db();
        $user = $this->currentUser();
        $idAccount = (int) $user['accountId'];
        $this->ensureAccount($db, $idAccount);

        $nome = trim((string) $data['nome']);
        $descrizione = isset($data['descrizione']) && $data['descrizione'] !== '' ? trim((string) $data['descrizione']) : null;
        $dataSottoscrizione = (string) $data['data_sottoscrizione'];
        $dataScadenza = (string) $data['data_scadenza'];
        $costo = (float) $data['costo'];

        $stmt = $db->prepare(
            'INSERT INTO abbonamento (nome, descrizione, data_sottoscrizione, data_scadenza, costo, id_account) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssdi', $nome, $descrizione, $dataSottoscrizione, $dataScadenza, $costo, $idAccount);
        $stmt->execute();

        return $this->showCreated($response, $db, $stmt->insert_id);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        if ($unauthorized = $this->requireUser($response)) {
            return $unauthorized;
        }

        $id = (int) $args['id'];
        $data = $this->body($request);
        $db = $this->db();
        $user = $this->currentUser();

        $current = $this->find($db, $id);
        if (!$current || (int) $current['id_account'] !== (int) $user['accountId']) {
            return $this->json($response, ['error' => 'Abbonamento non trovato'], 404);
        }

        $nome = $data['nome'] ?? $current['nome'];
        $descrizione = array_key_exists('descrizione', $data) ? $data['descrizione'] : $current['descrizione'];
        $dataSottoscrizione = $data['data_sottoscrizione'] ?? $current['data_sottoscrizione'];
        $dataScadenza = $data['data_scadenza'] ?? $current['data_scadenza'];
        $costo = isset($data['costo']) ? (float) $data['costo'] : (float) $current['costo'];
        $idAccount = (int) $user['accountId'];
        $this->ensureAccount($db, $idAccount);

        $stmt = $db->prepare(
            'UPDATE abbonamento SET nome = ?, descrizione = ?, data_sottoscrizione = ?, data_scadenza = ?, costo = ?, id_account = ? WHERE id = ?'
        );
        $stmt->bind_param('ssssdii', $nome, $descrizione, $dataSottoscrizione, $dataScadenza, $costo, $idAccount, $id);
        $stmt->execute();

        return $this->showCreated($response, $db, $id);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        if ($unauthorized = $this->requireUser($response)) {
            return $unauthorized;
        }

        $id = (int) $args['id'];
        $user = $this->currentUser();
        $db = $this->db();
        $stmt = $db->prepare('DELETE FROM abbonamento WHERE id = ? AND id_account = ?');
        $idAccount = (int) $user['accountId'];
        $stmt->bind_param('ii', $id, $idAccount);
        $stmt->execute();

        return $this->json($response, ['success' => true]);
    }

    private function showCreated(Response $response, MySQLi $db, int $id): Response
    {
        $item = $this->find($db, $id);

        return $this->json($response, $item, 201);
    }

    private function find(MySQLi $db, int $id): ?array
    {
        $stmt = $db->prepare('SELECT * FROM abbonamento WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();

        return $item ?: null;
    }
}
