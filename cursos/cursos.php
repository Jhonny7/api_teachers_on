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


/*obtener videos por categoría */
$app->get('/getVideosByCategory', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();

        $idCategoria = $app->request()->params('idCategoria');

        //$resultado = $db->getRadios();
        $sql = 'SELECT 
                v.id,
                v.descripcion,
                v.url,
                vc.descripcion as descripcion_categoria
                FROM videos v
                INNER JOIN video_categoria vc ON (vc.id = v.id_categoria)
                WHERE id_categoria = ?';
        $sth = $db->prepare($sql);
        $sth->bindParam(1, $idCategoria, PDO::PARAM_INT);
        //$sqlFinal = $sth->debugDumpParams();
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($rows)){
                $response["status"] = "A";
                $response["description"] = "Exitoso";
                $response["idTransaction"] = time();
                $response["parameters"] = $rows;
                $response["timeRequest"] = date("Y-m-d H:i:s");

                echoResponse(200, $response);
        }else{
            $response["status"] = "I";
            $response["description"] = "No hay videos para la categoría seleccionada";
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

/* Crear Video curso*/
$app->post('/createCurso', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();
        $idEstatusActivo = 1;
        //Creación de level.
        $createVideo = 'INSERT INTO videos (`id_categoria`, `descripcion`, `url`, `id_usuario_create`) 
                            VALUES (?,?,?,?)';

        $idUsuario = $data['idUsuario'];
        $descripcion = $data['descripcion'];
        $url = $data['url'];
        $idCategoria = $data['idCategoria'];


        $sthCurso = $db->prepare($createVideo);

        $sthCurso->bindParam(1, $idCategoria, PDO::PARAM_INT);
        $sthCurso->bindParam(2, $descripcion, PDO::PARAM_STR);
        $sthCurso->bindParam(3, $url, PDO::PARAM_STR);
        $sthCurso->bindParam(4, $idUsuario, PDO::PARAM_INT);

        $sthCurso->execute();

        $idCurso = $db->lastInsertId();

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
        $response["parameters"] = $idCurso;
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

/* Editar Curso */
$app->post('/updateCurso', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();
        //Creación de level.UPDATE `futbol_americano_v1`.`equipo` SET `nombre` = 'sdfsds' WHERE (`id` = '1');
        //UPDATE `teacherson`.`patrocinadores` SET `id_status` = '1', `descripcion` = 'Pruebas2' WHERE (`id` = '1');
        $updateCurso = 'UPDATE videos 
                            SET `id_usuario_mod` = ?, 
                                `id_categoria` = ?, 
                                `fecha_ult_mod` = now(), 
                                `url` = ?,
                                `descripcion` = ?
                            WHERE (`id` = ?)';
        //UPDATE `teacherson`.`patrocinadores` SET `descripcion` = 'Pruebass', `url_image` = 'https://via.placeholder.com/728x90.png?text=prueba2' WHERE (`id` = '1');

        $descripcion = $data['descripcion'];
        $idUsuario = $data['idUsuario'];
        $id = $data['id'];
        $idCategoria = $data['idCategoria'];
        $url = $data['url'];

        $sthCurso = $db->prepare($updateCurso);

        $sthCurso->bindParam(1, $idUsuario, PDO::PARAM_INT);
        $sthCurso->bindParam(2, $idCategoria, PDO::PARAM_INT);
        $sthCurso->bindParam(3, $url, PDO::PARAM_STR);
        $sthCurso->bindParam(4, $descripcion, PDO::PARAM_STR);
        $sthCurso->bindParam(5, $id, PDO::PARAM_INT);

        $sthCurso->execute();


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

/* Eliminar Curso*/
$app->post('/deleteCurso', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();

        $id = $data['id'];
        $deleteCurso = 'DELETE FROM videos WHERE id = ?';
        $sthDeleteCurso = $db->prepare($deleteCurso);

        $sthDeleteCurso->bindParam(1, $id, PDO::PARAM_INT);            
        $sthDeleteCurso->execute(); 

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