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
    protected static $hashAlgorithm = 'sha256';

    /** General hashing algorithm output size */
    protected static $hashLength = 64;

    /**
     * Collection of social ancestors
     * @var Core[]
     */
    protected static $ancestors = array();

    /** Module identifier */
    public $id = 'social';

    /** Database table name for interaction */
    public $dbTable;

    /* Database user email field */
    public $dbEmailField = 'email';

    /**
     * Pointer to current user object
     * @var \samson\activerecord\dbRecord
     */
    protected $user;

    /** Is user authorized */
    protected $authorized = false;

    /**
     * @return \samson\activerecord\dbRecord Pointer to current authorized user object
     */
    public function & user()
    {
        return $this->user;
    }

    /**
     * @return bool True if user is authorized
     */
    public function authorized()
    {
        return $this->authorized;
    }

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

    /** Module initialization */
    public function init(array $params = array())
    {
        // Store this module as ancestor
        self::$ancestors[$this->id] = & $this;

        // If we are not authorized
        if(!$this->authorized) {
            // Search for user in session
            $pointer = & $_SESSION[ $this->identifier() ];
            if (isset($pointer)) {

                // Load user from session
                $this->user = unserialize($pointer);
                $this->authorized = true;

                // Tell all ancestors that we are in
                foreach (self::$ancestors as & $ancestor) {
                    $ancestor->user = & $this->user;
                    $ancestor->authorized = true;
                }
            }
        }
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

    /**
     * @return string Unique module state identifier
     */
    public function identifier()
    {
        return str_replace(array('\\','/'), '_', __NAMESPACE__.'/auth_'.url()->base());
    }

    /**
     * Finish authorization process and return asynchronous response
     * @param \samson\activerecord\dbRecord $user Pointer to filled user object
     * @param bool $remember Flag for setting cookie for further automatic authorization
     *
     * @return array Asynchronous response array
     */
    public function authorize(\samson\activerecord\dbRecord & $user, $remember = false)
    {
        // Store pointer to authorized user
        $this->user = & $user;

        $this->authorized = true;

        // Save user in session
        $_SESSION[ $this->identifier() ] = serialize( $this->user );

        // Tell all ancestors that we are in
        foreach (self::$ancestors as & $ancestor) {
            $ancestor->user = & $this->user;
            $ancestor->authorized = true;
        }

        // Return authorization status
        return $this->authorized;
    }

    /** Call deauthorization process */
    public function deauthorize()
    {
        // Tell all ancestors that we are out
        foreach (self::$ancestors as & $ancestor) {
            $ancestor->authorized = false;
            unset($ancestor->user);
            unset($_SESSION[$ancestor->identifier()]);
        }
    }

    /** Обработчик сериализации объекта */
    public function __sleep()
    {
        // Remove all unnecessary fields from serialization
        return array_diff( parent::__sleep(), array( 'authorized', 'user' ));
    }
}
 