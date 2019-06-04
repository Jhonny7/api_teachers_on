<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: text/html; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"'); 

require_once '../include/DbHandler.php'; 
//require_once '../services/fcm_service.php'; 

require '../libs/Slim/Slim.php'; 

\Slim\Slim::registerAutoloader(); 
$app = new \Slim\Slim(); 


/* Usando GET para consultar los autos */
$app->get('/getPatrocinadores', function(){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        //$resultado = $db->getRadios();
        //$sql = 'SELECT id, descripcion, url_image, base64_image FROM patrocinadores';

        $selectPatrocinadores = 'SELECT id, descripcion, url_image, base64_image FROM patrocinadores';
        $sthPatrocinadores = $db->prepare($selectPatrocinadores);
        $sthPatrocinadores->execute();
        $rows = $sthPatrocinadores->fetchAll(PDO::FETCH_ASSOC);

        //$array = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $response["status"] = "A";
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $rows;
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

/* Crear Patrocinador*/
$app->post('/createPatrocinador', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();
        $idEstatusActivo = 1;
        //Creación de level.
        $createPatrocinador = 'INSERT INTO patrocinadores (`id_usuario`, `descripcion`, `url_image`, `base64_image`, `id_status`) VALUES (?,?,?,?,1)';

        $idUsuario = $data['idUsuario'];
        $descripcion = $data['descripcion'];
        $urlImage = $data['urlImage'];
        $base64Image = $data['base64Image'];


        $sthPatrocinador = $db->prepare($createPatrocinador);
        $sthPatrocinador->bindParam(1, $idUsuario, PDO::PARAM_INT);
        $sthPatrocinador->bindParam(2, $descripcion, PDO::PARAM_STR);
        $sthPatrocinador->bindParam(3, $urlImage, PDO::PARAM_STR);
        $sthPatrocinador->bindParam(4, $base64Image, PDO::PARAM_STR);

        $sthPatrocinador->execute();

        $idPatrocinador = $db->lastInsertId();

        //Commit exitoso de transacción
        $db->commit();

        /*$fcm = new FCMNotification();

        $token = "f99ERxWzZLM:APA91bG5U5zsltA6rObvRz0K9Lu7N0r1cds6kRVt-d_w1c1whh8nFdYtfmZVehVGMLFA-J_bXh-TXL_eCUYV_Q6GHY5R_AQahLf6r4ow-tAjdJ2Zpzx-pFZ-24KpSIe8eCHznJqrziyo";
        $title = "Notification title";
        $body = "Hello I am from Your php server";
        $notification = array('title' =>$title , 'body' => $body, 'sound' => 'default');
        $arrayToSend = array('to' => $token, 'notification' => $notification,'priority'=>'high');

        $return = $fcm->sendData($arrayToSend);*/

        $response["status"] = "A";
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $idPatrocinador;
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

/* Editar Patrocinador */
$app->post('/updatePatrocinador', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();
        //Creación de level.UPDATE `futbol_americano_v1`.`equipo` SET `nombre` = 'sdfsds' WHERE (`id` = '1');
        //UPDATE `teacherson`.`patrocinadores` SET `id_status` = '1', `descripcion` = 'Pruebas2' WHERE (`id` = '1');
        $updatePatrocinador = 'UPDATE patrocinadores SET id_status = ?, descripcion = ?, url_image = ?, base64_image = ? WHERE id = ?';
        //UPDATE `teacherson`.`patrocinadores` SET `descripcion` = 'Pruebass', `url_image` = 'https://via.placeholder.com/728x90.png?text=prueba2' WHERE (`id` = '1');


        $idStatus = $data['idStatus'];
        $descripcion = $data['descripcion'];
        $urlImage = $data['urlImage'];
        $base64Image = $data['base64Image'];
        $id = $data['id'];

        $sthPatrocinador = $db->prepare($updatePatrocinador);
        $sthPatrocinador->bindParam(1, $idStatus, PDO::PARAM_INT);
        $sthPatrocinador->bindParam(2, $descripcion, PDO::PARAM_STR);
        $sthPatrocinador->bindParam(3, $urlImage, PDO::PARAM_STR);
        $sthPatrocinador->bindParam(4, $base64Image, PDO::PARAM_STR);
        $sthPatrocinador->bindParam(5, $id, PDO::PARAM_INT);

        $sthPatrocinador->execute();


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

/* Eliminar Patrocinador*/
$app->post('/deletePatrocinador', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();

        $id = $data['id'];
        $deletePatrocinador = 'DELETE FROM patrocinadores WHERE id = ?';
        $sthDeletePatrocinio = $db->prepare($deletePatrocinador);
        $sthDeletePatrocinio->bindParam(1, $id, PDO::PARAM_INT);            
        $sthDeletePatrocinio->execute(); 

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