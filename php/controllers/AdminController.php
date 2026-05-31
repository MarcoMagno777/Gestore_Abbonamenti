<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController
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

    $stmt = $mysqli_connection->prepare(
      "SELECT * 
       FROM account a WHERE a.tipo = ?"
    );

    $stmt->bind_param("s", "user");
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function deleteAccounts(Request $request, Response $response, $args){
    $mysqli_connection = $this->DB();

    $stmt = $mysqli_connection->prepare(
      "DELETE account 
       WHERE tipo = ?"
    );

    $stmt->bind_param("s", "user");
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0){
      $response->getBody()->write(json_encode(["error" => "Account non trovato"]));
      return $response->withHeader("Content-type", "application/json")->withStatus(401);
    }

    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function deleteAccount(Request $request, Response $response, $args){
    $mysqli_connection = $this->DB();
    $idA = $args['idA'];

    $stmt = $mysqli_connection->prepare(
      "DELETE account 
       WHERE id = ?"
    );

    $stmt->bind_param("i", $idA);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0){
      $response->getBody()->write(json_encode(["error" => "Account non trovato"]));
      return $response->withHeader("Content-type", "application/json")->withStatus(401);
    }

    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function updatePassword(Request $request, Response $response, $args){
    $mysqli_connection = $this->DB();
    $idA = $args['idA'];

    $stmt = $mysqli_connection->prepare(
      "UPDATE account SET password = ? WHERE id = ?"
    );

    $stmt->bind_param("is", $idA, "password");
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0){
      $response->getBody()->write(json_encode(["error" => "Account non trovato"]));
      return $response->withHeader("Content-type", "application/json")->withStatus(401);
    }

    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

}
