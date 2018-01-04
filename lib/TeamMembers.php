<?php
    class TeamMembers implements Iterator {
        private $position = 0;

        public $TeamID = 0;

        public $TeamMembers = [];

        public function __construct ($id) {
            global $_ACCOUNT;
            $this->TeamID = $id;
            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("SELECT CAPID, Role FROM ".DB_TABLES['TeamMembers']." WHERE TeamID = :tid");
            $stmt->bindValue(":tid", $id);
            $this->TeamMembers = DBUtils::ExecutePDOStatement($stmt);
        }

        public function has (\Member $member) {
            $ret = false;
            foreach ($this->TeamMembers as $mem) {
                $ret = $ret || ((int)$mem['CAPID'] == $member->uname);
                if ($ret) return true;
            }
            return false;
        }

        public function add (\Member $member, $role = '') {
            global $_ACCOUNT;
            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['TeamMembers']." VALUES (:tid, :cid, :role, :aid)");
            $stmt->bindValue(":tid", $this->TeamID);
            $stmt->bindValue(":cid", $member->uname);
            $stmt->bindValue(':role', $role);
            $stmt->bindValue(':aid', $_ACCOUNT->id);
            $this->TeamMembers[] = [
                'CAPID' => $member->uname,
                'Role' => $role
            ];
            if (!$stmt->execute()) {
                if ($stmt->errorInfo()[1] == 1062) {
                    return false;
                }
                trigger_error($stmt->errorInfo()[2], 512);
            }
            return true;
        }

        public function remove (\Member $member) {
            global $_ACCOUNT;
            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['TeamMembers']." WHERE CAPID = :cid AND TeamID = :tid AND AccountID = :aid");
            $stmt->bindValue(':cid', $member->uname);
            $stmt->bindValue(':tid', $this->TeamID);
            $stmt->bindValue(':aid', $_ACCOUNT->id);
            for ($i = 0; $i < count($this->TeamMembers); $i++) {
                if ($this->TeamMembers[$i]['CAPID'] == $member->uname) {
                    array_splice($this->TeamMembers, $i, 1);
                    break;
                }
            }
            if (!$stmt->execute()) {
                trigger_error($stmt->errorInfo()[2], 512);
            }
            return true;
        }

        public function modify (\Member $member, $role = Null) {
            global $_ACCOUNT;
            for ($i = 0; $i < count($this->TeamMembers); $i++) {
                if ($this->TeamMembers[$i]['CAPID'] == $member->capid) {
                    break;
                }
            }
            $row = $this->TeamMembers[$i];
            if (isset($role)) $row['Role'] = $role;

            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("UPDATE ".DB_TABLES['TeamMembers']." SET
                Role = :role
            WHERE TeamID = :tid AND CAPID = :cid AND AccountID = :aid;");
            $stmt->bindValue(':role', $row['Role']);
            $stmt->bindValue(':tid', $this->TeamID);
            $stmt->bindValue(':cid', $member->uname);
            $stmt->bindValue(':aid', $_ACCUONT->id);
            if (!$stmt->execute()) {
                trigger_error($stmt->errorInfo()[2], 512);
            }
            return true;
        }

        public function set ($data) {
            global $_ACCOUNT;
            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['TeamMembers']." WHERE TeamID = :tid AND AccountID = :aid;");
            $stmt->bindValue(':tid', $this->TeamID);
            $stmt->bindValue(':aid', $_ACCOUNT->id);
            if (!$stmt->execute()) {
                trigger_error($stmt->errorInfo()[2], 512);
            }
            foreach ($data as $datum) {
                $this->add(Member::Estimate($datum['CAPID']), $datum['role']);
            }
            return true;
        }

        /**
         * Implementations of Iterator
         */

        public function rewind () {
            $this->position = 0;
        }

        public function current () {
            return $this->TeamMembers[$this->position]['Role'];
        }

        public function key () {
            return (int)$this->TeamMembers[$this->position]['CAPID'];
        }

        public function next () {
            $this->position++;
        }

        public function valid () {
            return isset($this->TeamMembers[$this->position]);
        }
    }