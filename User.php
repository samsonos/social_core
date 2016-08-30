<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 02.03.14 at 13:19
 */

namespace samson\social;

/**
 * Generic class for storing user data in local format
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2013 SamsonOS
 * @version 
 */
class User implements \samsonframework\core\RenderInterface
{
    /** User given name */
    public $name = '';

    /** User second name */
    public $surname = '';

    /** User social system identifier */
    public $socialID = '';

    /** User email address */
    public $email = '';

    /** User date of birth */
    public $birthday = '';

    /** User gender */
    public $gender = '';

    /** User native language/locale */
    public $locale = '';

    /** User small photo */
    public $photo;

    /** User additional data */
    public $other;

    /** Transform object for view rendering */
    public function toView($prefix = null, array $restricted = array())
    {
        return array(
            $prefix.'name' => $this->name,
            $prefix.'surname' => $this->surname,
            $prefix.'socialID' => $this->socialID,
            $prefix.'email' => $this->email,
            $prefix.'birthday' => $this->birthday,
            $prefix.'gender' => $this->gender,
            $prefix.'locale' => $this->locale,
            $prefix.'photo' => $this->photo,
            $prefix.'other' => $this->other
        );
    }

    /**
     * Generic user constructor
     * @param object|array $userData Entity recieved from social system
     */
    public function __construct($userData = null)
    {

    }
}
 
