<?php
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Plugin\Plugin;
use Aqua\Ragnarok\Server;
use Aqua\Site\Dispatcher;
use Aqua\UI\Menu;
use Aqua\UI\ScriptManager;

define('Aqua\ROOT', str_replace('\\', '/', rtrim(dirname(__DIR__), DIRECTORY_SEPARATOR)));
define('Aqua\SCRIPT_NAME', basename(__FILE__));
define('Aqua\PROFILE', 'ADMINISTRATION');
define('Aqua\ENVIRONMENT', 'DEVELOPMENT');

require_once '../lib/bootstrap.php';
$response = App::response();
$response->capture()->compression(true);
try {
	if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
		$response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));
	} else {
		$role = App::user()->role();
		App::autoloader('Page')->addDirectory(__DIR__ . '/application/pages');
		Server::init();
		$viewRagnarokMenu = $role->hasPermission('edit-server-settings');
		if($viewRagnarokMenu) {
			$ragnarokSubmenu = array(array(
				'title' => __('admin-menu', 'ragnarok-add-server'),
				'url'   => ac_build_url(array( 'path' => array( 'ragnarok' ) ))
			));
		}
		foreach(Server::$servers as $server) {
			$serverSubmenu = array();
			if($server->charmapCount) {
				$serverSubmenu['url'] = ac_build_url(array( 'path' => array( 'r', $server->key ) ));
				$charmaps = array();
				foreach($server->charmap as $charmap) {
					$charmaps[] = array(
						'title' => htmlspecialchars($charmap->name),
						'url' => ac_build_url(array( 'path' => array( 'r', $server->key, $charmap->key ) )),
						'submenu' => array(array(
							'title' => 'x',
							'url' => ac_build_url(array( 'path' => array( 'r', $server->key, $charmap->key ) ))
						))
					);
				}
				if($server->charmapCount === 1) {
					$serverSubmenu['submenu'] = $charmaps[0]['submenu'];
				} else {
					$serverSubmenu['submenu'] = $charmaps;
				}
			} else if(!$viewRagnarokMenu) {
				continue;
			} else {
				$serverSubmenu['url'] = ac_build_url(array( 'path' => array( 'r', $server->key ) ));
			}
			$serverSubmenu['title'] = htmlspecialchars($server->name);
			$ragnarokSubmenu[] = $serverSubmenu;
		}
		$menu = new Menu;
		$menu->append('dashboard', array(
				'class' => array( 'option-dashboard' ),
				'title' => __('admin-menu', 'dashboard'),
				'url'   => \Aqua\WORKING_URL
			));
		if($role->hasPermission('create-pages')) {
			$menu->append('pages', array(
					'class'   => array( 'option-pages' ),
					'title'   => __('admin-menu', 'pages'),
					'url'     => ac_build_url(array( 'path' => array( 'page' ) )),
					'submenu' => array(
					array(
						'title' => __('admin-menu', 'pages'),
						'url'   => ac_build_url(array( 'path' => array( 'page' ) )),
					),
					array(
						'title' => __('admin-menu', 'new-page'),
						'url'   => ac_build_url(array( 'path' => array( 'page' ), 'action' => 'new' )),
					)
				)));
		}
		if($role->hasPermission('publish-posts')) {
			$menu->append('posts', array(
					'class'   => array( 'option-posts' ),
					'title'   => __('admin-menu', 'news'),
					'url'     => ac_build_url(array( 'path' => array( 'news' ) )),
					'submenu' => array(
					array(
						'title' => __('admin-menu', 'news-posts'),
						'url'   => ac_build_url(array( 'path' => array( 'news' ) )),
					),
					array(
						'title' => __('admin-menu', 'news-new-post'),
						'url'   => ac_build_url(array( 'path' => array( 'news' ), 'action' => 'new' )),
					),
					array(
						'title' => __('admin-menu', 'news-categories'),
						'url'   => ac_build_url(array( 'path' => array( 'news', 'category' ) )),
					),
					array(
						'title' => __('admin-menu', 'news-comments'),
						'url'   => ac_build_url(array( 'path' => array( 'news', 'comments' ) )),
					)
				)));
		}
		$submenu = array(array(
			'title' => __('admin-menu', 'users'),
			'url'   => ac_build_url(array( 'path' => array( 'user' ), 'action' => 'index' )),
		));
		if($role->hasPermission('manage-roles')) {
			$submenu[] = array(
				'title' => __('admin-menu', 'users-roles'),
				'url'   => ac_build_url(array( 'path' => array( 'role' ) )),
			);
		}
		$menu->append('users', array(
				'class'   => array( 'option-users' ),
				'title'   => __('admin-menu', 'users'),
				'url'     => ac_build_url(array( 'path' => array( 'user' ) )),
				'submenu' => $submenu
			));
		if(!empty($ragnarokSubmenu)) {
			$menu->append('ragnarok', array(
					'class'   => array( 'option-ragnarok' ),
					'title'   => __('admin-menu', 'ragnarok'),
					'url'     => '#',
					'submenu' => $ragnarokSubmenu
				));
		}
		if($role->hasPermission('manage-plugins')) {
			$menu->append('plugins', array(
					'class' => array( 'option-plugins' ),
					'title' => __('admin-menu', 'plugins'),
					'url'   => ac_build_url(array( 'path' => array( 'plugin' ) ))
				));
		}
		if($role->hasPermission('view-cp-logs')) {
			$menu->append('logs', array(
					'class'   => array( 'option-logs' ),
					'title'   => __('admin-menu', 'logs'),
					'url'     => '#',
					'submenu' => array(
					array(
						'title' => __('admin-menu', 'login-log'),
						'url'   => ac_build_url(array( 'path' => array( 'log' ), 'action' => 'login' ))
					),
					array(
						'title' => __('admin-menu', 'ban-log'),
						'url'   => ac_build_url(array( 'path' => array( 'log' ), 'action' => 'ban' ))
					),
					array(
						'title' => __('admin-menu', 'pp-log'),
						'url'   => ac_build_url(array( 'path' => array( 'log' ), 'action' => 'paypal' ))
					),
					array(
						'title' => __('admin-menu', 'credit-log'),
						'url'   => ac_build_url(array( 'path' => array( 'log' ), 'action' => 'credit' ))
					),
					array(
						'title' => __('admin-menu', 'error-log'),
						'url'   => ac_build_url(array( 'path' => array( 'log' ), 'action' => 'error' ))
					)
				)));
		}
		if($role->hasPermission('edit-cp-settings')) {
			$menu->append('settings', array(
					'class' => array( 'option-settings' ),
					'title' => __('admin-menu', 'settings'),
					'url'   => ac_build_url(array( 'path' => array( 'settings' ) ))
				));
		}
		$dispatcher = new Dispatcher(
			include __DIR__ . '/application/routing.php',
			include __DIR__ . '/application/permission.php'
		);
		App::registrySet('ac_admin_menu', $menu);
		App::registrySet('ac_dispatcher', $dispatcher);
		Plugin::init();
		echo $dispatcher->dispatch(App::user(), App::response());
	}
} catch(Exception $exception) {
	$error = ErrorLog::logSql($exception);
	if(!headers_sent()) {
		$response->endCapture(false)->capture();
		$tpl = new \Aqua\UI\Template;
		$tpl->set('error', $error);
		echo $tpl->render('exception/layout');
	}
}
$response->send(true);
ignore_user_abort(true);
