<?php
namespace Flex\Banana\Adapter;

use Flex\Banana\Db\DbMySqli;
use Flex\Banana\Db\WhereHelper;
use Flex\Banana\Db\WhereHelperInterface;
use Flex\Banana\Adapter\BaseAdapter;

class DbMySqlAdapter extends BaseAdapter{
    public WhereHelperInterface $whereHelper;
    public function __construct(
        public DbMySqli $db,
        ?WhereHelperInterface $whereHelper = null
    ){
        # WhereHelper 를 상속은 커스텀 클래스 등록 가능
        $this->whereHelper = $whereHelper ?? new WhereHelper();
    }
}

?>