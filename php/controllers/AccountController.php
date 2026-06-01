<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AccountController extends BaseController
{
    public function show(Request $request, Response $response, array $args): Response
    {
        if ($unauthorized = $this->requireUser($response)) {
            return $unauthorized;
        }

        $user = $this->currentUser();
        $id = (int) $user['accountId'];
        $db = $this->db();

        $stmt = $db->prepare('SELECT id, username, email FROM account WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $this->json($response, $stmt->get_result()->fetch_assoc());
    }
}
