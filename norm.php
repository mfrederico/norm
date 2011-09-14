<?php 

//============================================================+
// Norm - Not an ORM                 
//-------------------------------------
// Version          : 1.2.3
// Author           : Matthew Frederico          
// License          : Whichever GPL works best for you
//-------------------------------------
// Copyright (c) 2010 Matthew Frederico
// 
// NORM is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// NORM is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with NORM.  If not, see <http://www.gnu.org/licenses/>.
//
// See LICENSE.TXT file for more information.
// -------------------------------------------------------------------
// Description      : Dynamic storage/retrieval of objects in a database
// Main features    :
// * Single class file
// * Object properties can be dynamically allocated
// * You don't have to create the database tables
// * 3 Main functions - store,get,del.  Norm takes care of the rest
// * Automatic table creation 
/**
 * NORM is a PHP class for storing and retrieving PHP objects to and from a database
 * <ul><li>3 main public methods - store,get,del</li>
 * <li>Creates your database tables on the fly as needed</li>
 * <li>Does it's best to maintian hierarchy</li></ul>
 * @package ultrize.norm
 */

/**
 * @author Matthew Frederico
 * @link http://www.ultrize.com/norm/
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @version 1.2.1
 * @copyright Copyright 2010 Matthew Frederico - ultrize.com
 * @package Norm
 */
class Norm
{
	/**
	 * Single only returns a single object's data
	 * @var SINGLE how to return data 
	 */
	const SINGLE	= 0;
	const FULL		= 1;

	/**
	 * Whether or not to skip null fields when "storing" 
     * @var SKIP_NULL
     */
	const SKIP_NULL	= 1;

	/**
	 * Whether or not to STORE null fields when "storing" 
     * @var STORE_NULL
     */
	const STORE_NULL	= 0;

	/**
	 * Return types for the setResultsGetMode()
	 * @var AS_INDEXED Default return type, as heirarchical index
	 * @see get() index()
	 */
	const AS_INDEXED= 0; // Default

	/**
	 * Return types for the setResultsGetMode()
	 * @var AS_ARRAY Returns it as a flat (non indexed) array
	 * @see get()
	 */
	const AS_ARRAY	= 1;

	/**
	 * Return types for the setResultsGetMode()
	 * @var AS_OBJECT - GET returns an object in "Norm::$results"
	 * @see get() $results
	 */
	const AS_OBJECT	= 2;

	// * Sets how the "get" method will return results from the db e.g: 0 indexed array,1 array, or 2 object 

	/**
	 * @var user The user name used to authenticate into the database (if applicable)
	 * @access protected
	 */
	protected $user = '';

	/**
	 * @var pass The password used to authenticate into the database (if applicable)
	 * @access protected
	 */
	protected $pass = '';

	/**
	 * @var dsna This is the DSN array broken into key value pairs
	 * @access protected
	 */
	protected $dsna = '';

	/**
	 * @var dsn This is the DSN string e.g. mysql:host=localhost;dbname=database
	 * @access protected
	 */
	protected $dsn = '';

	/**
	 * @var tableList This is the internal table list pointer for NORM
	 * @access protected
	 */
	protected $tableList		= array();

	/**
	 * @var tableColumns This is the internal table schema for NORM
	 * @access protected
	 */
	protected $tableColumns	= array();

	/**
	 * @var relatedTables Keeps a hierchical relationship of tables / objects
	 * @access protected
	 */
	protected $relatedTables	= array();

	/**
	 * @var maps creates the mapping for all created objects
	 * @access protected
	 */
	protected $maps			= array();

	/**
	 * @var orderVars array of column names to set as order by clause
	 * @access protected
	 */
	protected $orderVars	= array();

	/**
	 * @var orderDir string direction (ASC/DESC) of order by 
	 * @access protected
	 */
	protected $orderDir	= array();

	/**
	 * @var whereVars collection of classes with vars to set as where clause
	 * @access protected
	 */
	protected $whereVars	= array();

	/**
	 * @var likeVars collection of column names to convert as "like" instead of "="
	 * @access protected
	 */
	protected $likeVars		= array();

	/**
	 * @var prefix the database table prefix
	 * @access protected
	 */
	protected $prefix		= '';

	/**
	 * @var resultsGetMode 0 = index results, 1 = separate results 2 = return object into data var
	 * @access protected
	 * @see $results get()
	 */
	protected $resultsGetMode			= 0;

	public $debug			= true;
	/**
	 * @var insertId Contains last insert id
	 * @access public
	 */
	public $insertId			= null;

	/**
	 * @var lastQuery Contains last query performed
	 * @access public
	 */
	public $lastQuery			= '';

	/**
	 * @var constrainList array of tables to constrain to
	 * @access private
	 */
	public $constrainList	= array();

    /**
     * @var limitStart int 
     * @access private
     */
     private $limitStart = 0;

    /**
     * @var limitEnd int 
     * @access private
     */
    private $limitEnd   = 0;

	/**
	 * @var results Contains flat array from the database.
	 * @access public
	 */
	public $results			= array();

	/**
	 * @var link This is the actual PDO link to the database
	 * @access protected
	 */
	private static $link = null;
	
