<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 11.04.14 at 15:17
 */
 namespace samson\social;

/**
 * Generic class for user registration
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2013 SamsonOS
 * @version 0.1
 */
class Core extends \samson\core\CompressableService
{
    /** General hashing algorithm */
    public static $hashAlgorithm = 'sha256';

    /** General hashing algorithm output size */
    public static $hashLength = 64;

    /** Module identifier */
    public $id = 'social';

    /** Database table name for interaction */
    public $dbTable;

    /* Database user email field */
    public $dbEmailField = 'email';

    /** External callable for handling social authorization */
    public $handler;

    /**
     * Hashing function
     * @param string $value Valur for hashing
     * @return string Hashed value
     */
    public function hash($value)
    {
        return hash(self::$hashAlgorithm, $value);
    }

    /** Module preparation */
    public function prepare()
    {
        // Create and check general database table fields configuration
        db()->createField($this, $this->dbTable, 'dbEmailField', 'VARCHAR(64)');

        return parent::prepare();
    }

    /**
     * Generic random password generator
     * @param int $length Password length
     *
     * @return string Generated password
     */
    public function generatePassword($length = 8)
    {
        $password = '';
        for ($i=0; $i<$length;$i++) {
            $password .= rand(0,9);
        }

        return $password;
    }
}
 