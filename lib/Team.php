<?php
    require_once (BASE_DIR."lib/Member.php");
    require_once (BASE_DIR."lib/DB_Utils.php");
    require_once (BASE_DIR."lib/Account.php");
    require_once (BASE_DIR."lib/TeamMembers.php");

    class Team {
        public $Name = '';

        public $Description = '';
        
        public $ID = 0;

        public $Coach,
               $Lead,
               $Mentor;

        public $Members;
    
        public static function Create ($info) {
            global $_ACCOUNT;

            $pdo = DBUtils::CreateConnection();

            if (!isset($info['TeamMentor'])) $info['TeamMentor'] = 0;
            if (!isset($info['TeamLead'])) $info['TeamLead'] = 0;
            if (!isset($info['TeamCoach'])) $info['TeamCoach'] = 0;

            $stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['Team']." (
                TeamName, TeamDescription, TeamLead, TeamCoach, TeamMentor, AccountID
            ) VALUES (
                :name, :desc, :lead, :coach, :mentor, :aid
            )");
               
            $stmt->bindValue(":lead", (int)$info['TeamLead']);
            $stmt->bindValue(':name', $info['TeamName']);
            $stmt->bindValue(':desc', $info['TeamDescription']);
            $stmt->bindValue(':coach', (int)$info['TeamCoach']);
            $stmt->bindValue(':mentor', (int)$info['TeamMentor']);
            $stmt->bindValue(':aid', $_ACCOUNT->id);
            if (!$stmt->execute()) {
                trigger_error($stmt->errorInfo()[2], 512);
            }

            $info['TeamID'] = $pdo->lastInsertId();

            return new self ($info);
        }

        public static function Get ($id) {
            global $_ACCOUNT;
            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['Team']." WHERE TeamID = :tid AND AccountID = :aid");
            $stmt->bindValue(':tid', $id);
            $stmt->bindValue(':aid', $_ACCOUNT->id);
            $data = DBUtils::ExecutePDOStatement($stmt);
            if (count($data) != 1) {
                return false;
            }
            $data[0]['TeamID'] = $id;
            return new self ($data[0]);
        }

        private function __construct ($data) {
            foreach ($data as $k => $v) {
                $k = substr($k, 4);
                $this->$k = $v;
            }
            $this->Members = new TeamMembers($this->ID);
        }

        public function isLeader (\Member $m) {
            return $m->hasPermission('Developer') ? true : in_array($m->uname, [$this->Coach, $this->Mentor, $this->Lead]);
        }

        public function set ($info) {
            global $_ACCOUNT;
            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("UPDATE ".DB_TABLES['Team']." SET 
                TeamName=:name, TeamDescription=:desc, TeamLead=:lead, TeamCoach=:coach, TeamMentor=:mentor
            WHERE TeamID = :id AND AccountID = :aid;");

            
            $stmt->bindValue(":lead", $info['TeamLead']);
            $stmt->bindValue(':name', $info['TeamName']);
            $stmt->bindValue(':desc', $info['TeamDescription']);
            $stmt->bindValue(':coach', $info['TeamCoach']);
            $stmt->bindValue(':mentor', $info['TeamMentor']);
            $stmt->bindValue(':aid', $_ACCOUNT->id);
            $stmt->bindValue(':id', $this->ID);
            if (!$stmt->execute()) {
                trigger_error($stmt->errorInfo()[2], 512);
            }
            foreach ($info as $k => $v) {
                $k = substr($k, 4);
                $this->$k = $v;
            }
        }

        public function setMembers ($data) {
            $this->Members->set($data);
        }

        public function __toString () {
            $str = $this->Name."\n\n";
            $str .= $this->Description."\n\n";
            if ($this->Coach !== 0) {
                $coach = Member::Estimate($this->Coach);
                if ($coach) {
                    $str .= "Team Coach: ".$coach->RankName."\n";
                }
            }
            if ($this->Mentor !== 0) {
                $mentor = Member::Estimate($this->Mentor);
                if ($mentor) {
                    $str .= "Team Mentor: ".$mentor->RankName."\n";
                }
            }
            $str .= "Team Leader: ".(Member::Estimate($this->Lead)->RankName)."\n";
            foreach ($this->Members as $mem => $role) {
                $mem = Member::Estimate($mem);
                $str .= "$role: {$mem->RankName}\n";
            }
            return $str;
        }
    }