	/**
	 * @var xmlptr
	 * @access protected
	 */
	private static $xmlptr = 0;

	/**
 	 * @param string $dsn the database dsn connection
	 * @param string $user username to connect to the database
	 * @param string $pass password to connect to the database
	 * @param string $attr extra PDO attributes passable
	 * @returns object 
	 * @access public 
	 */
	public function __construct($dsn,$user = null,$pass = null,$attr = null) 
	{
		//Use our custom handler
		set_error_handler(array($this,'trigger_my_error'));

		$this->dsn	= $dsn;
		$this->dsna = self::parseDsn($dsn);

		$this->user = $user;
		$this->pass = $pass;

        if ( self :: $link ) 
		{
            return self;
        }
		else $this->revive();

        return $this;
    }

    /**
     * Attempts to reconnect to the DB if necessary.
     * @returns object 
     * @access public 
     */
    public function revive()
    {
        try
        {
            self :: $link = new PDO ( $this->dsn, $this->user, $this->pass, $attr ) ;
        }
        catch (exception $e)
        {
            die($e->getMessage());
        }
        return $this;
    }
	/**
	 * Allows you to set the table prefix
	 * @param string $prefix prefix for the table
	 * @access public
	 * @returns object 
	 * @see store() del() tie() buildSet() .. 
	 */
	public function setTablePrefix($prefix)
	{
		$this->prefix = $prefix;
		return($this);
	}

	/**
	 * Ties an array of objects together
	 * @param object $obj1 this is the parent object
	 * @param array $objArrays this is the array of objects 
	 * @param int $skipNull skip null fields (passthrough for store())
	 * @access public
	 * @returns object 
	 * @see store()
	 */
	public function tie($obj1,$objArrays = array(),$skipNull=1)
	{
		// Make sure we have an array of objects
		if (!is_array($objArrays)) 
		{
			$nobj[] = $objArrays;
			$objArrays = $nobj;
		}

		$t1	= self::getTableName($obj1);

		// Build the lookup table
		foreach($objArrays as $idx=>$nextObj)
		{
			$t2 = self::getTableName($nextObj);

			// Table name of the lookup table 
			$tableName = "{$t1}_{$t2}";

			// Columns for the lookup table
			$var1 = "{$t1}_id";
			$var2 = "{$t2}_id";

			$table[$var1] = 0;
			$table[$var2] = 0;

			// Create the structure of the lookup table
			$Q = $this->buildSet($tableName,$table);
			if (strlen($Q))
			{
				$this->query($Q);
				// Create unique index for this lookup
				$Q = "ALTER TABLE {$this->prefix}{$tableName} ADD unique index({$tableName}_{$var1},{$tableName}_{$var2})";
				$this->query($Q);
			}
		}

		// Now store the data into the lookup table for each object
		foreach($objArrays as $obj2)
		{
			$tmp = $this->store($obj2,$skipNull);
			if (isset($obj1->id) && isset($obj2->id))
			{
				$Q="INSERT INTO {$this->prefix}{$tableName} SET `{$tableName}_{$var1}`='{$obj1->id}', `{$tableName}_{$var2}`='{$obj2->id}'";
				$this->query($Q);
			}
			else 
			{
				if ($this->dbg) trigger_error('Trying to tie objects that have no id '.self::getTableName($obj1).' -> '.self::getTableName($obj2).' - cannot save to database!',E_USER_NOTICE);
			}
		}
	}

	/**
	 * pushes array key=>value pairs of data into my object, 
	 * creating new object vars (e.g. $obj->key = value) where necessary.
     * great for stuffing objects with data from form fields:<Br />
     * <code>Norm::stuff($_REQUEST['user'],$user,'id,login,password');</code>
	 * @param array $array array containing key=>value pairs
	 * @param array $obj object to "stuff" into
	 * @param string $fields a csv of fieldnames to "stuff" into the object
	 * @access public
	 * @returns object 
	 * @see store() tie()
	 */
	public function stuff($array,$obj,$fields = '')
	{
		// Get this objects name
		$n = self::getTableName($obj);
		// convert me to an array!	
		$fields = explode(',',$fields);
		if (!empty($array))
		{
			foreach($array as $k=>$v)
			{
				$k = str_replace($n.'_','',$k);
				$obj->$k = $v;
			}
		}
		return($this);
	}

	/**
	 * Deletes an object hierarchy from the database - Norm does it's best to 
	 * delete all references to this object as well.
	 * @param object $obj This is the object to delete.  
	 * @access public
	 * @returns object
	 * @see get()
	 */
	public function del($obj)
	{
		//go through each populated obj var and peform multi where clauses against it
		$tableName	= self::getTableName($obj);
		$objVars	= get_object_vars($obj);

		$this->getTableSchema($tableName);

		$this->getRelatedTables($tableName);

		// Delete all my relationships
		if (!empty($this->relatedTables[$tableName]))
		{
			foreach(array_keys($this->relatedTables[$tableName]) as $joinTable)
			{
				$Q = "DELETE FROM {$this->prefix}{$tableName}_{$joinTable} WHERE {$tableName}_{$joinTable}_{$tableName}_id='{$obj->id}'";
				$this->query($Q);
			}
		}
		$Q="DELETE FROM {$this->prefix}{$tableName} WHERE {$tableName}_id='{$obj->id}'";
		$this->query($Q);
		return($this);
	}
    /**
     * Sets the "limit" parameters of the final result set
     * @param int $start starting block
     * @param int $end quantity to return
     * @access public
     * @returns object
     * @see get() del() where()
     */
    public function limit($start = 0,$end=1)
    {
        $this->limitStart = intval($start);
        $this->limitEnd   = intval($end);
        return($this);
    }

