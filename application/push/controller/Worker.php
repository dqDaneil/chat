<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2019/6/20
 * Time: 10:36
 */

namespace app\push\controller;


use think\worker\Server;

class Worker extends Server
{

    protected $socket = 'websocket://daneil.live.com:2346';
    private $_name = [];

    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage( $connection,$data ){
        if( !$this->isJson($data) ){
            $connection->send('非法请求');
            return;
        }
        $data = json_decode($data,true);
        if( !isset($data['type']) ){
            $connection->send('非法请求');
            return;
        }

        switch ( $data['type'] ){
            case 'message' :
                foreach ( $this->worker->connections as $_connection ){
                    $_connection->send($this->createData('message',['name'=>$this->getName($connection->id),'message'=>$data['data']['message']]));
                }
                break;
            case 'setName' :
                $this->setName($connection->id,$data['data']['name']);
                $connection->send($this->createData('msg',['msg'=>'设置昵称为:'.$data['data']['name']]));
                break;
        }
    }

    /**
     * 连接建立时回调方法
     * @param $connection
     */
    public function onConnect( $connection ){
        //设置默认昵称
        $this->setName($connection->id,'匿名'.rand(1000,9999));
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {
        unset($this->_name[$connection->id]);
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {

    }

    /**
     * 是否是JSON
     * @param $str
     */
    private function isJson($str){
        json_decode($str);
        return (json_last_error() == JSON_ERROR_NONE && !is_numeric($str));
    }

    /**
     * 设置用户名
     * @param $id
     * @param $name
     */
    private function setName($id,$name){
        $this->_name[$id] = $name;
    }

    /**
     * 获取用户名
     * @param $id
     * @return mixed
     */
    private function getName($id){
        return $this->_name[$id];
    }

    /**
     * 返回数据
     * @param $type
     * @param $data
     * @param int $code
     * @return false|string
     */
    private function createData($type,$data,$code=0){
        $_data = [
            'code' => $code,
            'type' => $type,
            'data' => $data
        ];
        return json_encode($_data);
    }

}