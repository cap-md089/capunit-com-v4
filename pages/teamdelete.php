<?php
    class Output {
        public static function doPut ($e, $c, $l, $m, $a) {
			if (!$a->paid) {return ['error' => 501];}
            $team = Team::Get($e['raw']['data']);
            if ($l && ($m->hasPermission('EditTeam') || $team->isLeader($m))) {
                $pdo = DBUtils::CreateConnection();
                $stmt = $pdo->prepare("DELETE FROM TeamMembers WHERE TeamID = :tid AND AccountID = :aid;");
                $stmt->bindValue(':tid', $team->ID);
                $stmt->bindValue(':aid', $a->id);
                if (!$stmt->execute()) {
                    trigger_error($stmt->errorInfo()[2], 512);
                }
                $stmt = $pdo->prepare("DELETE FROM Team WHERE TeamID = :tid AND AccountID = :aid;");
                $stmt->bindValue(':tid', $team->ID);
                $stmt->bindValue(':aid', $a->id);
                if (!$stmt->execute()) {
                    trigger_error($stmt->errorInfo()[2], 512);
                }
                return "Team deleted!";
            } else {
                return false;
            }
        }
    }