	/**
	 * Sets the "order by" parameters of any related objects if necessary
	 * @param mixed $orderBy csv, or array of column names (tablename_column) to specify column attributes
	 * @param string $dir Direction to sort, ASC or DESC
	 * @access public
	 * @returns object
     * @see get() del() where()
	 */
	public function orderby($orderBy,$dir='ASC')
	{
		$this->orderDir = $dir;

		if (is_array($orderBy) && !empty($orderby)) $this->orderVars = $orderBy;
		else $this->orderVars = explode(',',$orderBy);
		return($this);
	}

	/**
	 * Sets the fields to look for a "like" parameter ( a percent sign ) in data passed to the specified field.
	 * @param string $column
	 * @access public
	 * @returns objet
     * @see get() where() 
	 */
	public function like($column)
	{
		$this->likeVars[$column] = true;	
		return($this);
	}

	/**
	 * Sets the "where" parameters of any related objects if necessary
	 * @param mixed $whereObjs objects to specify column attributes
	 * @access public
	 * @returns object
     * @see get() del() orderby()
	 */
	public function where($whereObjs = array())
	{
		if (is_array($whereObjs)) 
		{
			foreach($whereObjs as $whereObj) 
			{
				$this->whereVars[self::getTableName($whereObj)] = get_object_vars($whereObj);
			}
		}
		else if (is_object($whereObjs)) $this->whereVars[self::getTableName($whereObjs)] = get_object_vars($whereObjs);
		return($this);
	}


    /**
     * recursive parses objects to create where claused based upon what is set/passed in get
     * @param mixed $objVars objects to specify column attributes
     * @access private
     * @returns string
     * @see get() del()
     */
    private function parseWhere($fromObj,$prefixTable = '')
    {
		if (!is_object($fromObj)) return(false);
		$WHERE		= '';
        $mainTable  = self::getTableName($fromObj);
		if ($mainTable) $mainTable .= '_';
		if (!empty($prefixTable)) $mainTable = $prefixTable.'_'.$mainTable;

        $objVars    = get_object_vars($fromObj);
        // This develops our WHERE clause from our own passed object
        if (!empty($objVars)) foreach($objVars as $k=>$v)
        {
            if (isset($v))
            {
                if (is_object($v))
                {
                    $WHERE .= self::parseWhere($v);
                }
                else 
				{
					$eq = (!empty($this->likeVars["{$mainTable}{$k}"])) ? 'LIKE' : '=';
					if ($eq == 'LIKE') $v = "%{$v}%";
					if ($v != null)
					{
						if (strlen($WHERE)) $WHERE .= " AND ";
						$WHERE .= "`{$mainTable}{$k}` {$eq} '{$v}' ";
					}
				}
            }
        }
        return($WHERE);
    }

	/**
	 * Reverse map to parent table
	 * @param string $tableName name of child table to map from
	 * @return array
	 * see getParentOf()
	**/
	public function getParentTable($tableName)
	{
		if (is_object($tableName)) $tableName = self::getTableName($tableName);
		$this->getMaps();

		foreach($this->maps as $k=>$a)
		{
			if (in_array($tableName,$a)) 
			{
				$parent[$tableName][] = $k;
			}
		}
		return($parent);
	}



	/**
	 * Returns an string containing any "WHERE or AND" clauses
	 * @param object $fromObj This is the child object
	 * @param string $cols CSV of column names - in the format "classname_column1,classname_column2 .. "
	 * @param string $tpf table prefix (usually for reverse joins)
	 * @access public
	 * @returns array
     * @see where() del() reduceTables() index()
	 */
	private function appendOpts($obj,$tpf = '',$Q = '')
	{
		$ORDER	= '';
		$WHERE	= '';
		$AND	= '';

		$WHERE = $this->parseWhere($obj,$tpf);

		// This builds any extra AND clauses
		if (!empty($this->whereVars)) foreach($this->whereVars as $k=>$v) 
		{
			foreach($v as $kn=>$vl) 
			{
				if (!is_null($vl))
				{
					if (!is_array($vl)) $AND .= "AND `{$k}_{$kn}`='{$vl}' ";
					else 
					{
						// This allows for arrays of id's for example..  $t->id = array(1,2,3,4,...)
						$AND .= "AND `{$k}_{$kn}` IN('".join("','",$vl)."') ";
					}
				}
			}
		}

		// This builds any ORDER clauses
		if (!empty($this->orderVars)) foreach($this->orderVars as $v) 
		{
			if (strlen($v)) $ORDER .= "`{$v}`,";
		}
		if (strlen($ORDER)) 
		{
			$ORDER = rtrim($ORDER,',');
			$ORDER = "ORDER BY {$ORDER} {$this->orderDir}";
		}

		// Put it all together
		if (!strlen($Q)) $Q .= " WHERE {$WHERE}";
		else $Q .=  " AND {$WHERE} ";
		if (empty($WHERE)) $Q .= '1';
		$Q .= " {$AND}";
		$Q .= " {$ORDER}";
        if ($this->limitStart || $this->limitEnd)
        {
            if ($this->limitEnd == 0) $this->limitEnd = 1;
            $Q .= " LIMIT {$this->limitStart},{$this->limitEnd}";
        }


		// Release the query parameters
		$this->orderVars	= '';
		$this->likeVars		= '';
		$this->orderDir		= '';
		$this->whereVars	= '';
		return($Q);
	}

