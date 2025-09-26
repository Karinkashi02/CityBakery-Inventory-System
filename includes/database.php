<?php
require_once(LIB_PATH_INC . DS . "config.php");

class Oracle_DB
{
    private $con;

    function __construct()
    {
        $this->db_connect();
    }

    public function db_connect()
    {
        $this->con = oci_connect(DB_USER, DB_PASS, DB_CONN_STRING);
        if (!$this->con) {
            $e = oci_error();
            die("Oracle connection failed: " . $e['message']);
        }
    }

    public function db_disconnect()
    {
        if ($this->con) {
            oci_close($this->con);
            unset($this->con);
        }
    }

    public function query($sql, $bindings = [])
    {
        $stmt = oci_parse($this->con, $sql);
        if (!$stmt) {
            $e = oci_error($this->con);
            die("Error parsing query:<pre>$sql</pre><br>" . $e['message']);
        }

        // Bind parameters if provided
        foreach ($bindings as $key => $value) {
            oci_bind_by_name($stmt, $key, $bindings[$key]);
        }

        $result = oci_execute($stmt);
        if (!$result) {
            $e = oci_error($stmt);
            die("Error executing query:<pre>$sql</pre><br>" . $e['message']);
        }

        return $stmt;
    }

    public function fetch_array($stmt)
    {
        return oci_fetch_array($stmt, OCI_BOTH + OCI_RETURN_NULLS);
    }

    public function fetch_assoc($stmt)
    {
        return oci_fetch_assoc($stmt);
    }

    public function fetch_object($stmt)
    {
        $row = oci_fetch_assoc($stmt);
        return $row ? (object)$row : false;
    }

    public function num_rows($stmt)
    {
        // Oracle does not support rowCount directly.
        // You need to fetch all rows and count them.
        $count = 0;
        while (oci_fetch($stmt)) {
            $count++;
        }
        // Reset cursor to allow re-fetching
        oci_execute($stmt);
        return $count;
    }

    public function affected_rows($stmt)
    {
        return oci_num_rows($stmt);
    }

    public function escape($str)
    {
        // For Oracle, basic escaping (though use bind variables whenever possible)
        return str_replace("'", "''", $str);
    }

    public function while_loop($stmt)
    {
        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $row;
        }
        return $results;
    }
}

$db = new Oracle_DB();
?>
