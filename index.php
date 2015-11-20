<?php


ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require 'vendor/autoload.php';
require 'Models/User.php';
require 'Models/partidos.php';
require 'Models/invitar.php';


function simple_encrypt($text,$salt){  
   return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $salt, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
}
 
function simple_decrypt($text,$salt){  
    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $salt, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
}



$app = new \Slim\Slim();

$app->enc_key = '1234567891011214';


$app->config('databases', [
    'default' => [
        'driver'    => 'mysql',
        'host'      => 'us-cdbr-iron-east-03.cleardb.net',
        'database'  => 'heroku_b2562bab37645c3',
        'username'  => 'bd90c798d775ac',
        'password'  => '9221123b',
        'charset'   => 'utf8',
        'collation' => 'utf8_general_ci',
        'prefix'    => ''
    ]
]);

$app->add(new Zeuxisoo\Laravel\Database\Eloquent\ModelMiddleware);
$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());
$app->add(new \Slim\Middleware\ContentTypes());

$app->options('/(:name+)', function() use ($app) {
    $app->render(200,array('msg' => 'API SeJuega'));
});

$app->get('/', function () use ($app) {
	$app->render(200,array('msg' => 'API SeJuega'));
});
$app->get('/usuario', function () use ($app) {
	$db = $app->db->getConnection();
	$users = $db->table('users')->select('id', 'name')->get();
	$app->render(200,array('data' => $users));
});
$app->post('/usuario', function () use ($app) {
	$input = $app->request->getBody();
	$name = $input['name'];
	if(empty($name)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'name is required',
        ));
	}
	$password = $input['password'];
	if(empty($password)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'password is required',
        ));
	}
	$email = $input['email'];
	if(empty($email)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'email is required',
        ));
	}
    $user = new User();
    $user->name = $name;
    $user->password = $password;
    $user->email = $email;
    $user->save();
    $app->render(200,array('data' => $user->toArray()));
});
$app->put('/usuario/:id', function ($id) use ($app) {
	$input = $app->request->getBody();
	
	$name = $input['name'];
	if(empty($name)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'name is required',
        ));
	}
	$password = $input['password'];
	if(empty($password)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'password is required',
        ));
	}
	$email = $input['email'];
	if(empty($email)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'email is required',
        ));
	}
	$user = User::find($id);
	if(empty($user)){
		$app->render(404,array(
			'error' => TRUE,
            'msg'   => 'user not found',
        ));
	}
    $user->name = $name;
    $user->password = $password;
    $user->email = $email;
    $user->save();
    $app->render(200,array('data' => $user->toArray()));
});
$app->get('/usuario/:id', function ($id) use ($app) {
	$user = User::find($id);
	if(empty($user)){
		$app->render(404,array(
			'error' => TRUE,
            'msg'   => 'user not found',
        ));
	}
	$app->render(200,array('data' => $user->toArray()));
});
$app->delete('/usuario/:id', function ($id) use ($app) {
	$user = User::find($id);
	if(empty($user)){
		$app->render(404,array(
			'error' => TRUE,
            'msg'   => 'user not found',
        ));
	}
	$user->delete();
	$app->render(200);
});

//login
$app->post('/login', function () use ($app) {
	$input = $app->request->getBody();
	$email = $input['email'];
	if(empty($email)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'email is required',
        ));
	}
	$password = $input['password'];
	if(empty($password)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'password is required',
        ));
	}
	$db = $app->db->getConnection();
	$user = $db->table('users')->select()->where('email', $email)->first();
	if(empty($user)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'user not exist',
        ));
	}
	if($user->password != $password){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'password dont match',
        ));
	}

	$token = simple_encrypt($user->id, $app->enc_key);	
	$app->render(200,array('token' => $token));
});

//logout
$app->get('/logout', function() use($app) {
 	$token="";
	$app->render(200,array('token' => ''));

});

//perfil
$app->get('/me', function () use ($app) {
	$token = $app->request->headers->get('auth-token');
	if(empty($token)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'Not logged',
        ));
	}
	$id_user_token = simple_decrypt($token, $app->enc_key);
	$user = User::find($id_user_token);
	if(empty($user)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'Not logged',
        ));
	}
	$app->render(200,array('data' => $user->toArray()));
});




//ver Partidos
$app->get('/partidos', function () use ($app) {
	$db = $app->db->getConnection();
	$users = $db->table('partidos')->select('id', 'nombre', 'fecha', 'participantes')->get();

	$app->render(200,array('data' => $users));
});


