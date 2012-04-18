<?php
/**
 * @package BibTeX for Wordpress
 * @author Fabrizio Ferrandi, Cristiana Bolchini
 * @version 2.1
 */
/*
Plugin Name: BibTeX extension to Wordpress
Plugin URI: http://github.com/cbolk/WPBibTex
Description: Bibtex plugin allows to add bibliography entries in a wordpress blog, supporting BibTeX style.
Author: Fabrizio Ferrandi, Cristiana Bolchini
Version: 2.1
Author URI: http://www.dei.polimi.it/people/ferrandi
*/

include dirname( __FILE__ ).'/models/database.php';

/**
 * BibTeX plugin class
 *
 * @package BibTeX extension to Wordpress
 * @author Fabrizio Ferrandi, Cristiana Bolchini
 * @copyright Copyright (C) Fabrizio Ferrandi, Cristiana Bolchini
 **/

class BibTeX_Plugin
{
	var $database_interface;

	/**
	 * Constructor instantiates the plugin and registers all actions and filters
	 *
	 * @return void
	 **/
	function BibTeX_Plugin ()
	{
		$this->database_interface = new BT_database;
		@include dirname( __FILE__ ).'/models/BibTeX.php';
		if (is_admin ())
		{
			if(class_exists('Structures_BibTex')) {
				add_action('admin_menu', array( &$this, 'BibTeX_admin_menu'));
				register_activation_hook(__FILE__,array(&$this,'db_setup'));
			}
			elseif(!class_exists('Structures_BibTex') && is_admin()) {
				add_action('admin_notices', array(&$this,'BibTeX_admin_message'));	
			}
			//CB some formatting for the admin 		
			add_action('admin_head', array( &$this, 'BibTeX_admin_css'));
		}
		add_filter('the_content', array( &$this, 'BibTeX_filter_content'));
		add_filter('the_excerpt', array( &$this, 'BibTeX_filter_content'));
		//CB bibtex appearing and disapperaring
		add_action('wp_head', array( &$this, 'BibTeX_plugin_user_head'));
	}

	/**
	 * Error message in case BibTex.php cannot be correctly parsed.
	 */
	function BibTeX_admin_message()
	{
		echo '
			<div id="BibTeX-message" class="updated fade">
				<p style="line-height: 1.3em;"><b>BibTeX extension to Wordpress could not be enabled because the "<code>Structures_BibTex</code>" Pear class is not available.</b>Please contact us for a fix.</p>
			</div>
			';
	}

	/**
	 *  Parsing template [bibtex allow=PUBTYPE year=PUBYEAR]
	 *
	 **/
	function BibTeX_filter_content($text)
	{
		//CB 20120418 - do not eliminate CR if bibtex is not there ...
		if(strpos($text, 'bibtex') === false)
			return $text;

		$regex = "/\[bibtex\s+(.*)]/U";
		$string = preg_replace('!\s+!', ' ', $text);
		//return $this->tag_pattern_management_nocallback($output);
		return preg_replace_callback ($regex,array (&$this, 'tag_pattern_management_mult'), $string);

	}
	
	function tag_pattern_management_mult($bibItems)
	{
		global $wpdb;

		$regex = "/\[bibtex\s+(.*)]/U";	
		$pattern = '/(allow|category|deny|cite|keyword|author|year|latest)=([a-zA-Z0-9]+(\\.[_A-Za-z0-9-]+)*)/i'; //[^>]+/i';
		$output = preg_replace($regex,"$1",$bibItems);
		$outString = $bibItems[0] . ': ';
		preg_match_all($pattern, $output[0], $result);
	/*
	 * Array ( [0] => Array ( [0] => allow=article [1] => year=2010 ) 
			   [1] => Array ( [0] => allow [1] => year ) 
			   [2] => Array ( [0] => article [1] => 2010 ) ) 
	 * 
	 * */	

		$isYear = 0;
		$isAllow = false;
		$isCite = false;
		$isAuthor = '';
		$isLatest = 0;
		$isKeyword = false;
		$isDeny = false;
		$isCategory = false;


		$condString = ' WHERE ';
		$nCond = count($result[1]);  /* or count($result[2]); */
		$iCond = 0;
		if ($result[1][$iCond] == 'year'){					/* year */
			$isYear = $result[2][$iCond];
			$condString .=  $result[0][$iCond];
		} else if ($result[1][$iCond] == 'allow') {			/* bibtex citation type */
			$isAllow = true;
			$condString .= ' type=\'' . $result[2][$iCond] . '\'';
		} else if ($result[1][$iCond] == 'deny') {			/* avoid bibtex citation type */
			$isDeny = true;
			$condString .= ' NOT type=\'' . $result[2][$iCond] . '\'';
		} else if ($result[1][$iCond] == 'keyword') {			/* keywords */
			$isKeyword = true;
			$condString .= ' INSTR(UPPER(keywords), UPPER(\'' . $result[2][$iCond] . '\')) > 0';
		} else if ($result[1][$iCond] == 'category') {			/* category */
			$isCategory = true;
			$condString .= ' UPPER(cat.name)=UPPER(\'' . $result[2][$iCond] . '\') ';
		} else if ($result[1][$iCond] == 'cite') {			/* bibtex single citation */
			$isCite = true;
			$condString .= ' cite=\'' . $result[2][$iCond] . '\'';
		} else if ($result[1][$iCond] == 'author') {			/* bibtex author */
			$isAuthor = $result[2][$iCond];
		} else if ($result[1][$iCond] == 'latest') {			/* latest publications, no filtering */
			$isLatest = $result[2][$iCond];
		} else {   /* UNSUPPORTED PATTERN */
			return 'UNSUPPORTED PATTERN ' . $output[0] . ' SEE the README for allowed patterns key=value';
		}
		$iCond = 1;
		while($iCond < $nCond){
			if ($result[1][$iCond] =='year'){					/* year */
				$isYear = $result[2][$iCond];
				$condString .= ' AND ' . $result[0][$iCond];
			} else if ($result[1][$iCond] =='allow') {			/* bibtex citation type */
				$isAllow = true;
				$condString .= ' AND type=\'' . $result[2][$iCond] . '\'';
			} else if ($result[1][$iCond] == 'deny') {			/* bibtex citation type */
				$isDeny = true;
				$condString .= ' NOT  type=\'' . $result[2][$iCond] . '\'';
			} else if ($result[1][$iCond] == 'keyword') {			/* keywords */
				$isKeyword = true;
				$condString .= ' INSTR(UPPER(keywords), UPPER(\'' . $result[2][$iCond] . '\')) > 0 ';
			} else if ($result[1][$iCond] == 'category') {			/* category */
				$isCategory = true;
				$condString .= ' UPPER(cat.name)=UPPER(\'' . $result[2][$iCond] . '\') ';
			} else if ($result[1][$iCond] == 'cite') {			/* bibtex single citation */ /*  SHOULD NOT HAPPEN */
				$isCite = true;
				$condString .= ' AND cite=\'' . $result[2][$iCond]  .'\'';
			} else if ($result[1][$iCond] == 'author') {			/* bibtex author */
				$isAuthor = $result[2][$iCond];
			} else if ($result[1][$iCond] == 'latest') {			/* latest publications, no filtering */
				$isLatest = $result[2][$iCond];
			} 		
			$iCond += 1; 
		}
		if($isYear == 0)
			$condString .= ' ORDER BY yy DESC, mm DESC';
		$strmsg = '';
		/* First part of the query, when necessary */
		if($isAuthor != ''){ /* publications by a specific author */
			$pids= $this->getPublicationsByAuthorName($isAuthor); /* author's lastname */
			if(!empty($pids)){
				/* potentially more than one ... */
				$num = count($pids);
				$fulllist = "<ul class='publist'>";
				for($i=0; $i < $num; $i++){
					$pid = $pids[$i]->pubid;
					$pubstring = $this->get_full_publication_info($pid);
					if(($isYear > 0 && strrpos($pubstring, ' yy = {' . $isYear . '}') > 0) || $isYear == 0)
							$fulllist .= "<li class='li" . ($i % 2). "'>" . $pubstring . "</li>\n";
				}
				$fulllist .= "</ul>";
				if($fulllist == "<ul class='publist'></ul>")	/* no publications in that year */
					$strmsg = "No publications by author '" .$isAuthor . '\' in year ' . $isYear;
				else
					$strmsg = $fulllist;
			} else {
				$strmsg = "No publications by author '" .$isAuthor;
			}		
		} else if ($isLatest > 0) {
				$pids= $this->getMostRecentPublications($isLatest); /* number X of latest publications */
				if(!empty($pids)){
					/* potentially more than one ... */
					$num = count($pids);
					$fulllist = "<ul class='publist'>";
					for($i=0; $i < $num; $i++){
						$pid = $pids[$i]->pubid;
						$fulllist .= "<li>" . $pids[$i]->year . "&nbsp;|&nbsp;" . $pids[$i]->month . "<br/>"  . $this->get_full_publication_info($pid) . "</li>\n";
					}
					$fulllist .= "</ul>";				
					$strmsg = $fulllist;
				} else {
					$strmsg = "<br/>";				
				}		
			
		} else {
			$strSQL = '';
			if($isAllow || $isDeny || $isCite || $isKeyword || $isYear) {
				$strSQL = 'SELECT pubid FROM '. $this->database_interface->get_tablename('main');
			} else if($isCategory) {
				$strSQL = "SELECT pub.id FROM ".$this->database_interface->get_tablename('categories')." AS pub INNER JOIN ".$this->database_interface->get_tablename('main_categories')." AS cat ON pub.categories=cat.id";
			} 
			/* query execution */
			$strSQL .= $condString;
			$query = $wpdb->prepare($strSQL);
			$pids= $wpdb->get_results($query);   /* one or more */
			if(!empty($pids)){
				/* potentially more than one ... */
				$num = count($pids);
				$fulllist = "<ul class='publist'>";
				for($i=0; $i < $num; $i++){
					$pid = $pids[$i]->pubid;
					$pubstring = $this->get_full_publication_info($pid);
					$fulllist .= "<li";
					if(!$isCite)
						$fulllist .= " class='li" . ($i % 2). "'";
					
					$fulllist .= ">" . $pubstring . "</li>\n";					
				}
				$fulllist .= "</ul>";
				$strmsg = $fulllist;
			} else 
				$strmsg = 'No publication matching the selected criteria ';
		}
		return $strmsg;
	}
	
	
	
