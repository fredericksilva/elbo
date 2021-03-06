<?php

namespace Elbo\Controllers\Admin;

use Elbo\{Models\User, Library\Controller};
use Symfony\Component\HttpFoundation\{Request, Response};

class UserDisableController extends Controller {
	use \Elbo\Middlewares\Session;
	use \Elbo\Middlewares\PersistLogin;
	use \Elbo\Middlewares\NotFoundIfNotAdmin;
	use \Elbo\Middlewares\CSRFProtected;

	protected $middlewares = [
		'manageSession',
		'persistLogin',
		'notFoundIfNotAdmin',
		'csrfProtected'
	];

	public function run(Request $request, array &$data) {
		$twig = $this->container->get(\Twig_Environment::class);

		$context = [
			'r_email' => $request->query->get('r_email'),
			'r_disabled' => $request->query->get('r_disabled'),
			'r_created_ip' => $request->query->get('r_created_ip'),
			'r_last_login_ip' => $request->query->get('r_last_login_ip'),
			'r_n' => $request->query->get('r_n'),
			'login_email' => User::where('id', $this->session->get('userid'))->pluck('email')->first()
		];

		$entry = User::where('id', $data['userid'])->first();

		if ($entry === null) {
			return new Response($twig->render('admin/user_notfound.html.twig', $context), 404);
		}

		$entry->disabled = true;
		$entry->save();

		return new Response($twig->render('admin/user_disabled.html.twig', $context));
	}
}
