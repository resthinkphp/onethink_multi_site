<?php

namespace Addons\BaiduRankTool\Controller;
use Home\Controller\AddonsController;

class BaiduRankToolController extends AddonsController{


	public function history(){
		$id = I('_id');

		$class = get_addon_class('BaiduRankTool');
        if(!class_exists($class))
            $this->error('插件不存在');
        $addon = new $class();

		$SiteKeyword = M("SiteKeyword");
		$SiteKeywordRank = M("SiteKeywordRank");

		$keywords = $SiteKeyword->where("id = $id")->find();
		if( $keywords == false ){
			$this->error("关键词不存在");
		}
		$this->assign('keywords',$keywords);
		$keyword  = $keywords['keyword'];
		$site_url = $keywords['site_url'];

		$data = array(
			'keyword' => $keyword,
			'site_url' => $site_url
		);

		$list = $SiteKeywordRank->where($data)->select();

		foreach( $list as $k=>$item ){
			$date = $item['datetime'];
			$date = date('Y-m-d',$date);

			$item['datetime'] = $date;

			$list[$k] = $item;
		}

		$this->assign('list',$list);

        $this->assign('execute', $this->fetch($addon->addon_path.'View/history.html'));
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->meta_title = '关键词排名历史';
		$this->display();
	}	

	/**
	 * 按站点分类显示
	 * @return 
	 */
	public function bysite(){
		$class = get_addon_class('BaiduRankTool');
        if(!class_exists($class))
            $this->error('插件不存在');
        $addon = new $class();
        $param = $addon->admin_list;
        extract($param);
        if(!isset($fields))
            $fields = '*';
        if(!isset($search_key))
            $key = 'title';
        else
            $key = $search_key;


        $model  =   D("Addons://{$name}/{$model}");
        // 条件搜索
        $map    =   array();
        foreach($_REQUEST as $name=>$val){
            if($fields == '*'){
                $fields = $model->getDbFields();
            }
            if(in_array($name, $fields)){
                $map[$name] = $val;
            }
        }
        if(!isset($order))  $order = '';
        $list = $model->field($fields)->select();

        $_list = array();
        foreach($list as $item){
        	$site_url = $item['site_url'];

        	if( empty($_list[$site_url]) ){
        		$_list[$site_url] = array();
        	}

        	$_list[$site_url][] = $item;
        }

        $fields = array();
        foreach ($list_grid as &$value) {
            // 字段:标题:链接
            $val = explode(':', $value);
            // 支持多个字段显示
            $field = explode(',', $val[0]);
            $value = array('field' => $field, 'title' => $val[1]);
            if(isset($val[2])){
                // 链接信息
                $value['href'] = $val[2];
                // 搜索链接信息中的字段信息
                preg_replace_callback('/\[([a-z_]+)\]/', function($match) use(&$fields){$fields[]=$match[1];}, $value['href']);
            }
            if(strpos($val[1],'|')){
                // 显示格式定义
                list($value['title'],$value['format']) = explode('|',$val[1]);
            }
            foreach($field as $val){
                $array = explode('|',$val);
                $fields[] = $array[0];
            }
        }

        $this->meta_title = '按站点分类显示';
		
		$this->assign('name', 'BaiduRankTool');
        $this->assign('model', $model->model);
        $this->assign('list_grid', $list_grid);
        $this->assign('_list', $_list);

        $this->assign('execute', $this->fetch($addon->addon_path.'View/bysite.html'));
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
		$this->display();
	}

	public function search(){
		set_time_limit(0);
		$today = date('Y-m-d');
		$today_timesteamp = strtotime($today);

		$id = I('_id');

		$SiteKeyword = M("SiteKeyword");
		$SiteKeywordRank = M("SiteKeywordRank");

		$keywords = $SiteKeyword->where("id = $id")->find();
		if( $keywords == false ){
			$this->error("没有查询词。");
		}
		$keyword  = $keywords['keyword'];
		$site_url = $keywords['site_url'];

		$now_rank = $this->getRank($keyword , $site_url);

		if( $now_rank == 0 ){
			$now_rank = "不在首页";
		}

		$data = array(
			'keyword' => $keyword,
			'site_url' => $site_url,
			'datetime' => $today_timesteamp,
		);

		$searched = $SiteKeywordRank->where($data)->find();

		if( $searched ){
			$SiteKeywordRank->where($data)->setField('rank', $now_rank);
		}else{
			$SiteKeywordRank->add($data);
		}

		$SiteKeyword->where(array('keyword' => $keyword,'site_url' => $site_url))->setField('rank', $now_rank);

		$this->success('查询成功',Cookie('__forward__'));
	}