	/**
	 * Returns an object hierarchy from a particular child up to parent
	 * @param object $fromObj This is the child object
	 * @param string $cols CSV of column names - in the format "classname_column1,classname_column2 .. "
	 * @access public
	 * @returns array
     * @see where() del() reduceTables() index()
	 */
	public function getParentOf($childObj)
	{
		if (is_object($childObj))	$tableName	= self::getTableName($childObj);
		else						return(false);

		$this->getMaps();
		foreach($this->maps as $k=>$a) if (in_array($tableName,$a)) $parent = $k;

		$Q="SELECT * FROM {$this->prefix}{$tableName} JOIN {$this->prefix}{$parent}_{$tableName} ON ({$parent}_{$tableName}_{$tableName}_id={$tableName}_id) JOIN {$this->prefix}{$parent} ON ({$parent}_id={$parent}_{$tableName}_{$parent}_id)";

		$Q .= $this->appendOpts($childObj);

		$this->query($Q,PDO::FETCH_ASSOC);
		$this->constrainList[] = $parent;
		if (!empty($this->constrainList)) $this->constrainResults();
		return($this);
	}

	/**
	 * Returns an object with all values unset/reset
	 * @param object $fromObj This is the main object to return
	 * @param string $keep CSV of column names - in the format "column1,column2 ..  "
	 * @param bool $getSet Whether or not to return the ENTIRE hierarchical structure
	 * @access public
	 * @returns object
	 */
	public function resetObj($obj,$keep = '')
	{
		if (!empty($keep)) $keep = explode(',',$keep);
		if (is_object($obj))		$objVars		= get_object_vars($obj);
		else return($obj);

		foreach($objVars as $k=>$v)
		{
			if (!in_array($k,$keep)) unset($obj->$k);
		}
		return($obj);
	}

	/**
	 * Returns an list of objects that are connected to the fromObj
	 * @param object $fromObj This is the main object we're looking at
	 * @param object $obj this is the instance of what we are looking for
	 * @param string $cols CSV of column names - in the format "classname_column1,classname_column2 .. "
	 * @param bool $getSet Whether or not to return the ENTIRE hierarchical structure
	 * @access public
	 * @returns array
     * @see where() del() reduceTables() index()
	 */
	public function getObjsFrom($obj,$fromObj,$cols = '*',$getSet = 1)
	{
		if (empty($fromObj) || empty($obj)) 
		{
			trigger_error('Unknown object - make sure objects have instance',E_USER_WARNING);
		}
		$getCols = explode(',',strtolower($cols));

		$fromTable		= self::getTableName($fromObj);
		$objTable		= self::getTableName($obj);

		$joinTable = "{$this->prefix}{$fromTable}_{$objTable}";

		if (is_object($obj))		$objVars		= get_object_vars($obj);
		if (is_object($fromObj))	$fromObjVars	= get_object_vars($fromObj);

		$Q="SELECT ".join(',',$getCols)." FROM `{$joinTable}` INNER JOIN `{$this->prefix}{$objTable}` ON (`{$this->prefix}{$objTable}_id`=`{$joinTable}_{$objTable}_id`)";
	
		$this->where($obj);
		$Q .= $this->appendOpts($fromObj,$joinTable);

		$this->query($Q,PDO::FETCH_ASSOC);

		// sets the results expectations
		return($this->getResults());
	}

	function getResults()
	{
		if (!empty($this->results))
		{
			if (!empty($this->constrainList)) $this->constrainResults();

			if ($this->resultsGetMode == 0) $this->results = $this->index($this->results);	
			return($this);
		}
		else 
		{
			$this->results = false;
			return($this);
		}
	}

