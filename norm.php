<?php 

//-------------------------------------
// Norm - Not an ORM                 
//-------------------------------------
// v .01 - Initial Release 08/06/2010
// Author  : Matthew Frederico          
// License : GPL or whatever works for you      
//-------------------------------------

define('NORM_DEFAULT',0);
define('NORM_FULL',1);

class Norm
{
	var $user = '';
	var $pass = '';
	var $dsna = '';

	var $tableList		= array();
	var $tableSchema	= array();

	private static $link = null;
	

	public function __construct($dsn,$user = null,$pass = null,$attr = null) 
	{
		$this->dsna = self::parseDsn($dsn);

		$this->user = $user;
		$this->pass = $pass;

        if ( self :: $link ) {
            return self;
        }

        self :: $link = new PDO ( $dsn, $user, $pass, $attr ) ;

        return $this;
    }

	// think: JOIN or attach or associate or something .. 
	public function tie($obj1,$obj2,$opt='')
	{
		return($this->tieMany($obj1,$obj2,$opt));

		// This fails with current implementation .. 
		// Default behavior to tie many if we have an array of objects
		if (is_array($obj2))  return($this->tieMany($obj1,$obj2,$opt));
		
		$t1	= get_class($obj1);
		$t2	= get_class($obj2);

		$this->store($obj2);
		$var = "{$t2}_id";
		$obj1->$var = $obj2->id;

		$this->store($obj1);

		if (!strlen($opt)) $opt = "{$t1}.id={$t2}.id";

		return($this);
	}

	public function tieMany($obj1,$objArrays = array(),$opt='')
	{
		// Make sure we have an array of objects
		if (!is_array($objArrays)) 
		{
			$nobj[] = $objArrays;
			$objArrays = $nobj;
		}

		$t1	= get_class($obj1);
		$t2 = get_class($objArrays[0]);

		$tableName = "{$t1}_{$t2}";

		$var1 = "{$t1}_id";
		$var2 = "{$t2}_id";

		$table[$var1] = 0;
		$table[$var2] = 0;


		// Create the structure of the lookup table
		$Q = $this->buildSet($tableName,$table);
		if (strlen($Q))
		{
			$data = self::$link->prepare($Q);
			$data->execute();

			$Q = "ALTER TABLE {$tableName} ADD unique index({$tableName}_{$var1},{$tableName}_{$var2})";
			$data = self::$link->prepare($Q);
			$data->execute();
		}

		// Now store the data into the lookup table
		foreach($objArrays as $obj2)
		{
			$tmp = $this->store($obj2);
			if (isset($obj1->id) && isset($obj2->id))
			{
				$Q="INSERT INTO {$tableName} SET `{$tableName}_{$var1}`='{$obj1->id}', `{$tableName}_{$var2}`='{$obj2->id}'";
				$data = self::$link->prepare($Q);
				$data->execute();
			}
			else 
			{
				user_error('Trying to tie objects that have no id - cannot save to database!',E_USER_ERROR);
			}
		}
	}

