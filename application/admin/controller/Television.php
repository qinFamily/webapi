<?php
/**
 * Created by PhpStorm.
 * User: chenly
 * Date: 2017/10/12
 * Time: 8:53
 */

namespace app\admin\controller;

use app\common\controller\BaseAdmin;
use think\Db;
use app\common\service\DataService;

class Television extends BaseAdmin{

    public $table = 'television';

    public function index(){

        $this->title = '电视台管理';
        //$db = Db::name($this->table)->order('id desc');

        $get = $this->request->get();

        $db = Db::field('a.*,b.name as countryName,c.name as provinceName,GROUP_CONCAT(d.name) AS typeNames')
            ->table("t_television")
            ->alias('a')
            ->join(' t_region b ',' a.country = b.code ','left')
            ->join(' t_region c ',' a.province = c.code ','left')
            ->join(' t_dict d','FIND_IN_SET(d.id , a.type_ids) ','left');


        foreach ([ 'name'] as $key) {
            if (isset($get[$key]) && $get[$key] !== '') {
                $db->where('a.'.$key, 'like', "%{$get[$key]}%");
            }
        }


        foreach (['type_id'] as $key) {
            if (isset($get[$key]) && $get[$key] !== '') {

                if($get[$key]>0){
                    //$db->where('a.'.$key, '=', "{$get[$key]}");
                    $map[]=['exp','FIND_IN_SET('.$get[$key].',a.type_ids)'];
                    $db->where($map);
                }

            }
        }
        $db->group('a.id');
        $db->order('a.id desc');

        $where = [
            "char"=>"CHANNEL",
            "pid"=>1
        ];

        $channels = Db::name("dict")->where($where)->order('id asc')->select();
        $this->assign('channels', $channels);

        return parent::_list($db);
    }

    protected function _index_data_filter(&$data)
    {
    }

    public function add()
    {
        return $this->_form($this->table, 'form');
    }

    public function upfile()
    {
        return $this->_myform($this->table, 'upfile','',[],[],'upfile');
    }

    public function getchildregion($parentCode){
        $db = Db::name("region")->where("parentCode",$parentCode)->order('code asc');
        $data = $db->select();
        return json($data);
    }

    public function edit()
    {
        return $this->_form($this->table, 'form');
    }

    protected function _upfile_my_filter(&$vo)
    {
        if ($this->request->isPost()) {
            if (isset($vo['type_ids']) && is_array($vo['type_ids'])) {
                $vo['type_ids'] = join(',', $vo['type_ids']);
            }
            $data = array();
            if (isset($vo['file_3'])) {
                $vo['file_3'] = str_replace("http://webapi.abigfish.org/","",$vo['file_3']);
                $lines = file($vo['file_3']);
                $i = 0;
                foreach($lines as $line)
                {
                    //$str .= $line.'<br>';
                    //$linestr = iconv('gbk', 'utf8', $line);
                    $context = explode(',',$line);
                    $data[$i]['country'] = $vo['country'];
                    $data[$i]['province'] = $vo['province'];
                    $data[$i]['city'] = $vo['city'];

                    //$data[$i]['name'] = mb_convert_encoding($context[0], "utf8", "gbk");//iconv('gbk', 'utf8',$context[0]);
                    $data[$i]['name'] = $context[0];
                    $url_1 = str_replace('\r\n','', $context[1]);
                    $data[$i]['url_1'] = $url_1;
                    //$data[$i]['url_1'] =mb_convert_encoding($context[1], "utf8", "gbk");;
                    $data[$i]['url_2'] ='#';
                    $data[$i]['url_3'] ='#';
                    $data[$i]['type_ids'] =$vo['type_ids'];
                    $i++;
                }
                //$output = iconv('gbk', 'utf8', $str);
            }else{
                $this->error("请上传文件");
            }
            $vo = $data;
            //$str = "";
            //for($i=0;$i<count($vo);$i++){

                //$str.=$vo[$i]['name'].',url='.$vo[$i]['url_1'].'<br>';
            //}

            //$this->error($str.is_array($vo));
        }

        if ($this->request->isGet()) {
            //$get = $this->request->get();
            if(isset($vo['country']) && $vo['country'] !== ''){
                $country = $vo['country'];
            }else{
                $country = 100000;
            }
            $countrys = Db::name("region")->where("parentCode",'0')->order('code asc')->select();
            $this->assign('countrys', $countrys);
            $this->assign('country', $country);

            $provinces = Db::name("region")->where("parentCode",$country)->order('code asc')->select();
            $this->assign('provinces', $provinces);

            if(isset($vo['province']) && $vo['province'] !== ''){
                $province = $vo['province'];
                $this->assign('province', $province);
            }else{
                $province = 0;
                $this->assign('province', $province);
            }
            if(isset($vo['city']) && $vo['city'] !== ''){
                $city = $vo['city'];
                $citys = Db::name("region")->where("parentCode",$province)->order('code asc')->select();
                $this->assign('citys', $citys);
                $this->assign('city', $city);
            }else{
                $this->assign('citys', "");
            }

            $where = [
                "char"=>"CHANNEL",
                "pid"=>1
            ];

            $channels = Db::name("dict")->where($where)->order('id asc')->select();
            $this->assign('channels', $channels);

            //$typeIds = explode(',',$vo['type_ids']);
            //$this->assign('typeIds', $typeIds);
        }
    }