	/**
	 * Manages the possible tags into patterns [bibtex patternKey=value patternKey2=value2]
	 * $bibItems is Array with [0] bibtex [1] patternKey=value [2] patternKey [3] value 
	 * @param object $bibItems
	 * @return list of publications matching the pattern
	 */
	function tag_pattern_management_multiple($bibItems)
	{
		/* the db */
		global $wpdb;


		$string = preg_replace('!\s!', '=', $bibItems[1]);
		$pairs = explode("=",$string);
		/* $pairs[0]=key $pairs[1]=value */
		if($pairs[0]=='cite') /* single bib citation */
		{
			/* retrieves the information on the publication */
			$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main')." WHERE cite=%s;", trim($pairs[1]));
			$pid= $wpdb->get_var($query); /* publication ID */
			if(!empty($pid)){
				return $this->get_full_publication_info($pid);
			} else {
				return "bibtex citation " . $pairs[1] . " not found.<br/>";
			}
		} else if($pairs[0]=='allow') /* citation type */
		{
			if($pairs[2]=='year') 
				$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main'). " WHERE type=%s AND yy=%s;", trim($pairs[1]), trim($pairs[3]));
			else
				$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main'). " WHERE type=%s ORDER BY yy DESC, mm DESC;", trim($pairs[1]));
			
			$pids= $wpdb->get_results($query); /* publication ID */
			if(!empty($pids)){
				/* potentially more than one ... */
				$num = count($pids);
				$fulllist = "<ul class='publist'>";
				for($i=0; $i < $num; $i++){
					$pid = $pids[$i]->pubid;
					$fulllist .= "<li class='li" . ($i % 2). "'>" . $this->get_full_publication_info($pid) . "</li>\n";
				}
				$fulllist .= "</ul>";
				return $fulllist;
			} else {
				if($pairs[2]=='year')
					return "No publications with type " . $pairs[1] . " in year " . $pairs[3] . "<br/>";				
				return "No publications with type " . $pairs[1] . " <br/>";				
			}
				
		} else if($pairs[0]=='deny') /* exclusion type */
		{
			$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main')." WHERE NOT type=%s ORDER BY year DESC;", $pairs[1]);
			$pids= $wpdb->get_results($query); /* publication ID */
			if(!empty($pids)){
				/* potentially more than one ... */
				$num = count($pids);
				$fulllist = "<ul class='publist'>";
				for($i=0; $i < $num; $i++){
					$pid = $pids[$i]->pubid;
					$fulllist .= "<li class='li" . ($i % 2). "'>" . $this->get_full_publication_info($pid) . "</li>\n";
				}
				$fulllist .= "</ul>";				
				return $fulllist;
			} else {
				return "";				
			}			
		} else if($pairs[0]=='category')
		{
			if($pairs[2]=='year') 
				$query = $wpdb->prepare("SELECT pub.id FROM ".$this->database_interface->get_tablename('categories')." AS pub INNER JOIN ".$this->database_interface->get_tablename('main_categories')." AS cat ON pub.categories=cat.id WHERE UPPER(cat.name)=UPPER(%s) AND yy=%s;", trim($pairs[1]), trim($pairs[3]));
			else
				$query = $wpdb->prepare("SELECT pub.id FROM ".$this->database_interface->get_tablename('categories')." AS pub INNER JOIN ".$this->database_interface->get_tablename('main_categories')." AS cat ON pub.categories=cat.id WHERE UPPER(cat.name)=UPPER(%s);", trim($pairs[1]));

			$pids= $wpdb->get_results($query); /* publication ID */
			if(!empty($pids)){
				/* potentially more than one ... */
				$num = count($pids);
				$fulllist = "<ul class='publist'>";
				for($i=0; $i < $num; $i++){
					$pid = $pids[$i]->id;
					$fulllist .= "<li class='li" . ($i % 2). "'>" . $this->get_full_publication_info($pid) . "</li>\n";
				}
				$fulllist .= "</ul>";				
				return $fulllist;
			} else {
				if($pairs[2]=='year')
					return "No publications with category = " . $pairs[1] . " in year " . $pairs[3] . " <br/>";
				return "No publications with category = " . $pairs[1] . " <br/>";				
			}
		} else if($bibItems[2]=='keyword')
		{
			$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main')." WHERE INSTR(UPPER(keywords), UPPER(%s)) > 0;", trim($pairs[1]));
			
			$pids= $wpdb->get_results($query); /* publication ID */
			if(!empty($pids)){
				/* potentially more than one ... */
				$num = count($pids);
				$fulllist = "<ul class='publist'>";
				for($i=0; $i < $num; $i++){
					$pid = $pids[$i]->pubid;
					$fulllist .= "<li class='li" . ($i % 2). "'>" . $this->get_full_publication_info($pid) . "</li>\n";
				}
				$fulllist .= "</ul>";				
				return $fulllist;
			} else {
				return "No publications with keyword '" . $pairs[1] . "'<br/>";				
			}		
		} else if($pairs[0]=='year') /* select by year */
			{
				$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main')." WHERE year=%s;", $pairs[1]);
				$pids= $wpdb->get_results($query); /* publication ID */
				if(!empty($pids)){
					/* potentially more than one ... */
					$num = count($pids);
					$fulllist = "<ul class='publist'>";
					for($i=0; $i < $num; $i++){
						$pid = $pids[$i]->pubid;
						$fulllist .= "<li class='li" . ($i % 2). "'>" . $this->get_full_publication_info($pid) . "</li>\n";
					}
					$fulllist .= "</ul>";				
					return $fulllist;
				} else {
					return "No publications in year " . $pairs[1] . "<br/>";				
				}		
		}  else if($pairs[0]=='author') /* select by author's lastname */
			{
				$pids= $this->getPublicationsByAuthorName($pairs[1]); /* author's lastname */
				if(!empty($pids)){
					/* potentially more than one ... */
					$num = count($pids);
					$fulllist = "<ul class='publist'>";
					for($i=0; $i < $num; $i++){
						$pid = $pids[$i]->pubid;
						$fulllist .= "<li>" . $this->get_full_publication_info($pid) . "</li>\n";
					}
					$fulllist .= "</ul>";				
					return $fulllist;
				} else {
					return "No publications by author '" .$pairs[1] . "'<br/>";				
				}		
		} else if($pairs[0]=='latest') /* select most recent X publications */
			{
				$pids= $this->getMostRecentPublications($pairs[1]); /* number X of latest publications */
				if(!empty($pids)){
					/* potentially more than one ... */
					$num = count($pids);
					$fulllist = "<ul class='publist'>";
					for($i=0; $i < $num; $i++){
						$pid = $pids[$i]->pubid;
						$fulllist .= "<li>" . $pids[$i]->year . "&nbsp;|&nbsp;" . $pids[$i]->month . "<br/>"  . $this->get_full_publication_info($pid) . "</li>\n";
					}
					$fulllist .= "</ul>";				
					return $fulllist;
				} else {
					return "<br/>";				
				}		
		} else
			/* returns un managed pattern */
			return $bibItems[0];
	}	
	
