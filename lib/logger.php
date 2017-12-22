<?php
    /**
     * @package lib/logger
     *
     * A logger, useful for loggin'
     *
     * Depending on the DEBUG_FILE_COUNT setting set in the main config file, each logger will have various outputs
     *  For 0:
     *      Filenames include LoggerName-[0-DEBUG_DEBUG_LEVEL].DEBUG
     *                        LoggerName-[0-DEBUG_LOG_LEVEL].LOG
     *                        LoggerName-[0-9].['WARN', 'ERROR']
     *  For 1:
     *      Filenames include LoggerName.['DEBUG', 'LOG', 'WARN', 'ERROR']
     *
     *  For 2:
     *      Everything is logged to LoggerName.LOG
     *
     *  For 3:
     *      Everything is logged to main.LOG
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
     */
    class Logger {
        /**
         * A logger, useful for loggin'
         *
         * @param str $name The name of the logger
         *
         * @return Logger
         */
        public function __construct ($name) {
            $this->name = $name;
        }
        /**
         * A function for formatting what is saved to the logs
         *
         * @param str $data The data to log
         * @param int $level The log level, 1-9
         * @param str $fileextension File extension, useful for the lower DEBUG_FILE_COUNTS where one is lacking on log space and must put LOG, WARN, etc somewhere
         *
         * @return str Formatted string
         */
        private function FormatOut ($data, $level, $fe) {
            global $_LOGLINE;
            switch (DEBUG_FILE_COUNT) {
                case 0 :
                case 1 :
                    return sprintf(DEBUG_FORMAT, $_LOGLINE, Util_Collection::GetISOTime(), $level, $data);
                break;

                case 2 :
                    return sprintf('{%s}' . DEBUG_FORMAT, $fe, $_LOGLINE, Util_Collection::GetISOTime(), $level, $data);
                break;

                case 3 :
                    return sprintf('[%s] {%s}' . DEBUG_FORMAT, $this->name, $fe, $_LOGLINE, Util_Collection::GetISOTime(), $level, $data);
                break;
            }

        }
        /**
         * The function that actually logs the data, used to keep the code DRY
         *
         * @param mixed $data Anything to log
         * @param str $fextension File extension
         * @param int $level The log level
         * @param str $prepend Data to prepend, as you can't prepend otherwise
         */
        private function LogData ($data, $fe, $level, $prepend=Null) {
            global $_LOGLINE;
            $_LOGLINE++;
            $prepend = isset($prepend) ? $prepend . ": " : '';
            $d = $this->FormatOut($prepend.rtrim(ltrim(rtrim(var_export($data, true)), "'"), "'".PHP_EOL), $level, $fe);
            switch (DEBUG_FILE_COUNT) {
                case 0 :
                    $filename = BASE_DIR . "logs/$this->name-$level.$fe";
                break;

                case 1 :
                    $filename = BASE_DIR . "logs/$this->name.$fe";
                break;

                case 2 :
                    $filename = BASE_DIR . "logs/$this->name.LOG";
                break;

                case 3 :
                    $filename = BASE_DIR . "logs/main.LOG";
                break;
            }
            if (!is_dir(BASE_DIR . "logs")) {
                mkdir (BASE_DIR . "logs", 0644, false);
            }
            file_put_contents ($filename, $d . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        /**
         * Clears the files this logger would write to when it logs data
         */
        public function ClearLogs () {
            switch (DEBUG_FILE_COUNT) {
                case 0 :
                    $names = ["LOG", "DEBUG", "WARN", "ERROR"];
                    foreach ($names as $fe) {
                        for ($i = 0; $i <= 9; $i++) {
                            if (file_exists(BASE_DIR . "logs/" . $this->name.'-'.$i.'.'.$fe)) {
                                file_put_contents(BASE_DIR . "logs/" . $this->name.'-'.$i.'.'.$fe, "");
                            }
                        }
                    }
                break;

                case 1 :
                    $names = ["LOG", "DEBUG", "WARN", "ERROR"];
                    foreach ($names as $fe) {
                        if (file_exists (BASE_DIR . "logs/" . $this->name . $fe)) {
                            file_put_contents(BASE_DIR . "logs/" . $this->name . $fe);
                        }
                    }
                break;

                case 2 :
                    if (file_exists (BASE_DIR . "logs/" . $this->name . 'LOG')) {
                        file_put_contents (BASE_DIR . "logs/" . $this->name . 'LOG');
                    }
                break;

                case 3 :

                break;
            }
        }

        /**
         * Logs data at a log error level
         *
         * If the log level provided is less than the DEBUG_LOG_LEVEL constant, it is not logged
         *
         * @param mixed $data Data to log
         * @param int $level Log level to log at
         * @param str|null $prepend A string to prepend to the $data before it is logged, useful for non-string data types
         */
        public function Log ($data, $level, $prepend=Null) {
            if ($level <= DEBUG_LOG_LEVEL) {
                $this->LogData($data, "LOG", $level, $prepend);
            }
        }

        /**
         * Logs data at a debug error level
         *
         * If the log level provided is less than the DEBUG_DEBUG_LEVEL constant, it is not logged
         *
         * @param mixed $data Data to log
         * @param int $level Log level to log at
         * @param str|null $prepend A string to prepend to the $data before it is logged, useful for non-string data types
         */
        public function Debug ($data, $level, $prepend=Null) {
            if ($level <= DEBUG_DEBUG_LEVEL) {
                $this->LogData($data, "DEBUG", $level, $prepend);
            }
        }

        /**
         * Logs data at a warn error level
         *
         * @param mixed $data Data to log
         * @param int $level Log level to log at
         * @param str|null $prepend A string to prepend to the $data before it is logged, useful for non-string data types
         */
        public function Warn ($data, $level, $prepend=Null) {
            $this->LogData($data, "WARN", $level, $prepend);
        }

        /**
         * Logs data at an error error level
         *
         * @param mixed $data Data to log
         * @param int $level Log level to log at
         * @param str|null $prepend A string to prepend to the $data before it is logged, useful for non-string data types
         */
        public function Error ($data, $level, $prepend=Null) {
            $this->LogData($data, "ERROR", $level, $prepend);
        }
    }