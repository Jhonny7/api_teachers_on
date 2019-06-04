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


/*obtener usuario para loguarse */
$app->get('/getAuthenticate', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();

        $user = $app->request()->params('user');
        $password = $app->request()->params('password');
        $passwordEncriptado = dec_enc("encrypt",$password);
        //$resultado = $db->getRadios();
        $sql = 'SELECT 
                id,
                id_rol,
                nombre,
                apellido_paterno,
                apellido_materno,
                email,
                id_status
                FROM usuario WHERE username = ? and password = MD5(?)';
        $sth = $db->prepare($sql);
        $sth->bindParam(1, $user, PDO::PARAM_STR);
        $sth->bindParam(2, $passwordEncriptado, PDO::PARAM_STR);
        //$sqlFinal = $sth->debugDumpParams();
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($rows)){
            if($rows[0]['id_status'] != 1){
                $response["status"] = "I";
                $response["description"] = "Usuario dado de baja, contacte a su administrador";
                $response["idTransaction"] = time();
                $response["parameters"] = [];
                $response["timeRequest"] = date("Y-m-d H:i:s");

                echoResponse(400, $response);
            }else{
                $response["status"] = "A";
                $response["description"] = "Exitoso";
                $response["idTransaction"] = time();
                $response["parameters"] = $rows[0];
                $response["timeRequest"] = date("Y-m-d H:i:s");

                echoResponse(200, $response);
            }
        }else{
            $response["status"] = "I";
            $response["description"] = "Credenciales erróneas";
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

/* update token by user */
$app->post('/updateToken', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $db->beginTransaction();
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        //Actualización de usuario con datos de jugador
        $updateUsuario = 'UPDATE usuario SET token = ? WHERE id = ?';
        $token = $data['token'];
        $id = $data['id'];
        $sthUsuario = $db->prepare($updateUsuario);
        $sthUsuario->bindParam(1, $token, PDO::PARAM_STR);
        $sthUsuario->bindParam(2, $id, PDO::PARAM_INT);
        $sthUsuario->execute();

        //Commit exitoso de transacción
        $db->commit();

        $response["status"] = "A";
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = [];
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