	/**
	 * Manages the possible tags into patterns [bibtex patternKey=value]
	 * $bibItems is Array with [0] full string [1] patternKey=value [2] patternKey [3] value
	 * @param object $bibItems
	 * @return list of publications matching the pattern
	 */
	function tag_pattern_management($bibItems)
	{
		/* the db */
		global $wpdb;
		if($bibItems[2]=='cite') /* single bib citation */
		{
			/* retrieves the information on the publication */
			$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main')." WHERE cite=%s;", trim($bibItems[3]));
			$pid= $wpdb->get_var($query); /* publication ID */
			if(!empty($pid)){
				return $this->get_full_publication_info($pid);
			} else {
				return "bibtex citation " . $bibItems[3] . " not found.<br/>";
			}
		} else if($bibItems[2]=='allow') /* citation type */
		{
			if($bibitems[4]=='year') 
				$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main'). " WHERE type=%s AND yy=%s;", trim($bibItems[3]), trim($bibItems[5]));
			else
				$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main'). " WHERE type=%s ORDER BY yy DESC, mm DESC;", trim($bibItems[3]));
			
			$pids= $wpdb->get_results($query); /* publication ID */
			if(!empty($pids)){
				/* potentially more than one ... */
				$num = count($pids);
				$fulllist = "<ul class='publist'>";
				for($i=0; $i < $num; $i++){
					$pid = $pids[$i]->pubid;
					$fulllist .= "<li class='li" . ($i % 2). "'>" . $this->get_full_publication_info($pid) . "</li>\n";
				}
				$fulllist .= "</ul>";
				return $fulllist;
			} else {
				if($bibitems[4]=='year')
					return "No publications with type " . $bibItems[3] . " in year " . $bibItems[5] . "<br/>";				
				return "No publications with type " . $bibItems[3] . " <br/>";				
			}
				
		} else if($bibItems[2]=='deny') /* exclusion type */
		{
			$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main')." WHERE NOT type=%s ORDER BY year DESC;", $bibItems[3]);
			$pids= $wpdb->get_results($query); /* publication ID */
			if(!empty($pids)){
				/* potentially more than one ... */
				$num = count($pids);
				$fulllist = "<ul class='publist'>";
				for($i=0; $i < $num; $i++){
					$pid = $pids[$i]->pubid;
					$fulllist .= "<li class='li" . ($i % 2). "'>" . $this->get_full_publication_info($pid) . "</li>\n";
				}
				$fulllist .= "</ul>";				
				return $fulllist;
			} else {
				return "";				
			}			
		} else if($bibItems[2]=='category')
		{
			$query = $wpdb->prepare("SELECT pub.id FROM ".$this->database_interface->get_tablename('categories')." AS pub INNER JOIN ".$this->database_interface->get_tablename('main_categories')." AS cat ON pub.categories=cat.id WHERE UPPER(cat.name)=UPPER(%s);", trim($bibItems[3]));
			$pids= $wpdb->get_results($query); /* publication ID */
			if(!empty($pids)){
				/* potentially more than one ... */
				$num = count($pids);
				$fulllist = "<ul class='publist'>";
				for($i=0; $i < $num; $i++){
					$pid = $pids[$i]->id;
					$fulllist .= "<li class='li" . ($i % 2). "'>" . $this->get_full_publication_info($pid) . "</li>\n";
				}
				$fulllist .= "</ul>";				
				return $fulllist;
			} else {
				return "No publications with category = " . $bibItems[3] . " <br/>";				
			}
		} else if($bibItems[2]=='keyword')
		{
			$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main')." WHERE INSTR(UPPER(keywords), UPPER(%s)) > 0;", trim($bibItems[3]));
			$pids= $wpdb->get_results($query); /* publication ID */
			if(!empty($pids)){
				/* potentially more than one ... */
				$num = count($pids);
				$fulllist = "<ul class='publist'>";
				for($i=0; $i < $num; $i++){
					$pid = $pids[$i]->pubid;
					$fulllist .= "<li class='li" . ($i % 2). "'>" . $this->get_full_publication_info($pid) . "</li>\n";
				}
				$fulllist .= "</ul>";				
				return $fulllist;
			} else {
				return "No publications with keyword '" . $bibItems[3] . "'<br/>";				
			}		
		} else if($bibItems[2]=='year') /* select by year */
			{
				$query = $wpdb->prepare("SELECT pubid FROM ".$this->database_interface->get_tablename('main')." WHERE year=%s;", $bibItems[3]);
				$pids= $wpdb->get_results($query); /* publication ID */
				if(!empty($pids)){
					/* potentially more than one ... */
					$num = count($pids);
					$fulllist = "<ul class='publist'>";
					for($i=0; $i < $num; $i++){
						$pid = $pids[$i]->pubid;
						$fulllist .= "<li class='li" . ($i % 2). "'>" . $this->get_full_publication_info($pid) . "</li>\n";
					}
					$fulllist .= "</ul>";				
					return $fulllist;
				} else {
					return "No publications in year " . $bibItems[3] . "<br/>";				
				}		
		}  else if($bibItems[2]=='author') /* select by author's lastname */
			{
				$pids= $this->getPublicationsByAuthorName($bibItems[3]); /* author's lastname */
				if(!empty($pids)){
					/* potentially more than one ... */
					$num = count($pids);
					$fulllist = "<ul class='publist'>";
					for($i=0; $i < $num; $i++){
						$pid = $pids[$i]->pubid;
						$fulllist .= "<li>" . $this->get_full_publication_info($pid) . "</li>\n";
					}
					$fulllist .= "</ul>";				
					return $fulllist;
				} else {
					return "No publications by author '" . $bibItems[3] . "'<br/>";				
				}		
		} else if($bibItems[2]=='latest') /* select most recent X publications */
			{
				$pids= $this->getMostRecentPublications($bibItems[3]); /* number X of latest publications */
				if(!empty($pids)){
					/* potentially more than one ... */
					$num = count($pids);
					$fulllist = "<ul class='publist'>";
					for($i=0; $i < $num; $i++){
						$pid = $pids[$i]->pubid;
						$fulllist .= "<li>" . $pids[$i]->year . "&nbsp;|&nbsp;" . $pids[$i]->month . "<br/>"  . $this->get_full_publication_info($pid) . "</li>\n";
					}
					$fulllist .= "</ul>";				
					return $fulllist;
				} else {
					return "<br/>";				
				}		
		} else
			/* returns un managed pattern */
			return $bibItems[0];
	}

	/**
	 * Retrieves all the information of a single publication, using the other
	 * functions that get the authors, details, and bibtex
	 * @param object $bibID: the publication ID
	 * @return the formatted string
	 */	
	function get_full_publication_info($bibID)
	{
		$authors = $this->get_publication_authors($bibID);
		$details = $this->get_publication_data($bibID);
		$bibkey = $details['cite'];;
		$bibtexcode = $this->get_publication_bibtex($bibID);
		return $this->format_publication($authors, $details, $bibkey, $bibtexcode);
	}
	
	
	
	/**
	 * retrieves the information of a single publication from the ID value
	 * @param object $bibID: the id of a publication
	 * @return the info on the publication
	 */
	function get_publication_data($bibID)
	{
		/* the db */
		global $wpdb;
		/* field names */
		$allfields = $this->database_interface->getTableFields(array($this->database_interface->get_tablename('main'),$this->database_interface->get_tablename('auth')));
		$bibfields =array_keys($allfields[$this->database_interface->get_tablename('main')]);
		/* record */
		$query = $wpdb->prepare("SELECT * FROM ".$this->database_interface->get_tablename('main')." WHERE pubid=%d;", $bibID);
		$bibdataobj = $wpdb->get_results($query);
		$bibdata = $this->array_combine_emulated($bibfields,$bibdataobj[0]);
		return $bibdata;
	}
	

	/**
	 * retrieves the information on a publication authors, organized in an array
	 * @param object $bibID: the id of a publication
	 * @return returns the set of authors for the publication
	 */
	function get_publication_authors($bibID)
	{
		/* the db */
		global $wpdb;
		/* field names */
		$allfields = $this->database_interface->getTableFields(array($this->database_interface->get_tablename('main'),$this->database_interface->get_tablename('auth')));
		$authfields =array_keys($allfields[$this->database_interface->get_tablename('auth')]);
		/* records */
		$query = $wpdb->prepare("SELECT * FROM ".$this->database_interface->get_tablename('auth')." AS A INNER JOIN ".$this->database_interface->get_tablename('pubauth')." AS PA ON A.Authid = PA.authid WHERE pubid=%d order by num;",$bibID);
		$authrowsobj = $wpdb->get_results($query);
		for($i=0;$i<count($authrowsobj);$i++)
			$authrows[$i]=$this->array_combine_emulated($authfields,$authrowsobj[$i]);
		
		return $authrows;
	}

	
	/**
	 * retrieves the bibtex code of a publication
	 * @param object $bibID: the id of a publication
	 * @return returns the set of authors for the publication
	 */
	function get_publication_bibtex($bibID)
	{
		/* the db */
		global $wpdb;
		/* record */
		$query = $wpdb->prepare("SELECT * FROM ".$this->database_interface->get_tablename('content')." WHERE id=%d;",$bibID);
		$bibstring = $wpdb->get_results($query);
		return $bibstring[0]->content;
	}	
	
	/**
	 * Pretty format of a publication
	 * @param object $authors
	 * @param object $details
	 * @param string $bibcite: bibtex citation key
	 * @param string $code: bibtex code
	 * @return 
	 */
	function format_publication($authors, $details, $bibcite, $code){
		$authstring = "";
		$bibtexcode = "";
		/* authors */
		if ($this->keyExistsOrIsNotEmpty('authorsnames',$details)){  /* author role */
      $authstring = $this->generateAuthorNamesString($authors);			
		} else if ($this->keyExistsOrIsNotEmpty('editor',$details)){ /* editor role */
			$authstring = $row['editor'];
		}
		/* publication details */
		$pub = "";
		if ($this->keyExistsOrIsNotEmpty('title',$details)){
			$pub = "\"".wp_specialchars($details['title']).",\" ";
		}
		if ($this->keyExistsOrIsNotEmpty('journal',$details)){
			$pub .= " in <i>".wp_specialchars($details['journal'])."</i>";
		} else if ($this->keyExistsOrIsNotEmpty('booktitle',$details)) {
			$pub .= " in <i>".wp_specialchars($details['booktitle'])."</i>";
		}
		if ($this->keyExistsOrIsNotEmpty('chapter',$details)){
			$pub .= ", Ch. ".wp_specialchars($details['chapter']);
		}
		if ($this->keyExistsOrIsNotEmpty('series',$details)){
			$pub .= ", ".wp_specialchars($details['series']) . " series";
		}
		if ($this->keyExistsOrIsNotEmpty('volume',$details)){
			$pub .= ", Vol. ".wp_specialchars($details['volume'])."";
		}
		if ($this->keyExistsOrIsNotEmpty('number',$details)){
			/* Technical reports */
			if("TECHREPORT"== strtoupper($details['type'])){
				$pub .= ", Tech. Report no. ".wp_specialchars($details['number'])."";	
				$pub .= ", " . wp_specialchars($details['institution']) ."";	
			} else {
				$pub .= ", No. ".wp_specialchars($details['number'])."";			
			}
		}
		if ($this->keyExistsOrIsNotEmpty('pages',$details)){
			$pub .= ", pp. ". str_replace("--", "-", wp_specialchars($details['pages']));
		}
		if ($this->keyExistsOrIsNotEmpty('organization',$details)){
			$pub .= ", ".wp_specialchars($details['organization']);
		}
		if ($this->keyExistsOrIsNotEmpty('year',$details)){
			$pub .= ", ".wp_specialchars($details['year']);
		}
		$pub .= ".";
		/* abstract */
		$abstract = "";
		if($this->keyExistsOrIsNotEmpty('abstract',$details)){
			$pub .= '<br/><a class="toggle" href="#' . sanitize_title_with_dashes($bibcite) . '_abs" >abstract</a>';
			$abstract = '<div class="bibtex abstract" id="' . sanitize_title_with_dashes($bibcite) . '_abs"><strong>Abstract: </strong><em>' . wp_specialchars($details['abstract']) . '</em></div>';
		}				
		/* doi */
		$doi = "";
		if($this->keyExistsOrIsNotEmpty('doi',$details)){
			$doi = $this->toURL(wp_specialchars($details['doi']));
		}		
		/* bibtex code -- if available */
		if($code != ''){ /* onclick="Effect.toggle(\''. $bibcite . "','appear'); return false\" */
//			if("" == $abstract)
//				$biblogo = "<br/>";
			$biblogo .= '<a class="toggle" href="#' . sanitize_title_with_dashes($bibcite) . '" >bibtex</a>';

			$pub .= "\n" . $biblogo;
			if("" == $doi)
				$bibtexcode = "<br/>";
			$bibtexcode .= $this->format_bibtex_code($bibcite, $code);
		} else {
			$doi .= '<br/>';
		}
		return wp_specialchars($authstring) . ", " . $pub . $doi . $abstract . $bibtexcode;
	}
	