    protected function _form_filter(&$vo)
    {
        if ($this->request->isPost()) {

            if (isset($vo['id'])) {
                unset($vo['name']);
            } elseif (Db::name($this->table)->where(['name' => $vo['name']])->find()) {
                $this->error('电视台已经存在，请重新添加！');
            }
            if (isset($vo['type_ids']) && is_array($vo['type_ids'])) {
                $vo['type_ids'] = join(',', $vo['type_ids']);
            }

            if (isset($vo['is_recommend'])) {
                $vo['is_recommend'] = 1;
            }else{
                $vo['is_recommend'] = 0;
            }
            if (isset($vo['is_hot'])) {
                $vo['is_hot'] = 1;
            }else{
                $vo['is_hot'] = 0;
            }
            if (isset($vo['is_new'])) {
                $vo['is_new'] = 1;
            }else{
                $vo['is_new'] = 0;
            }

            if(isset($vo['icon']) && $vo['icon']!=""){
                $info = getimagesize($vo['icon']);
                $vo['icon_width'] = $info['0'];
                $vo['icon_height'] = $info['1'];
            }

        }

        if ($this->request->isGet()) {
            //$get = $this->request->get();
            if(isset($vo['country']) && $vo['country'] !== ''){
                $country = $vo['country'];
            }else{
                $country = 100000;
            }
            $countrys = Db::name("region")->where("parentCode",'0')->order('code asc')->select();
            $this->assign('countrys', $countrys);
            $this->assign('country', $country);

            $provinces = Db::name("region")->where("parentCode",$country)->order('code asc')->select();
            $this->assign('provinces', $provinces);

            if(isset($vo['province']) && $vo['province'] !== ''){
                $province = $vo['province'];
                $this->assign('province', $province);
            }else{
                $province = 0;
                $this->assign('province', $province);
            }
            if(isset($vo['city']) && $vo['city'] !== ''){
                $city = $vo['city'];
                $citys = Db::name("region")->where("parentCode",$province)->order('code asc')->select();
                $this->assign('citys', $citys);
                $this->assign('city', $city);
            }else{
                $this->assign('citys', "");
            }

            $where = [
                "char"=>"CHANNEL",
                "pid"=>1
            ];

            $channels = Db::name("dict")->where($where)->order('id asc')->select();
            $this->assign('channels', $channels);

            //$typeIds = explode(',',$vo['type_ids']);
            //$this->assign('typeIds', $typeIds);
        }
    }

    public function del()
    {
        if (DataService::update($this->table)) {
            $this->success("删除成功!", '');
        }
        $this->error("删除失败, 请稍候再试!");
    }

    public function forbid()
    {

        if (DataService::update($this->table)) {
            $this->success("禁用成功！", '');
        }
        $this->error("禁用失败，请稍候再试！");
    }

    /**
     * 用户禁用
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("启用成功！", '');
        }
        $this->error("启用失败，请稍候再试！");
    }

}