	/**
	 * Returns an object hierarchy from the database - Norm does it's best to 
	 * return all references to this object as well.  Get is an ending method
	 * @param object $fromObj This is the main object to return
	 * @param string $cols CSV of column names - in the format "classname_column1,classname_column2 .. "
	 * @param bool $getSet Whether or not to return the ENTIRE hierarchical structure
	 * @access public
	 * @returns array
     * @see where() del() reduceTables() index()
	 */
	public function get($fromObj,$cols = '*',$getSet = 1)
	{
		$getCols = explode(',',strtolower($cols));
		
		$tableName	= self::getTableName($fromObj);
		$objVars	= get_object_vars($fromObj);

		$Q="SELECT ".join(',',$getCols)." FROM {$this->prefix}{$tableName}";

		// Builds join mapping
		if ($getSet)
		{
			$joins = $this->reduceTables($tableName);
			if (!empty($joins))
			{
				$joins = array_reverse($joins);
				foreach($joins as $joinTable=>$qrys)
				{
					foreach($qrys as $qry) 
					{
						if (empty($this->constrainList) || in_array($qry['mapTo'],$this->constrainList))
						{
							$Q .= " JOIN {$this->prefix}{$qry['table']}_{$qry['mapTo']} ON ({$qry['table']}_{$qry['mapTo']}_{$qry['table']}_id={$qry['table']}_id) ";
							$Q .= " JOIN {$this->prefix}{$qry['mapTo']} ON ({$qry['mapTo']}_id={$qry['table']}_{$qry['mapTo']}_{$qry['mapTo']}_id) ";
						}
					}
				}
			}
		}
	
		$Q .= $this->appendOpts($fromObj);

		$this->query($Q,PDO::FETCH_ASSOC);

		return($this->getResults());
	}

	/**
	 * returns only the constrained variables
	 * @access private
	 * @returns object
     * @see get()
	 */
	private function constrainResults()
	{
		if (!empty($this->results))
		{
			foreach($this->results as $idx=>$kvp)
			{
				foreach($kvp as $k=>$v)
				{
					list($tbl,$val) = explode('_',$k);
					if (!in_array($tbl,$this->constrainList)) unset($this->results[$idx][$k]);
				}			
			}
		}
		return($this);
	}

	/**
	 * Sets the list of objects to constrain to in the database (get/set)
	 * @param string $objList csv list of objects to constrain to
	 * @access public
	 * @returns object
     * @see where() del() reduceTables() index()
	 */
	public function constrainTo($objList)
	{
		$cl = explode(',',$objList);	
		foreach($cl as $c) $this->constrainList[] = "{$this->prefix}{$c}";
		return($this);	
	}

	/**
	 * Raw queries for things norm may not be able to do (like that would ever happen :-)
	 * this will also allow me to log queries / responses for future caching.
	 * @param string $Q the raw sql 
	 * @param int PDO fetch mode
	 * @access public
	 * @returns object
     * @see get() del() tie() $this->results for insert id
	 */
	public function query($Q,$fetchmode = PDO::FETCH_NUM)
	{
		$data = self::$link->prepare($Q);
		$data->execute();
		$data->setFetchMode($fetchmode);

		$this->results			= $data->fetchAll();
		$this->insertId			= self::$link->lastInsertId();
		$this->lastQuery		= $Q;

		$this->orderVars		= array();
		$this->whereVars		= array();
		$this->likeVars			= array();

		return($this);
	}

	/**
	 * Stores the data objects and any relationships into the database use it for both inserts and updates.  Norm will decide.
	 * @param object $obj this is the object with any arrays of objects connected to it
	 * @param bool $skipNull if a field is null, don't assign it's value to the database when updating
	 * @access public
	 * @returns object
     * @see get() del() tie()
	 */
	public function store($obj,$skipNull = 1)
	{

		$tableName	= self::getTableName($obj);
		if (!strlen($tableName)) 
		{
			trigger_error('Cannot store object without name!',E_USER_NOTICE);
			return(false);
		}
		$objVars	= get_object_vars($obj);
		$tieThese	= array();

		if (!empty($objVars))
		{
			foreach($objVars as $k=>$val)
			{
				// Skip null fields
				if ($skipNull && $val == null) { unset($objVars[$k]); continue; }

				// perhaps change this to mean 1:1?
				// Allows me to store just direct object
				if (is_object($objVars[$k]))
				{
	//				$this->store($obj->$k,$skipNull);
					$tieThese[] = array($obj,$obj->$k);
					unset($objVars[$k]);
					unset($obj->$k);
				}
				// perhaps change this to mean 1:many?
				// Allows me to store an array of objects
				else if (is_array($objVars[$k]))
				{
					foreach($objVars[$k] as $storeMe)
					{
						$tieThese[] = array($obj,$storeMe);
					}
					unset($objVars[$k]);
					unset($obj->$k);
				}
			}
		}

		// Auto calibrate the database (Make this toggleable))
		$set = self::buildSet($tableName,$objVars);
		if (strlen($set)) 
		{
			$this->query($set);
		}
		

		// Should probably turn this entire jalopy into a prepared statement.
		if (!empty($obj->id))
			$Q="UPDATE `{$this->prefix}{$tableName}` SET";
		else
			$Q="INSERT INTO `{$this->prefix}{$tableName}` SET";

		if (!empty($objVars))
		{
			foreach($objVars as $k=>$v)
			{
				$v = addslashes($v);
				if ($k == 'id') $WHERE[] = "`{$tableName}_{$k}`='{$v}'";
				else $SET.=" `{$tableName}_{$k}`='{$v}',";
			}
			$SET = rtrim($SET,',');
		}

		$Q .= "{$SET}";
		if (!empty($WHERE)) $Q .= " WHERE ".join('AND',$WHERE);

		$this->query($Q);

		if (empty($obj->id))
		{
			$lid = $this->insertId;
			if ($lid) $obj->id = $lid;
		}
		else $this->insertId = $obj->id;

		if (!empty($tieThese)) foreach ($tieThese as $objs) 
		{
			if (is_object($objs[0]) && is_object($objs[1]))
				$this->tie($objs[0],$objs[1],$skipNull);
		}
	//	if (!empty($objArrays)) $this->tie($obj,$objArrays,$skipNull);

		return($this);
	}

