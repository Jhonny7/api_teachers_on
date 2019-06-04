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
$app->get('/categorias', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();

        //$resultado = $db->getRadios();
        $sql = 'SELECT 
                id,
                descripcion,
                img_base64,
                img_url,
                color
                FROM video_categoria';
        $sth = $db->prepare($sql);
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
            $response["description"] = "No hay categorías en nuestra base de datos";
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

/* Obtener categoría*/
$app->get('/getCategoriaById', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        $idCategoria = $app->request()->params('idCategoria');
        //Creación de level.
        $getCategoria= 'SELECT * FROM video_categoria WHERE id = ?';
        $sth = $db->prepare($getCategoria);
        $sth->bindParam(1, $idCategoria, PDO::PARAM_INT);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        $response["status"] = "A";
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $rows[0];
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

/* Crear Categoría*/
$app->post('/createCategoria', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();
        $idEstatusActivo = 1;
        //Creación de level.
        $createCategoria = 'INSERT INTO video_categoria (`descripcion`, `id_usuario_create`, `img_url`, `img_base64`, `color`) VALUES (?, ?,?,?,?)';

        $idUsuario = $data['idUsuario'];
        $descripcion = $data['descripcion'];

        $img_url = $data['img_url'];
        $img_base64 = $data['img_base64'];
        $color = $data['color'];


        $sthCategoria = $db->prepare($createCategoria);

        $sthCategoria->bindParam(5, $color, PDO::PARAM_STR);
        $sthCategoria->bindParam(4, $img_base64, PDO::PARAM_STR);
        $sthCategoria->bindParam(3, $img_url, PDO::PARAM_STR);

        $sthCategoria->bindParam(2, $idUsuario, PDO::PARAM_INT);
        $sthCategoria->bindParam(1, $descripcion, PDO::PARAM_STR);

        $sthCategoria->execute();

        $idCategoria = $db->lastInsertId();


        $updateCategoria = 'UPDATE video_categoria SET `path` = ? WHERE id = ?';  
        $sthCategoria2 = $db->prepare($updateCategoria);

        //Arma file
        $file = createFile($img_base64);
        //
        $sthCategoria2->bindParam(1, $file, PDO::PARAM_STR);
        $sthCategoria2->bindParam(2, $idCategoria, PDO::PARAM_INT);

        $sthCategoria2->execute();


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
        $response["parameters"] = $idCategoria;
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

/* Editar Categoría */
$app->post('/updateCategoria', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();
        //Creación de level.UPDATE `futbol_americano_v1`.`equipo` SET `nombre` = 'sdfsds' WHERE (`id` = '1');
        //UPDATE `teacherson`.`patrocinadores` SET `id_status` = '1', `descripcion` = 'Pruebas2' WHERE (`id` = '1');
        $updateCategoria = 'UPDATE video_categoria SET `descripcion` = ?, `id_usuario_ult_mod` = ?, `fecha_ult_mod` = now(),
        `img_url` = ?,`img_base64` = ?, `color` = ?
        WHERE id = ?';
        //UPDATE `teacherson`.`patrocinadores` SET `descripcion` = 'Pruebass', `url_image` = 'https://via.placeholder.com/728x90.png?text=prueba2' WHERE (`id` = '1');

        $descripcion = $data['descripcion'];
        $idUsuario = $data['idUsuario'];
        $id = $data['id'];

        $img_url = $data['img_url'];
        $img_base64 = $data['img_base64'];
        $color = $data['color'];

        $sthCategoria = $db->prepare($updateCategoria);

        $sthCategoria->bindParam(1, $descripcion, PDO::PARAM_STR);
        $sthCategoria->bindParam(2, $idUsuario, PDO::PARAM_INT);

        $sthCategoria->bindParam(3, $img_url, PDO::PARAM_STR);
        $sthCategoria->bindParam(4, $img_base64, PDO::PARAM_STR);
        $sthCategoria->bindParam(5, $color, PDO::PARAM_STR);

        $sthCategoria->bindParam(6, $id, PDO::PARAM_INT);

        $sthCategoria->execute();


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

/* Eliminar Categoría*/
$app->post('/deleteCategoria', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        $db->beginTransaction();

        $id = $data['id'];
        $deleteCategoria = 'DELETE FROM video_categoria WHERE id = ?';
        $sthDeleteCategoria = $db->prepare($deleteCategoria);

        $sthDeleteCategoria->bindParam(1, $id, PDO::PARAM_INT);            
        $sthDeleteCategoria->execute(); 

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


function createFile($base64){
    try{
        $img = str_replace('data:image/png;base64,', '', $base64);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $path = "/images/";
        if (!file_exists($path)) {
            mkdir($path, 755, true);
        }
        $file = $path . uniqid() . '.png';
        $success = file_put_contents($file, $data);
        if($success){
            return $file;
        }else{
            return "Error File";
        }
    }catch(Exception $e){
        return "Error File";
    }
} 
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