	/**
	 * 批量查询所有关键词
	 * @param  boolean $force [description]
	 * @return [type]         [description]
	 */
	public function searchAll($force = true){
		set_time_limit(0);
		$today = date('Y-m-d');
		$today_timesteamp = strtotime($today);


		$SiteKeyword = M("SiteKeyword");
		$SiteKeywordRank = M("SiteKeywordRank");

		$keywords = $SiteKeyword->where("status = 1")->select();

		foreach( $keywords as $item ){
			$keyword = $item['keyword'];
			$site_url = $item['site_url'];
			$rank = $item['rank'];

			if( $rank == 0 || $force ){
				$now_rank = $this->getRank($keyword , $site_url);
			}

			if( $now_rank == 0 ){
				$now_rank = "不在首页";
			}

			$data = array(
				'keyword' => $keyword,
				'site_url' => $site_url,
				'datetime' => $today_timesteamp,
			);

			$searched = $SiteKeywordRank->where($data)->find();

			if( $searched ){
				$SiteKeywordRank->where($data)->setField('rank', $now_rank);
			}else{
				$SiteKeywordRank->add($data);
			}

			$SiteKeyword->where(array('keyword' => $keyword,'site_url' => $site_url))->setField('rank', $now_rank);

		}

		$this->success('查询成功',Cookie('__forward__'));
	}

	/**
	 * 查询关键词排名
	 * @param  string $keyword 关键词
	 * @param  string $url     要查询的网站
	 * @return string|int          查询结果
	 */
	public function getRank($keyword = '阿瑞吡坦', $url = 'www.c-aring.com'){
		Vendor('HtmlDomParser.HtmlDomParser');

		$html = \HtmlDomParser::file_get_html( 'http://www.baidu.com/s?wd='.urlencode($keyword) );

		if( !$html->root ){
			return "搜索失败";
		}

		$ret = $html->find('div[class=c-container]'); 

		foreach( $ret as $i=>$e ){
			$url_span = $e->find('span[class=g]');

			if( count($url_span) == 0 ){
				$url_span = $e->find('span[class=c-showurl]');
			}

			if( strpos($url_span[0]->plaintext,$url) !== false ){
				return $i+1;
			};

		}

		return '不在首页';

	}

	/**
	 * 同AdminController的lists方法
	 * @param  [type]  $model [description]
	 * @param  array   $where [description]
	 * @param  string  $order [description]
	 * @param  boolean $field [description]
	 * @return [type]         [description]
	 */
	protected function lists ($model,$where=array(),$order='',$field=true){
        $options    =   array();
        $REQUEST    =   (array)I('request.');
        if(is_string($model)){
            $model  =   M($model);
        }

        $OPT        =   new \ReflectionProperty($model,'options');
        $OPT->setAccessible(true);

        $pk         =   $model->getPk();
        if($order===null){
            //order置空
        }else if ( isset($REQUEST['_order']) && isset($REQUEST['_field']) && in_array(strtolower($REQUEST['_order']),array('desc','asc')) ) {
            $options['order'] = '`'.$REQUEST['_field'].'` '.$REQUEST['_order'];
        }elseif( $order==='' && empty($options['order']) && !empty($pk) ){
            $options['order'] = $pk.' desc';
        }elseif($order){
            $options['order'] = $order;
        }
        unset($REQUEST['_order'],$REQUEST['_field']);

        if(empty($where)){
            $where  =   array('status'=>array('egt',0));
        }
        if( !empty($where)){
            $options['where']   =   $where;
        }
        $options      =   array_merge( (array)$OPT->getValue($model), $options );
        $total        =   $model->where($options['where'])->count();

        if( isset($REQUEST['r']) ){
            $listRows = (int)$REQUEST['r'];
        }else{
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 30;
        }
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p =$page->show();
        $this->assign('_page', $p? $p: '');
        $this->assign('_total',$total);
        $options['limit'] = $page->firstRow.','.$page->listRows;

        $model->setProperty('options',$options);

        return $model->field($field)->select();
    }
}
