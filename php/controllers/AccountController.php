<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AlunniController
{

  private function DB(){

    $host = getenv('DB_HOST') ?: 'db';
    $database = getenv('DB_DATABASE') ?: 'scuola';
    $username = getenv('DB_USERNAME') ?: 'scuola';
    $password = getenv('DB_PASSWORD') ?: 'scuola';
    $mysqli_connection = new MySQLi($host, $username, $password, $database);
    return $mysqli_connection;

  }

  public function index(Request $request, Response $response, $args){
    $mysqli_connection = $this->DB();

    $idA = $args['idA'];

    $stmt = $mysqli_connection->prepare(
      "SELECT * 
       FROM abbonamento a 
       JOIN account aa ON a.id = aa.id_account 
       WHERE a.id = ?"
    );

    $stmt->bind_param("i", $idA);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function register(Request $request, Response $response, $args){
    $mysqli_connection = $this->DB();
    $data = $request->getParsedBody();
    $tipo = "user";

    if(!isset($data['username']) || !isset($data['password'])){
      $response->getBody()->write(json_encode(["error" => "Missing username or password"]));
      return $response->withHeader("Content-type", "application/json")->withStatus(400);
    }

    if(strlen($data['username']) < 3 || strlen($data['password']) < 6){
      $response->getBody()->write(json_encode(["error" => "Username must be at least 3 characters and password at least 6 characters"]));
      return $response->withHeader("Content-type", "application/json")->withStatus(400);
    }

    $stmt = $mysqli_connection->prepare(
      "SELECT * FROM account WHERE username = ? OR email = ?"
    );
  
    $stmt->bind_param("ss", $data['username'], $data['email']);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0){
      $response->getBody()->write(json_encode(["error" => "Account già esistente"]));
      return $response->withHeader("Content.type", "application/json")->withStatus(400);
    }

    $stmt = $mysqli_connection->prepare(
      "INSERT INTO account (username, email, password, tipo) VALUES (?, ?, ?, ?)"
    );
  
    $stmt->bind_param("ssss", $data['username'], $data['email'], $data['password'], $tipo);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function login(Request $request, Response $response, $args){
    $mysqli_connection = $this->DB();
    $data = $request->getParsedBody();

    $stmt = $mysqli_connection->prepare("SELECT * FROM account a WHERE password = ? AND username = ?");
    $stmt->bind_param("ss", $data['password'], $data['username']);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows == 0){
      $response->getBody()->write(json_encode(["error" => "Credenziali non valide"]));
      return $response->withHeader("Content-type", "application/json")->withStatus(401);
    }

    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function account(Request $request, Response $response, $args){
    $mysqli_connection = $this->DB();
    $id = $args['id'];

    $stmt = $mysqli_connection->prepare("SELECT username, email, tipo FROM account WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function subscriptions(Request $request, Response $response, $args){
    $mysqli_connection = $this->DB();
    $id = $args['idA'];

    $stmt = $mysqli_connection->prepare("SELECT * FROM abbonamento a JOIN account aa ON aa.id = a.id_account WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);


    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function detailsSubscription(Request $request, Response $response, $args){
    $mysqli_connection = $this->DB();
    $id = $args['idA'];
    $idS = $args['idS'];

    $stmt = $mysqli_connection->prepare("SELECT * FROM abbonamento WHERE id_account = ? AND id = ?");
    $stmt->bind_param("ii", $id, $idS);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);


    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function addSubscription(Request $request, Response $response, $args){
    $mysqli_connection = $this->DB();
    $id = $args['idA'];
    $data = $request->getParsedBody();

    $stmt = $mysqli_connection->prepare("INSERT INTO abbonamento (nome, descrizione, data_sottoscrizione, data_scadenza, costo, id_account)
    VALUES ?, ?, ?, ?, ?, ?");
    $stmt->bind_param("ssddni", $data['nome'], $data['descrizione'], $data['data_sottoscrizione'], $data['data_scadenza'], $data['costo'], $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function deleteSubscription(Request $request, Response $response, $args){
    $mysqli_connection = $this->DB();
    $id = $args['idA'];
    $idS = $args['idS'];

    $stmt = $mysqli_connection->prepare("DELETE FROM abbonamento WHERE id = ? AND id_account = ?");
    $stmt->bind_param("ii", $idS, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function updateSubscription(Request $request, Response $response, $args){
    $host = getenv('DB_HOST') ?: 'db';
    $database = getenv('DB_DATABASE') ?: 'scuola';
    $username = getenv('DB_USERNAME') ?: 'scuola';
    $password = getenv('DB_PASSWORD') ?: 'scuola';

    $mysqli_connection = new MySQLi($host, $username, $password, $database);
    $result = $mysqli_connection->query("SELECT * FROM alunni");
    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }
}
