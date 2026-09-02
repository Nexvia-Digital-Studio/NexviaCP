<?php

/**
 * Nexvia SSO — passwordless webmail login via panel-issued HMAC token.
 *
 * The panel (only for an authenticated panel session that owns the mail
 * domain) redirects the browser to:  ?_nxvsso=<token>
 *
 * Token payload: "<account>@<domain>;<expiry-unixts>", base64url, signed
 * HMAC-SHA256 with a server-side key. The token NEVER contains any
 * password. IMAP login happens via a Dovecot master user whose secret
 * lives only on this server.
 */
class nexvia_sso extends rcube_plugin
{
	private static ?array $creds = null;

	public function init()
	{
		$this->add_hook('startup', [$this, 'startup']);
		$this->add_hook('authenticate', [$this, 'authenticate']);
		$this->add_hook('login_after', [$this, 'after_login']);
	}

	public function startup($args)
	{
		if (!isset($_GET['_nxvsso']) || !empty($_SESSION['user_id'])) {
			return $args;
		}

		$rcmail = rcube::get_instance();
		$data = $this->verify($_GET['_nxvsso']);

		if ($data === false) {
			if ($rcmail->task == 'login') {
				$rcmail->output->show_message('Oturum bağlantısı geçersiz veya süresi geçmiş. Lütfen giriş yapın.', 'error');
			}
			return $args;
		}

		$master = @file_get_contents('/etc/roundcube/nexvia-sso.secret');
		$master = $master === false ? '' : trim($master);
		if ($master === '') {
			return $args;
		}

		// Stage credentials in-process: index.php purges the session before
		// the authenticate hook fires, so $_SESSION would be wiped here.
		self::$creds = [
			'user' => $data['account'] . '*nexviamaster',
			'pass' => $master,
		];

		$args['task'] = 'login';
		$args['action'] = 'login';

		return $args;
	}

	public function authenticate($args)
	{
		if (empty(self::$creds) || !empty($_SESSION['user_id'])) {
			return $args;
		}

		$args['user'] = self::$creds['user'];
		$args['pass'] = self::$creds['pass'];
		$args['valid'] = true;
		$args['cookiecheck'] = false;
		self::$creds = null;

		return $args;
	}

	public function after_login($args)
	{
		// display the real account name, not the master-user login string
		$u = isset($_SESSION['username']) ? $_SESSION['username'] : '';
		$p = strpos($u, '*nexviamaster');
		if ($p !== false) {
			$_SESSION['username'] = substr($u, 0, $p);
		}

		return $args;
	}

	/**
	 * Verify HMAC token. Returns ['account'=>..,'exp'=>..] or false.
	 */
	private function verify($token)
	{
		if (!is_string($token) || strlen($token) > 512 || substr_count($token, '.') !== 1) {
			return false;
		}

		[$b64, $sig] = explode('.', $token);

		if (!preg_match('/^[A-Za-z0-9_-]+$/', $b64) || !preg_match('/^[a-f0-9]{64}$/', $sig)) {
			return false;
		}

		$key = @file_get_contents('/etc/roundcube/nexvia-sso-hmac.key');
		$key = $key === false ? '' : trim($key);
		if ($key === '') {
			return false;
		}

		$b = $b64 . str_repeat('=', (4 - strlen($b64) % 4) % 4);
		$payload = @base64_decode(strtr($b, '-_', '+/'), true);
		if ($payload === false || substr_count($payload, ';') !== 1) {
			return false;
		}

		[$account, $exp] = explode(';', $payload);
		if (!filter_var($account, FILTER_VALIDATE_EMAIL) || !ctype_digit($exp) || (int) $exp < time()) {
			return false;
		}

		$calc = hash_hmac('sha256', $payload, $key);
		if (!hash_equals($calc, $sig)) {
			return false;
		}

		return ['account' => $account, 'exp' => (int) $exp];
	}
}
