<?php

//include 'Autoload.php';

include 'SelectServer.php';
include 'Http.php';

/**
 * 创建服务器
 * @var Ambiguous $server
 */
$server = new Server();

$n = 16;
for($i = 0; $i < $n; $i++){
    $pid = pcntl_fork();
    if ($pid == -1) {
        exit('could not fork');
    } elseif ($pid == 0) {
        break;
    }
}

if ($pid == 0) {
    $server->onReceive = function($cli, $msg) {
        $result = Http::resolve($msg);
        //var_dump($result);
        if(!$result['data']){
            echo "error http request!\n";
            return 0;
        }
        switch ($result['data']['path']){
            case "/task_nosleep" ://保存任务
                $md5 = substr(md5(json_encode($result['data'])), 8, 16);
                $result['data']['md5'] = $md5;
                $task_file = "T0001";
                file_put_contents($task_file, json_encode($result['data'])."\n", FILE_APPEND | LOCK_EX);
                $resp = ['key' => $result['data']['md5']];
                break;
            case "/task" ://保存任务
                $md5 = substr(md5(json_encode($result['data'])), 8, 16);
                $result['data']['md5'] = $md5;
                $task_file = "T0001";
                file_put_contents($task_file, json_encode($result['data'])."\n", FILE_APPEND | LOCK_EX);
                $resp = ['key' => $result['data']['md5']];
                usleep(1000);
                break;
            case "/query" ://查询任务进度
                $params = $result['data']['params'];
                $key = $params['key'];
                $resp = query_result($key);
                break;
            case "/download" ://下载文件
                $params = $result['data']['params'];
                $key = $params['key'];
                $resp = query_result($key);
                if($ret['status'] == "ok"){
                    download($key);
                }
                break;
            default :
                $resp = ['invalid http request!'];
                break;
        }
        
        $response = Http::response($resp);
        return $response;
        //stream_socket_sendto($cli, $response);
    };
    $server->accept();
} else {
    for ($i = 0; $i < $n; $i++) {
        pcntl_wait($status);
    }
    $server->close();
}

