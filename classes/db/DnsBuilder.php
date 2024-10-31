<?php
namespace Flex\Banana\Classes\Db;

class DnsBuilder 
{
    private string $dsn_mysql = "mysql:host={host};port={port};dbname={dbname};charset={charset}";
    private string $dsn_pgsql = "pgsql:host={host};port={port};dbname={dbname}";

    public function __construct(
        public string $db_type
    ){}

    public function createDSN(string $host, string $dbname, int $port, string $charset) : string 
    {
        $dsn_tpl = match( $this->db_type ) {
            "mysql" => $this->dsn_mysql,
            "pgsql" => $this->dsn_pgsql
        };

        $result = "";
        $result = $this->bindingDNS($dsn_tpl, [
            "host"    => $host,
            "dbname"  => $dbname,
            "port"    => $port,
            "charset" => $charset
        ]);

        return $result;
    }

    private function bindingDNS (string $tpl, array $dsn_options) : string 
    {
        preg_match_all("/({+)(.*?)(})/", $tpl, $matches);
        $patterns = $matches[0];
        $columns  = $matches[2];

        # binding
        foreach($patterns as $idx => $text){
            $column_name = $columns[$idx];
            $render_args[$text] = (trim($dsn_options[$column_name])) ? $dsn_options[$column_name] :'';
        }
        return trim(strtr($tpl, $render_args));
    }
}
