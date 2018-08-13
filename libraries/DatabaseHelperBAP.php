<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DatabaseHelperBAP.php
 * <br />Database Helper Class
 * <br />
 * <br />This is a DB helper for consistency and simplicity
 * 
 * @author Basit Adhi Prabowo, S.T. <basit@unisayogya.ac.id>
 * @access public
 * @link https://github.com/basit-adhi/MyCodeIgniterLibs/blob/master/libraries/DatabaseHelperBAP.php
 */
class DatabaseHelperBAP
{
    /**
     *
     * @var array session value that stored in session
     */
    private $session_ofpartitionfield;
    /**
     *
     * @var array database's table
     */
    private $tables;
    /**
     *
     * @var CI super-object
     */
    protected $CI;

    // We'll use a constructor, as you can't directly call a function
    // from a property definition.
    function __construct()
    {
        // Assign the CodeIgniter super-object
        $this->CI =& get_instance();
        //--
        $this->CI->load->helper('variablebap');
        $this->loadSession();
        $this->tables = new TableStructure();
    }

    // --------------------------------------------------------------------
    
    /**
     * Initial table structure
     * Customize this function, change all variable inside this function to fit your needs
     */
    private function loadSession()
    {
//Example:
//        $this->session_ofpartitionfield = array("tahunanggaran" => ifnull($this->CI->session->userdata("idtahunanggaran"), 0));
        $this->session_ofpartitionfield = array("tahunanggaran" => ifnull($this->CI->session->userdata("idtahunanggaran"), 0));
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Register table(s) definition
     * Example 1:
     * $this->databasehelperbap->registerTable("table1");           - will register a table
     * Example 2:
     * $this->databasehelperbap->registerTable("table1, table2");   - will register more than one table
     * Customize this function (conditional switch), change to fit your tables definition
     * @param type $tablename   table(s) to select
     */
    function registerTable($tablename)
    {
        /* if $tablename consist more than 1 table (use comma), then register it 1 by 1 */
        if (strpos($tablename, ",") !== false)
        {
           foreach (explode(",", $tablename) as $singletablename)
           {
               $this->registerTable(trim($singletablename));
           }
        }
        /* register 1 table */
        elseif (!array_key_exists($tablename, $this->tables->name))
        {
            //Customize this conditional switch, change to fit your tables definition
//Example:
//            switch ($tablename)
//            {
//                case "ueu_tbl_tahunanggaran"    : $this->tables->addTableStructure($tablename, "ta", "idtahunanggaran", array(), array()); break;
//                case "ueu_tbl_unit"             : $this->tables->addTableStructure($tablename, "tu", "id_unit", array(), array("tahunanggaran" => "tahunanggaran")); break;
//                case "ueu_tbl_user"             : $this->tables->addTableStructure($tablename, "tus", "idlog", array("ueu_tbl_unit" => "id_unit"), array("tahunanggaran" => "tahunanggaran")); break;
//                default: break;
//            }
            switch ($tablename)
            {
                case "ueu_tbl_tahunanggaran"    : $this->tables->addTableStructure($tablename, "ta", "idtahunanggaran", array(), array()); break;
                case "ueu_tbl_unit"             : $this->tables->addTableStructure($tablename, "tu", "id_unit", array(), array("tahunanggaran" => "tahunanggaran")); break;
                case "ueu_tbl_user"             : $this->tables->addTableStructure($tablename, "tus", "idlog", array("ueu_tbl_unit" => "id_unit"), array("tahunanggaran" => "tahunanggaran")); break;
                default: break;
            }
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Adds a SELECT clause to a query, automatically add join, automatically add partition filter
     * @param type $select      The SELECT portion of a query
     * @param type $fromtable   table(s) to select, example1 : "table1", example2: "table1, table2" 
     */
    private function selectfrom($select, $fromtable)
    {
        $this->loadSession();
        /* convert select to array */
        $fromtables     = explode(",", $fromtable);
        /* generate select and from */
        $this->CI->db->select($select);
        $this->CI->db->from(implode_2a(",", $fromtables, select_array_from_values($this->tables->tablealias, $fromtables)));
        /* try generate join and partition filter */
        foreach ($fromtables as $table)
        {
            /* generate join */
            if (!empty($this->tables->onjoin[$table])) 
            {
                foreach ($this->tables->onjoin[$table] as $tablejoin => $field)
                {
                    //only join registered tables, not all
                    if (in_array($this->tables->tablealias[$table], $this->tables->name)) 
                    {
                        $this->CI->db->where($this->tables->tablealias[$table].".".$field, $this->tables->tablealias[$tablejoin].".".$this->tables->key[$tablejoin]);
                    }
                }
            }
            /* generate partition filter */
            if (!empty($this->tables->partitionkey[$table])) 
            {
                foreach ($this->tables->partitionkey[$table] as $fieldPartitionInTable => $indexPartitionInSession)
                {
                    $this->CI->db->where($this->tables->tablealias[$table].".".$fieldPartitionInTable, $this->session_ofpartitionfield[$indexPartitionInSession]);
                }
            }
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Adds a SELECT clause to a query, automatically add join, automatically add partition filter, then execute $this->CI->db->get(). You can combine with CI Query Builder before use this function.
     * Same as if you use CI:
     * $this->db->select($select);
     * $this->db->from($fromtable);
     * $this->db->where($join1, $join2);        - implicit, see registerTable()
     * $this->db->where($partitionkey, $value); - implicit, see registerTable()
     * $this->db->get();
     * Example 1:
     * $this->databasehelperbap->get_selectfrom("*", "ueu_tbl_unit");
     * Example 2:
     * $this->db->order_by($order);
     * $this->db->where($customwhere);
     * $this->databasehelperbap->get_selectfrom("*", "ueu_tbl_unit");
     * @param type $select      The SELECT portion of a query
     * @param type $fromtable   table(s) to select, example1 : "table1", example2: "table1, table2" 
     * @param type $where       Filter / where portion of a query (array)
     * @return type CI_DB_result instance (same as $this->CI->db->get())
     */
    function get_selectfrom($select, $fromtable, $where = array(), $limit = null, $offset = null)
    {
        if ($limit != NULL)
        {
            $this->CI->db->limit($limit, ifnull($offset, 0));
        }
        $this->selectfrom($select, $fromtable);
        if (is_array($where) && count($where) > 0)
        {
            $this->CI->db->where($where);
        }
        return $this->get();
    }
    
    // --------------------------------------------------------------------
    
    /**
     * return CI DB
     * @return type CI DB
     */
    function db()
    {
        return $this->CI->db;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Generates a platform-specific query string that counts all records returned by an Query Builder query
     * @return type CI_DB_result instance (same as $this->CI->db->get())
     */
    function get()
    {
        return $this->CI->db->get();
    }
}

class TableStructure
{
    /**
     *
     * @var array   table's name
     */
    var $name               = array();
    /**
     *
     * @var array   primary key of the table
     */
    var $key                = array();
    /**
     *
     * @var array   alias of the table
     */
    var $tablealias         = array();
    /**
     *
     * @var array   partition key 
     */
    var $partitionkey       = array();
    /**
     *
     * @var array   other table that join in the current table
     */
    var $onjoin             = array();
    
    /**
     * Add an table structure
     * @param string $tablename     table's name
     * @param string $tablealias    alias of the table
     * @param string $key           primary key of the table (without alias)
     * @param array $onjoin         other table that join in the current table (without alias), format: array("other table name" => "field in the current table that join to other table", ...)
     * @param array $partitionkey   partition key (without alias), format: array("partition key in the table" => "session index", ...)
     */
    function addTableStructure($tablename, $tablealias, $key, $onjoin = array(), $partitionkey = array())
    {
        if (!in_array_r($tablename, $this->name))
        {
            if (!in_array_r($tablealias, $this->tablealias))
            {
                $this->tablealias[$tablename]   = $tablealias;
            }
            else
            {
                echo "ERROR: Duplicate Table Alias!!";
            }
            $this->name[]                       = $tablename;
            $this->key[$tablename]              = $key;
            $this->onjoin[$tablename]           = $onjoin;
            $this->partitionkey[$tablename]     = $partitionkey;
        }
    }
        
}
/**
EXAMPLE
 * ------------------------------
Model application/models/Mexample.php
 * ------------------------------
<?php
class Mexample extends CI_Model {

    var $tabledef;

    function __construct()
    {
	parent::__construct();
        $this->load->library('DatabaseHelperBAP');
        $this->databasehelperbap->registerTable("ueu_tbl_unit,ueu_tbl_user");
    }

    //generate: select * from ueu_tbl_unit tu, ueu_tbl_user tus where tus.id_unit=tu.id_unit and tu.tahunanggaran=$this->CI->session->userdata("idtahunanggaran") and tus.tahunanggaran=$this->CI->session->userdata("idtahunanggaran")
    public function getDataSample1($name)
    {
        return $this->databasehelperbap->get_selectfrom("*", "ueu_tbl_unit,ueu_tbl_user");
    }

    //generate: select * from ueu_tbl_unit tu, ueu_tbl_user tus where tus.id_unit=tu.id_unit and tu.tahunanggaran=$this->CI->session->userdata("idtahunanggaran") and tus.tahunanggaran=$this->CI->session->userdata("idtahunanggaran") and filter1=$filter1
    public function getDataSample2($name, $filter1)
    {
        $this->databasehelperbap->db()->where("fiter1", $filter1);
        return $this->databasehelperbap->get_selectfrom("*", "ueu_tbl_unit,ueu_tbl_user");
    }
}
 */
