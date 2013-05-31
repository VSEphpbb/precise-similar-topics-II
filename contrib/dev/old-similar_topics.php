<?php
/**
 *
 * @package Precise Similar Topics II
 * @copyright (c) 2013 Matt Friedman
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

class phpbb_ext_vse_similartopics_core_similar_topics
{
	/**
	 * Are similar topics enabled?
	 */
	private $is_active		= false;

	/**
	 * The maximum number of similar topics to display
	 */
	private $topic_limit	= 5;

	/**
	 * The maximum age of similar topics to display (in days)
	 */
	private $topic_age		= 365;

	/**
	 * Cache static SQL queries for similar topics
	 */
	private $cache_time		= 0;

	/**
	 * String of words defined in ACP to be ignored in similar topic searches
	 */
	private $ignore_words	= '';

	/**
	 * String of forum IDs that are not to be searched for similar topics
	 */
	private $ignore_forums	= '';

	/**
	 * Is the current forum allowed to display similar topics?
	 */
	private $forum_allowed	= true;

	/**
	 * Is the board using a MySQL database?
	 */
	private $mysql_db		= true;

	/**
	 * Similar topics class constructor method
	 */
	public function __construct()
	{
		global $config, $db, $forum_id;

		$this->is_active     = (bool) $config['similar_topics'];
		$this->topic_limit   = (int) $config['similar_topics_limit'];
		$this->topic_age     = (int) $config['similar_topics_time'];
		$this->cache_time    = (int) $config['similar_topics_cache'];
		$this->ignore_words  = (string) $config['similar_topics_words'];
		$this->ignore_forums = (string) $config['similar_topics_ignore'];
		$this->forum_allowed = (!in_array($forum_id, explode(',', $config['similar_topics_hide']))) ? true : false;
		$this->mysql_db      = (($db->sql_layer == 'mysql4') || ($db->sql_layer == 'mysqli')) ? true : false;
	}

	/**
	 * Get similar topics by matching topic titles
	 *
	 * NOTE: Currently requires MySQL due to the use of FULLTEXT indexes
	 * and MATCH and AGAINST and UNIX_TIMESTAMP. MySQL FULLTEXT has built-in
	 * English ignore words. We'll use phpBB's ignore words for non-English
	 * languages. We also remove any admin-defined special ignore words.
	 *
	 * @param Event $event Event object
	 * @return null
	 */
	public function get_similar_topics($event)
	{
		global $auth, $cache, $config, $user, $db, $template, $phpbb_root_path, $phpEx;

		// All reasons to bail out of the MOD
		if (!$this->is_active || !$this->mysql_db || !$this->topic_limit || !$this->forum_allowed)
		{
			return;
		}

		$topic_title = $this->strip_topic_title($event['topic_data']['topic_title']);

		// If the stripped down topic_title is empty, no need to continue
		if (empty($topic_title))
		{
			return;
		}

		// Similar Topics query
		$sql_array = array(
			'SELECT'	=> "f.forum_id, f.forum_name, t.*, 
				MATCH (t.topic_title) AGAINST ('" . $db->sql_escape($topic_title) . "') AS score",

			'FROM'		=> array(
				TOPICS_TABLE	=> 't',
			),

			'LEFT_JOIN'	=> array(
				array(
					'FROM'	=>	array(FORUMS_TABLE	=> 'f'),
					'ON'	=> 'f.forum_id = t.forum_id',
				),
			),

			'WHERE'		=> "MATCH (t.topic_title) AGAINST ('" . $db->sql_escape($topic_title) . "') >= 0.5
				AND t.topic_status <> " . ITEM_MOVED . '
				AND t.topic_approved = 1
				AND t.topic_time > (UNIX_TIMESTAMP() - ' . $this->topic_age . ')
				AND t.topic_id <> ' . (int) $event['topic_data']['topic_id'],

		//	'GROUP_BY'	=> 't.topic_id',
		//	'ORDER_BY'	=> 'score DESC', // this is done automatically by MySQL when not using IN BOOLEAN MODE
		);

		// Add topic tracking data to the query (only when query caching is off)
		if ($user->data['is_registered'] && $config['load_db_lastread'] && !$this->cache_time)
		{
			$sql_array['LEFT_JOIN'][] = array('FROM' => array(TOPICS_TRACK_TABLE => 'tt'), 'ON' => 'tt.topic_id = t.topic_id AND tt.user_id = ' . $user->data['user_id']);
			$sql_array['LEFT_JOIN'][] = array('FROM' => array(FORUMS_TRACK_TABLE => 'ft'), 'ON' => 'ft.forum_id = f.forum_id AND ft.user_id = ' . $user->data['user_id']);
			$sql_array['SELECT'] .= ', tt.mark_time, ft.mark_time as f_mark_time';
		}
		else if ($config['load_anon_lastread'] || $user->data['is_registered'])
		{
			// Cookie based tracking copied from search.php
			$tracking_topics = (isset($_COOKIE[$config['cookie_name'] . '_track'])) ? ((STRIP) ? stripslashes($_COOKIE[$config['cookie_name'] . '_track']) : $_COOKIE[$config['cookie_name'] . '_track']) : '';
			$tracking_topics = ($tracking_topics) ? tracking_unserialize($tracking_topics) : array();
		}

		// Now lets see if the current forum is set to search only in specified forums
		if (!empty($event['topic_data']['similar_topic_forums']))
		{
			$sql_array['WHERE'] .= ' AND ' . $db->sql_in_set('f.forum_id', explode(',', $event['topic_data']['similar_topic_forums']));
		}
		// Otherwise, lets see what forums are not allowed to be searched, and ignore those
		else if (!empty($this->ignore_forums))
		{
			$sql_array['WHERE'] .= ' AND ' . $db->sql_in_set('f.forum_id', explode(',', $this->ignore_forums), true);
		}

		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query_limit($sql, $this->topic_limit, 0, $this->cache_time);

		// Grab icons
		$icons = $cache->obtain_icons();

		$rowset = array();

		while ($row = $db->sql_fetchrow($result))
		{
			$similar_forum_id = (int) $row['forum_id'];
			$similar_topic_id = (int) $row['topic_id'];
			$rowset[$similar_topic_id] = $row;

			if ($auth->acl_get('f_read', $similar_forum_id))
			{
				// Get topic tracking info
				if ($user->data['is_registered'] && $config['load_db_lastread'] && !$this->cache_time)
				{
					$topic_tracking_info = get_topic_tracking($similar_forum_id, $similar_topic_id, $rowset, array($similar_forum_id => $row['f_mark_time']));
				}
				else if ($config['load_anon_lastread'] || $user->data['is_registered'])
				{
					$topic_tracking_info = get_complete_topic_tracking($similar_forum_id, $similar_topic_id);

					if (!$user->data['is_registered'])
					{
						$user->data['user_lastmark'] = (isset($tracking_topics['l'])) ? (int) (base_convert($tracking_topics['l'], 36, 10) + $config['board_startdate']) : 0;
					}
				}

				$folder_img = $folder_alt = $topic_type = '';
				$replies = ($auth->acl_get('m_approve', $similar_forum_id)) ? $row['topic_replies_real'] : $row['topic_replies'];
				$unread_topic = (isset($topic_tracking_info[$similar_topic_id]) && $row['topic_last_post_time'] > $topic_tracking_info[$similar_topic_id]) ? true : false;
				topic_status($row, $replies, $unread_topic, $folder_img, $folder_alt, $topic_type);

				$topic_unapproved = (!$row['topic_approved'] && $auth->acl_get('m_approve', $similar_forum_id)) ? true : false;
				$posts_unapproved = ($row['topic_approved'] && $row['topic_replies'] < $row['topic_replies_real'] && $auth->acl_get('m_approve', $similar_forum_id)) ? true : false;
				$u_mcp_queue = ($topic_unapproved || $posts_unapproved) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=queue&amp;mode=' . (($topic_unapproved) ? 'approve_details' : 'unapproved_posts') . '&amp;t=' . $similar_topic_id, true, $user->session_id) : '';

				$base_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $similar_forum_id . '&amp;t=' . $similar_topic_id);

				$template->assign_block_vars('similar', array(
					'TOPIC_AUTHOR_FULL'		=> get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
					'FIRST_POST_TIME'		=> $user->format_date($row['topic_time']),
					'LAST_POST_TIME'		=> $user->format_date($row['topic_last_post_time']),
					'LAST_POST_AUTHOR_FULL'	=> get_username_string('full', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),

					'PAGE_NUMBER'			=> phpbb_on_page($template, $user, $base_url, $replies + 1, $config['posts_per_page'], 1), 
					'TOPIC_REPLIES'			=> $replies,
					'TOPIC_VIEWS'			=> $row['topic_views'],
					'TOPIC_TITLE'			=> $row['topic_title'],
					'FORUM_TITLE'			=> $row['forum_name'],

					'TOPIC_IMG_STYLE'		=> $folder_img,
					'TOPIC_FOLDER_IMG'		=> $user->img($folder_img, $folder_alt),
					'TOPIC_FOLDER_IMG_ALT'	=> $user->lang[$folder_alt],

					'TOPIC_ICON_IMG'		=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['img'] : '',
					'TOPIC_ICON_IMG_WIDTH'	=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['width'] : '',
					'TOPIC_ICON_IMG_HEIGHT'	=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['height'] : '',
					'ATTACH_ICON_IMG'		=> ($auth->acl_get('u_download') && $auth->acl_get('f_download', $similar_forum_id) && $row['topic_attachment']) ? $user->img('icon_topic_attach', $user->lang['TOTAL_ATTACHMENTS']) : '',
					'UNAPPROVED_IMG'		=> ($topic_unapproved || $posts_unapproved) ? $user->img('icon_topic_unapproved', ($topic_unapproved) ? 'TOPIC_UNAPPROVED' : 'POSTS_UNAPPROVED') : '',

					'S_UNREAD_TOPIC'		=> $unread_topic,
					'S_TOPIC_REPORTED'		=> (!empty($row['topic_reported']) && $auth->acl_get('m_report', $similar_forum_id)) ? true : false,
					'S_TOPIC_UNAPPROVED'	=> $topic_unapproved,
					'S_POSTS_UNAPPROVED'	=> $posts_unapproved,

					'U_NEWEST_POST'			=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $similar_forum_id . '&amp;t=' . $similar_topic_id . '&amp;view=unread') . '#unread',
					'U_LAST_POST'			=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $similar_forum_id . '&amp;t=' . $similar_topic_id . '&amp;p=' . $row['topic_last_post_id']) . '#p' . $row['topic_last_post_id'],
					'U_VIEW_TOPIC'			=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $similar_forum_id . '&amp;t=' . $similar_topic_id),
					'U_VIEW_FORUM'			=> append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $similar_forum_id),
					'U_MCP_REPORT'			=> append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=reports&amp;mode=reports&amp;f=' . $similar_forum_id . '&amp;t=' . $similar_topic_id, true, $user->session_id),
					'U_MCP_QUEUE'			=> $u_mcp_queue,
				));

				phpbb_generate_template_pagination($template, $base_url, 'similar.pagination', 'start', $replies + 1, $config['posts_per_page'], 1, true, true);
			}
		}

		$db->sql_freeresult($result);

		$user->add_lang_ext('vse/similartopics', 'similar_topics');

		$template->assign_vars(array(
			'L_SIMILAR_TOPICS'	=> $user->lang['SIMILAR_TOPICS'],
			'NEWEST_POST_IMG'	=> $user->img('icon_topic_newest', 'VIEW_NEWEST_POST'),
			'LAST_POST_IMG'		=> $user->img('icon_topic_latest', 'VIEW_LATEST_POST'),
			'REPORTED_IMG'		=> $user->img('icon_topic_reported', 'TOPIC_REPORTED'),
		));
	}

	/**
	 * Remove problem characters (and if needed, ignore-words) from topic title
	 *
	 * @param	string	$text 	The topic title
	 * @return	string	The topic title
	 * @access	private
	 */
	private function strip_topic_title($text)
	{
		global $user;

		// Strip quotes, ampersands
		$text = str_replace(array('&quot;', '&amp;'), '', $text);

		$english_lang = ($user->lang_name == 'en' || $user->lang_name == 'en_us') ? true : false;
		$ignore_words = !empty($this->ignore_words) ? true : false;

		if (!$english_lang || $ignore_words)
		{
			$text = $this->strip_stop_words($text, $english_lang, $ignore_words);
		}

		return $text;
	}

	/**
	 * Remove any non-english and/or custom defined ignore-words
	 *
	 * @param	string	$text 			The topic title
	 * @param	bool	$english_lang 	False means use phpBB's ignore words 
	 * @param	bool	$ignore_words 	True means strip custom ignore words
	 * @return	string	The topic title
	 * @access	private
	 */
	private function strip_stop_words($text, $english_lang, $ignore_words)
	{
		global $user, $phpEx;

		$words = array();

		if (!$english_lang && file_exists("{$user->lang_path}{$user->lang_name}/search_ignore_words.$phpEx"))
		{
			// Retrieve a language dependent list of words to be ignored (method copied from search.php)
			include("{$user->lang_path}{$user->lang_name}/search_ignore_words.$phpEx");
		}

		if ($ignore_words)
		{
			// Merge any custom defined ignore words from the ACP to the stop-words array
			$words = array_merge($this->make_word_array($this->ignore_words), $words);
		}

		// Remove stop-words from the topic title text
		$words = array_diff($this->make_word_array($text), $words);

		// Convert our words array back to a string
		$text = !empty($words) ? implode(' ', $words) : '';

		return $text;
	}

	/**
	 * Helper function to split string into an array of words
	 *
	 * @param	string	$text 	String of plain text words
	 * @return	array	array of plaintext words
	 * @access	private
	 */
	private function make_word_array($text)
	{
		// Strip out any non-alpha-numeric characters using PCRE regex syntax
		$text = trim(preg_replace('#[^\p{L}\p{N}]+#u', ' ', $text));

		$words = explode(' ', utf8_strtolower($text));
		foreach ($words as $key => $word)
		{
			// Strip words of 2 characters or less
			if (utf8_strlen(trim($word)) < 3)
			{
				unset($words[$key]);
			}
		}

		return $words;
	}
}