	public function sxmlto_array($obj)
	{
          $arr = (array)$obj;
          if(empty($arr)){
              $arr = "";
          } else {
              foreach($arr as $key=>$value){
                  if(!is_scalar($value)){
                      $arr[$key] = sx_array($value);
                  }
              }
          }
          return $arr;
      }

	/** 
	* Converts simplexml to usable norm object
	* 
	*/
	public function simplexmlconvert($obj)
	{
		//$std = json_decode(str_replace('@attributes','__attributes',json_encode($obj)));
		$std = sxmlto_array($obj);

		print_r($std);

		return($final);
	}


	/**
	 * Returns the class name of the object - lowercase.  <em>(windows compatability)</em>
	 * @param object $obj if object has a method name "getTableName" returns the return value of that as the table name
	 * @access private
	 * @returns string
	 */
	private function getTableName($obj)
	{
		if (is_object($obj))
		{
			if (method_exists($obj,'getTableName'))
			{
				return($obj->getTableName());
			}
			else
			{
				return(strtolower(get_class($obj)));
			}
		}
		elseif (is_string($obj)) 
			return(strtolower($obj));
		else return(false);
	}


	/**
	 * Sets how the "get" method will return results from the db e.g: 0 indexed array,1 array, or 2 object 
	 * @param bool $mode (0) / 1 / 2 
	 * @access public
	 * @returns object
     * @see index() get() 
	 */
	public function setResultsGetMode($mode = 0)
	{
		$this->resultsGetMode = $mode;
		return($this);
	}

	// Alias
	public function setGetMode($mode = 0)
	{
		return($this->setResultsGetMode($mode));
	}

	/**
	 * Parses the key=>value pairs of a result set into its individual data structure
	 * @param array $dataset This is the returned data from PDO fetchAll()
	 * @access private
	 * @returns array
     * @see index()
	 */
	private function parseKVP($dataset)
	{
		// Build my dataset KVP
		foreach($dataset as $idx=>$data)
		{
			foreach($data as $k=>$values)
			{
				$pointers	= explode('_',$k);

				$idname = $pointers[0].'_id'; 
				if (!empty($data[$idname])) $i = $data[$idname];

				// These are the actual vars
				if (count($pointers) == 2)
				{
					$tblVars[$pointers[0]][$i][$pointers[1]] = $values;
				}
				// These are the relationships
				if (count($pointers) == 4) 
				{
					// Prevent cyclical linkage to pointing to itself in lookup tables.
					if ($pointers[0] != $pointers[2]) $tblVars[$pointers[0]][$i][$pointers[1]][$values] = $values;
				}
			}
		}
		return(array_reverse($tblVars));
	}

	/**
	 * indexes the results from the database into a usable assoc array 
	 * @param array $dataset This is the returned data from PDO fetchAll()
	 * @access public
	 * @returns array
     * @see get() parseKVP()
	 */
	public function index($dataset)
	{
		if (empty($dataset)) 
		{
			trigger_error('Results Empty',E_USER_NOTICE);
			return(null);
		}

		// Get all my table vars
		$tblVars = $this->parseKVP($dataset);
		$rootTable = array_pop(array_keys($tblVars));
		
		// Condense -> assign each array to it's correct structure
		foreach($tblVars as $tbl=>$cols)
		{
			foreach($cols as $col_id=>$data)
			{
				foreach($data as $k=>$v)
				{
					// Make sure we aren't putting a "comment" inside of a "comment"
                    if (@is_array($v) && $k != $tbl)
					{
						foreach($v as $vid)
						{
							if (is_array($tblVars[$k][$vid]) && !empty($tblVars[$k][$vid]))
							{
								// Graft these arrays to their ID's
								$tblVars[$tbl][$col_id][$k][$vid] = $tblVars[$k][$vid];
							}
						}
						// reindex these
						if (!empty($tblVars[$tbl][$col_id][$k]) && is_array($tblVars[$tbl][$col_id][$k])) $tblVars[$tbl][$col_id][$k] = array_values($tblVars[$tbl][$col_id][$k]);
					}
				}
			}
		}

		// Trim out what we've grafted
		foreach(array_keys($tblVars) as $unsetMe) if ($unsetMe != $rootTable) unset($tblVars[$unsetMe]); 

		// reindex my root table to start indexing at 0
		$tblVars[$rootTable] = array_values($tblVars[$rootTable]);

		return($tblVars);
	}


	/**
	 * parses the DSN string into usable parts
	 * @param string $dsn the DSN string for database connection
	 * @returns array
	 * @access private
	 */
	private function parseDsn($dsn)
	{
		list($dbType,$str) = explode(':',$dsn);
		$dsnParts = explode(';',$str);
		foreach($dsnParts as $p)
		{
			list($k,$v) = explode('=',$p);
			$dsna[$k] = $v;
		}
		return($dsna);
	}

