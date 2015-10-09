<?php

namespace Addons\BaiduRankTool;
use Common\Controller\Addon;
use Think\Db;

/**
 * 百度关键词排名查询插件
 * @author slpi1
 */

class BaiduRankToolAddon extends Addon{

    public $info = array(
        'name'=>'BaiduRankTool',
        'title'=>'百度关键词排名查询',
        'description'=>'百度关键词排名查询工具',
        'status'=>1,
        'author'=>'slpi1',
        'version'=>'0.1'
    );

    public $admin_list = array(
        'model'     => 'site_keyword',             //要查的表
        'fields'    => '*',                 //要查的字段
        'map'       => '',                  //查询条件, 如果需要可以再插件类的构造方法里动态重置这个属性
        'order'     => 'sort asc, id desc', //排序,
        'list_grid' => array(               //这里定义的是除了id序号外的表格里字段显示的表头名和模型一样支持函数和链接
            'keyword:关键词:[EDIT]',
            'site_url:域名',
            'rank:今日排名',
            'status:状态',
            'id:操作:Addons/execute/_addons/BaiduRankTool/_controller/BaiduRankTool/_action/search/_id/[id]|查询,Addons/execute/_addons/BaiduRankTool/_controller/BaiduRankTool/_action/history/_id/[id]|历史排名'
        ),
    );

    public $custom_adminlist ="admin.html";

    public function install(){
        $db_config = array();
        $db_config['DB_TYPE'] = C('DB_TYPE');
        $db_config['DB_HOST'] = C('DB_HOST');
        $db_config['DB_NAME'] = C('DB_NAME');
        $db_config['DB_USER'] = C('DB_USER');
        $db_config['DB_PWD'] = C('DB_PWD');
        $db_config['DB_PORT'] = C('DB_PORT');
        $db_config['DB_PREFIX'] = C('DB_PREFIX');
        $db = Db::getInstance($db_config);
        //读取插件sql文件
        $sqldata = file_get_contents('http://'.$_SERVER['HTTP_HOST'].__ROOT__.'/Addons/'.$this->info['name'].'/install.sql');
        $sqlFormat = $this->sql_split($sqldata, $db_config['DB_PREFIX']);

        $counts = count($sqlFormat); 
        for ($i = 0; $i < $counts; $i++) {
            $sql = trim($sqlFormat[$i]);
            if (strstr($sql, 'CREATE TABLE')) {
                preg_match('/CREATE TABLE `([^ ]*)`/', $sql, $matches);
                mysql_query("DROP TABLE IF EXISTS `$matches[1]");
                $db->execute($sql);
            }
        }
        return true;
    }

    public function uninstall(){
        $db_config = array();
        $db_config['DB_TYPE'] = C('DB_TYPE');
        $db_config['DB_HOST'] = C('DB_HOST');
        $db_config['DB_NAME'] = C('DB_NAME');
        $db_config['DB_USER'] = C('DB_USER');
        $db_config['DB_PWD'] = C('DB_PWD');
        $db_config['DB_PORT'] = C('DB_PORT');
        $db_config['DB_PREFIX'] = C('DB_PREFIX');
        $db = Db::getInstance($db_config);
        //读取插件sql文件
        $sqldata = file_get_contents('http://'.$_SERVER['HTTP_HOST'].__ROOT__.'/Addons/'.$this->info['name'].'/uninstall.sql');
        $sqlFormat = $this->sql_split($sqldata, $db_config['DB_PREFIX']);
        $counts = count($sqlFormat);

        for ($i = 0; $i < $counts; $i++) {
                $sql = trim($sqlFormat[$i]);
                $db->execute($sql);//执行语句
        }
        return true;
    }

    /**
    * 解析数据库语句函数
    * @param string $sql  sql语句   带默认前缀的
    * @param string $tablepre  自己的前缀
    * @return multitype:string 返回最终需要的sql语句
    */
    public function sql_split($sql, $tablepre) {

           if ($tablepre != "onethink_")
                   $sql = str_replace("onethink_", $tablepre, $sql);
           $sql = preg_replace("/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=utf8", $sql);

           if ($r_tablepre != $s_tablepre)
                   $sql = str_replace($s_tablepre, $r_tablepre, $sql);
           $sql = str_replace("\r", "\n", $sql);
           $ret = array();
           $num = 0;
           $queriesarray = explode(";\n", trim($sql));
           unset($sql);
           foreach ($queriesarray as $query) {
                   $ret[$num] = '';
                   $queries = explode("\n", trim($query));
                   $queries = array_filter($queries);
                   foreach ($queries as $query) {
                           $str1 = substr($query, 0, 1);
                           if ($str1 != '#' && $str1 != '-')
                                   $ret[$num] .= $query;
                   }
                   $num++;
           }
           return $ret;
    }


}