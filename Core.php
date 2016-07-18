<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 11.04.14 at 15:17
 */
namespace samson\social;

use samsonframework\orm\RecordInterface;
use samson\core\CompressableService;
use samsonphp\event\Event;

 /**
  * Generic class for user authorization.
  * TODO: Avoid $_SESSION using
  * TODO: Integrate possible javascript client authorization logic
  * TODO: Refactor code
  * TODO: Looses db() dependency
  *
  * @author Vitaly Egorov <egorov@samsonos.com>
  * @copyright 2015 SamsonOS
  */
class Core extends CompressableService
{
    /** Initialization end */
    const EVENT_INIT_END = 'social.core.init';

    /** @var self[] Collection of social ancestors */
    protected static $ancestors = array();

    /** General hashing algorithm */
    public $hashAlgorithm = 'sha256';

    /** General hashing algorithm output size */
    public $hashLength = 64;

    /**
     * @deprecated Should be changed to events logic.
     * @var callable External initialization handler
     */
    public $initHandler;

    /** Module identifier */
    public $id = 'social';

    /** Database table name for interaction */
    public $dbTable = \samsoncms\api\generated\User::class;

    /** Database primary field name */
    public $dbPrimaryField = 'UserID';

    /* Database user email field */
    public $dbEmailField = 'email';

    /* Database user password field */
    public $dbPasswordField = 'Password';

    /** Database user token field */
    public $accessToken = 'accessToken';

    /** @var self Pointer to current social module who has authorized */
    public $active;

    /** @var RecordInterface Pointer to current user object */
    protected $user;

    /** Is user authorized */
    protected $authorized = false;

    /**
     * Update authorization status of all social services.
     *
     * @param RecordInterface $user Pointer to authorized user database record
     */
    protected function update(RecordInterface &$user)
    {
        // Load user from session
        $this->user = $user;
        $this->authorized = true;

        // Save to session
        $_SESSION[$this->identifier()] = serialize($this->user);

        // Tell all ancestors that we are in
        foreach (self::$ancestors as & $ancestor) {
            $ancestor->user = &$this->user;
            $ancestor->active = &$this;
            $ancestor->authorized = $this->authorized;
        }
    }

    /** @return RecordInterface Pointer to current authorized user object */
    public function &user()
    {
        return $this->user;
    }

    /** @return bool True if user is authorized */
    public function authorized()
    {
        return $this->authorized;
    }

    /**
     * Hashing function.
     *
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
     * Module initialization.
     *
     * @param array $params Initializarion parameters
     */
    public function init(array $params = array())
    {
        // Store this module as ancestor
        self::$ancestors[$this->id] = &$this;

        // If we are not authorized
        if (!$this->authorized) {
            // TODO: Remove session dependency as storage can be different
            // Search for user in session
            $pointer = &$_SESSION[$this->identifier()];
            if (isset($pointer)) {
                // Load user from session
                $this->user = unserialize($pointer);

                $this->update($this->user);
            }
        }

        // TODO: Should be removed in next majot version
        // If external init handler is set
        if (is_callable($this->initHandler)) {
            // Call external handler and pass reference on this object
            call_user_func_array($this->initHandler, array(&$this));
        }

        // New approach instead of old handler
        Event::fire(self::EVENT_INIT_END, array(&$this));

        return parent::init($params);
    }

    /**
     * Generic random password generator.
     *
     * @param int $length Password length
     * @return string Generated password
     */
    public function generatePassword($length = 8)
    {
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= rand(0, 9);
        }
        return $password;
    }

    /** @return string Unique module state identifier */
    public function identifier()
    {
        // TODO: Remove url() dependency
        return str_replace(array('\\', '/'), '_', __NAMESPACE__ . '/' . $this->id . '_auth_' . url()->base());
    }

    /**
     * Finish authorization process and return asynchronous response.
     *
     * @param RecordInterface $user Pointer to filled user object
     * @return array Asynchronous response array
     */
    public function authorize(RecordInterface & $user)
    {
        // Store pointer to authorized user
        $this->user = &$user;

        $this->authorized = true;

        // TODO: Remove session dependency as storage can be different
        // Save user in session
        $_SESSION[$this->identifier()] = serialize($this->user);

        $this->active = &$this;

        $this->update($this->user);

        // Return authorization status
        return $this->authorized;
    }

    /** Initiate de-authorization process */
    public function deauthorize()
    {
        // Tell all ancestors that we are out
        foreach (self::$ancestors as & $ancestor) {
            $ancestor->authorized = false;
            unset($ancestor->user);
            // TODO: Remove session dependency as storage can be different
            unset($_SESSION[$ancestor->identifier()]);
            setcookie('_cookie_accessToken');
        }
    }

    /** Serialization handler */
    public function __sleep()
    {
        // Remove all unnecessary fields from serialization
        return array_diff(parent::__sleep(), array('authorized', 'user'));
    }
}
