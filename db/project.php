<?php

namespace OCA\Owncollab\Db;


class Project
{
    /** @var Connect $connect object instance working with database */
    private $connect;

    /** @var string $tableName table name in database */
    private $tableName;

    /** @var string $fields table fields name in database */
    private $fields = [
        'open',
        'name',
        'create_uid',
        'show_today_line',
        'show_task_name',
        'show_user_color',
        'critical_path',
        'scale_type',
        'scale_fit',
        'is_share',
        'share_link',
        'share_is_protected',
        'share_password',
        'share_email_recipient',
        'share_is_expire',
        'share_expire_time',
    ];

    /**
     * Project constructor.
     * @param $connect
     * @param $tableName
     */
    public function __construct($connect, $tableName) {
        $this->connect = $connect;
        $this->tableName = '*PREFIX*' . $tableName;
    }

    /**
     * Retrieve all date of project settings
     *
     * @return array|null
     */
    public function get(){
        $sql = "SELECT *, DATE_FORMAT( `share_expire_time`, '%d-%m-%Y %H:%i:%s') as share_expire_time
                FROM `{$this->tableName}`";
        return $this->connect->query($sql);
    }


    /**
     * Retrieve all registered resource
     *
     * @return array|null
     */
    public function getGroupsUsersList(){

        $records = $this->getGroupsUsers();
        $result = [];

        // Operation iterate and classify users into groups
        foreach($records as $record) {
            $result[$record['gid']][] = [
                'gid'=> $record['gid'],
                'uid'=> $record['uid'],
                'displayname'=> ($record['displayname'])?$record['displayname']:$record['uid']
            ];
        }
        return $result;
    }


    /**
     * Retrieve all records from Users
     *
     * @return mixed
     */
    public function getGroupsUsers() {

        $sql = "SELECT gu.uid, gu.gid, u.displayname
                FROM *PREFIX*group_user gu
                LEFT JOIN *PREFIX*users u ON (u.uid = gu.uid)";

        return $this->connect->queryAll($sql);
    }

    /**
     * Update single field in the project
     *
     * @param $field
     * @param $value
     * @return bool|int
     */
    public function updateField($field, $value) {
        $result = false;
        if(in_array($field, $this->fields)) {
            $sql = "UPDATE {$this->tableName}
                    SET {$field} = :field
                    WHERE open = 1";
            $result = $this->connect->db->executeUpdate($sql, [
                ':field'=>$value
            ]);
        }
        return $result;
    }

    /**
     * Update share fields in the project
     *
     * @param $field
     * @param $value
     * @param $share_link
     * @return bool|int
     */
    public function updateShared($field, $value, $share_link) {
        $result = false;
        if(in_array($field, $this->fields)) {

            $clean = ", `share_is_protected` = 0, `share_password` = NULL, `share_email_recipient` = NULL, `share_is_expire` = 0, `share_expire_time` = NULL";

            $sql = "UPDATE `{$this->tableName}`
                SET `is_share` = :is_share, `share_link` = :share_link " . ($value===0?$clean:'') . "
                WHERE `open` = 1";

            $result = $this->connect->db->executeUpdate($sql,[
                ':is_share'=> $value,
                ':share_link'=> $share_link
            ]);

        }
        return $result;
    }

    /**
     * Get for public
     * @param $share_link
     * @return array|bool|null
     */
    public function getShare($share_link = false) {
        $result = false;
        $project = $this->get();
        if($share_link == false){
            return $project['share_link'];
        }else{
            if($project && $project['is_share'] == 1 && $project['share_link'] == $share_link){
                $result = $project;
            }
            return $result;
        }
    }

    /**
     * @param $uid
     * @return mixed
     */
    public function getUserEmail($uid) {
        $sql = "SELECT * FROM oc_preferences
                WHERE configkey = 'email'
                AND appid = 'settings'
                AND userid = :uid";
        return $this->connect->query($sql, [':uid'=>$uid]);
    }



}