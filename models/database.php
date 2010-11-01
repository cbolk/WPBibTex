<?php

/**
 * Collect database information and interface functions
 *
 * @package BibTeX extension to Wordpress
 * @author Fabrizio Ferrandi, Cristiana Bolchini
 * @copyright Copyright (C) Fabrizio Ferrandi, Cristiana Bolchini
 * @version 2.0
 **/

class BT_database
{
	var $bib_config_table;
	
	var $bib_pub_table;
	
	var $bib_auth_table;

	var $bib_pubauth_table;				/* new */
	
	var $bib_categories_table;
	
	var $main_categories_table;
	
	var $bib_content_table;

	/**
	 * Retrieves information about the given tables
	 *
	 * @access	public
	 * @param 	array|string 	A table name or a list of table names
	 * @param	boolean			Only return field types, default true
	 * @return	array An array of fields by table
	 */
	function getTableFields( $tables, $typeonly = true )
	{
		global $wpdb;
		settype($tables, 'array'); //force to array
		$result = array();
		foreach ($tables as $tblval)
		{
			$query = $wpdb->prepare('SHOW FIELDS FROM ' . $tblval);
			$fields = $wpdb->get_results($query);

			if($typeonly)
			{
				foreach ($fields as $field) {
					$result[$tblval][$field->Field] = preg_replace("/[(0-9)]/",'', $field->Type );
				}
			}
			else
			{
				foreach ($fields as $field) {
					$result[$tblval][$field->Field] = $field;
				}
			}
		}

		return $result;
	}
	
	function BT_database()
	{
		global $table_prefix;
		$this->bib_config_table = $table_prefix . 'bib_config';
		$this->bib_pub_table = $table_prefix . 'bib_publication';
		$this->bib_auth_table = $table_prefix . 'bib_authors';
		$this->bib_pubauth_table = $table_prefix . 'bib_pubauth';
		$this->bib_categories_table = $table_prefix . 'bib_pubcategories';
		$this->main_categories_table = $table_prefix . 'bib_categories';
		$this->bib_content_table = $table_prefix . 'bib_content';
		global $wpdb;
		$wpdb->show_errors();
		
	}
	
	function get_tablename($sel)
	{
		switch($sel)
		{
			case 'config':return $this->bib_config_table;break;
			case 'main':return $this->bib_pub_table;break;
			case 'auth':return $this->bib_auth_table;break;
			case 'pubauth':return $this->bib_pubauth_table;break;
			case 'categories':return $this->bib_categories_table;break;
			case 'main_categories':return $this->main_categories_table;break;
			case 'content':return $this->bib_content_table;break;
		}
		return '';
	}
	
