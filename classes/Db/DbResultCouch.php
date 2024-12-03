<?php
namespace Flex\Banana\Classes\Db;

class DbResultCouch {
    private $result;
    private $docs;
    private $currentIndex;
    private $numRows;

    public function __construct(string|array $result) {
        if(!is_array($result)){
            try {
                $result = json_decode($result, true);
            }catch (\JsonException $e) {
            }
        }
        $this->result = $result;

        $this->docs = $this->result['body']['docs'] ?? [];
        $this->currentIndex = 0;
        $this->numRows = count($this->docs);
    }

    public function fetch_assoc() {
        if ($this->currentIndex < $this->numRows) {
            return $this->docs[$this->currentIndex++];
        }
        return false;
    }

    public function fetch_array() {
        if ($this->currentIndex < $this->numRows) {
            $doc = $this->docs[$this->currentIndex++];
            return array_merge($doc, array_values($doc));
        }
        return false;
    }

    public function fetch_row() {
        if ($this->currentIndex < $this->numRows) {
            return array_values($this->docs[$this->currentIndex++]);
        }
        return false;
    }

    public function fetch_object() {
        if ($this->currentIndex < $this->numRows) {
            return (object)$this->docs[$this->currentIndex++];
        }
        return false;
    }

    public function num_rows() {
        return $this->numRows;
    }

    public function fetch_all() {
        return array_map(function($doc) {
            return array_merge($doc, array_values($doc));
        }, $this->docs);
    }

    public function fetch_column($column = 0) {
        if ($this->currentIndex < $this->numRows) {
            $doc = $this->docs[$this->currentIndex++];
            $values = array_values($doc);
            return isset($values[$column]) ? $values[$column] : null;
        }
        return false;
    }

    // CouchDB 특화 메서드
    public function get_bookmark() {
        return $this->result['bookmark'] ?? null;
    }

    public function get_warning() {
        return $this->result['warning'] ?? null;
    }

    public function get_execution_stats() {
        return $this->result['execution_stats'] ?? null;
    }
}