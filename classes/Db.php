<?php

class Db {

    private $conn; //Database variable
    private $select_result; //result

    public function __construct() { //polaczenie
        $host = "localhost";
        $username = "root";
        $password = "";
        $dbname = "news";

        try {
            $this->conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            echo nl2br("\n\r");
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage() . "";
            die();
        }
    }

    public function getDB() { //funkcja przekazujaca baze
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }
    }

    function __destruct() { //zamykanie
        $this->conn = null;
    }

    public function select($sql) { //zapytanie sql
//parameter $sql – select string
//variable $results – association table with querry results
        return $this->conn->prepare($sql); //
    }

    //dodawanie wiadomosci
    public function addMessage($name, $type, $content) {
        if(isset($_SESSION['add message'])) {
            
            $login = $_SESSION['logged'];
            $sql_id = "SELECT u.id FROM user u"
            . " WHERE u.login=:login"
            . " LIMIT 1";

            $stmt = $this->conn->prepare($sql_id);
            $stmt->execute(['login' => $login]);
            $user_id = $stmt->fetch();
            $user_id= $user_id[0];
            
        
            $sql = "INSERT INTO message (`name`,`type`, `message`,`deleted`,`id_user`)
        VALUES ('" . filter_var($name, FILTER_SANITIZE_STRING) . "','" . $type . "','" . $content . "',0,'" . $user_id . "')";
            //$sql = "INSERT INTO message (`name`,`type`, `message`,`deleted`)
//VALUES ('" . $name . "','" . $type . "','" . addslashes($content) . "',0)"; //bez zabezpieczenia
            echo $sql;
            echo "<BR\>";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt;
            //return $this->conn->prepare($sql);
        } else {
            echo "brak uprawnien";
        }
        
    }
    
    //pobieranie wiadomosci
    public function getMessage($message_id) {
        foreach ($this->select_result as $message):
            if ($message->id == $message_id) {
                return $message->message;
            }
        endforeach;
    }

    //pobieranie danych widomosci na podstawie id (do edycji)
    public function getMessageById($messageId) {
        $sql = "SELECT * FROM message WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $messageId);
        $stmt->execute();

        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        return $message;
    }
    
    //sprawdzanie czy uzytkownik jest autorem wiadomosci
    private function messageAuthor($id) {
    $login = $_SESSION['logged'];
    //$sql = "SELECT id FROM message WHERE id=:id AND author_login=:author_login";
    $sql = "SELECT id FROM message WHERE id=:id AND id_user = (SELECT id FROM user WHERE login=:login)"; 
    $stmt = $this->conn->prepare($sql);
    $stmt->execute(['id' => $id, 'login' => $login]);
    $result = $stmt->fetch();
    
    return !empty($result); //gdy bedzie wynik - jest autorem
}
    



    //edycja wiadomosci
    public function editMessage($id, $name, $type, $content) {
        if(isset($_SESSION['edit message']) || $this->messageAuthor($id))
        {
        
            $sql = "UPDATE message SET name='" . filter_var($name, FILTER_SANITIZE_STRING) . "', type='" . $type . "', message='" . filter_var($content, FILTER_SANITIZE_STRING) . "' WHERE id='" . filter_var($id, FILTER_VALIDATE_INT) . "'";
            echo $sql;
            echo "<BR\>";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt;
            //return $this->conn->prepare($sql);
             }
     else {
        echo "brak uprawnien";
    }
    }

    //usuwanie wiadomosci
    public function delete_message($id) {
        
           if(isset($_SESSION['delete message']) || $this->messageAuthor($id))  {
            if (isset($_POST['delete_message'])) {
            if (filter_var($id, FILTER_VALIDATE_INT)) {
                try {
                    $sql = "UPDATE `message` SET `deleted`=1 WHERE `id`=:id";
                    $data = [
                        'id' => $id
                    ];
                    $this->conn->prepare($sql)->execute($data);
                    return true;
                } catch (Exception $e) {
                    print 'Exception' . $e->getMessage();
                }
            }
        }
           } else {
               echo "brak uprawnien";
           }
        
            
        
        
        
    }

    // dodaj uprawnienia do usera
    public function addUserPrivilege($userId, $privilegeId) {
        $sql = "INSERT INTO user_privilege (id_user, id_privilege) VALUES (:userId, :privilegeId)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':privilegeId', $privilegeId);
        $stmt->execute();
    }

    // usun uprawnienia od usera
    public function removeUserPrivilege($userId, $privilegeId) {
        $sql = "DELETE FROM user_privilege WHERE id_user = :userId AND id_privilege = :privilegeId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':privilegeId', $privilegeId);
        $stmt->execute();
    }

    // tworzenie roli
    public function createRole($roleName, $description) {
        $sql = "INSERT INTO role (role_name, description) VALUES (:roleName, :description)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':roleName', $roleName);
        $stmt->bindParam(':description', $description);
        $stmt->execute();
    }

    // usuwanie roli
    public function deleteRole($roleId) {
        $roleId = filter_var($roleId, FILTER_VALIDATE_INT);

        try {
            // usuwanie powiazanych rekordow z role_privilege
            $deleteRolePrivilegeSql = "DELETE FROM role_privilege WHERE id_role = :roleId";
            $deleteRolePrivilegeStmt = $this->conn->prepare($deleteRolePrivilegeSql);
            $deleteRolePrivilegeStmt->execute(['roleId' => $roleId]);

            // uswanie roli
            $deleteRoleSql = "DELETE FROM role WHERE id = :roleId";
            $deleteRoleStmt = $this->conn->prepare($deleteRoleSql);
            $deleteRoleStmt->execute(['roleId' => $roleId]);

        } catch (PDOException $e) {
            echo "Exception: " . $e->getMessage();
        }
    }

    // dodaj upranienia do roli
    public function addRolePrivilege($roleId, $privilegeId) {
        $sql = "INSERT INTO role_privilege (id_role, id_privilege) VALUES (:roleId, :privilegeId)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':roleId', $roleId);
        $stmt->bindParam(':privilegeId', $privilegeId);
        $stmt->execute();
    }

    // usun uprawnienie z roli
    public function removeRolePrivilege($roleId, $privilegeId) {
        $sql = "DELETE FROM role_privilege WHERE id_role = :roleId AND id_privilege = :privilegeId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':roleId', $roleId);
        $stmt->bindParam(':privilegeId', $privilegeId);
        $stmt->execute();
    }

    // przypisz role do uzytkownika
    public function assignUserRole($roleId, $userId) {
        $sql = "INSERT INTO user_role (id_role, id_user) VALUES (:roleId, :userId)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':roleId', $roleId);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
    }

    // usun role od uzytkownika
    public function removeUserRole($roleId, $userId) {
        $sql = "DELETE FROM user_role WHERE id_role = :roleId AND id_user = :userId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':roleId', $roleId);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
    }

}

?>