//ver Partidos de un id
$app->get('/mispartidos', function () use ($app) {


		$token = $app->request->headers->get('auth-token');
		if(empty($token)){
			$app->render(500,array(
				'error' => TRUE,
				'msg'   => 'Not logged',
			));
		}
		$id_user_token = simple_decrypt($token, $app->enc_key);
		$user = User::find($id_user_token);
		if(empty($user)){
			$app->render(500,array(
				'error' => TRUE,
				'msg'   => 'Not logged',
			));
		}

	$db = $app->db->getConnection();
	$users = $db->table('partidos')->select('id', 'nombre', 'fecha', 'participantes', 'hora', 'lugar')->where('id_usuario', $user->id )->get();

	$app->render(200,array('data' => $users));
});

//Crear Partidos
$app->post('/partidos', function () use ($app) {

	$token = $app->request->headers->get('auth-token');
	$id_user_token = simple_decrypt($token, $app->enc_key);
	$user = User::find($id_user_token);


	$input = $app->request->getBody();
	$name = $input['nombre'];
	if(empty($name)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'name is required',
        ));
	}
	$fecha = $input['fecha'];
	if(empty($fecha)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'password is required',
        ));
	}

	$hora = $input['hora'];
	if(empty($hora)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'email is required',
        ));
	}

		$lugar = $input['lugar'];
	if(empty($lugar)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'email is required',
        ));
	}


		$id_usuario = $user->id;
	if(empty($id_usuario)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'email is required',
        ));
	}

    $partido = new Partido();
    $partido->nombre = $name;
    $partido->id_usuario=$id_usuario;
    $partido->fecha = $fecha;
    $partido->hora = $hora;
    $partido->lugar = $lugar;
    $partido->save();
    $app->render(200,array('data' => $partido->toArray()));
});


//invitar
$app->post('/partidos/:id/invitar', function () use ($app) {

	$token = $app->request->headers->get('auth-token');
	if(empty($token)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'Not logged',
        ));
	}
	$id_user_token = simple_decrypt($token, $app->enc_key);
	$user = User::find($id_user_token);
	if(empty($user)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'Not logged',
        ));
	}
	$db = $app->db->getConnection();
	$partido = Partido::find($id);
	if(empty($partido)){
		$app->render(404,array(
			'error' => TRUE,
            'msg'   => 'partido not found',
        ));
	}
	$input = $app->request->getBody();
	$text = $input['text'];
	if(empty($text)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'text is required',
        ));
	}
	$text_array = explode(',', $text);
	$created = array();
	foreach ($text_array as $key => $text) {
		$invitar = new invitar();
		$invitar->id_usuario = $text
		$invitar->id_partido = $partido->id;
		$invitar->save();
		$created[] = $invitar->toArray();
	}
	$app->render(200,array('data' => $created));
});
});



//Modificar Partido 

$app->put('/partidos/:id', function ($id) use ($app) {
	$input = $app->request->getBody();
	
	$name = $input['nombre'];
	if(empty($name)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'name is required',
        ));
	}
	$fecha = $input['fecha'];
	if(empty($fecha)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'password is required',
        ));
	}
	$participantes = $input['participantes'];
	if(empty($participantes)){
		$app->render(500,array(
			'error' => TRUE,
            'msg'   => 'email is required',
        ));
	}
	$user = Partido::find($id);
	if(empty($user)){
		$app->render(404,array(
			'error' => TRUE,
            'msg'   => 'user not found',
        ));
	}
    $user->nombre = $name;
    $user->fecha = $fecha;
    $user->participantes = $participantes;
    $user->save();
    $app->render(200,array('data' => $user->toArray()));
});

//ver partido Id
$app->get('/partidos/:id', function ($id) use ($app) {
	$user = Partido::find($id);
	if(empty($user)){
		$app->render(404,array(
			'error' => TRUE,
            'msg'   => 'user not found',
        ));
	}
	$app->render(200,array('data' => $user->toArray()));
});

$app->delete('/partidos/:id', function ($id) use ($app) {
	$user = Partido::find($id);
	if(empty($user)){
		$app->render(404,array(
			'error' => TRUE,
            'msg'   => 'user not found',
        ));
	}

	$user->delete();
	$app->render(200);
});





$app->run();
?>
