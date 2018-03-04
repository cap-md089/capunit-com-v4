<?php
    /**
     * @package lib/DB_Utils
     *
     * A collection of static methods that help out with databases
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
     */
    class DB_Utils {
        /**
         * @var PDO $PDO A PDO connection to be returned so a script isn't creating multiple connections
         */
        private static $PDO = null;

        /**
         * This function returns a PDO object based on constants defined in config.php
         *
         * @return PDO
         */
        public static function CreateConnection () {
            if (!isset(self::$PDO)) {
               try {
                    self::$PDO = new PDO (DB_PROTOCOL . ":host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_UNAME, DB_UPASS, array (
                        PDO::ATTR_PERSISTENT => false
                    ));
               } catch (PDOException $e) {
                   //need to provide error message to user/web server log here.  
                   // !!! First troubleshooting step: check database address in config.php !!!
               }
            }
            return self::$PDO;
        }

        /**
         * Executes a PDO statement and returns the data, wrapped in a try/catch statement
         *
         * @param PDOStatement A statement to execute and fetch data from
         *
         * @return array[] An array of associative arrays containing table data
         */
        public static function ExecutePDOStatement ($stmt, $loud=true) {
            $retdata = array ();
            try {
                if ($stmt->execute()) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $retdata[] = $row;
                    }
                } else if ($loud) {
                    require_once(BASE_DIR."lib/Error.php");
                    ErrorMSG::Log($stmt->errorInfo()[2], $stmt->queryString);
                }
            } catch (PDOException $e) {
                require_once(BASE_DIR."lib/Error.php");
                ErrorMSG::Log($stmt->errorInfo()[2], $stmt->queryString);
            }
            return $retdata;
        }
    }

    class DBUtils extends DB_Utils {}