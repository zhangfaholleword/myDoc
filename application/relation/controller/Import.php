<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2018/12/28
 * Time: 9:32
 */

namespace app\relation\controller;

//此父类可能有变动，自行修改
class Import{
    public static function relationUpload($filename,$extension){
        //引入文件（把扩展文件放入vendor目录下，路径自行修改）
        vendor("PHPExcel.PHPExcel");


        //区分上传文件格式
        if($extension == 'xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
        }else if($extension == 'xls'){
            $objReader =new \PHPExcel_Reader_Excel5();
        }else{
            echo json_encode(['msg' => 'excel格式问题']);
            exit;
        }
        $objPHPExcel = $objReader->load($filename, $encode = 'utf-8');
        $excel_array = $objPHPExcel->getsheet(0)->toArray();   //转换为数组格式
        array_shift($excel_array);  //删除第一个数组(标题);

        return $excel_array;
    }
}