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
    /** Module identifier */
    public $id = 'social';

    /** Database table name for interaction */
    public $dbTable;

    /* Database user email field */
    public $dbEmailField = 'email';
   
    /** External callable for handling social authorization */
    public $handler;

    /** Module preparation */
    public function prepare()
    {
        // Create and check general database table fields configuration        
        db()->createField($this, $this->dbTable, 'dbEmailField', 'VARCHAR(64)');        

        return parent::prepare();
    }

    public function authorize()
    {

    }

    public function authentication()
    {

    }

    /** Universal controller */
    public function __HANDLER()
    {
        $result = array('status' => '0');

        // Collection of required fields
        $required = array('nick' => '', 'email' => '', 'password' => '');

        // If we received post data
        if (isset($_POST)) {

            // Convert all keys to lowercase
            $_POST = array_change_key_case_unicode($_POST);
            $required = array_change_key_case_unicode($required);

            // Get keys difference with required array
            $difference = array_diff(array_keys($required), array_keys($_POST));
            if (sizeof($difference) === 0) {

                /**@var \playtop\user $user */
                $user = null;
                // Try to find user with this email
                if(dbQuery('\playtop\user')->email($_POST['email'])->first($user)) {
                    $result['error'] = 'Email is busy';

                } else {

                    // Create new database record object without saving to database
                    $user = new \playtop\user(false);

                    // Convert object variables to lower case and iterate them
                    foreach (array_change_key_case_unicode(get_object_vars($user)) as $field => $oldValue) {
                        // If we have received field - fill it with simple filtering
                        if (isset($_POST[$field])) {
                            $user->$field = filter_var($_POST[$field]);
                        }
                    }

                    // Create new database record
                    $user->save();

                    // Pass user object to frontend
                    $result['user'] = $user;

                    // All required field are passed
                    $result['status'] = '1';
                }

            } else { // Not all required fields has been posted
                $result['error'] = 'Not all fields passed from form';
                $result['not_filled'] = $difference;
            }
        }

        return $result;
    }

}
 