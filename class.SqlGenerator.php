<?php 
/**
 * class.SqlGenerator.php
 * 
 * @link http://MYnetDESK.pl
 * @author gcdreak
 * @version 0.1
 */


	/**
	 * Class generating SQL queries using methods invoking like $obj->select().
	 * 
	 * @author gcdreak
	 */
	class SqlGenerator {
		
		/**
		 * SQL query.
		 * @var string
		 */
		private $sql;
		
		/**
		 * Type of SQL query.
		 * @var string
		 */
		private $sqlAction;
		
		/**
		 * Tables using in query.
		 * @var array
		 */
		private $sqlTables = array();
		
		/**
		 * Fields using in query. 
		 * @var array
		 */
		private $sqlFields = array();
		
		/**
		 * Variable using to store field when use $db->where('fieldName')->in('1,2')
		 * @var string
		 */
		private $sqlTmpField;
		
		/**
		 * Variable using to store ORDER BY clauses.
		 * @var unknown_type
		 */
		private $order;
		
		/**
		 * SQL conditions.
		 * @var array
		 */
		private $sqlConditions = array();
		
		/**
		 * Fields and values to set. Associative array. 
		 * @var array
		 */
		private $sqlSetValues = array();
		
		/**
		 * Fields and values to insert. Associative array. 
		 * @var array
		 */
		private $sqlInsertValues = array();
		
		/**
		 * SQL limit.
		 * @var mixed	string or integer
		 */
		private $sqlLimit;
		
		/**
		 * String with all tables using in query.
		 * @var string
		 */
		private $sqlStrTables;
		
		/**
		 * String with all fields using in query.
		 * @var string
		 */
		private $sqlStrFields;
		
		/**
		 * String with all conditions using in query.
		 * @var string
		 */
		private $sqlStrConditions;
		
		
		/**
		 * Set query type as SELECT and set fields using in query.
		 * 
		 * @see setFields()
		 * @param mixed	$fields	Array or string with fields using in query.
		 * @return object SqlGenerator
		 */
		public function select($fields) {
			$this->setFields($fields);
			
			$this->sqlAction = 'SELECT';
			
			return $this;
		}
		
		
		/**
		 * Set query type as INSERT and set data to insert.
		 *  
		 * @param array $arrValues Array with fields and values to insert. Associative array. 
		 * @return object SqlGenerator
		 */
		public function insert($arrValues) {
			$this->sqlAction = 'INSERT';
			$index = count($this->sqlInsertValues);
			$fields = array();
			
			if( is_array($arrValues[0])) {
				$fields = array_keys($arrValues[0]);

				for($i=0, $j = count($arrValues); $i < $j; $i++) {
					$this->sqlInsertValues[$index] = $arrValues[$i];
					$index++;
				}
				
			} else {
				$fields = array_keys($arrValues);
				
				$this->sqlInsertValues[$index] = $arrValues;
			}
			
			$this->sqlFields = $fields;
			
			return $this;
		}
		
		
		/**
		 * Set table using in query. 
		 * 
		 * @param string $table Table using in query.
		 * @return object SqlGenerator
		 */
		public function into($table) {
			$this->table($table);
			
			return $this;
		}
		
		
		/**
		 * Set query type as UPDATE and set tables using in query.
		 * 
		 * @see setTables()
		 * @param mixed $tables Array or string with tables. 
		 * @return object SqlGenerator
		 */
		public function update($tables = null) {
			$this->setTables($tables);
			
			$this->sqlAction = 'UPDATE';
			
			return $this;
		}
		
		
		/**
		 * Add field and new value of this field using in UPDATE queries.
		 *  
		 * @param string $field Name of fields in database table.
		 * @param mixed $newVal New value.
		 * @return object SqlGenerator
		 */
		public function set($field, $newVal) {
			$this->sqlSetValues[$field] = $newVal;
			
			return $this;
		}
		
		
		/**
		 * Set query type as DELETE and set table(s) if optional $table is passed.
		 * 
		 * @see setTables()
		 * @param mixed $tablesable using in query. 
		 * @return object SqlGenerator
		 */
		public function delete($tables = null) {
			$this->sqlAction = 'DELETE';

			$this->setTables($tables);
			
			return $this;
		}
		
		
		/**
		 * Set LIMIT using in query.
		 * 
		 * @param mixed $limit Integer or string with limit.
		 * @return object SqlGenerator
		 */
		public function limit($limit) {
			$this->sqlLimit = $limit;
			
			return $this;
		}
		
		
		/**
         * Set queery type as ALTER TABLE and set table using in query if $table is given.
         * 
         * @see setTable()
         * @param string $table String with table name. 
         * @return object SqlGenerator
         */
		public function alterTable($table = null) {
			$this->setTable($table);
			
			$this->sqlAction = 'ALTER TABLE';
			
			return $this;
		}
		
		
		/**
		 * Set or add table using in query.
		 * 
		 * @param string $strTable Table name.
		 * @return object SqlGenerator
		 */
		public function table($strTable) {
			$this->sqlTables[] = $strTable;
			
			return $this;
		}

		
		/**
		 * Set or add tables using in query.
		 * 
		 * @param array $arrTables Table's names.
		 * @return object SqlGenerator
		 */
		public function tables($arrTables) {
			array_merge($this->sqlTables, $arrTables);
			
			return $this;
		}
		
		
		/**
		 * Using to set conditions using in query.
		 *  
		 * @param string $field Name of fields or all condition with field name.
		 * @param string $condition Condition of $field.
		 * @param string $joiner Word using to join condition with others. AND or OR.
		 * @return object SqlGenerator
		 */
		public function where($field, $condition = null, $joiner = 'AND') {
			
			if( $condition === null) {
				$this->sqlTmpField = $field;
			} else {
				$joiner = strtoupper($joiner);
				
				if( $condition === false) {
					$condition = $field;
					$field = ' ';
				}
				
				if( count($this->sqlConditions)) {
					$this->sqlConditions[] = sprintf('%s %s %s', $joiner, $field, $condition);
				} else {
					$this->sqlConditions[] = sprintf('%s %s', $field, $condition);
				}
			}
				
			return $this;
		}
		
		
		/**
		 * Add extra condition with AND.
		 *
		 * @see where()
		 * @param string $field Name of field.
		 * @param string $condition Condition to field $field.
		 * @return object SqlGenerator
		 */
		public function andSql($field, $condition) {
			$this->where($field, $condition);
			
			return $this;
		}
		
		
		/**
		 * Add extra condition with OR.
		 *
		 * @see where()
		 * @param string $field Name of field.
		 * @param string $condition Condition to field $field.
		 * @return object SqlGenerator
		 */
		public function orSql($field, $condition) {
			$this->where($field, $condition, 'OR');
			
			return $this;
		}

		
		/**
		 * Set LIKE condition in query.
		 * 
		 * @see where()
		 * @param string $field Name of field or condition.
		 * @param string $condition Condition to field $field.
		 * @param string $joiner Word using to join condition with others. AND or OR.
		 * @return object SqlGenerator
		 */
		public function like($field, $condition = null, $joiner = 'AND') {
			
			if( $condition === null) {
				$condition = $field;
				$field = $this->sqlTmpField;
			}

			$condition = sprintf('LIKE %s', $condition);
			
			$this->where($field, $condition, $joiner);
			
			return $this;
		}
		
		
		/**
		 * Set IN condition in query.
		 * 
		 * @param mixed $field String, integer or array. Name of field if $values is given. Else values if field has been set via where('FieldName')->in(array(1,4,6)). 
		 * @param mixed $values Array or string with values.
 		 * @param string $joiner Word using to join condition with others. AND or OR.
		 * @return object SqlGenerator
		 */
		public function in($field, $values = null, $joiner = 'AND') {
			
			if( $values === null) {
				$values = $field;
				$field = $this->sqlTmpField;
			}
			
			if( is_array($values)) {
				$condition = sprintf('IN (%s)', join(', ', $values));
			} else {  
				$condition = sprintf('IN (%s)', $values);
			}
			
			$this->where($field, $condition, $joiner);
			
			return $this;
		}
		
		
		/**
		 * Set tables using in query from array or string.
		 * 
		 * @param mixed $table Array with tables or string with one table's name. 
		 * @return object SqlGenerator
		 */
		public function setTables($table) {
			if( is_array($table)) {
				$this->sqlTables = array_merge($this->sqlTables, $table);
			} elseif( is_string($table)) {
				$this->sqlTables[] = $table;
			}
			
			return $this;
		}
		
		
		/**
		 * Set fields using in query.
		 * 
		 * @param mixed $fields Array with fields or string with one field's name. 
		 * @return object SqlGenerator
		 */
		public function setFields($fields) {
			if( is_array($fields)) {
                $this->sqlFields = array_merge($this->sqlFields, $fields);
            } elseif( is_string($fields)){
                $this->sqlFields[] = $fields;
            }
            
            return $this;
		}
		
		
		/**
		 * Set full user's query.
		 * 
		 * @param string $sql SQL query to database.
		 * @return object SqlGenerator
		 */
		public function setSql($sql) {
			$this->sql = $sql;
			
			return $this;
		}

		
		/**
		 * Set ORDER BY clouse
		 *  
		 * @param 	string	$orderBy String representing ORDER BY clause.
		 * @return 	object 	SqlGenerator
		 */
		private function order($orderBy) {
			$this->order = $orderBy;
			
			return $this;
		}
		
		
		/**
		 * Alias to clearLastStatementData(). 
		 * 
		 * @see clearLastStatementData()
		 * @param bool $clearStrValues Clear all object variables?
		 */
		public function reset($clearStrValues) {
			$this->clearLastStatementData($clearStrValues);
		}
		
		
		/**
		 * Alias to setTables().
		 * 
		 * @see setTables()
		 * @param mixed $tables Array or string with tables using in query.
		 * @return object SqlGenerator
		 */
		public function from($tables) {
			$this->setTables($tables);
			
			return $this;
		}
		
		
		/**
		 * Return generated SQL query.
		 * 
		 * @see buildSql()
		 * @see clearLastStatementData()
		 * @param bool $semicolon Specify if query will be ended with semicolon.
		 * @return string Generated SQL query.
		 */
		public function getSql($semicolon = null) {
			$this->buildSql();
			
			if( $semicolon)
				$sql = $this->sql . ';';
			else
				$sql = $this->sql;
				
			$this->clearLastStatementData();
			
			return $sql;
		}
		
		
		/**
		 * Generate SQL query from data already setted if query hadn't been setted by setSql().
		 * 
		 * @see setSql()
		 * @see buildSelectSql()
		 * @see buildUpdateSql()
		 * @see buildInsertSql()
		 * @see buildDeleteSql()
		 * @see buildAlterTableSql()
		 */
		protected function buildSql() {
			if( !$this->sql) {
				$this->sql = $this->sqlAction;
				
				$this->sqlStrTables = join(', ', $this->sqlTables);
				$this->sqlStrFields = join(', ', $this->sqlFields);
				$this->sqlStrConditions = join(' ', $this->sqlConditions);
				
				switch($this->sqlAction) {
					case 'SELECT': $this->buildSelectSql(); break;
					case 'UPDATE': $this->buildUpdateSql(); break;
					case 'INSERT': $this->buildInsertSql(); break;
					case 'DELETE': $this->buildDeleteSql(); break;
					case 'ALTER TABLE': $this->buildAlterTableSql(); break;
				}
			}
		}
		
		
		/**
		 * Build SELECT query.
		 * 
		 * @see setConditions()
		 * @see setLimit()
		 */
		protected function buildSelectSql() {
			$this->sql .= ' ' . $this->sqlStrFields;
			$this->sql .= ' FROM ' . $this->sqlStrTables;
			
			$this->setConditions();
			$this->setLimit();
		}
		
		
		/**
		 * Build UPDATE query.
		 * 
		 * @see setConditions()
		 * @see setLimit()
		 */
		protected function buildUpdateSql() {
			$this->sql .= ' ' . $this->sqlStrTables;
			$this->sql .= ' SET';
			
			foreach($this->sqlSetValues as $field => $val) {
				$this->sql .= ' ' . $field . ' = ' . $val;
			}
			
			$this->setConditions();
			$this->setLimit();
		}
		
		
		/**
		 * Build INSERT query.
		 * 
		 */
		protected function buildInsertSql() {
			$this->sql .= ' INTO ' . $this->sqlStrTables;
			$this->sql .= ' (' . join(', ', $this->sqlFields) . ') ';
			$this->sql .= 'VALUES ';
			
			$len = count($this->sqlInsertValues);
			
			if( $len === 1) {
				$this->sql .='(' . join(', ', array_values($this->sqlInsertValues[0])) . ')';
			} else {
				for( $i = 0; $i < $len; $i++) {
					$values[] = '(' . join(', ', array_values($this->sqlInsertValues[$i])) . ')';
				}

				$this->sql .= join(', ', $values);
			}
		}
		
		
		/**
		 * Build ALTER TABLE query.
		 * 
		 * @see setConditions()
		 * @see setLimit()
		 */
		public function buildDeleteSql() {
			$this->sql .= ' FROM ' . $this->sqlStrTables;
			
			$this->setConditions();
			$this->setLimit();
		}
		
		
		/**
		 * Add SQL conditions to complete query.
		 * 
		 * @see where()
		 */
		protected function setConditions() {
			if( count($this->sqlConditions)) {
				$this->sql .= ' WHERE ' . $this->sqlStrConditions;
			}
		}
		
		
		/**
		 * Add SQL LIMIT to complete query.
		 * 
		 * @see limit()
		 */
		protected function setLimit() {
			if( $this->sqlLimit) {
				$this->sql .= ' LIMIT ' . $this->sqlLimit;
			}
		}
		
		
		/**
		 * Clear data used to generated query. It's important to built next query.
		 *  
		 * @param bool $clearStrValues Clear all object variables?
		 */
		protected function clearLastStatementData($clearStrValues = null) {
			$this->sql = null;
			$this->sqlConditions = array();
			$this->sqlFields = array();
			$this->sqlTables = array();
			$this->sqlSetValues = array();
			$this->sqlInsertValues = array();
			$this->sqlLimit = null;
			$this->sqlTmpField = null;
			$this->order = null;
			
			if( $clearStrValues) {
				$this->sqlStrConditions = null;
				$this->sqlStrFields = null;
				$this->sqlStrTables = null;
			}
		}
		
		
		/**
		 * Return SQL keyword of query.
		 * 
		 * @return string SQL keyword of query.
		 */
		public function getSqlAction() {
			return $this->sqlAction;
		}
		
		
		/**
		 * Return SQL query.
		 * 
		 * @return string Generated SQL query. 
		 */
		public function __toString() {
			$this->buildSql();
			
			return $this->sql;
		}
	}
	