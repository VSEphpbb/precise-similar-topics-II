<?php
/**
*
* info_acp_similiar_topics [Română]
* 
* @package language
* @copyright (c) 2013 Matt Friedman (Translated by Ionuţ Butnaru and corrected by Ivan Petre Paul, both from phpBB Romanian Community www.phpbb.ro)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, array(
	'PST_TITLE_ACP'		=> 'Subiecte similare',
	'PST_TITLE'			=> 'Subiecte similare precise II',
	'PST_LEGEND1'		=> 'Setări generale',
	'PST_ENABLE'		=> 'Activaţi subiectele similare',
	'PST_LEGEND2'		=> 'Încarcă setări',
	'PST_LIMIT'			=> 'Numărul de subiecte similare care să fie afişate',
	'PST_LIMIT_EXPLAIN'	=> 'Aici puteţi preciza câte subiecte similare să fie afişate. Implicite sunt 5.',
	'PST_TIME'			=> 'Perioada de căutare',
	'PST_TIME_EXPLAIN'	=> 'Această opţiune vă permite să configuraţi perioada de căutare a subiectelor similare. De exemplu, dacă alegeţi “5 zile” sistemul ca afişa numai subiectele similare din ultimele 5 zile. Implicit este un an.',	
	'PST_YEARS'			=> 'Ani',
	'PST_MONTHS'		=> 'Luni',
	'PST_WEEKS'			=> 'Săptămâni',
	'PST_DAYS'			=> 'Zile',
	'PST_CACHE'			=> 'Dimeniune cache',
	'PST_CACHE_EXPLAIN'	=> 'Subiectele similare memorate vor expira după acest timp, în secunde. Alegeţi 0 dacă doriţi să dezactivaţi memorarea subiectelor similare.',
	'PST_LEGEND3'		=> 'Setări forum',
	'PST_NOSHOW_LIST'	=> 'Nu afişa în',
	'PST_NOSHOW_TITLE'	=> 'Nu afişa subiecte similare în',
	'PST_IGNORE_SEARCH'	=> 'Nu căuta în',
	'PST_IGNORE_TITLE'	=> 'Nu căuta subiecte similare în',
	'PST_ADVANCED'		=> 'Advanced',
	'PST_ADVANCED_TITLE'=> 'Apăsaţi pentru a configura setările avansate ale subiectelor similare pentru',
	'PST_ADVANCED_EXP'	=> 'Aici puteţi alege forumuri specifice din care să afişaţi subiecte similare. Numai subiectele similare găsite în forumurile pe care le-aţi ales aici vor fi afişate în <strong>%s</strong>.<br /><br />Nu alegeţi orice forumuri dacă doriţi ca toate subiectele similare din toate forumurile ce pot fi căutate să fie afişate în acest forum.',
	'PST_ADVANCED_FORUM'=> 'Setări avansate pe forum pentru',
	'PST_DESELECT_ALL'	=> 'Deselectaţi tot',
	'PST_LEGEND4'		=> 'Setări opţionale',
	'PST_WORDS'			=> 'Cuvinte speciale ignorate',
	'PST_WORDS_EXPLAIN'	=> 'Adăugaţi cuvinte speciale unice care ar trebui ignorate atunci când sunt căutate subiecte similare. (Reţineţi: Cuvintele care sunt cunoscute ca frecvente în limba dumneavoastră sunt implicit ignorate.) Separaţi cuvintele între ele cu un spaţiu. Majusculele sunt nesemnificative. Maxim 255 de caractere.',
	'PST_SAVED'			=> 'Setări actualizate',
	'PST_FORUM_INFO'	=> '“Nu afişa în”: Nu va afişa subiectele similare în forumurile alese.<br />“Nu căuta în” : Nu va căuta subiecte similare în forumurile alese.',
	'PST_WARNING'		=> 'MODificarea Subiecte similare nu va funcţiona pe forumul dumneavoastr. MODificarea Subiecte similare necesită o bază de date cu MySQL 4 sau MySQL 5.',
	'PST_LOG_MSG'		=> '<strong>Setările pentru subiectele similare au fost modificate</strong>',
));