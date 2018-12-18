<?php
class ACL {

    var $perms = array();  //Array : Stores the permissions for the user
    var $userID = 0;   //Integer : Stores the ID of the current user
    var $userRoles = array(); //Array : Stores the roles of the current user
    var $con;
    
    
    function __construct($userID = '') {
        
        $myDatabase = new Database();
        
        $conn = $myDatabase->getConexion();
        
        $this->con = $conn;
        echo "<br><br><br><br>";
        echo "<br><br><br><br>";
        echo "<br><br><br><br>";
        echo "<br><br><br><br>";
        
        if ($userID != '') {
            $this->userID = floatval($userID);
        } else {
            $this->userID = floatval($_SESSION['userID']);
        }
        $this->userRoles = $this->getUserRoles('ids');
        $this->buildACL();
    }
    

    function buildACL() {
        //first, get the rules for the user's role
        if (count($this->userRoles) > 0) {
            $this->perms = array_merge($this->perms, $this->getRolePerms($this->userRoles));
        }
        //then, get the individual user permissions
        $this->perms = array_merge($this->perms, $this->getUserPerms($this->userID));
    }

    function getPermKeyFromID($permID) {
        $strSQL = "SELECT `permKey` FROM `permissions` WHERE `ID` = " . floatval($permID) . " LIMIT 1";
        $data = mysql_query($strSQL);
        $row = mysql_fetch_array($data);
        return $row[0];
    }

    function getPermNameFromID($permID) {
        $strSQL = "SELECT `permName` FROM `permissions` WHERE `ID` = " . floatval($permID) . " LIMIT 1";
        $data = mysql_query($strSQL);
        $row = mysql_fetch_array($data);
        return $row[0];
    }

    function getRoleNameFromID($roleID) {
        $strSQL = "SELECT `roleName` FROM `roles` WHERE `ID` = " . floatval($roleID) . " LIMIT 1";
        $data = mysql_query($strSQL);
        $row = mysql_fetch_array($data);
        return $row[0];
    }

    function getUserRoles() {

        $strSQL = "SELECT * FROM `user_roles` WHERE `userID` = " . floatval($this->userID) . " ORDER BY `addDate` ASC";
        $stmt = $this->con->prepare($strSQL);
        $stmt->execute();
        $resp = array();
        while ($row = $stmt->fetch()) {
            $resp[] = $row['roleID'];
        }
        return $resp;
    }

    function getAllRoles($format = 'ids') {
        $format = strtolower($format);
        $strSQL = "SELECT * FROM `roles` ORDER BY `roleName` ASC";
        $data = mysql_query($strSQL);
        $resp = array();
        while ($row = mysql_fetch_array($data)) {
            if ($format == 'full') {
                $resp[] = array("ID" => $row['ID'], "Name" => $row['roleName']);
            } else {
                $resp[] = $row['ID'];
            }
        }
        return $resp;
    }

    function getAllPerms($format = 'ids') {
        $format = strtolower($format);
        $strSQL = "SELECT * FROM `permissions` ORDER BY `permName` ASC";

        $stmt = $this->con->prepare($strSQL);
        $stmt->execute();
        $resp = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($format == 'full') {
                $resp[$row['permKey']] = array('ID' => $row['ID'], 'Name' => $row['permName'], 'Key' => $row['permKey']);
            } else {
                $resp[] = $row['ID'];
            }
        }
        return $resp;
    }

    function getRolePerms($role) {

        if (is_array($role)) {
            $roleSQL = "SELECT * FROM `role_perms` WHERE `roleID` IN (" . implode(",", $role) . ") ORDER BY `ID` ASC";
        } else {
            $roleSQL = "SELECT * FROM `role_perms` WHERE `roleID` = " . floatval($role) . " ORDER BY `ID` ASC";
        }
        
        $stmt = $this->con->prepare($roleSQL);

        $perms = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pK = strtolower($this->getPermKeyFromID($row['permID']));
            if ($pK == '') {
                continue;
            }
            if ($row['value'] === '1') {
                $hP = true;
            } else {
                $hP = false;
            }
            $perms[$pK] = array('perm' => $pK, 'inheritted' => true, 'value' => $hP, 'Name' => $this->getPermNameFromID($row['permID']), 'ID' => $row['permID']);
        }
        return $perms;
    }

    function getUserPerms($userID) {
        $strSQL = "SELECT * FROM `user_perms` WHERE `userID` = " . floatval($userID) . " ORDER BY `addDate` ASC";
        $stmt = $this->con->prepare($strSQL);

        $perms = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pK = strtolower($this->getPermKeyFromID($row['permID']));
            if ($pK == '') {
                continue;
            }
            if ($row['value'] == '1') {
                $hP = true;
            } else {
                $hP = false;
            }
            $perms[$pK] = array('perm' => $pK, 'inheritted' => false, 'value' => $hP, 'Name' => $this->getPermNameFromID($row['permID']), 'ID' => $row['permID']);
        }
        return $perms;
    }

    function userHasRole($roleID) {
        foreach ($this->userRoles as $k => $v) {
            if (floatval($v) === floatval($roleID)) {
                return true;
            }
        }
        return false;
    }

    function hasPermission($permKey) {
        $permKey = strtolower($permKey);
        if (array_key_exists($permKey, $this->perms)) {
            if ($this->perms[$permKey]['value'] === '1' || $this->perms[$permKey]['value'] === true) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getUsername($userID) {
        if ($userID == "") {
            $userID = 1;
        }
        $strSQL = "SELECT username FROM users WHERE ID = " . floatval($userID) . " LIMIT 1";
        $stmt = $this->con->prepare($strSQL);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row[0];
    }

}
