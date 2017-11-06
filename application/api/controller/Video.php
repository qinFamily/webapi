<?php
/**
 * Created by PhpStorm.
 * User: chenly
 * Date: 2017/11/6
 * Time: 9:18
 */

namespace app\api\controller;

use think\Request;
use think\Db;
use think\db\Query;
use think\Image;
use think\Config;
use think\File;
use app\common\service\DataService;
use app\common\service\FileService;
use app\admin\model\User as UserModel;
use app\common\controller\BaseApiRest;
use org\Upload;

class Video extends BaseApiRest{

    public $table = 'video';

    public function uploadVideo(){


        try{

            $tempFile = $_FILES['vfile']['name'];

            //$file = Request::instance()->file('vfile');
            $config = [
                'exts'=>['mp4'],
                'rootPath'=> 'static' . DS . 'upload'  .DS,
                'savePath'=>'video/',
                'saveName'=>date('YmdHis')
            ];
            $upload = new Upload($config,'LOCAL');
            $info   =   $upload->upload();

            if($info){

                $filename = $info['vfile']['savename'];
                $uploadPath = FileService::getBaseUriLocal().$info['vfile']['savepath'];
                $fullpath = $uploadPath.$filename;
                $size = $info['vfile']['size'];

                $data = [
                    'url'=> $fullpath,
                    'hit'=>0,
                    'status'=>0,
                    'title'=>'test',
                    'size'=>$size
                ];

                $db = Db::name($this->table);
                $pk = $db->getPk() ? $db->getPk() : 'id';
                $result = DataService::save($db, $data, $pk, []);

                if($result !== false){
                    return json(["code"=>10000,"desc"=>"上传成功","data"=>$data]);
                }else{
                    return json(["code"=>20001,"desc"=>"保存成功","data"=>$data]);
                }
            }else{
                return json(["code"=>20001,"desc"=>"上传失败","data"=>[]]);
            }


            //$info = $file->validate(['size'=>156780,'ext'=>'mp4']);
            /*
            if($info){
                $md51 = join('/',str_split(md5(mt_rand(10000,99999)),16));
                $filePath = 'static' . DS . 'upload'  .DS.$md51;

            }*/



        }catch(\Exception $e){
            return json(["code"=>20001,"desc"=>"上传异常","data"=>$e->getMessage()]);
        }


    }
}