	/**
	 * Maps the database tables into a hierarchy
	 * @access public
	 * @returns array or false
	 */
	public function setMap($mapThis,$hasThis)
	{
		$this->maps[$mapThis][] = $hasThis;
		return($this);
	}

	/**
	 * Maps the database tables into a hierarchy
	 * @access public
	 * @returns array or false
	 */
	public function getMaps()
	{
		if (!empty($this->maps)) return($this->maps);

		if (!empty($this->getTableList()->tableList))
		{
			foreach($this->tableList as $tbl)
			{
				@list($main,$has) = explode('_',$tbl);
				if (!empty($has))
				{
					$this->setMap($main,$has);
				}
			}
			return($this->maps);
		}
		return(false);
	}

	/**
	 * Reduces the table structure of an object into its mapping
	 * @param object $obj the object to reduce
	 * @returns array or false
	 * @access private
	 */
	private function reduceTables($obj)
	{
		$table = $this->getTableName($obj);
		$this->getMaps();
		if (!empty($this->maps[$table]))
		{
			foreach($this->maps[$table] as $idx=>$mapToTable)
			{
				if (isset($this->maps[$mapToTable])) $Q = $this->reduceTables($mapToTable);
				$Q[$table][] = array('mapTo'=>$mapToTable,'table'=>"{$table}");
			}
			return($Q);
		}
		return(false);
	}

	/**
	 * Finds any of the tables that are associated with a particular table
     * Removes "table prefixes" from the tables as well.
	 * @param string $table the name of the table to get associations
	 * @returns array or null
	 * @access private
	 */
	private function getRelatedTables($table)
	{
		$i			= 0;
		$this->getTableList();

		if (!empty($this->tableList))
		{
			// get initial lookp tables
			foreach($this->tableList as $idx=>$tbl)
			{
				$tbl = str_replace($this->prefix,'',$tbl);
				@list($thisObj,$hasA) = explode('_',$tbl);
				if ($thisObj == $table && !empty($hasA)) $this->relatedTables[$thisObj][$hasA] = 1;
			}
		}
		
		if (!empty($this->relatedTables[$table])) return($this->relatedTables[$table]);
		else return(null);
	}

	/**
	 * Gets a list of tables from this database connection
	 * @returns array
	 * @access private
	 * @todo Only get list that contains table_prefix
	 */
	private function getTableList()
	{
		if (empty($this->tableList))
		{
			$Q="SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='{$this->dsna['dbname']}' AND TABLE_NAME LIKE '{$this->prefix}%'"; 
			$ts = $this->query($Q)->results;

			foreach($ts as $idx=>$val)
			{
				if (!empty($this->prefix)) $val[0] = substr($val[0],strlen($this->prefix));
				$this->tableList[] = $val[0];
			}
		}
		//asort($this->tableList);
		return($this);
	}
	
	/**
	 * Gets a the schema for a particular table
	 * @param string $tableName the name of the table to get the schema for
	 * @returns array
	 * @access private
	 */
	private function getTableSchema($tableName)
	{
		if (!strlen($tableName)) return(false);
		if (empty($this->tableColumns[$tableName]))
		{
			$Q="SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME='{$this->prefix}{$tableName}' AND TABLE_SCHEMA='{$this->dsna['dbname']}'"; 
			$ts = $this->query($Q)->results;

			if (empty($ts)) $this->tableColumns[$tableName] = array();
			else
			{
				// Remove the column prefixes whilst setting the table data
				foreach($ts as $i=>$val) 
				{
					$this->tableColumns[$tableName][] = str_replace($tableName.'_','',$val[0]);
				}
			}
		}
		return($this->tableColumns[$tableName]);
	}

	/**
	 * Compare 2 schemas to see what the diff is (for automatic colum creation) additive only
	 * @param array $schema1 the first  schema from to compare
	 * @param array $schema2 the second schema from to compare
	 * @access private
	 * @returns array
     * @see getTableSchema()
	 */
	private function compareSchemas($objSchema,$dbSchema,$ignore = array())
	{
		//$objSchema = array_flip($objSchema);
		$dbSchema = array_flip($dbSchema);

		foreach($ignore as $k=>$v)
		{
			unset($objSchema[$v]);
			unset($dbSchema[$v]);
		}


		$objSchema	= array_keys($objSchema);
		$dbSchema	= array_keys($dbSchema);

		/*
		print "<hr><pre>object schema:\n";
		print_r($objSchema);
		print "<b>Compared to DB:</b>";
		print_r($dbSchema);

		print "<b>Diff:</b>";
		print_r(array_diff($objSchema,$dbSchema));
		*/
		/*
		print "s1<pre>".print_r($objSchema,true)."</pre>";
		print "s2<pre>".print_r($dbSchema,true)."</pre>";

		print "diff<pre>".print_r(array_diff($objSchema,$dbSchema),true)."</pre>";
		*/

		return(array_diff($objSchema,$dbSchema));
	}

	/**
	 * figure out if a field is a datetime field by returning a valid unix timestamp
	 * @param string $dt a string containing a date or a time parsable by php
	 * @returns int 
	 * @access private
	 */
	private function is_datetime($dt)
	{
		return (strtotime($dt));
	}