  /**
   * Generates a string with all authors names, First Middle Last, from an array of elements
   * @param object $authors: the array of first middle and last elements
   * @return The string with all authors, separated by commas except for the last one, separated 
   * by the AND word.
   */
  function generateAuthorNamesString($authors)
  {
    for($i=0;$i<count($authors);$i++){
        if($i!=0){
          if($i==count($authors)-1){
            $authstring = $authstring." and ";
          } else {
            $authstring = $authstring.", ";
          }
        }
        if($this->keyExistsOrIsNotEmpty('first',$authors[$i])){
          $fnames = array();
          $fnames = split(" ",str_replace('~', " ", $authors[$i]['first']));
          for($j=0; $j < sizeof($fnames); $j++){
            //$authstring = $authstring." ". $fnames[$j];
            //CB: initials only
            $authstring = $authstring." ". substr($fnames[$j],0,1) . ". ";
          }
        }
        if($this->keyExistsOrIsNotEmpty('middle',$authors[$i])){
          $authstring = $authstring." ". substr($authors[$i]['middle'],0,1) . ". ";
        }
        if($this->keyExistsOrIsNotEmpty('last',$authors[$i])){
          $authstring = $authstring." ".str_replace('~', "&nbsp;", $authors[$i]['last']);
        }
    }
    str_replace("  ", " ", $authstring);
    $authstring = trim($authstring);
    return $authstring;
  }

  /**
   * Generates a shortened string with the authors' names. If there are at most two authors,
   * they are both listed, otherwise et al. is used
   **/
   function generateAuthorShortNameString($autharray)
   {
        $authnames = '';
        $shortauthnames = '';
        $authorcount=0;
        $authortot=count($autharray);
        foreach ($autharray as $author){
          $authorcount++;
//        foreach ($author as $afield => $avalues)
//          $author[$afield]=ereg_replace('[{}]','',$wpdb->escape($avalues));
          
          $shortauthnames =$shortauthnames.$author['first']." ";
 	        $shortauthnames =$shortauthnames.substr($author['middle'],0,1)." ";
   	      $shortauthnames =$shortauthnames.$author['last'];
     	    if($authortot>2){
       	    $shortauthnames = $shortauthnames." <i>et al.</i>";
         	  break;
         	} else if ($authortot==2 && $authorcount ==1)
           	$shortauthnames = $shortauthnames." and ";
        }
        return $shortauthnames;
   }

  
	/**
	 * creates a <div> for the bibtex code  
	 * @param string $bibkey: the bibtex identifier
	 * @param string $bibstring: the bibtex string
	 * @return the html string for the bibtex code formatting
	 */
	function format_bibtex_code($bibkey,$bibstring){
		$bibtexstring = "<div class='bibtex' id='" . sanitize_title_with_dashes($bibkey) . "'><code>" .  ($this->formatBibtex($bibkey, $bibstring)) . "</code></div>";
		return $bibtexstring;
	}
	
	/**
	 * A little more formatting
	 * @param object $strURL
	 * @return 
	 */
	function formatBibtex($key, $entry){
		$order = array("},");
		$replace = "}, <br />\n &nbsp;";
		
		$entry = preg_replace('/\s\s+/', ' ', trim($entry));
		$new_entry = str_replace($order, $replace, $entry);
		$new_entry = str_replace(", author", ", <br />\n &nbsp;&nbsp;author", $new_entry);
		$new_entry = str_replace(", Author", ", <br />\n &nbsp;&nbsp;author", $new_entry);
		$new_entry = str_replace(", AUTHOR", ", <br />\n &nbsp;&nbsp;author", $new_entry);
		$new_entry = preg_replace('/\},?\s*\}$/', "}\n}", $new_entry);
		$new_entry = str_replace(" " . $key, $key, $new_entry);
		$new_entry = str_replace($key .", ", $key . ",<br />\n &nbsp;&nbsp;", $new_entry);
		/* bold */
		$new_entry = str_replace($key, "<b>" . $key . "</b>", $new_entry);
	    return $new_entry;
	}	
	
	/**
	 * formats a link to doi
	 * @param string $strURL
	 * @return the formatted HTM string
	 */
	function toURL($strURL) {
		$string = ' <a target="_blank" title="opens in a new window" href="' . $strURL . '" class="followdoi">doi</a>';
		return $string;
	}


	
  /* PLUGIN MENU */
	
	
	function BibTeX_admin_menu ()
	{
  		$allowed_group = 'edit_posts';
  		
		// Add the admin panel pages for BibTeX. Use permissions pulled from above
   		if (function_exists('add_menu_page')) 
     	{
       		add_menu_page(__("BibTeX Plugin", 'BibTeX-plugin'), __("BibTeX Plugin", 'BibTeX-plugin'), $allowed_group, 'BibTeX-plugin', array( &$this, 'view_references'));
     	}
   		if (function_exists('add_submenu_page')) 
     	{
       		add_submenu_page('BibTeX-plugin', __("View References", 'BibTeX-plugin'), __("View References", 'BibTeX-plugin'), $allowed_group, 'BibTeX-plugin', array( &$this, 'view_references'));
       		add_submenu_page('BibTeX-plugin', __("Input References", 'BibTeX-plugin'), __("Input References", 'BibTeX-plugin'), $allowed_group, 'BibTeX-input-references', array( &$this, 'input_references'));
       		add_submenu_page('BibTeX-plugin', __("View Categories", 'BibTeX-plugin'), __("View Categories", 'BibTeX-plugin'), $allowed_group, 'BibTeX-view-categories', array( &$this, 'view_categories'));
       		add_submenu_page('BibTeX-plugin', __("View Authors", 'BibTeX-plugin'), __("View Authors", 'BibTeX-plugin'), $allowed_group, 'BibTeX-view-authors', array( &$this, 'view_authors'));
       		add_submenu_page('BibTeX-plugin', __("Merge Authors", 'BibTeX-plugin'), __("Merge Authors", 'BibTeX-plugin'), $allowed_group, 'BibTeX-merge-authors', array( &$this, 'merge_authors'));
       		add_submenu_page('BibTeX-plugin', __("Configuration", 'BibTeX-plugin'), __("Configuration", 'BibTeX-plugin'), 'manage_options', 'BibTeX-configuration', array( &$this, 'configuration'));
       		//add_action( "admin_head", 'calendar_add_javascript' );
     	}
    }
	
    function view_references()
    {
    	$task = isset ($_POST['task']) ? $_POST['task'] : '';
    	$task = empty($task) ? (isset ($_GET['task']) ? $_GET['task'] : '') : $task;
   		switch($task)
    	{
			case "remove":
				$this->delBib();$this->viewBib();
				break;
			case "allDelete":
				$this->delAllBib();$this->viewBib();
				break;
			case "edit":
				$this->editBib();
				break;
			case "saveEdit":
				$this->saveEditBib();$this->viewBib();
				break;
			/* CB */
			case "deleteAuthorPublication":
				$this->delAuthorPub();$this->editBib();
				break;
			case 'cancel':
				$this->checkin();$this->viewBib();
				break;
			default:
				$this->viewBib();
				break;
		  }
    }
    
    function delBib()
    {
    	$id = isset ($_POST['post']) ? $_POST['post'] : array();
    	global $wpdb;
    	foreach($id as $bid)
    	{
				$query = $wpdb->prepare("DELETE from ".$this->database_interface->get_tablename('main')." where pubid=%d", $bid);
				$wpdb->query($query);
				$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('content')." where id=(%d)", $bid);
				$wpdb->query($query);
				$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('categories')." where id=(%d)", $bid);
				$wpdb->query($query);
				
