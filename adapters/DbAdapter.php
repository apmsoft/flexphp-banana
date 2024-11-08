<?php
namespace Flex\Banana\Adapters;

use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Adapters\BaseAdapter;

class DbAdapter extends BaseAdapter{
    public function __construct(
        public DbManager $db
    ){}
}