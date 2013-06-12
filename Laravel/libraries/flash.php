<?php namespace Local;

/**
 * Custom library that allows management of system messages
 * for things like errors, form feedback, and general overall
 * information. Generally the system messages are output in
 * the header view partial that is rendered with every page.
 * For output the flash messages utilize Twitter Bootstrap.
 *
 * @author David Varney
 * @example Display And Flush All Messages In The Session: Local\Flash::display_flashes(true);
 * @example Add A Success Message: Local\Flash::set_success('This is a success message');
 * @example Flush All Flashes: Local\Flash::flush_flashes();
 */

class Flash {

    /**
     * Set the Notice Flash
     *
     * @author David Varney
     */
    static public function set_notice($new_flash = null){
        $notices = \Session::get('notices');
        if(null == $notices){
            $notices = array();
        }
        if(null != $new_flash){
            $notices[] = $new_flash;
            \Session::put('notices', $notices);
        }
    }

    /**
     * Set the Info Flash
     *
     * @author David Varney
     */
    static public function set_info($new_flash = null){
        $infos = \Session::get('infos');
        if(null == $infos){
            $infos = array();
        }

        if(null != $new_flash){
            $infos[] = $new_flash;
            \Session::put('infos', $infos);
        }
    }

    /**
     * Set the Error Flash
     *
     * @author David Varney
     */
    static public function set_error($new_flash = null){
        $errors = \Session::get('errors');
        if(null == $errors){
            $errors = array();
        }

        if(null != $new_flash){
            $errors[] = $new_flash;
            \Session::put('errors', $errors);
        }
    }

    /**
     * Set the Success Flash
     *
     * @author David Varney
     */
    static public function set_success($new_flash = null){
        $successes = \Session::get('successes');
        if(null == $successes){
            $successes = array();
        }

        if(null != $new_flash){
            $successes[] = $new_flash;
            \Session::put('successes', $successes);
        }
    }

    /**
     * Get all the Notice Flashes
     *
     * @author David Varney
     */
    static public function get_notices(){
        return \Session::get('notices');
    }

    /**
     * Get all the Error Flashes
     *
     * @author David Varney
     */
    static public function get_errors(){
        return \Session::get('errors');
    }

    /**
     * Get all the Info Flashes
     *
     * @author David Varney
     */
    static public function get_infos(){
        return \Session::get('infos');
    }

    /**
     * Get all the Success Flashes
     *
     * @author David Varney
     */
    static public function get_successes(){
        return \Session::get('successes');
    }

    /**
     * Flush all the Notice Flashes
     *
     * @author David Varney
     */
    static public function flush_notices(){
        \Session::forget('notices');
    }

    /**
     * Flush all the Info Flashes
     *
     * @author David Varney
     */
    static public function flush_infos(){
        \Session::forget('infos');
    }

    /**
     * Flush all the Error Flashes
     *
     * @author David Varney
     */
    static public function flush_errors(){
        \Session::forget('errors');
    }

    /**
     * Flush all the Success Flashes
     *
     * @author David Varney
     */
    static public function flush_successes(){
        \Session::forget('successes');
    }

    /**
     * Retrieves all of the flashes that are stored in the Session
     *
     * @author David Varney
     * @param boolean $flush A boolean parameter for flushing all of the flashes
     * @return array $flashes
     */
    static public function get_flashes($flush = false){
        $flashes['notices'] = self::get_notices();
        $flashes['errors'] = self::get_errors();
        $flashes['infos'] = self::get_infos();
        $flashes['successes'] = self::get_successes();

        if($flush){
            self::flush_flashes();
        }

        return $flashes;
    }

    /**
     * Removes all of the flashes from the Session
     *
     * @author David Varney
     * @return void
     */
    static public function flush_flashes(){
        self::flush_notices();
        self::flush_infos();
        self::flush_errors();
        self::flush_successes();
    }

    /**
     * Retrieves all the flashes from the Session and outputs
     * them utilizing Twitter Boostrap for formatting.
     *
     * @author David Varrney
     * @param boolean $flush A boolean parameter for flushing all of the flashes
     * @return str $output
     */
    static public function display_flashes($flush = false){
        $flashes = self::get_flashes($flush);

        if(null != $flashes){
            $output = '';
            foreach($flashes as $type=>$flash_array){
                // First lets set some variables for each of the different types of Flashes
                switch($type){
                    case 'errors':
                        if(null != $flash_array){
                            $class = 'alert-error';
                            $label = 'Error:';
                        }
                        break;
                    case 'infos':
                        if(null != $flash_array){
                            $class = 'alert-info';
                            $label = 'Info:';
                        }
                        break;
                    case 'successes':
                        if(null != $flash_array){
                            $class = 'alert-success';
                            $label = 'Success:';
                        }
                        break;
                    case 'notices':
                        if(null != $flash_array){
                            $class = '';
                            $label = 'Notice:';
                        }
                        break;
                }

                // Lets add the HTML for each of the flashes to $output
                if(null != $flash_array){
                    foreach($flash_array as $flash){
                        $output .=  '<div class="alert alert-block '. $class .'">';
                        $output .=  '<button type="button" class="close" data-dismiss="alert">&times;</button>';
                        $output .=  '<h4>'. $label .'</h4> '. $flash;
                        $output .=  '</div>';
                    }
                }

            }
            return $output;
        }else{
            return false;
        }
    }
}