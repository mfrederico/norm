<?php 

//-------------------------------------
// Norm - Not an ORM                 
//-------------------------------------
// v 1.0 - Major Release
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
	var $relatedTables	= array();
	var $maps			= array();

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
		/*
		if (is_array($obj2))  return($this->tieMany($obj1,$obj2,$opt));
		
		$t1	= get_class($obj1);
		$t2	= get_class($obj2);

		$this->store($obj2);
		$var = "{$t2}_id";
		$obj1->$var = $obj2->id;

		$this->store($obj1);

		if (!strlen($opt)) $opt = "{$t1}.id={$t2}.id";

		return($this);
		*/
	}

	private function getClass($obj)
	{
		return(strtolower(get_class($obj)));
	}

	public function tieMany($obj1,$objArrays = array(),$opt='')
	{
		// Make sure we have an array of objects
		if (!is_array($objArrays)) 
		{
			$nobj[] = $objArrays;
			$objArrays = $nobj;
		}

		$t1	= self::getClass($obj1);

		// Build the lookup table
		foreach($objArrays as $idx=>$nextObj)
		{
			$t2 = self::getClass($nextObj);

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

				// Create unique index for this lookup
				$Q = "ALTER TABLE {$tableName} ADD unique index({$tableName}_{$var1},{$tableName}_{$var2})";
				$data = self::$link->prepare($Q);
				$data->execute();
			}
		}

		// Now store the data into the lookup table for each object
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
				trigger_error('Trying to tie objects that have no id '.self::getClass($obj1).' -> '.self::getClass($obj2).' - cannot save to database!',E_USER_NOTICE);
			}
		}
	}

	// push / STUFF array KVP data into my object
	public function stuff($array,$obj,$fields = '')
	{
		// Get this objects name
		$n = self::getClass($obj);
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
		$tableName	= self::getClass($obj);
		$objVars	= get_object_vars($obj);

		$ts		= $this->getTableSchema($tableName);

		// Delete all my relationships
		$this->getRelatedTables($tableName);
		if (!empty($this->relatedTables[$tableName]))
		{
			foreach(array_keys($this->relatedTables[$tableName]) as $joinTable)
			{
				$Q = "DELETE FROM {$joinTable}_{$tableName} WHERE {$joinTable}_{$tableName}_{$tableName}_id='{$obj->id}'";
				$data = self::$link->prepare($Q);
				$data->execute();
			}
		}
		$Q="DELETE FROM {$tableName} WHERE {$tableName}_id='{$obj->id}'";
		$data = self::$link->prepare($Q);
		$data->execute();
		return($this);
	}

	public function get($fromObj,$cols = '*',$whereObjs = array(),$getSet = 0)
	{
		$cols=strtolower($cols);
		$getCols = explode(',',$cols);
		
		//go through each populated obj var and peform multi where clauses against it
		$tableName	= self::getClass($fromObj);
		$objVars	= get_object_vars($fromObj);

/*
$Q2 = "SELECT * FROM titles INNER JOIN titles_movielist ON (titles_movielist_titles_id=titles_id) INNER JOIN movielist ON(movielist_id=titles_movielist_movielist_id) INNER JOIN movielist_cuepoints ON(movielist_cuepoints_movielist_id=titles_movielist_movielist_id) INNER JOIN cuepoints ON(movielist_cuepoints_cuepoints_id=cuepoints_id) WHERE titles_id=1";
*/

		$Q="SELECT ".join(',',$getCols)." FROM {$tableName}";

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
						$Q .= " INNER JOIN {$qry['table']}_{$qry['mapTo']} ON ({$qry['table']}_{$qry['mapTo']}_{$qry['table']}_id={$qry['table']}_id) INNER JOIN {$qry['mapTo']} ON ({$qry['mapTo']}_id={$qry['table']}_{$qry['mapTo']}_{$qry['mapTo']}_id) ";
					}
				}
			}
		}
	
		// This develops our WHERE clause
		if (!empty($objVars)) foreach($objVars as $k=>$v) if (!empty($v)) $WHERE .= "{$tableName}_{$k}='{$v}' ";

		if (is_array($whereObjs)) foreach($whereObjs as $whereObj) $whereVars[self::getClass($whereObj)] = get_object_vars($whereObj);
		// This builds the AND clause	
		if (!empty($whereVars)) foreach($whereVars as $k=>$v) 
		{
			foreach($v as $kn=>$vl) 
			{
				if (!empty($vl)) $AND .= "AND {$k}_{$kn}='{$vl}' ";
			}
		}

		// Put it all together
		$Q .= " WHERE {$WHERE}";
		if (empty($WHERE)) $Q .= '1';
		$Q .= " {$AND}";

		$data = self::$link->prepare($Q);
		$data->execute();

		$data->setFetchMode(PDO::FETCH_ASSOC);

		return(self::condense($data->fetchAll(),$tableName));	
	}


	//  If I don't reindex, then it will keep id as the index of the array
	public function condense($dataset,$tableName,$reindex = 1)
	{
		if (empty($dataset)) 
		{
			trigger_error('Results Empty',E_USER_NOTICE);
			return(null);
		}
		// Build my dataset KVP
		foreach($dataset as $idx=>$data)
		{
			foreach($data as $k=>$values)
			{
				$pointers	= explode('_',$k);
				$valVar		= $pointers[count($pointers)-1];
				$keyVar		= $pointers[count($pointers)-2];
				$attrs[$keyVar][$idx][$valVar] = $values;

				if (count($pointers) == 4) 
				{
					if ($pointers[0] == $pointers[2]) $lastId = $values;
					if ($pointers[0] != $pointers[2]) $map[$pointers[0]][$lastId][$pointers[2]][$values] = $pointers[3];
				}
			}
		}

		// Now condense down to array of tables / objects
		foreach($attrs as $table=>$array)
		{
			foreach($array as $idx=>$v)
				foreach($v as $col=>$val)
				{
					$final[$table][$v['id']][$col] = $val;
				}
		}

		// put array together based on mapping
		if (!empty($map))
		{
			// This allows us to condense from the greatest to the least
			$map = array_reverse($map);
			foreach($map as $root=>$array)
			{
				foreach($array as $rootIdx=>$dataField)
				{
					$newFinal[$root][$rootIdx] = array();
					foreach($dataField as $key=>$id)
					{
						foreach($id as $idx=>$colname)
						{
							if ($reindex) $final[$root][$rootIdx][$key][] = $final[$key][$idx];
							else $final[$root][$rootIdx][$key][$idx] = $final[$key][$idx];
						}
					}
				}
			}
			foreach(array_keys($final) as $k) if ($k != $tableName) unset($final[$k]);
		}

		return($final);
	}

	public function store($obj,$ignoreNull = 1)
	{
		$tableName	= self::getClass($obj);
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
				if ($ignoreNull && $val == null) { unset($objVars[$k]); continue; }
				// perhaps change this to mean 1:1?
				// Allows me to store just direct object
				if (is_object($objVars[$k]))
				{
					$this->store($obj->$k);
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

		if (!empty($objVars))
		{
			foreach($objVars as $k=>$v)
			{
				$Q.=" `{$tableName}_{$k}`='{$v}',";
			}
			$Q = rtrim($Q,',');
		}

		$storage = self::$link->prepare($Q);
		$storage->execute();

		if (!isset($obj->id))
		{
			$lid = self::$link->lastInsertId();
			if ($lid) $obj->id = $lid;
		}

		if (!empty($tieThese)) foreach ($tieThese as $objs) 
		{
			if (is_object($objs[0]) && is_object($objs[1]))
				$this->tie($objs[0],$objs[1]);
		}
		if (!empty($objArrays)) $this->tie($obj,$objArrays);

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

	private function getMaps()
	{
		if (!empty($this->maps)) return($this->maps);
		$tableList = $this->getTableList();
		if (!empty($tableList))
		{
			foreach($tableList as $tbl)
			{
				@list($main,$has) = explode('_',$tbl);
				if (!empty($has))
				{
					$this->maps[$main][] = $has;
				}
			}
			return($this->maps);
		}
		return(false);
	}

	public function reduceTables($obj)
	{
		if (is_object($obj)) $table = self::getClass($table);
		else $table = $obj;
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

	private function getRelatedTables($table)
	{
		$related	= array();
		$i			= 0;
		$tableList	= $this->getTableList();

		if (!empty($tableList))
		{
			// get initial lookp tables
			foreach($tableList as $idx=>$tbl)
			{
				@list($thisObj,$has) = explode('_',$tbl);
				if (in_array($has,$this->getTablelist())) $related[$has][$thisObj] = 1;
			}
		}
		$this->relatedTables = $related;
		
		if (!empty($this->relatedTables[$table])) return($this->relatedTables[$table]);
		else return(null);
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
		//asort($this->tableList);
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
			$tableName	= self::getClass($v);
														$Q .= "`{$tableName}_{$k}` int unsigned not null,";
		}
		else $Q .= "`{$t}_{$k}` varchar(255) not null,"; // Kinda generic type / catch all.

		return($Q);
	}

	private function buildSet($tableName,$objVars)
	{
		if (!strlen($tableName)) return(false);

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
