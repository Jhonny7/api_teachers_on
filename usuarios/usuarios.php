<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: text/html; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"'); 

require_once '../include/DbHandler.php'; 
require_once '../services/fcm_service.php'; 

require '../libs/Slim/Slim.php'; 

\Slim\Slim::registerAutoloader(); 
$app = new \Slim\Slim(); 


/* Obtener todos los usuarios del sistema*/
$app->get('/getUsuarios', function(){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        //$resultado = $db->getRadios();
        $sql = 'SELECT id,
                id_rol,
                nombre,
                apellido_paterno,
                apellido_materno,
                email FROM usuario WHERE id_rol != 1';

        $array = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $response["status"] = "A";
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $array;
        $response["timeRequest"] = date("Y-m-d H:i:s");

        echoResponse(200, $response);
    }catch(Exception $e){
        $response["status"] = "I";
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $e;
        $response["timeRequest"] = date("Y-m-d H:i:s");

        echoResponse(400, $response);
    }
});

/* Cambio de contraseña por usuario */
$app->post('/changePassword', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $sqlExist = 'SELECT * FROM usuario WHERE password = MD5(?) AND id = ?';
        $password = $data['password'];
        $idUsuario = $data['idUsuario'];
        $newPassword = $data['newPassword'];
        $passwordEncriptado = dec_enc('encrypt',$password);
        $sthSqlExist = $db->prepare($sqlExist);
        $sthSqlExist->bindParam(1, $passwordEncriptado, PDO::PARAM_STR);
        $sthSqlExist->bindParam(2, $idUsuario, PDO::PARAM_INT);
        $sthSqlExist->execute();
        $rows = $sthSqlExist->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($rows)){

            $sqlUpdatePassword = 'UPDATE usuario SET password = MD5(?) WHERE id = ?';
            $newPasswordEncriptado = dec_enc('encrypt',$newPassword);
            $sthSqlUpdatePassword = $db->prepare($sqlUpdatePassword);
            $sthSqlUpdatePassword->bindParam(1, $newPasswordEncriptado, PDO::PARAM_STR);
            $sthSqlUpdatePassword->bindParam(2, $idUsuario, PDO::PARAM_INT);
            $sthSqlUpdatePassword->execute();
            $rows = $sthSqlUpdatePassword->fetchAll(PDO::FETCH_ASSOC);

            $querieNotifi = 'SELECT * FROM usuario WHERE id_rol = 1';
            $sthAdmin = $db->prepare($querieNotifi);
            $sthAdmin->execute();
            $rowsAdmin = $sthAdmin->fetchAll(PDO::FETCH_ASSOC);

            $envio = "";
            if($rowsAdmin[0] != null && $rowsAdmin[0]['token'] != null){
                $envio = "Se envia notificacion";
                $token = $rowsAdmin[0]['token'];

                $fcm = new FCMNotification();
                $title = "Cambio de contraseña";
                $body = "Que tal ".$rowsAdmin[0]['nombre'].", te informamos que ".$rows[0]['nombre']." ha actualizado su contraseña.";
                $notification = array('title' =>$title , 'body' => $body, 'sound' => 'default');
                $arrayToSend = array('to' => $token, 'notification' => $notification,'priority'=>'high');

                $return = $fcm->sendData($arrayToSend);
            }else{
                $envio = "No se envia notificacion";
            }

            $response["status"] = "A";
            $response["description"] = "Exitoso";
            $response["idTransaction"] = time();
            $response["parameters"] = $envio;
            $response["timeRequest"] = date("Y-m-d H:i:s");

            echoResponse(200, $response);
        }else{
            $response["status"] = "I";
            $response["description"] = "Password incorrecto favor de verificar correctamente";
            $response["idTransaction"] = time();
            $response["parameters"] = [];
            $response["timeRequest"] = date("Y-m-d H:i:s");

            echoResponse(400, $response);
        }
    }catch(Exception $e){

        $response["status"] = "I";
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $e;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(400, $response);
    }
});

/* Actualizar usuario */
$app->post('/updateUsuario', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();

        $updateUsuario = 'UPDATE usuario SET id_status = ?, nombre = ?, apellido_paterno = ?, apellido_materno = ?, email = ? WHERE id = ?';

        $idStatus = $data['idStatus'];
        $nombre = $data['nombre'];
        $apellido_paterno = $data['apellido_paterno'];
        $apellido_materno = $data['apellido_materno'];
        $email = $data['email'];
        $id = $data['id'];

        $sthUsuario = $db->prepare($updateUsuario);

        $sthUsuario->bindParam(1, $idStatus, PDO::PARAM_INT);
        $sthUsuario->bindParam(2, $nombre, PDO::PARAM_STR);
        $sthUsuario->bindParam(3, $apellido_paterno, PDO::PARAM_STR);
        $sthUsuario->bindParam(4, $apellido_materno, PDO::PARAM_STR);
        $sthUsuario->bindParam(5, $email, PDO::PARAM_STR);
        $sthUsuario->bindParam(6, $id, PDO::PARAM_INT);

        $sthUsuario->execute();


        //Commit exitoso de transacción
        $db->commit();

        $response["status"] = "A";
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $id;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(200, $response);

    }catch(Exception $e){
        $db->rollBack();
        $response["status"] = "I";
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $e;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(400, $response);
    }
});

