<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 11.04.14 at 15:17
 */
 namespace samson\social;

 use samson\activerecord\dbRecord;
 use samson\core\CompressableService;

 /**
 * Generic class for user authorization
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2013 SamsonOS
 * @version 0.1
 */
class Core extends CompressableService
{
    /** General hashing algorithm */
    public $hashAlgorithm = 'sha256';

    /** General hashing algorithm output size */
    public $hashLength = 64;

    /** @var callable External initialization handler */
    public $initHandler;

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

    /* Database user password field */
    public $dbPasswordField = 'Password';

    /** Database user token field */
    public $accessToken = 'accessToken';

    /**
     * Pointer to current user object
     * @var dbRecord
     */
    protected $user;

    /** Is user authorized */
    protected $authorized = false;

    /** Pointer to current social module who has authorized */
    public $active;

    /**
     * Update authorization status of all social services
     * @param dbRecord $user Pointer to authorized user database record
     */
    protected function update(dbRecord & $user)
    {
        // Load user from session
        $this->user = $user;
        $this->authorized = true;

        // Save to session
        $_SESSION[$this->identifier()] = serialize($this->user);

        // Tell all ancestors that we are in
        foreach (self::$ancestors as & $ancestor) {
            $ancestor->user = & $this->user;
            $ancestor->active = & $this;
            $ancestor->authorized = $this->authorized;
        }
    }

    /**
     * @return dbRecord Pointer to current authorized user object
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
        (isset($_SESSION[m('socialemail')->identifier()])) ? $this->authorized = true : $this->authorized = false;

        return $this->authorized;
    }

    /**
     * Hashing function
     * @param string $value Valur for hashing
     * @return string Hashed value
     */
    public function hash($value)
    {
        return hash($this->hashAlgorithm, $value);
    }

    /** Module preparation */
    public function prepare()
    {
        // Create and check general database table fields configuration
        db()->createField($this, $this->dbTable, 'dbEmailField', 'VARCHAR(64)');
        // Create and check general database table fields configuration
        db()->createField($this, $this->dbTable, 'dbPasswordField', 'VARCHAR(64)');
        // Create and check general database table fields configuration
        db()->createField($this, $this->dbTable, 'accessToken', 'VARCHAR(256)');

        return parent::prepare();
    }

    /**
     * Module initialization
     * @param array $params
     */
    public function init(array $params = array())
    {
        // Store this module as ancestor
        self::$ancestors[$this->id] = & $this;

        // If we are not authorized
        if (!$this->authorized) {
            // Search for user in session
            $pointer = & $_SESSION[ $this->identifier() ];
            if (isset($pointer)) {

                // Load user from session
                $this->user = unserialize($pointer);

                $this->update($this->user);
            }
        }

        // If external init handler is set
        if (is_callable($this->initHandler)) {
            // Call external handler and pass reference on this object
            call_user_func_array($this->initHandler, array(&$this));
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
        for ($i=0; $i<$length; $i++) {
            $password .= rand(0, 9);
        }
        return $password;
    }

    /**
     * @return string Unique module state identifier
     */
    public function identifier()
    {
        return str_replace(array('\\','/'), '_', __NAMESPACE__.'/'.$this->id.'_auth_'.url()->base());
    }

    /**
     * Finish authorization process and return asynchronous response
     * @param dbRecord $user Pointer to filled user object
     *
     * @return array Asynchronous response array
     */
    public function authorize(dbRecord & $user)
    {
        // Store pointer to authorized user
        $this->user = & $user;

        $this->authorized = true;

        // Save user in session
        $_SESSION[$this->identifier()] = serialize($this->user);

        $this->active = & $this;

        $this->update($this->user);

        // Return authorization status
        return $this->authorized;
    }

    /** Initiate deauthorization process */
    public function deauthorize()
    {
        // Tell all ancestors that we are out
        foreach (self::$ancestors as & $ancestor) {
            $ancestor->authorized = false;
            unset($ancestor->user);
            unset($_SESSION[$ancestor->identifier()]);
            setcookie('_cookie_accessToken');
        }
    }

    /** Обработчик сериализации объекта */
    public function __sleep()
    {
        // Remove all unnecessary fields from serialization
        return array_diff(parent::__sleep(), array( 'authorized', 'user' ));
    }
}