	function create_tables()
	{
		global $wpdb;
		$tables = $wpdb->get_col("SHOW TABLES");
		if (in_array($this->bib_config_table, $tables) || 
			in_array($this->bib_pub_table, $tables) || 
			in_array($this->bib_auth_table, $tables) ||
			in_array($this->bib_pubauth_table, $tables) ||
			in_array($this->bib_categories_table, $tables) ||
			in_array($this->main_categories_table, $tables) ||
			in_array($this->bib_content_table, $tables)) {
			return;
		}
		$sql = $wpdb->prepare("CREATE TABLE ".$this->bib_config_table." (
			`variable` VARCHAR(32),
			`value` VARCHAR(32),
			`tooltip` TEXT,
			`name` VARCHAR(255)
			)");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("INSERT INTO ".$this->bib_config_table." (variable,value,tooltip,name) values ('edit','on','Allow frontend users to edit?','Allow edit?')");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("INSERT INTO ".$this->bib_config_table." (variable,value,tooltip,name) values ('add','on','Allow frontend users to create new references', 'Allow add?')");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("INSERT INTO ".$this->bib_config_table." (variable,value,tooltip,name) values ('download','on','Allow frontend users to download bibtex files', 'Allow download?')");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("INSERT INTO ".$this->bib_config_table." (variable,value,tooltip,name) values ('truncate','off','Truncate titles, authorsnames and journal names in frontend to fit each reference on one line','Truncate text in table?')");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("INSERT INTO ".$this->bib_config_table." (variable,value,tooltip,name) values ('fullnames','on','Show fullnames rather than surnames when displaying references','Display full names?')");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("INSERT INTO ".$this->bib_config_table." (variable,value,tooltip,name) values ('etal','on','Shorten names in frontend table to a main author and et al. if there are more than two authors','Use et al.?')");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("INSERT INTO ".$this->bib_config_table." (variable,value,tooltip,name) values ('manualinput','on','Allow manual input of fields when adding new references','Allow manual input?')");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("INSERT INTO ".$this->bib_config_table." (variable,value,tooltip,name) values ('topbuttons','off','Include a second set of navigation buttons at the top of the table in the frontend','Include top navigation buttons?')");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("INSERT INTO ".$this->bib_config_table." (variable,value,tooltip,name) values ('smallicons','on','Display small icons for url link in frontend rather than larger ones','Small icons?')");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("INSERT INTO ".$this->bib_config_table." (variable,value,tooltip,name) values ('formatted','of','Display formatted text references rather than a table with separate columns','Formatted display?')");
		$wpdb->query($sql);
		
		$sql = $wpdb->prepare("CREATE TABLE ".$this->bib_pub_table." (
			`type` VARCHAR(255) NOT NULL,
			`cite` VARCHAR(255) NOT NULL,
			`title` TEXT NOT NULL,
			`booktitle` VARCHAR(255),
			`journal` VARCHAR(255),
			`pages` VARCHAR(255),
			`year` VARCHAR(255) NOT NULL,
			`yy` INT,			
			`volume` VARCHAR(255),
			`number` VARCHAR(255),
			`month` VARCHAR(255),
			`mm` INT,
			`address` VARCHAR(255),
			`annote` TEXT,
			`doi` TEXT,
			`chapter` VARCHAR(255),
			`edition` VARCHAR(255),
			`editor` VARCHAR(255),
			`eprint` VARCHAR(255),
			`howpublished` VARCHAR(255),
			`institution` VARCHAR(255),
			`organization` VARCHAR(255),
			`publisher` VARCHAR(255),
			`school` VARCHAR(255),
			`series` VARCHAR(255),
			`abstract` TEXT,
			`keywords` TEXT,
			`authorsnames` TEXT,
			`pubid` INT AUTO_INCREMENT,
			`shortauthnames` VARCHAR(255),
			`checkedout` INT DEFAULT 0,
			PRIMARY KEY (pubid),
			UNIQUE KEY `cite` (`cite`)
			)");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("CREATE TABLE ".$this->bib_auth_table." (
			`authid` INT AUTO_INCREMENT,
			`first` VARCHAR(255),
			`middle` VARCHAR(255),
			`last` VARCHAR(255),
			`isInternal` BOOLEAN DEFAULT 1,
			PRIMARY KEY (authid)
			)");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("CREATE TABLE ".$this->bib_pubauth_table." (
			`pubid` INT,
			`authid` INT,
			`num` INT	
			)");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("CREATE TABLE ".$this->bib_categories_table." (
			`id` INT NOT NULL,
			`categories` INT NOT NULL
			)");
		$wpdb->query($sql);
		/// temporary table
		$sql = $wpdb->prepare("CREATE TABLE ".$this->main_categories_table." (
      		`id` int(11) NOT NULL AUTO_INCREMENT,
      		`name` varchar(255) NOT NULL ,
      		`description` text NOT NULL ,
      		`params` text NOT NULL,
      		PRIMARY KEY (id)
			)");
		$wpdb->query($sql);
		$sql = $wpdb->prepare("INSERT INTO ".$this->main_categories_table." (name,description,params) values ('default','default','')");
		$wpdb->query($sql);
		///end temporary table
		
		$sql = $wpdb->prepare("CREATE TABLE ".$this->bib_content_table." (
			`id` INT,
			`content` TEXT
			)");
		$wpdb->query($sql);
	}
	
	/**/
	function drop_tables()
	{
		global $wpdb;
		$tables = $wpdb->get_col("SHOW TABLES");
		if (in_array($this->bib_config_table, $tables)){
		 	$sql = $wpdb->prepare("DROP TABLE ".$this->bib_config_table.";");
			$wpdb->query($sql);
		}
		if (in_array($this->bib_pub_table, $tables)){
		 	$sql = $wpdb->prepare("DROP TABLE ".$this->bib_pub_table.";");
			$wpdb->query($sql);
		}
		if (in_array($this->bib_auth_table, $tables)){
		 	$sql = $wpdb->prepare("DROP TABLE ".$this->bib_auth_table.";");
			$wpdb->query($sql);
		}
		if (in_array($this->bib_pubauth_table, $tables)){
		 	$sql = $wpdb->prepare("DROP TABLE ".$this->bib_pubauth_table.";");
			$wpdb->query($sql);
		}
		if (in_array($this->bib_categories_table, $tables)){
		 	$sql = $wpdb->prepare("DROP TABLE ".$this->bib_categories_table.";");
			$wpdb->query($sql);
		}
		if (in_array($this->main_categories_table, $tables)){
		 	$sql = $wpdb->prepare("DROP TABLE ".$this->main_categories_table.";");
			$wpdb->query($sql);
		}
		if (in_array($this->bib_content_table, $tables)){
		 	$sql = $wpdb->prepare("DROP TABLE ".$this->bib_content_table.";");
			$wpdb->query($sql);
		}
	}
}

?>