				/* DELETE publication authors reference*/
				$query = $wpdb->prepare("DELETE from ".$this->database_interface->get_tablename('pubauth')." where pubid=%d", $bid);
				$wpdb->query($query);
				/* delete all authors without publications */
				$this->deleteAuthorsNoPub();			
			}
    }

	function delAuthorPub()
	{
 		$pubid = $_GET['id'];
		$authid = $_GET['authid'];
		$this->deletePublicationAuthor($authid,$pubid);
  }
  

	/**
	 * Deletes all authors without a publication 
	 */
	function deleteAuthorsNoPub()
	{

    	global $wpdb;
			$query = $wpdb->prepare("SELECT DISTINCT A.authid from ".$this->database_interface->get_tablename('auth')."  A LEFT JOIN ".$this->database_interface->get_tablename('pubauth')." PA ON A.authid = PA.authid WHERE PA.pubid IS NULL;");
		$authordata = $wpdb->get_results($query);
		foreach($authordata as $auth){
			$query = $wpdb->prepare("DELETE from ".$this->database_interface->get_tablename('auth')." WHERE authid=".$auth->authid.";");
			$wpdb->query($query);
		}		
	}

    function delAllBib()
    {
    	global $wpdb;
    	$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('main'));
			$wpdb->query($query);
			$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('auth'));
			$wpdb->query($query);
			$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('content'));
			$wpdb->query($query);
			$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('categories'));
			$wpdb->query($query);
    }
	
    function editBib()
    {
    	$id = isset ($_GET['id']) ? $_GET['id'] : '';
    	$id = empty($id) ? (isset ($_POST['id']) ? $_POST['id'] : '') : $id;
    	$allfields = $this->database_interface->getTableFields(array($this->database_interface->get_tablename('main'),$this->database_interface->get_tablename('auth')));
		$fields =array_keys($allfields[$this->database_interface->get_tablename('main')]);
		$authfields =array_keys($allfields[$this->database_interface->get_tablename('auth')]);
		global $wpdb;
		$query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('main')." where pubid=(%d)", $id);
		$rows = $wpdb->get_results($query);
		$row=$this->array_combine_emulated($fields,$rows[0]);
		
		// fail if checked out not by 'me'
		$user = wp_get_current_user ();
		if ($row['checkedout']!=$user->ID && $row['checkedout']!=0)
		{
			$this->render_admin ('checkedout');
		}
		else
		{
			//check out
			$row['checkedout']= $user->ID;
			$query = $wpdb->prepare("update ".$this->database_interface->get_tablename('main')." set checkedout=%d where pubid=%d", $user->ID, $id);
			$wpdb->query($query);

			$query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('auth')." A INNER JOIN " .$this->database_interface->get_tablename('pubauth'). " PA ON A.authid = PA.authid where PA.pubid=%d order by num", $id);
			$authrowsobj=$wpdb->get_results($query);
	
			for($i=0;$i<count($authrowsobj);$i++)
			{
				$authrows[$i]=$this->array_combine_emulated($authfields,$authrowsobj[$i]);
			}
			$authornumber = isset ($_POST['authornumber']) ? $_POST['authornumber'] : count($authrows);
			if($authornumber>count($authrows))
			{
				for($i=count($authrows);$i<$authornumber;$i++)
				{
					$authrows[$i]=$this->array_combine_emulated($authfields,array("","","","","",""));
				}
			}
			$query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('main_categories')." order by id");
			$catsobj = $wpdb->get_results($query);
			foreach($catsobj as $cat)
			{
				$cats[$cat->id]=$cat->name;
			}
			//get category info
			$query = $wpdb->prepare("SELECT categories from ".$this->database_interface->get_tablename('categories')." where id=(%d)", $id);
			$catrows=$wpdb->get_results($query);
			$authfields = array_diff($authfields,array('id'));
			$authfields = array_diff($authfields,array('num'));
			$fields = array_diff($fields,array('pubid'));
			$fields = array_diff($fields,array('authorsnames'));
			$fields = array_diff($fields,array('shortauthnames'));
			$fields = array_diff($fields,array('checkedout'));
			$this->render_admin ('bibEdit', array ('row' => $row, 'authrows' => $authrows, 'cats' => $cats, 'id' => $id, 'fields' => $fields, 'authfields' => $authfields, 'authornumber' => $authornumber, 'catrows' => $catrows));
		}
    }

	function array_combine_emulated( $keys, $vals )
	{
 		$keys = array_values( (array) $keys );
 		$vals = array_values( (array) $vals );
 		$n = max( count( $keys ), count( $vals ) );
 		$r = array();
 		for( $i=0; $i<$n; $i++ )
 		{
  			$r[ $keys[ $i ] ] = $vals[ $i ];
 		}
 		return $r;
	}
	
    function saveEditBib()
    {
    	global $wpdb;
		$query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('config'));
		$querysets = $wpdb->get_results($query);
		foreach($querysets as $config)
		{
			$sets[$config->variable]=$config->value;
		}    
		$catIds = $_POST['category'];
		$id = $_POST['id'];
		$authornumber = $_POST['authornumber'];
		$errors="";
		$bibtex = new Structures_BibTex();
		$minibibtex = new Structures_BibTex();
		$bibtex->maxEntryLength = 10000;
		$minibibtex->maxEntryLength = 10000;
		//get old content from db.
		$query = $wpdb->prepare("SELECT content from ".$this->database_interface->get_tablename('content')." where id=(%d);", $id);
		$content= $wpdb->get_results($query);
		$minibibtex->content = $content;
		$minibibtex->parse();
		$newdata = $minibibtex->data[0];
		$allfields = $this->database_interface->getTableFields(array($this->database_interface->get_tablename('main'),$this->database_interface->get_tablename('auth')));
		$fields =array_keys($allfields[$this->database_interface->get_tablename('main')]);
//		$authfields =array_keys($allfields[$this->database_interface->get_tablename('auth')]);
    	