	/**
	 * Figures out the data type to store in the database for table creation / alteration
	 * @param string $table the name of the table
     * @param string $col name of the column
	 * @access private
	 * @returns string
	 * @see buildSet()
	 */
	private function buildType($table,$col,$v)
	{
		// We could elaborate this function for a little better datatype accomodation .. 
		$Q = '';
		if (is_int($v)) 								$Q .= "`{$table}_{$col}` int not null,";
		else if (is_float($v)) 							$Q .= "`{$table}_{$col}` float not null,";
		//else if (self::is_datetime($v)) 				$Q .= "`{$table}_{$col}` timestamp not null,";
		else if (is_string($v) && strlen($v) <= 255)	$Q .= "`{$table}_{$col}` varchar(255) not null,";
		else if (is_string($v) && strlen($v) > 255)		$Q .= "`{$table}_{$col}` text default(''),";
		else if (is_object($v)) 						
		{
			$tableName	= self::getTableName($v);
														$Q .= "`{$tableName}_{$col}` int unsigned not null,";
		}
		else $Q .= "`{$table}_{$col}` varchar(255) not null,"; // Kinda generic type / catch all.

		return($Q);
	}

	/**
	 * actually builds the ALTER TABLE and CREATE TABLE for the database.
	 * @param string $tableName the name of the table to create
     * @param object $objVars the object containing variables to create
	 * @access private
	 * @returns string
	 * @see getTableSchema() compareSchemas() buildType()
	 */
	private function buildSet($tableName,$objVars)
	{
		if (!strlen($tableName)) return(false);

		$Q = null;
		$dbSchema = $this->getTableSchema($tableName);

		// check if we need to alter tables
		if (!empty($dbSchema))
		{
			// Get my last found column from db schema
			if (isset($dbSchema[count($dbSchema)-2])) $lastCol = $dbSchema[count($dbSchema)-2];

			$diff = $this->compareSchemas($objVars,$dbSchema,array($tableName.'_id',$tableName.'_updated'));
			foreach($diff as $x=>$k)
			{
				// Should be able to return an array, and run each of these queries .. BUUUT .. for now .. 
				$Q="ALTER TABLE `{$this->prefix}{$tableName}` ADD ".$this->buildType($tableName,$k,$objVars[$k]);
				$Q = rtrim($Q,',');

				if (strlen($lastCol)) $Q.=" AFTER `{$tableName}_{$lastCol}`";
				$this->query($Q);
				$this->tableColumns[$tableName][] = $k;
			}
			return(false);
		}
		// Do we need to create new data table?
		else
		{
			$Q="CREATE TABLE IF NOT EXISTS `{$this->prefix}{$tableName}` (`{$tableName}_id` int(11) unsigned not null primary key auto_increment,";
			foreach($objVars as $k=>$v) 
			{
				$Q .= $this->buildType($tableName,$k,$v);
				$this->tableColumns[$tableName][] = $k;
			}
			$Q .= $tableName.'_updated timestamp not null default now())';
		}
		return($Q);
	}

    /** 
     * reindex an array to one of the key values
	 * @param array $array array of assoc arrays
	 * @param string $newIdx a "visible" assoc key to use as new array index
     * @returns array
    **/
    public function setIndex($array,$newIdx)
    {
        $countIdx = array();
        $newArray = array();

        if (is_array($array))
        {
            foreach($array as $idx=>$value)
            {
                if (is_array($value))
                {   
                    if ($countIdx[$value[$newIdx]]++ == 1)
                    { 
                        $oldVal = $newArray[$value[$newIdx]];
                        unset($newArray[$value[$newIdx]]);

                        $newArray[$value[$newIdx]][] = $oldVal;
                        $newArray[$value[$newIdx]][] = $value;
                    } 
                    else if ($countIdx[$value[$newIdx]] > 1)
                    {
                        $newArray[$value[$newIdx]][] = $value;
                    } 
                    else
                    {
                        $newArray[$value[$newIdx]] = $value;
                    }
                }
                else
                {
                    $newArray[$array[$newIdx]][$idx] = $value;
                }
            }
            return($newArray);
        }
        else return($array);
    }

	// Beginning of error handling
	public function trigger_my_error($level, $message) 
	{
		if ($level == E_USER_WARNING || $level ==E_USER_ERROR) 
		{
			$callee = debug_backtrace();
			$dat = $callee[2];
			die("<Br />".$message.' in <strong>'.$dat['file'].'</strong> on line <strong>'.$dat['line'].'</strong>');
		}
		return(true);
	}

}

if (!function_exists('print_pre'))
{
	function print_pre($str,$str2 = null)
	{
		$trace = debug_backtrace();
		$caller = $trace[1];
		print "<b>{$caller['class']}->{$caller['function']}</b><br />";

		if (is_array($str) && is_array($str2))
		{
			print "<table><thead>";
			print "<th>Var 1</th>";
			print "<th>Var 2</th></thead>";
			print "<tdata><tr>";
			print "<td valign=\"top\"><pre>\n".print_r($str,true)."</pre></td>";
			print "<td valign=\"top\"><pre>\n".print_r($str2,true)."</pre></td>";
			print "</table>";
		}
		else
			print "<pre>".print_r($str,true)."</pre>";
	}
}

?>
