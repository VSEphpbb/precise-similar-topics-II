<?php
/**
*
* Precise Similar Topics
*
* @copyright (c) 2015 Matt Friedman
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace vse\similartopics\tests\core;

class similar_topics_test extends \phpbb_test_case
{
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $auth;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $cache;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $db;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $dispatcher;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $pagination;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $request;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $content_visibility;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $phpEx;

	public function setUp()
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx;

		// Classes we just need to mock for the constructor
		$this->cache = $this->getMockBuilder('\phpbb\cache\service')
			->disableOriginalConstructor()
			->getMock();
		$this->db = $this->getMock('\phpbb\db\driver\driver_interface');
		$this->dispatcher = new \phpbb_mock_event_dispatcher();
		$this->pagination = $this->getMockBuilder('\phpbb\pagination')
			->disableOriginalConstructor()
			->getMock();
		$this->request = $this->getMock('\phpbb\request\request');
		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();
		$this->content_visibility = $this->getMockBuilder('\phpbb\content_visibility')
			->disableOriginalConstructor()
			->getMock();

		// Classes used in the tests
		$this->auth = $this->getMock('\phpbb\auth\auth');
		$this->config = new \phpbb\config\config(array());
		$this->user = new \phpbb\user('\phpbb\datetime');
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $phpEx;
	}

	public function get_similar_topics()
	{
		return new \vse\similartopics\core\similar_topics(
			$this->auth,
			$this->cache,
			$this->config,
			$this->db,
			$this->dispatcher,
			$this->pagination,
			$this->request,
			$this->template,
			$this->user,
			$this->content_visibility,
			$this->phpbb_root_path,
			$this->phpEx
		);
	}

	public function is_available_test_data()
	{
		return array(
			array(
				array(
					'similar_topics' => true,
					'similar_topics_limit' => true,
				),
				array('user_similar_topics' => true),
				array('u_similar_topics', 0, true),
				'mysqli',
				true,
			),
			array(
				array(
					'similar_topics' => false,
					'similar_topics_limit' => true,
				),
				array('user_similar_topics' => true),
				array('u_similar_topics', 0, true),
				'mysqli',
				false,
			),
			array(
				array(
					'similar_topics' => true,
					'similar_topics_limit' => false,
				),
				array('user_similar_topics' => true),
				array('u_similar_topics', 0, true),
				'mysqli',
				false,
			),
			array(
				array(
					'similar_topics' => true,
					'similar_topics_limit' => true,
				),
				array('user_similar_topics' => false),
				array('u_similar_topics', 0, true),
				'mysqli',
				false,
			),
			array(
				array(
					'similar_topics' => true,
					'similar_topics_limit' => true,
				),
				array('user_similar_topics' => true),
				array('u_similar_topics', 0, false),
				'mysqli',
				false,
			),
			array(
				array(
					'similar_topics' => false,
					'similar_topics_limit' => false,
				),
				array('user_similar_topics' => false),
				array('u_similar_topics', 0, false),
				'mysqli',
				false,
			),
			array(
				array(
					'similar_topics' => '',
					'similar_topics_limit' => '',
				),
				array('user_similar_topics' => true),
				array('u_similar_topics', 0, true),
				'mysqli',
				false,
			),
			array(
				array(
					'similar_topics' => true,
					'similar_topics_limit' => true,
				),
				array('user_similar_topics' => ''),
				array('u_similar_topics', 0, true),
				'mysqli',
				false,
			),
			array(
				array(
					'similar_topics' => true,
					'similar_topics_limit' => true,
				),
				array('user_similar_topics' => true),
				array('u_similar_topics', 0, true),
				'mysql4',
				true,
			),
			array(
				array(
					'similar_topics' => true,
					'similar_topics_limit' => true,
				),
				array('user_similar_topics' => true),
				array('u_similar_topics', 0, true),
				'innodb',
				false,
			),
			array(
				array(
					'similar_topics' => null,
					'similar_topics_limit' => null,
				),
				array('user_similar_topics' => true),
				array('u_similar_topics', 0, true),
				'mysqli',
				false,
			),
			array(
				array(
					'similar_topics' => true,
					'similar_topics_limit' => true,
				),
				array('user_similar_topics' => null),
				array('u_similar_topics', 0, true),
				'mysqli',
				false,
			),
			array(
				array(
					'similar_topics' => true,
					'similar_topics_limit' => true,
				),
				array('user_similar_topics' => true),
				array('u_similar_topics', 0, true),
				'',
				false,
			),
		);
	}

	/**
	 * @dataProvider is_available_test_data
	 */
	public function test_is_available($config_data, $user_data, $auth_data, $sql_layer, $expected)
	{
		$this->config = new \phpbb\config\config($config_data);
		$this->user->data['user_similar_topics'] = $user_data['user_similar_topics'];
		$this->auth->expects($this->any())
			->method('acl_get')
			->with($this->stringContains('_'), $this->anything())
			->will($this->returnValueMap(array($auth_data)));
		$this->db->expects($this->any())
			->method('get_sql_layer')
			->will($this->returnValue($sql_layer));

		$similar_topics = $this->get_similar_topics();

		$this->assertEquals($expected, $similar_topics->is_available());
	}

	public function forum_available_test_data()
	{
		return array(
			array(1, '1,2,3,4', false),
			array(1, '2,3,4,5', true),
			array(1, '1', false),
			array(1, '2', true),
			array(1, '', true),
			array(1, null, true),
			array(null, '', false),
			array(null, null, false),
		);
	}

	/**
	 * @dataProvider forum_available_test_data
	 */
	public function test_forum_available($forum_id, $unavailable, $expected)
	{
		$this->config->offsetSet('similar_topics_hide', $unavailable);

		$similar_topics = $this->get_similar_topics();

		$this->assertEquals($expected, $similar_topics->forum_available($forum_id));
	}

	public function clean_topic_title_test_data()
	{
		return array(
			array('The quick, brown fox jumps over a lazy dog.', 'brown lazy', 'the quick fox jumps over dog'),
			array('The quick, brown fox jumps over a lazy dog.', 'the quick brown fox jumps over a lazy dog', ''),
			array('The quick, brown fox jumps over a lazy dog.', '', 'the quick brown fox jumps over lazy dog'),
			array('El zorro marrón rápido salta por encima de un perro perezoso.', 'marrón', 'zorro rápido salta por encima perro perezoso'),
			array('The "quick", brown fox & jumps &amp; over a &quot;lazy&quot; dog.', 'brown lazy', 'the quick fox jumps over dog'),
		);
	}

	/**
	 * @dataProvider clean_topic_title_test_data
	 */
	public function test_clean_topic_title($test_string, $ignore_words, $expected)
	{
		$this->config->offsetSet('similar_topics_words', $ignore_words);

		$similar_topics = $this->get_similar_topics();

		$this->assertSame($expected, $similar_topics->clean_topic_title($test_string));
	}
}
