<?php


function notify_init(&$a) {
	if(! local_user())
		return;

	if($a->argc > 2 && $a->argv[1] === 'view' && intval($a->argv[2])) {
		$r = q("select * from notify where id = %d and uid = %d limit 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		if(count($r)) {
			q("update notify set seen = 1 where ( link = '%s' or ( parent != 0 and parent = %d and otype = '%s' )) and uid = %d",
				dbesc($r[0]['link']),
				intval($r[0]['parent']),
				dbesc($r[0]['otype']),
				intval(local_user())
			);

			// Friendica-Client
			$friendicamobile = ($_SERVER['HTTP_USER_AGENT'] == "Apache-HttpClient/UNAVAILABLE (java 1.4)");

			// The friendica client has problems with the GUID. this is some workaround
			if ($friendicamobile) {
				require_once("include/items.php");
				$urldata = parse_url($r[0]['link']);
				$guid = basename($urldata["path"]);
				$itemdata = get_item_id($guid, local_user());
				if ($itemdata["id"] != 0)
					$r[0]['link'] = $a->get_baseurl().'/display/'.$itemdata["nick"].'/'.$itemdata["id"];
			}

			goaway($r[0]['link']);
		}

		goaway($a->get_baseurl(true));
	}

	if($a->argc > 2 && $a->argv[1] === 'mark' && $a->argv[2] === 'all' ) {
		$r = q("update notify set seen = 1 where uid = %d",
			intval(local_user())
		);
		$j = json_encode(array('result' => ($r) ? 'success' : 'fail'));
		echo $j;
		killme();
	}

}


function notify_content(&$a) {
	if(! local_user())
		return login();

		$notif_tpl = get_markup_template('notifications.tpl');

		$not_tpl = get_markup_template('notify.tpl');
		require_once('include/bbcode.php');

		$r = q("SELECT * from notify where uid = %d and seen = 0 order by date desc",
			intval(local_user())
		);

		if (count($r) > 0) {
			foreach ($r as $it) {
				$notif_content .= replace_macros($not_tpl,array(
					'$item_link' => $a->get_baseurl(true).'/notify/view/'. $it['id'],
					'$item_image' => $it['photo'],
					'$item_text' => strip_tags(bbcode($it['msg'])),
					'$item_when' => relative_date($it['date'])
				));
			}
		} else {
			$notif_content .= t('No more system notifications.');
		}

		$o .= replace_macros($notif_tpl, array(
			'$notif_header' => t('System Notifications'),
			'$tabs' => '', // $tabs,
			'$notif_content' => $notif_content,
		));

	return $o;


}