	// push array data into my object
	public function stuff($array,$obj,$fields = '')
	{
		// Get this objects name
		$n = get_class($obj);
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

	public function del($obj)
	{
		//go through each populated obj var and peform multi where clauses against it
		$tableName	= get_class($obj);
		$objVars	= get_object_vars($obj);

		$ts		= $this->getTableSchema($tableName);

		// Delete all my relationships
		$tables = $this->getRelatedTables($tableName);
		if (!empty($tables))
		{
			foreach($tables as $joinTable=>$dataTable)
			{
				$Q = "DELETE FROM {$joinTable} WHERE {$joinTable}_{$dataTable}_id='{$obj->id}'";

				$data = self::$link->prepare($Q);
				$data->execute();
			}
		}

		$Q="DELETE FROM {$tableName} WHERE {$tableName}_id='{$obj->id}'";
		$data = self::$link->prepare($Q);
		$data->execute();
		return($this);
	}

	public function get($obj,$cols = '*',$getSet = 0)
	{
		$cols = explode(',',$cols);
		
		//go through each populated obj var and peform multi where clauses against it
		$tableName	= get_class($obj);
		$objVars	= get_object_vars($obj);
		$ts = $this->getTableSchema($tableName);

		$Q="SELECT ".join(',',$cols)." FROM {$tableName}";

		// Build my join (will have to do this for all DSNs .. :-/
		// this is 1 to 1 tie 
		/* THIS NEEDS WORK
		foreach($ts as $k=>$v)
		{
			list($table,$col) = explode('_',$v);
			if ($table != $tableName)
			{
				if ($this->getTableSchema($table))
					$Q .= " LEFT JOIN {$table} ON({$tableName}.{$v}={$table}.{$col})";
			}
		}
		*/
		// This is 1 to many tie
		if ($getSet)
		{
			$tables = $this->getRelatedTables($tableName);
			if (!empty($tables))
			{
				foreach($tables as $joinTable=>$dataTable)
				{
					$Q .= ",{$joinTable} LEFT JOIN {$dataTable} ON ({$joinTable}_{$dataTable}_id={$dataTable}_id)";
				}
			}
		}
	
		// build the where clause
		$Q .= " WHERE ";
		
		// This develops our where clause
		if (!empty($objVars))
		{
			foreach($objVars as $k=>$v)
			{
				if (!empty($v)) $Q .= "{$tableName}_{$k}='{$v}' AND ";
			}
		}
		$Q .= '1';

		$data = self::$link->prepare($Q);
		$data->execute();

		$data->setFetchMode(PDO::FETCH_ASSOC);

		return($data->fetchAll());	
	}

	public function store($obj)
	{
		$tableName	= get_class($obj);
		$objVars	= get_object_vars($obj);

		// Auto calibrate the database
		$set = self::buildSet($tableName,$objVars);
		if (strlen($set))
		{
			$schema = self::$link->prepare($set);
			$schema->execute();
		}

		if (isset($obj->id))
			$Q="UPDATE `{$tableName}` SET";
		else
			$Q="INSERT INTO `{$tableName}` SET";

		foreach($objVars as $k=>$v)
		{
			$Q.=" `{$tableName}_{$k}`='{$v}',";
		}
		$Q = rtrim($Q,',');

		$storage = self::$link->prepare($Q);
		$storage->execute();

		if (!isset($obj->id))
		{
			$lid = self::$link->lastInsertId();
			if ($lid) $obj->id = $lid;
		}

		return($this);
	}

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

	private function getRelatedTables($table)
	{
		$leftJoin = array();
		$i = 0;
		$t = $this->getTableList();

		if (!empty($t))
		{
			foreach($t as $k=>$v)
			{
				@list($tr,$tl) = explode('_',$v);
				if (strlen($tl)) $leftJoin[$tl] = $v;
			}
			$leftJoin = array_flip($leftJoin);
		}
		return($leftJoin);
	}

	private function getTableList()
	{
		if (empty($this->tableList))
		{
			$Q="SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='{$this->dsna['dbname']}'"; 
			$dbSchema = self::$link->prepare($Q);
			$dbSchema->execute();

			$ts = $dbSchema->fetchAll(PDO::FETCH_COLUMN);
			if (!count($ts)) $ts = false;
			$this->tableList  = $ts;
		}
		return($this->tableList);
	}
	
	private function getTableSchema($tableName)
	{
		if (!strlen($tableName)) return(false);
		if (empty($this->tableSchema[$tableName]))
		{
			$Q="SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME='{$tableName}' AND TABLE_SCHEMA='{$this->dsna['dbname']}'"; 
			$dbSchema = self::$link->prepare($Q);
			$dbSchema->execute();

			$ts = $dbSchema->fetchAll(PDO::FETCH_COLUMN);
			if (!count($ts)) $ts = false;

			$this->tableSchema[$tableName] = $ts;
		}
		return($this->tableSchema[$tableName]);
	}

	private function compareSchemas($schema1,$schema2,$ignore = array())
	{
		$schema1 = array_flip($schema1);
		$schema2 = array_flip($schema2);

		foreach($ignore as $k=>$v)
		{
			unset($schema1[$v]);
			unset($schema2[$v]);
		}

		$schema1 = array_keys($schema1);
		$schema2 = array_keys($schema2);

		return(array_diff($schema1,$schema2));
	}

	private function is_datetime($v)
	{
		return (strtotime($v));
	}

	private function buildType($t,$k,$v)
	{
		$Q = '';
		if (is_int($v)) 								$Q .= "`{$t}_{$k}` int not null,";
		else if (is_float($v)) 							$Q .= "`{$t}_{$k}` float not null,";
		//else if (self::is_datetime($v)) 				$Q .= "`{$t}_{$k}` timestamp not null,";
		else if (is_string($v) && strlen($v) <= 255)	$Q .= "`{$t}_{$k}` varchar(255) not null,";
		else if (is_string($v) && strlen($v) > 255)		$Q .= "`{$t}_{$k}` text default(''),";
		else if (is_object($v)) 						
		{
			$tableName	= get_class($v);
														$Q .= "`{$tableName}_{$k}` int unsigned not null,";
		}
		else $Q .= "`{$t}_{$k}` varchar(255) not null,"; // Kinda generic type / catch all.

		return($Q);
	}

	private function buildSet($tableName,$objVars)
	{
		$Q = null;
		$dbSchema = $this->getTableSchema($tableName);

		// check if we need to alter tables
		if (!empty($dbSchema))
		{
			$v = array_keys($objVars);

			// Get my last found column from db schema
			$lastCol = $dbSchema[count($dbSchema)-2];

			$diff = $this->compareSchemas($v,$dbSchema,array($tableName.'_id',$tableName.'_updated'));
			foreach($diff as $x=>$k)
			{
				$Q="ALTER TABLE `{$tableName}` ADD ".$this->buildType($tableName,$k,$objVars[$k]);
				$Q = rtrim($Q,',');
				if (strlen($lastCol)) $Q.=" AFTER `{$lastCol}`";
			}
		}
		// Do we need to create new data table?
		else
		{
			$Q="CREATE TABLE IF NOT EXISTS `{$tableName}` (`{$tableName}_id` int(11) unsigned not null primary key auto_increment,";
			foreach($objVars as $k=>$v)
			{
				$Q .= $this->buildType($tableName,$k,$v);
			}
			$Q .= $tableName.'_updated timestamp not null default now())';
		}
		return($Q);
	}
}

?>