//		$authfields = array_diff($authfields,array('authid'));
//		$authfields = array_diff($authfields,array('num'));
		$fields = array_diff($fields,array('pubid'));
		$fields = array_diff($fields,array('authorsnames'));
		$fields = array_diff($fields,array('shortauthnames'));
		$fields = array_diff($fields,array('checkedout'));
		foreach($fields as $field)
		{
			$stringin= isset ($_POST[$field]) ? $_POST[$field] : '';
			if(''!=$stringin)
			{
				$newdata[$field]=$stringin;
			}
			else
			{
				if(array_key_exists($field,$newdata))
				{
					//old data needs to be deleted
					unset($newdata[$field]); 
					$strSQL ="update ".$this->database_interface->get_tablename('content')." set " .$field. "=NULL where pubid=". $id .";";
					$query = $wpdb->prepare(strSQL);
					$wpdb->query($query);
				}
			}
		}
    /**CB NOT WITH NEW AUTHOR MANAGEMENT */
    /*
		$newauthor=array();
		for($i=0;$i<$authornumber;$i++)
		{
			foreach($authfields as $authfield)
			{
				$stringin=isset ($_POST[$authfield.$i]) ? $_POST[$authfield.$i] : '';
				$newdata['author'][$i][$authfield] = $stringin;
			}
			if($newdata['author'][$i]['last']!='')
			{
				$newauthor[]=$newdata['author'][$i];
			}
		}
		if(count($newauthor)>0)
		{
			$newdata['author']=$newauthor;
		}
		$bibtex->addEntry($newdata);
		//check all fields allowed in mysql
		$fields[]='author';
		foreach ($newdata as $fieldsgiven => $valuesgiven)
		{
			if(!in_array($fieldsgiven,$fields))
			{
				unset($newdata[$fieldsgiven]); 
			}
		}
		$authexists = 0;
		if(array_key_exists('author',$newdata))
		{
			$authexists = 1;
			$autharray = $newdata['author'];
			$newdata = array_diff($newdata,$autharray);
		}
    **/
    //prepare statement for inserting fields
		foreach($newdata as $key=>$data)
		{
			$updates[] = $key."='".$wpdb->escape($data)."'";
		}
    if(count($updates))
		{
			$update = implode(",", array_values($updates));
			$query = "update ".$this->database_interface->get_tablename('main')." set ".$update." where pubid=".$id;
			$wpdb->query($query);
		}

		//prepare statement for author info
		/*
		if($authexists)
		{
			$authnames = '';
			$shortauthnames = '';
			//delete old values 
			$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('auth')." where id=%d", $id);
			$wpdb->query($query);
			$authorcount=0;
			foreach ( $autharray as $author)
			{
				$authorcount++;
				foreach ($author as $afield => $avalues)
				{
					$author[$afield]=ereg_replace('[{}]','',$wpdb->escape($avalues));
				}
				$authnames =$authnames." ";
				if($authorcount==1)
				{
					$shortauthnames =$shortauthnames." ";
				}
				if($sets['fullnames']=="on")
				{
					$authnames =$authnames.$author['first']." ";
					if($authorcount==1)
					{
						$shortauthnames =$shortauthnames.$author['first']." ";
					}
				}	
				$authnames =$authnames.$author['last'];
				if($authorcount==1)
				{
					$shortauthnames =$shortauthnames.$author['last'];
				}
				$values2 = implode("','", array_values($author));
				$keys2 = implode(",", array_keys($author));
				$query = "insert into ".$this->database_interface->get_tablename('auth')." (id,num,".$keys2.") values (".$id.",".$authorcount.",'".$values2."')";
				$wpdb->query($query);
			}
			if($authorcount>2)
			{
				$shortauthnames = $shortauthnames." <i>et al.</i>";
			}
			else
			{
				$shortauthnames = $authnames;
			}
			$query = $wpdb->prepare("update ".$this->database_interface->get_tablename('main')." set authorsnames=%s where pubid=%d", $authnames, $id);
			$wpdb->query($query);
			$query = $wpdb->prepare("update ".$this->database_interface->get_tablename('main')." set shortauthnames=%s where pubid=%d", $shortauthnames, $id);
			$wpdb->query($query);
		}
    */
		//sort out categories
		//delete old values 
		$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('categories')." where id=%d", $id);
		$wpdb->query($query);
		//prepare statements for inserting categoryids
		foreach ( $catIds as $catId )
		{
			$query = $wpdb->prepare("insert into ".$this->database_interface->get_tablename('categories')." (id,categories) values (%d,%d)", $id, $catId);
			$wpdb->query($query);
		}
		//prepare statement for inserting bibtex
		$query = $wpdb->prepare("update ".$this->database_interface->get_tablename('content')." set content=%s where id=%d", $bibtex->bibTex(), $id);
		$wpdb->query($query);
		//check in
		$query = $wpdb->prepare("update ".$this->database_interface->get_tablename('main')." set checkedout='0' where pubid=(%d)", $id);
		$wpdb->query($query);
    }
    
    function checkin()
    {
    	global $wpdb;
		  $id = $_POST['id'];
		  //check in
		  $query = $wpdb->prepare("update ".$this->database_interface->get_tablename('main')." set checkedout='0' where pubid=(%d)", $id);
		  $wpdb->query($query);
    }
    
    function viewBib()
    {
    	global $wpdb;
		$query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('config').";");
		$querysets = $wpdb->get_results($query);
		foreach($querysets as $config)
		{
			$sets[$config->variable]=$config->value;
		}
		$query = $wpdb->prepare("SELECT pubid,shortauthnames,authorsnames,title,year,doi,eprint,checkedout from ".$this->database_interface->get_tablename('main'));
		$rows = $wpdb->get_results($query);

		$query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('main_categories')." order by id;");
		$catobj = $wpdb->get_results($query);
		foreach($catsobj as $cat)
		{
			$cats[$cat->id]=$cat->name;
		}
		$this->render_admin ('bibView', array ('rows' => $rows, 'cats' => $cats, 'sets' => $sets));
    }
    
    function input_references()
    {
    	$task = isset ($_POST['task']) ? $_POST['task'] : '';
    	switch($task)
    	{
			case 'save':
				$this->bibSave();
				break;
			default:
				$this->bibInput();
				break;
		  }
    }
    
    function getStatistics(&$bibtex)
	 {
    	$stat = $bibtex->getStatistic();
    	$ret = 'Parsed Data:<ul>';
    	$sum = 0;
    	foreach ($stat as $type=>$val)
    	{
	        $ret .= "<li>".htmlentities("$val $type")."</li>";
        	$sum += $val;
    	}
    	$ret .= '</ul>';
    	$ret .= "".htmlentities("TOTAL $sum records.")."<br /><br />";
    	$ret .= count($bibtex->warnings).' Warnings'.'<ul>';
    	if(count($bibtex->warnings))
    	{
	        foreach ($bibtex->warnings as $warn)
    	    {
            	$ret .= '<li>';
            	$ret .= $warn['warning'];
            	$ret .= ($warn['entry'])?(': '.substr($warn['entry'], 0, 20)) : '';
            	if(strlen($warn['entry'])>20) $ret .= '...';
            	$ret .= ' in record ';
            	$ret .= substr($warn['wholeentry'], 0, 70);
            	if(strlen($warn['wholeentry'])>70) $ret .= '...';
            	$ret .= ' (length: '.strlen($warn['wholeentry']).')';
            	$ret .= '</li>';
        	}
    	}
    	$ret .= '</ul>';
    	return $ret;
	}
    
    function bibSave()
    {
    	$parsed_status = false;
    	global $wpdb;
		  $query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('config'));
		  $querysets = $wpdb->get_results($query);
		  foreach($querysets as $config)
			   $sets[$config->variable]=$config->value;
    	$allfields = $this->database_interface->getTableFields(array($this->database_interface->get_tablename('main'),$this->database_interface->get_tablename('auth')));
		  $fields =array_keys($allfields[$this->database_interface->get_tablename('main')]);
		  $authfields =array_keys($allfields[$this->database_interface->get_tablename('auth')]);
    	$bibtex = new Structures_BibTex();
    	$bibtex->maxEntryLength = 10000;
    	$catIds = $_POST['category'];
		  $inputtype = $_POST['inputtype'];
		  //adding a bibtex string
		  if($inputtype=="file") {  // FILE INPUT
			   $filename = $_FILES['userfile']['tmp_name'];
			   $origfilename = $_FILES['userfile']['name'];
			   if (strcasecmp(substr($origfilename,-4),'.bib')==0)
			   {
				    $bibtex->loadFile($filename);
				    $parsed_status = $bibtex->parse();
				    if($parsed_status===true)
                	$parsed_status = (0 == count($bibtex->warnings));
            $message = $this->getStatistics($bibtex);
			   } else {
				    $message="Not a .bib file";
				    $parsed_status = false;
			   }
		  } elseif($inputtype=="string") { // PASTED BIBTEX STRING
		     $bibstring = isset ($_POST['bib'] ) ? $_POST['bib'] : '';
			   $bibstring = str_replace("\\","",$bibstring);
    	
			   $bibtex->content = $bibstring;
			   $parsed_status = $bibtex->parse();
			   if($parsed_status)
            $parsed_status = (0 == count($bibtex->warnings));
        $message = $this->getStatistics($bibtex);
		  } else {  // INSERTING ALL FIELDS MANUALLY
			  $authornumber=isset ($_POST['authornumber'] ) ? $_POST['authornumber'] : '';
			  //get fields
			  foreach($fields as $field) {
			    $stringin=isset ($_POST[$field] ) ? $_POST[$field] : '';
			    if(''!=$stringin) {
					 $newdata[$field]=$stringin;
				  }
			   }
			 for($i=0;$i<$authornumber;$i++) {
				foreach($authfields as $authfield) {
					$stringin=isset ($_POST[$authfield.$i] ) ?  $_POST[$authfield.$i]: '';
					if(''!=$stringin) {
						$newdata['author'][$i][$authfield] = $stringin;
					}
				}
			}
			if(count($newdata))
			{
				$bibtex->addEntry($newdata);
				$parsed_status=true;
			}
			else
			{
				$parsed_status = false;
				$message="No file or text data";
			}
		}
		if($parsed_status){
			$this->savetomysql($bibtex->data,$fields,$catIds,$sets);
			///reset of the inputtype POST variable
			$_POST['inputtype']='';
			$this->bibInput();
    } else {
      $errcod = array('All parsed records are <b>DROPPED</b>');
		}
    echo '<hr />', $message;
    echo '<hr />';	
  }
    
  //saves bibtex data in mysql
	function savetomysql($bibarray,$fields,$catIds,$sets)
	{
		global $wpdb;
		foreach ($bibarray as $paper)
		{
			$minibibtex = new Structures_BibTex();
			$minibibtex->maxEntryLength = 10000;
			$minibibtex->data[0] = $paper;
			$authexists = 0;
			if(array_key_exists('author',$paper)){
				$authexists = 1;
				$autharray = $paper['author'];
				$paper = array_diff($paper,$autharray);
			}
			//check all fields allowed
			$unsavedfields=array();
			foreach ($paper as $fieldsgiven => $valuesgiven) {
				if((!in_array($fieldsgiven,$fields))) {
					$unsavedfields[]=$paper[$fieldsgiven];
					unset($paper[$fieldsgiven]); 
				} else {
					//sort out escape chars and remove {}
					$paper[$fieldsgiven]=ereg_replace('[{}]','',$wpdb->escape($valuesgiven));
				}
			}			

			//search for urls elsewhere
			if(!array_key_exists('doi',$paper))
			{
				$urlstring1=array();
				foreach($unsavedfields as $field) {
					if(preg_match('!(http://|ftp://|https://)[a-z0-9_\.\/\?\&-\=]*!i',$field,$urlstring1)){
						$paper['doi']=sanitize_url($urlstring1[0]);
					} elseif(preg_match('!(www\.)[a-z0-9_\.\/\?\&-\=]*!i',$field,$urlstring1) ) {
						$paper['doi']=sanitize_url("http://".$urlstring1[0]);
					}
				}
				$urlstring2=array();
				if(array_key_exists('note',$paper))
				{
					if(preg_match('!(http://|ftp://|https://)[a-z0-9_\.\/\?\&-\=]*!i',$paper['note'],$urlstring2) )
					{
						$paper['doi']=sanitize_url($urlstring2[0]);
					}
					elseif(preg_match('!(www\.)[a-z0-9_\.\/\?\&-\=]*!i',$paper['note'],$urlstring2) )
					{
						$paper['doi']=sanitize_url("http://".$urlstring2[0]);
					}
				}
				if(array_key_exists('howpublished',$paper))
				{
					if(preg_match('!(http://|ftp://|https://)[a-z0-9_\.\/\?\&-\=]*!i',$paper['howpublished'],$urlstring2) )
					{
						$paper['url']=sanitize_url($urlstring2[0]);
					}
					elseif(preg_match('!(www\.)[a-z0-9_\.\/\?\&-\=]*!i',$paper['howpublished'],$urlstring2) )
					{
						$paper['url']=sanitize_url("http://".$urlstring2[0]);
					}
				}
			}
			
			//sort out eprint
			if((!array_key_exists('eprint',$paper))&&array_key_exists('doi',$paper))
			{
				$urlstring=$paper['doi'];
				if(substr($urlstring, -3, 3)=="pdf"||substr($urlstring, -3, 3)=="PDF")
				{
					$paper['eprint']=$urlstring;
					unset($paper['doi']); 
				}
			}
			//prepare statement for inserting fields
			$values = implode("','", array_values($paper));
			$keys = implode(",", array_keys($paper));
			$query = "insert into ".$this->database_interface->get_tablename('main')." (".$keys.") values ('".$values."')";
			$wpdb->query($query);
			
			//get the new pubid
			$query = $wpdb->prepare("SELECT PUBID from ".$this->database_interface->get_tablename('main')." ORDER BY pubid DESC LIMIT 1");
			$pubID= $wpdb->get_var($query);  /* NEW PUBLICATION ID */
	
			// Use the 'cite' parameter to dynamically assign this entry to the appropriate 
			// categories.
			$dynamicCats = array() ; 
			if (array_key_exists('cite', $paper))
			{
				$categoryKeys = $paper['cite'];
				for ($i=0; $i < strlen($categoryKeys); $i++)
				{
					$query = $wpdb->prepare("SELECT id FROM ".$this->database_interface->get_tablename('main_categories')." WHERE params LIKE %s", $categoryKeys[$i]);
					$res = $wpdb->get_results($query);
					if ( count ($res) > 0 )
					{
						$dynamicCats[] = $res; 
					}
				}
			}
			
			//prepare statements for inserting category ids
			foreach ( array_merge($catIds,$dynamicCats) as $catId )
			{
				$query = $wpdb->prepare("INSERT INTO ".$this->database_interface->get_tablename('categories')." (id,categories) values (%d,%d)", $pubID, $catId);
				$wpdb->query($query);
			}
			
			//prepare statement for author info
			// CB: new version, with additional author table
			
			if($authexists) {
				$authnames = '';
				$authorcount=0;
        $authortot=count($autharray);
				foreach ( $autharray as $author){
					$authorcount++;
					foreach ($author as $afield => $avalues)
						$author[$afield]=ereg_replace('[{}]','',$wpdb->escape($avalues));
					$authnames =$authnames." ";
					$authnames =$authnames.$author['first']." ";
					if($author['middle'] != '')
						$authnames =$authnames.substr($author['middle'],0,1).". "; 
					
					$authnames =$authnames.$author['last'];
					$values2 = implode("','", array_values($author));
					$keys2 = implode(",", array_keys($author));
					
					/* check to see if it already exists in the db, author table */
					$autID = $this->getAuthorID($author['last'],$author['first']);
					if($autID == 0){
						$query = "INSERT INTO ".$this->database_interface->get_tablename('auth')." (first, middle, last) VALUES ('".$values2."')";
						$wpdb->query($query);
						//retrieve the new pubid
						$query = $wpdb->prepare("SELECT authid from ".$this->database_interface->get_tablename('auth')." ORDER BY authid DESC LIMIT 1");
						$autID= $wpdb->get_var($query);  /* NEW PUBLICATION ID */
					}
					/* add relation publication (pubID) - author (autID) */
					$query = "INSERT INTO ".$this->database_interface->get_tablename('pubauth')." (pubid, authid, num) VALUES (".$pubID.",".$autID.",".$authorcount.")";
					$wpdb->query($query);
          if($authorcount == $authortot-1)
            $authnames =$authnames." and";
          else if($authorcount < $authortot-1)
            $authnames =$authnames.",";
				}
	      $authors = $this->get_publication_authors($pubID);
        $shortauthnames = $this->generateAuthorShortNameString($authors);
				$query = $wpdb->prepare("UPDATE ".$this->database_interface->get_tablename('main')." SET authorsnames=%s WHERE pubid=%d", trim($authnames), $pubID);
				$wpdb->query($query);
				
				$query = $wpdb->prepare("UPDATE ".$this->database_interface->get_tablename('main')." SET shortauthnames=%s WHERE pubid=%d", $shortauthnames, $pubID);
				$wpdb->query($query);
				
			}
	
			//prepare statement for inserting bibtex
			$query = $wpdb->prepare("INSERT INTO ".$this->database_interface->get_tablename('content')." (id,content) VALUES (%d,%s)", $pubID, $minibibtex->bibTex());
			$wpdb->query($query);
			
		}
	}
    
	/**
	 * Retrieves an author ID if it exists, and returns it, based on a match on last and first names.
	 * @param object $first Author's first name
	 * @param object $last Author's last name
	 * @return the author ID if it exists in the DB, 0 if it does not exist
	 */
	function getAuthorID($last, $first)
	{
    	global $wpdb;
		$strSQL = "SELECT * from ".$this->database_interface->get_tablename('auth') . " WHERE UPPER(Last)='" . strtoupper(trim($last)) . "';";
		$query = $wpdb->prepare($strSQL);
		$authordata = $wpdb->get_results($query);
		if($first=='*')
			if(count($authordata)>0)
				return $authordata[0]->authid;
		foreach($authordata as $auth){
			if(trim($first) == trim($auth->first))
				return $auth->authid;
		}
		/* author has not been found */
		return 0;
	}
    
	/**
	 * Adds an author if it does not exist another one with equal last and first name
	 * 
	 */
   function addAuthorIfNew($firstname, $middlename, $lastname, $isInt)
	 {
	   global $wpdb;
	   $authID = $this->getAuthorID($lastname, $firstname);
		 /* there is not another author with same last and first name */
		 if($authID == 0){
		   $strSQL = "INSERT INTO ".$this->database_interface->get_tablename('auth') . " (first, middle, last, isInternal) ";
			 $strSQL = $strSQL." VALUES('".$firstname."', '".$middlename."', '".$lastname."',".$isInt.");";
			 $query = $wpdb->prepare($strSQL);
			 $wpdb->query($query);
			 /* retrieved the uid of the author just insterted */
			 $query = $wpdb->prepare("SELECT authid FROM ".$this->database_interface->get_tablename('auth')." ORDER BY authid DESC LIMIT 1;");
			 $authID= $wpdb->get_var($query);
     }
     return $authID;
	}


	function getPublicationsByAuthorName($lastname)
	{
   		global $wpdb;
		$authid = $this->getAuthorID($lastname,'*');
		return $this->getPublicationsByAuthorID($authid);
	}

	function getPublicationsByAuthorID($authid)
	{
    	global $wpdb;
		$strSQL = "SELECT pubid FROM ".$this->database_interface->get_tablename('pubauth')." WHERE authid=".$authid . ';';
		$query = $wpdb->prepare($strSQL);
		$pubdata = $wpdb->get_results($query);
		return $pubdata;
	}

	/**
	 * Retrieves the last num publications 
	 * @param object $num The number of publications to be retrieved
	 * @return The IDs of the publications
	 */
	function getMostRecentPublications($num)
	{
    global $wpdb;
		$strSQL = "SELECT pubid, yy as year, month FROM ".$this->database_interface->get_tablename('main')." ORDER BY yy desc, mm desc LIMIT ".$num;
		$query = $wpdb->prepare($strSQL);
		$pubdata = $wpdb->get_results($query);
		return $pubdata;
	}



	/**
	 * Removes an author from a publication.
	 * @param object $authid Author's id
	 * @param object $pubid The publication id
	 * @return 
	 */
	function deletePublicationAuthor($authid,$pubid)
	{
    	global $wpdb;
		$query = $wpdb->prepare("DELETE FROM ".$this->database_interface->get_tablename('pubauth') . " WHERE pubid=".$pubid." AND authid=".$authid.";");
		$wpdb->query($query);
		//renumbering the remaining authors
		$query = $wpdb->prepare("SELECT * FROM ".$this->database_interface->get_tablename('pubauth') . " WHERE pubid=".$pubid." ORDER BY num;");	
		$authordata = $wpdb->get_results($query);
		$i=1;
		foreach($authordata as $auth){
			$query = $wpdb->prepare("UPDATE ".$this->database_interface->get_tablename('pubauth') . " SET num=".$i." WHERE pubid=".$pubid." AND authid=".$auth->authid.";");
			$wpdb->query($query);
			$i++;
		}
		return;
	}
	
	
	function saveBib2DB($bibarray,$fields,$catIds,$sets){
		
	}	
	
	function bibInput()
    {
    	global $wpdb;
		$query = $wpdb->prepare("SELECT * FROM ".$this->database_interface->get_tablename('config'));
		$querysets = $wpdb->get_results($query);
		foreach($querysets as $config)
		{
			$sets[$config->variable]=$config->value;
		}
		$query = $wpdb->prepare("SELECT * FROM ".$this->database_interface->get_tablename('main_categories')." order by id");
		$cats = $wpdb->get_results($query);
		$inputtype = isset ($_POST['inputtype']) ? $_POST['inputtype'] : '';
		$authornumber = isset ($_POST['authornumber']) ? $_POST['authornumber'] : '';
		$allfields = $this->database_interface->getTableFields(array($this->database_interface->get_tablename('main'),$this->database_interface->get_tablename('auth')));
		$fields =array_keys($allfields[$this->database_interface->get_tablename('main')]);
		$authfields =array_keys($allfields[$this->database_interface->get_tablename('auth')]);
		$authfields = array_diff($authfields,array('id'));
		$authfields = array_diff($authfields,array('num'));
		$fields = array_diff($fields,array('pubid'));
		$fields = array_diff($fields,array('authorsnames'));
		$fields = array_diff($fields,array('shortauthnames'));
		$fields = array_diff($fields,array('checkedout'));
		$this->render_admin ('bibInput', array ('sets' => $sets, 'inputtype'=> $inputtype, 'cats' => $cats, 'authornumber' => $authornumber, 'fields' => $fields, 'authfields' => $authfields));
    }
	

    function view_authors()
    {
    	$task = isset ($_POST['task']) ? $_POST['task'] : '';
      $task = empty($task) ? (isset ($_GET['task']) ? $_GET['task'] : '') : $task;
   		switch($task)
    	{
			case 'authNew':
				$this->authNew();
				break;
			case 'authSave':
				$this->authSave();$this->viewAuth();
				break;
			case 'authDelete':
				$this->delAuth();$this->viewAuth();
				break;
      /* CB */
      case "authUpdate":
        $this->authNew();
        break;
			default:
				$this->viewAuth();
				break;
			}
    }


	/* AUTHORS MANAGEMENT */

    function authNew()
    {
      $pubid = $_GET['id'];
      $authid = $_GET['authid'];
      if($authid != ''){
        global $wpdb;
        $query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('auth')." WHERE authid=".$authid);
        $rows = $wpdb->get_results($query);
      }
    	$this->render_admin ('authNew', array ('rows' => $rows));
    }

    function authSave()
    {
		$authID = $_POST['authid'];
		$authFirst = $_POST['authFirst'];
		$authMiddle = $_POST['authMiddle'];
		$authLast = $_POST['authLast'];
		$isInt = isset ($_POST['isInternal']) ? $_POST['isInternal'] : 0;
		$pubID = isset ($_POST['pubid']) ? $_POST['pubid'] : 0;
		global $wpdb;

		if($authID > 0){ /* author update, at the moment there is no menu supporting it */
			$query = $wpdb->prepare("UPDATE ".$this->database_interface->get_tablename('auth')." SET first='".$authFirst."', middle='".$authMiddle."', last='".$authLast."', isInternal=".$isInt." WHERE authid=".$authID.";");
			$wpdb->query($query);
		} 	else {
			$authID = $this->addAuthorIfNew($authFirst,$authMiddle,$authLast,$isInt);
		}
		if($pubID > 0){
			$query = $wpdb->prepare("SELECT count(*)+1 FROM ".$this->database_interface->get_tablename('pubauth')." WHERE pubid=".$pubID.";");
			$authNum= $wpdb->get_var($query);
			$query = $wpdb->prepare("INSERT INTO ".$this->database_interface->get_tablename('pubauth')." (pubid, authid, num)  VALUES(".$pubID.", ".$authID.", ".$authNum.");");
			$wpdb->query($query);
		}
	}

	/**
	 *	Lists all authors.
	 *
	 **/
	function viewAuth()
	{
		global $wpdb;
		$query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('auth')." order by Last, First");
		$rows = $wpdb->get_results($query);
		$this->render_admin ('authView', array ('rows' => $rows));
	}    

	/**
	 *	Deletes a list of authors and the publications related to them.
	 *	Finally, it removes all authors without publications.
	 **/
	function delAuth()
	{
			$id = isset ($_POST['post']) ? $_POST['post'] : array();
	    	global $wpdb;
	    	foreach($id as $authid)
	    	{
				/* deleting all the author's publications */
				$strSQL = "SELECT pubid FROM ".$this->database_interface->get_tablename('pubauth')." WHERE authid=".$authid.";";
				$query = $wpdb->prepare($strSQL);
				$pubids = $wpdb->get_results($query);
				foreach($pubids as $pid){
					$strSQL = "DELETE FROM ".$this->database_interface->get_tablename('main')." WHERE pubid=".$pid->pubid.";";
					$query = $wpdb->prepare($strSQL);
					$wpdb->query($query);
				}
				/* deleting the author from the publication author table */
				$strSQL = "DELETE FROM ".$this->database_interface->get_tablename('pubauth')." WHERE authid=".$authid.";";
				$query = $wpdb->prepare($strSQL);
				$wpdb->query($query);
				/* deleting the author from the table */
				$strSQL = "DELETE FROM ".$this->database_interface->get_tablename('auth')." WHERE authid=".$authid.";";
				$query = $wpdb->prepare($strSQL);
				$wpdb->query($query);
			}
			$this->deleteAuthorsNoPub();
	}

  /**
   * Merges all the authors passed from the form: the first one is to replace all others
   *
   **/
   function mergeAuth()
   {
   		global $wpdb;
			$query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('auth')." order by Last, First");
			$rows = $wpdb->get_results($query);
    	$this->render_admin ('authMerge', array ('rows' => $rows));
   }

   function merge_authors()
   {
   		$task = isset ($_POST['task']) ? $_POST['task'] : '';
    	$firstA = isset ($_POST['mainAuth']) ? $_POST['mainAuth'] : -1;
   		
  		if($task == "authMerge"){
	    	if($firstA == -1)
	    		$this->mergeAuth();
	    	 else {
	  	   	$this->mergeAuthorList();
	  	   	$this->mergeAuth();
	    	}
  		} else 
				$this->mergeAuth();
  		
   }

	/**
	 *  Receives the list of authors to be merged, and one by one performs the operation
	 *	Returns the number of merged authors.
	 */
  function mergeAuthorList()
  { 
    	$id = isset ($_POST['post']) ? $_POST['post'] : array();
    	$firstA = isset ($_POST['mainAuth']) ? $_POST['mainAuth'] : -1;

	    global $wpdb;
 	  	$i=0;
   		foreach($id as $authid)
    	{
    		if($firstA != $authid){
	    		$this->merge2Authors($firstA,$authid);
	    		$i++;
    		}
	    }
	    return $i;
  }
  
  
  
  /**
   * Merges two authors, the second is substituted with the first in the references
   * To clean-up the db from duplicate entries (shortened names)
   **/
   function merge2Authors($firstA, $secondA)
   { 
    	global $wpdb;
    	/* retrieve publications: shortnames need to be updated */
    	$strSQL = "SELECT pubid from " .$this->database_interface->get_tablename('pubauth')." WHERE authid=".$secondA.";";
    	$query = $wpdb->prepare($strSQL);
    	$pubids = $wpdb->get_results($query);
    	
			$query = $wpdb->prepare("UPDATE ".$this->database_interface->get_tablename('pubauth')." SET authid=".$firstA." WHERE authid=".$secondA.";");
   		$wpdb->query($query);
   		
   		/* remove the author who - now - has no publications */
   		$this->deleteAuthorsNoPub();


   		/* update shortnames in publications */
   		foreach($pubids as $pid)
				$this->updateBibShortAuthors($pid);
   		
   		
   }
    
  /**
   * Updates the shortname field of the publication based on the names of the authors.
   * Used for maintanance when changing authors.
   * 
   */
   function updateBibShortAuthors($pubid){
      global $wpdb;
      $authors = $this->get_publication_authors($pubid);
      $shortNames = $this->generateAuthorShortNameString($authors);
      $query = $wpdb->prepare("UPDATE ".$this->database_interface->get_tablename('main')." SET authorsnames=%s WHERE pubid=%d", $authnames, $pubID);
      $wpdb->query($query);
   }

	/* CATEGORIES */
    function view_categories()
    {
    	$task = isset ($_POST['task']) ? $_POST['task'] : '';
    	switch($task)
    	{
			case 'catNew':
				$this->catNew();
				break;
			case 'catSave':
				$this->catSave();$this->viewCat();
				break;
			case 'catDelete':
				$this->delCat();$this->viewCat();
				break;
			default:
				$this->viewCat();
				break;
			}
    }
	
	
    function catNew()
    {
    	$this->render_admin ('catNew');
    }
    
    function catSave($option)
    {
		$catName = $_POST['catName'];
		$catDesc = $_POST['catDesc'];
		global $wpdb;
		$query = $wpdb->prepare("insert into ".$this->database_interface->get_tablename('main_categories')." (name,description) values (%s,%s)", $catName,$catDesc);
		$wpdb->query($query);
	}
    
    //delete category
    function delCat()
    {
		$id = isset ($_POST['post']) ? $_POST['post'] : array();
    	global $wpdb;
    	foreach($id as $cid)
    	{
			//find all relevant references
			$query = $wpdb->prepare("SELECT pubid from ".$this->database_interface->get_tablename('main')." AS bib LEFT JOIN ".$this->database_interface->get_tablename('categories')." AS cat ON bib.pubid=cat.id WHERE cat.categories=(%d)", $cid);
			$pubids = $wpdb->get_results($query);
			//delete values from cat table
			$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('categories')." where categories=(%d)", $cid);
			$wpdb->query($query);
			//clean up author and content data:
			foreach($pubids as $aid)
			{
				//find all relevant references
				$query = $wpdb->prepare("SELECT categories from ".$this->database_interface->get_tablename('categories')." where id=(%d)", $aid);
				$catids = $wpdb->get_results($query);
				if(count($catids)==0)
				{
					$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('auth')." where id=(%d)", $aid);
					$wpdb->query($query);
					$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('content')." where id=(%d)", $aid);
					$wpdb->query($query);
				}
			}
			//finally delete category
			$query = $wpdb->prepare("delete from ".$this->database_interface->get_tablename('main_categories')." where id=(%d)", $cid);
			$wpdb->query($query);
		}
	}
	
    //view categories
	function viewCat()
	{
		global $wpdb;
		$query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('main_categories')." order by id");
		$rows = $wpdb->get_results($query);
		$this->render_admin ('catView', array ('rows' => $rows));
	}    


    
    function configuration()
    {
    	$task = isset ($_POST['task']) ? $_POST['task'] : '';
    	if ($task == 'confSave')
			$this->confSave();
		global $wpdb;
		$query = $wpdb->prepare("SELECT * from ".$this->database_interface->get_tablename('config'));
		$querysets = $wpdb->get_results($query);
		foreach($querysets as $config)
		{
			$sets[$config->variable]=$config->value;
			$tips[$config->variable]=$config->tooltip;
			$names[$config->variable]=$config->name;
		}
    	$this->render_admin ('configInput', array ('sets' => $sets, 'tips' => $tips, 'names' => $names));
    }
    function confSave($option)
    {
		global $wpdb;
    	$query = $wpdb->prepare("SELECT variable from ".$this->database_interface->get_tablename('config'));
		$querysets = $wpdb->get_results($query);
		foreach($querysets as $config)
		{
			$configparam = isset ($_POST[$config->variable]) ? $_POST[$config->variable] : 'off' ;
			$query = $wpdb->prepare("update ".$this->database_interface->get_tablename('config')." set value=%s where variable=%s", $configparam, $config->variable);
			$wpdb->query($query);
		}
    }
    
     
    /**
     * changing the administration style to suit plugin
     * WIP
     * CB
     **/  
	function BibTeX_admin_css() {
      echo '<link type="text/css" rel="stylesheet" href="' . $this->get_bt_pluginURL() . '/BibTeX.admin.css" />' . "\n";
    }

	//CB: to include scriptaculous on pages where the bib appears
    function bibtex_plugin_user_head() {
       echo '<script src="'.$this->get_bt_pluginURL().'/js/jquery-1.2.3.js"  type="text/javascript"></script>'."\n";
       echo '<script src="'.$this->get_bt_pluginURL().'/js/bibtex.js"  type="text/javascript"></script>'."\n";
       echo '<link type="text/css" rel="stylesheet" href="' . $this->get_bt_pluginURL() . '/BibTeX.user.css" />' . "\n";
//       echo "<style type=\"text/css\">
// 			    div.bibtex {display: none;}
//				</style>";
    }

    	    
	/**
	 *return the BibTeX plugin dir
	 *
	 * @return the BibTeX plugin dir
	 **/
	function get_bt_plugindir()
	{
		return WP_PLUGIN_DIR . DIRECTORY_SEPARATOR. basename(dirname(__FILE__)) ;
	}
	/**
	 *return the BibTeX plugin URL
	 *
	 * @return the BibTeX plugin URL
	 **/
	function get_bt_pluginURL()
	{
		return get_option('siteurl').'/wp-content/plugins/'. basename(dirname(__FILE__)) ;
	}
	
	
	/**
	 * Renders an admin section of display code
	 *
	 * @param string $ug_name Name of the admin file(without extension)
	 * @param string $array Array of variable name=>value that is available to the display code(optional)
	 * @return void
	 **/
	function render_admin( $ug_name, $ug_vars = array() ) {

		foreach ( $ug_vars AS $key => $val ) {
			$$key = $val;
		}

		if ( file_exists( "{$this->get_bt_plugindir()}/view/admin/$ug_name.php" ) )
			include "{$this->get_bt_plugindir()}/view/admin/$ug_name.php";
		else
			echo "<p>Rendering of admin template {$this->get_bt_plugindir()}/view/admin/$ug_name.php failed</p>";
	}
	
	function db_setup() {
		$this->database_interface->create_tables();
	}
	
	function db_remove() {
		$this->database_interface->drop_tables();		
	}
	
  /* utilities */
  
	function debugMessage($message){
		echo "
		<script type='text/javascript' >
		alert('".$message."');
		</script>
		";
		
	}
	
  function keyExistsOrIsNotEmpty($key,$array)
  {
    if(array_key_exists($key,$array))
      if($array[$key]!="")
        return true;
      return false;
    return false;
  }
  	
}

/**
 * Our one and only instance of the plugin
 *
 * @global bibtex_extension The plugin
 **/

$bibtex_extension = new BibTeX_Plugin ();

?>