/* Crear usuario */
$app->post('/createUsuario', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();

        



        $createUsuario = 'INSERT INTO usuario (`id_rol`, `id_status`, `username`, `password`, `fecha_alta`, `nombre`, `apellido_paterno`, `apellido_materno`, `email`) VALUES (?, 1, ?, MD5(?), now(), ?, ?, ?, ?)';

        $idRol = $data['idRol'];
        $userName = $data['userName'];
        $password = $data['password'];
        $passwordEncriptado = dec_enc('encrypt',$password);

        $nombre = $data['nombre'];
        $apellido_paterno = $data['apellidoPaterno'];
        $apellido_materno = $data['apellidoMaterno'];
        $email = $data['email'];

        //
        $querieVerifyUser = 'SELECT id FROM usuario WHERE LOWER(username) = LOWER(?)';
        $sth = $db->prepare($querieVerifyUser);
        $sth->bindParam(1, $userName, PDO::PARAM_STR);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
        //
        if(empty($rows)){
            $sthUsuario = $db->prepare($createUsuario);

            $sthUsuario->bindParam(1, $idRol, PDO::PARAM_INT);
            $sthUsuario->bindParam(2, $userName, PDO::PARAM_STR);
            $sthUsuario->bindParam(3, $passwordEncriptado, PDO::PARAM_STR);

            $sthUsuario->bindParam(4, $nombre, PDO::PARAM_STR);
            $sthUsuario->bindParam(5, $apellido_paterno, PDO::PARAM_STR);
            $sthUsuario->bindParam(6, $apellido_materno, PDO::PARAM_STR);
            $sthUsuario->bindParam(7, $email, PDO::PARAM_STR);

            $sthUsuario->execute();
            $idUsuario = $db->lastInsertId();

            //Commit exitoso de transacción
            $db->commit();

            $response["status"] = "A";
            $response["description"] = "Exitoso";
            $response["idTransaction"] = time();
            $response["parameters"] = $idUsuario;
            $response["timeRequest"] = date("Y-m-d H:i:s");
            echoResponse(200, $response);
        }else{
            $response["status"] = "I";
            $response["description"] = "El usuario ".$userName." ya está en uso, intente con uno distinto.";
            $response["idTransaction"] = time();
            $response["parameters"] = [];
            $response["timeRequest"] = date("Y-m-d H:i:s");
            echoResponse(200, $response);
        }
        //
    }catch(Exception $e){
        $db->rollBack();
        $response["status"] = "I";
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $e;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(400, $response);
    }
});

/* Borrar usuario */
$app->post('/deleteUsuario', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();

        $id = $data['id'];
        $deleteUsuario = 'DELETE FROM usuario WHERE (`id` = ?)';

        $sthUsuario = $db->prepare($deleteUsuario);

        $sthUsuario->bindParam(1, $id, PDO::PARAM_INT);

        $sthUsuario->execute();

        //Commit exitoso de transacción
        $db->commit();

        $response["status"] = "A";
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $id;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(200, $response);

    }catch(Exception $e){
        $db->rollBack();
        $response["status"] = "I";
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $e;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(400, $response);
    }
});

/* corremos la aplicación */
$app->run();

 
/**
 * Mostrando la respuesta en formato json al cliente o navegador
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}

/**
 * Agregando un leyer intermedio e autenticación para uno o todos los metodos, usar segun necesidad
 * Revisa si la consulta contiene un Header "Authorization" para validar
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        //$db = new DbHandler(); //utilizar para manejar autenticacion contra base de datos
 
        // get the api key
        $token = $headers['Authorization'];
        
        // validating api key
        if (!($token == API_KEY)) { //API_KEY declarada en Config.php
            
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Acceso denegado. Token inválido";
            echoResponse(401, $response);
            
            $app->stop(); //Detenemos la ejecución del programa al no validar
            
        } else {
            //procede utilizar el recurso o metodo del llamado
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Falta token de autorización";
        echoResponse(400, $response);
        
        $app->stop();
    }
}

/*
 *Función para encriptar contraseñas
 */
function dec_enc($action, $string) {
    $output = false;
 
    $encrypt_method = "AES-256-CBC";
    $secret_key = 'This is my secret key';
    $secret_iv = 'This is my secret iv';
 
    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
 
    if( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    }
    else if( $action == 'decrypt' ){
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
 
    return $output;
}
?>