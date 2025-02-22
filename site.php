<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;

$app->get('/', function() {
    
	$products = Product::listAll();

	$page = new Page();

	$page->setTpl("index", [

		'products' => Product::checkList($products)

	]);

});

$app->get("/categories/:idcategory", function($idcategory) {

	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($page);

	$pages = [];

	for ($i=1; $i <= $pagination['pages']; $i++) { 
		
		array_push($pages, [

			'link' => '/ecommerce/categories/' . $category->getidcategory() . '?page=' . $i,
			'page' => $i

		]);

	}

	$page = new Page([

		'header' => false,
		'footer' => false

	]);

	$page->setTpl("category", [

		'category' => $category->getValues(),
		'products' => $pagination["data"],
		'pages' => $pages

	]);

});

$app->get("/products/:desurl", function($desurl) {

	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page([

		'header' => false,
		'footer' => false

	]);

	$page->setTpl("product-detail", [

		'product' => $product->getValues(),
		'categories' => $product->getCategories()

	]);

});

$app->get("/cart", function() {

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [

		'cart' => $cart->getValues(),
		'products' => $cart->getProducts(),
		'error' => Cart::getMsgError()

	]);

});

$app->get("/cart/:idproduct/add" , function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	for ($i=0; $i < $qtd; $i++) { 
		
		$cart->addProduct($product);
		
	}

	header("Location: /ecommerce/cart");
	exit;

});

$app->get("/cart/:idproduct/minus" , function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product);

	header("Location: /ecommerce/cart");
	exit;

});

$app->get("/cart/:idproduct/remove" , function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product, true);

	header("Location: /ecommerce/cart");
	exit;

});

$app->post("/cart/freight", function() {

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /ecommerce/cart");
	exit;

});

$app->get("/checkout", function() {

	User::verifyLogin(false);

	$address = new Address();

	$cart = Cart::getFromSession();

	if (isset($_GET['zipcode'])) {

		$_GET['zipcode'] = $cart->getdeszipcode();

	}

	if (isset($_GET['zipcode'])) {

		$address->loadFromCEP($_GET['zipcode']);		

		$cart->setdeszipcode($_GET['zipcode']);

		$cart->save();

		$cart->getCalculateTotal();

	}

	if (!$address->getdesaddress()) $address->setdesaddress('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');

	$page = new Page();

	$page->setTpl("checkout", [

		'cart' => $cart->getValues(),
		'address' => $address->getValues(),
		'products' => $cart->getProducts(),
		'error' => Address::getMsgError()

	]);

});

$app->post("/checkout", function() {

	User::verifyLogin(false);

	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {

		Address::setMsgError("Informe o CEP!");
		header("Location: /ecommerce/checkout");
		exit;

	}

	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {

		Address::setMsgError("Informe o endereço!");
		header("Location: /ecommerce/checkout");
		exit;

	}

	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {

		Address::setMsgError("Informe o bairro!");
		header("Location: /ecommerce/checkout");
		exit;

	}

	if (!isset($_POST['descity']) || $_POST['descity'] === '') {

		Address::setMsgError("Informe a cidade!");
		header("Location: /ecommerce/checkout");
		exit;

	}

	if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {

		Address::setMsgError("Informe o estado!");
		header("Location: /ecommerce/checkout");
		exit;

	}

	if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {

		Address::setMsgError("Informe o país!");
		header("Location: /ecommerce/checkout");
		exit;

	}

	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	header("Location: /ecommerce/order");
	exit;

});

$app->get("/login", function() {

	$page = new Page();

	$page->setTpl("login", [

		'error' => User::getError(),
		'errorRegister' => User::getErrorRegister(),
		'registerValues' => (isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']

	]);

});

$app->post("/login", function() {

	try {

		User::login($_POST['login'], $_POST['password']);

	} catch(Exception $e) {

		User::setError($e->getMessage());

	}

	header("Location: /ecommerce/checkout");
	exit;

});

$app->get("/logout", function() {

	User::logout();

	header("Location: /ecommerce/login");
	exit;

});

$app->post("/register", function() {

	$_SESSION['registerValues'] = $_POST;

	if (!isset($_POST['name']) || $_POST['name'] == '') {

		User::setErrorRegister("Preencha o seu nome!");
		header("Location: /ecommerce/login");
		exit;

	}

	if (!isset($_POST['email']) || $_POST['email'] == '') {

		User::setErrorRegister("Preencha o seu email!");
		header("Location: /ecommerce/login");
		exit;

	}

	if (!isset($_POST['password']) || $_POST['password'] == '') {

		User::setErrorRegister("Preencha a senha!");
		header("Location: /ecommerce/login");
		exit;

	}

	if (User::checkLoginExist($_POST['email']) === true) {

		User::setErrorRegister("Este endereço de email já está sendo usado por outro usuário!");
		header("Location: /ecommerce/login");
		exit;

	}

	$user = new User();

	$user->setData([

		'inadmin' => 0,
		'deslogin' => $_POST['email'],
		'desperson' => $_POST['name'],
		'desemail' => $_POST['name'],
		'despassword' => $_POST['password'],
		'nrphone' => $_POST['phone']

	]);

	$user->save();

	User::login($_POST['email'], $_POST['password']);

	header("Location: /ecommerce/checkout");
	exit;

});

$app->get("/forgot", function() {

	$page = new Page();

	$page ->setTpl("forgot");

});

$app->post("/forgot", function() {

	$user = User::getForgot($_POST["email"], false);

	header("Location: /ecommerce/forgot/sent");
	exit;

});

$app->get("/forgot/sent", function() {

	$page = new Page([

		'header' => false,
		'footer' => false

	]);

	$page ->setTpl("forgot-sent");

});

$app->get("/forgot/reset/", function() {

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();

	$page ->setTpl("forgot-reset", array(

		"name"=>$user["desperson"],
		"code"=>$_GET["code"]

	));

});

$app->post("/forgot/reset", function() {

	$forgot = User::validForgotDecrypt($_POST["code"]);

	User::setForgotUsed($user["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [

		"cost"=>12

	]);

	$user->setPassword($password);

	$page = new Page();

	$page ->setTpl("forgot-reset-success");

});

$app->get("/profile", function() {

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile", [

		'user' => $user->getValues(),
		'profileMsg' => User::getSuccess(),
		'profileError' => User::getError()

	]);

});

$app->post("/profile", function() {

	User::verifyLogin(false);

	if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {

		User::setError("Preencha o seu nome!");

		header("Location: /ecommerce/profile");
		exit;

	}

	if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {

		User::setError("Preencha o seu email!");

		header("Location: /ecommerce/profile");
		exit;

	}

	$user = User::getFromSession();

	if ($_POST['desemail'] !== $user->getdesemail()) {

		if (User::checkLoginExist($_POST['desemail']) === true) {

			User::setError("Este email já está cadastrado!");

			header("Location: /ecommerce/profile");
			exit;

		}

	}

	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];

	$user->setData($_POST);

	$user->update();

	User::setSuccess("Dados alterados com sucesso!");

	header("Location: /ecommerce/profile");
	exit;

});

?>