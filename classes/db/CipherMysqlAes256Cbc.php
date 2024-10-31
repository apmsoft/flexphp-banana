<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Db\Cipher;
use Flex\Banana\Classes\Db\DbCipherInterface;
use Flex\Banana\Classes\Db\DbManager;
use \PDO;
use \Exception;

class CipherMysqlAes256Cbc implements DbCipherInterface
{
    public const __version = '0.1';
    private const RANDOM_BYTES = 16;  // IV 길이
    public const ENCRYPTION_MODE = 'aes-256-cbc';

    public function __construct(
        private string $hashkey,
        private DbManager $dbManager
    ){
        $this->dbManager = $dbManager;
    }

    public function setHashKey(string $hashkey) : self
    {
        $this->hashkey = $hashkey;
    return $this;
    }

    public function encrypt(string $column): string
    {
        return "HEX(AES_ENCRYPT('{$column}', SHA2('{$this->hashkey}', 512), RANDOM_BYTES(" . self::RANDOM_BYTES . ")))";
    }

    public function decrypt(string $column): string
    {
        return "AES_DECRYPT(UNHEX($column), SHA2('{$this->hashkey}', 512))";
    }


    public function set_encryption_mode(): void
    {
        $pdo = $this->dbManager->pdo;

        # 서버 버전
        $mysql_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        if (version_compare($mysql_version, '5.7.0', '<')) {
            throw new Exception(sprintf("Setting(%s) is not possible for MySQL version lower than 5.7.0", CipherMysqlAes256Cbc::ENCRYPTION_MODE));
        }

        # encryption_mode 확인
        $encryption_mode_qry = "SELECT @@session.block_encryption_mode as em";
        $stmt = $pdo->query($encryption_mode_qry);
        $encryption_row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (isset($encryption_row['em'])) {
            if ($encryption_row['em'] !== CipherMysqlAes256Cbc::ENCRYPTION_MODE) {
                $set_encrypt_qry = sprintf("SET @@session.block_encryption_mode = '%s'", CipherMysqlAes256Cbc::ENCRYPTION_MODE);
                $pdo->exec($set_encrypt_qry);
            }
        }
    }
}