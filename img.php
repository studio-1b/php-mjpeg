
/**
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

$db_no = 2;
if (isset($_POST['cmd']) === true) {
  $host = 'redis-leader';
  if (getenv('GET_HOSTS_FROM') == 'env') {
    $host = getenv('REDIS_LEADER_SERVICE_HOST');
  }

  //header('Content-Type: image/jpeg');
  if ($_POST['cmd'] == 'echo') {
    header('Content-Type: image/jpeg');
    $binary=base64_decode($_POST['value']);
    //https://security.stackexchange.com/questions/42825/web-applications-terminate-strings-on-null-byte
    header("Content-Length: " . strlen($binary));
    echo $binary;

  } elseif ($_POST['cmd'] == 'set') {
    header('Content-Type: application/json');
    //$client = new Predis\Client([
    //  'scheme' => 'tcp',
    //  'host'   => $host,
    //  'port'   => 6379,
    //]);
    $client = new Predis\Client([
      'scheme' => 'tcp',
      'host'   => $host,
      'port'   => 6379,
      'database'=>$db_no,
    ]);
    $key=$_POST['key'];
    $key=substr( $key, 0, 1 );
    if($client->exists($key)) {
      $next = $client->get($key);
      $next = ($next + 1) % 60;
    } else {
      $next = 0;
    }
    $binary=base64_decode($_POST['value']);
    //https://security.stackexchange.com/questions/42825/web-applications-terminate-strings-on-null-byte
    $client->set($key . strval($next), $binary);
    $client->set($key, $next);
    print('{"message": "Updated' . strlen($binary) . '"}');

  } else {
    // basic headers
    header("Content-type: image/jpeg");
    //header("Expires: Mon, 1 Jan 2099 05:00:00 GMT");
    //header("Last-Modified: " . date(DATE_RFC2822 , $imageData['created'] . " GMT");
    $host = 'redis-follower';
    if (getenv('GET_HOSTS_FROM') == 'env') {
      $host = getenv('REDIS_FOLLOWER_SERVICE_HOST');
    }
    //$client = new Predis\Client([
    //  'scheme' => 'tcp',
    //  'host'   => $host,
    //  'port'   => 6379,
    //]);
    $client = new Predis\Client([
      'scheme' => 'tcp',
      'host'   => $host,
      'port'   => 6379,
      'database'=>$db_no,
    ]);
    $key=$_POST['key'];
    $key=substr( $key, 0, 1 );
    if($client->exists($key)) {
      $prev = $client->get($key);
    } else {
      exit();
    }
    $value = $client->get($key . strval($prev));
    header("Content-Length: " . strlen($value));
    echo $value;
    //$value = $client->get($key);
    //header("Content-Length: " . strlen($value));
    //echo $value;
  }
} elseif (isset($_GET['cmd']) === true) {
  $host = 'redis-leader';
  if (getenv('GET_HOSTS_FROM') == 'env') {
    $host = getenv('REDIS_LEADER_SERVICE_HOST');
  }

  if ($_GET['cmd'] == 'mjpeg') {
    // no headers
    header_remove();
    $boundary="bob_boundary";
    header("Content-type: multipart/x-mixed-replace; boundary=" . $boundary);
    //header("Expires: Mon, 1 Jan 2099 05:00:00 GMT");
    //header("Last-Modified: " . date(DATE_RFC2822 , $imageData['created'] . " GMT");
    //header("Content-Length: $imageData['size'] bytes");
    $host = 'redis-follower';
    if (getenv('GET_HOSTS_FROM') == 'env') {
      $host = getenv('REDIS_FOLLOWER_SERVICE_HOST');
    }
    //$client = new Predis\Client([
    //  'scheme' => 'tcp',
    //  'host'   => $host,
    //  'port'   => 6379,
    //]);
    $client = new Predis\Client([
      'scheme' => 'tcp',
      'host'   => $host,
      'port'   => 6379,
      'database'=>$db_no,
    ]);
    $key=$_GET['key'];
    $key=substr( $key, 0, 1 );
    if ($client->exists($key)) {
      $start = $client->get($key);
    } else {
      $start = 0;
      exit();
    }
    for ($x = 1; $x <= 60; $x++) {
      $index = ($start + $x) % 60;
      if ($client->exists($key . strval($index))) {
        $value = $client->get($key . strval($index));
        $len = strlen($value);
      } else {
        $len = 0;
      }
      if ($len != 0) {
        echo "--" . $boundary . "\r\n";
        echo "Content-Type: image/jpeg\r\n";
        echo "Content-Length: " . strlen($len) . "\r\n";
        echo "\r\n";
        echo $value;
      }
    }

    $counter = 0;
    $prev = $start;
    while (connection_aborted() == 0) {
      sleep(1);
      $next = $client->get($key);
      if($next != $prev) {
        $len = strlen($next);
        $value = $client->get($key . strval($next));
        echo "--" . $boundary;
        echo "Content-Type: image/jpeg";
        echo "Content-Length: " . strlen($len);
        echo "";
        echo $value;

        $prev = $next;
      }
      // always time out mjpeg after hour of streaming
      $counter = $counter +1;
      if($counter >3600) {
          //make user refresh every hour, aws ec2 data out is expensie
          //https://aws.amazon.com/blogs/mt/using-aws-cost-explorer-to-analyze-data-transfer-costs/
          //See elastic IP out... https://blog.economize.cloud/ec2-other-costs-explorer-pricing/
          exit();
      }
    }

  } elseif ($_GET['cmd'] == 'set') {
    header('Content-Type: application/json');
    //$client = new Predis\Client([
    //  'scheme' => 'tcp',
    //  'host'   => $host,
    //  'port'   => 6379,
    //]);
    $client = new Predis\Client([
      'scheme' => 'tcp',
      'host'   => $host,
      'port'   => 6379,
      'database'=>$db_no,
    ]);
    $key=$_GET['key'];
    $key=substr( $key, 0, 1 );
    if($client->exists($key)) {
      $next = $client->get($key);
      $next = ($next + 1) % 60;
    } else {
      $next = 0;
    }

    $binary=base64_decode($_GET['value']);
    //https://security.stackexchange.com/questions/42825/web-applications-terminate-strings-on-null-byte
    $client->set($key . strval($next), $binary);
    $client->set($key, $next);
    print('{"message": "Updated' . strlen($binary) . '"}');

  } else {
    // basic headers
    header("Content-type: image/jpeg");
    //header("Expires: Mon, 1 Jan 2099 05:00:00 GMT");
    //header("Last-Modified: " . date(DATE_RFC2822 , $imageData['created'] . " GMT");
    //header("Content-Length: $imageData['size'] bytes");
    $host = 'redis-follower';
    if (getenv('GET_HOSTS_FROM') == 'env') {
      $host = getenv('REDIS_FOLLOWER_SERVICE_HOST');
    }
    //$client = new Predis\Client([
    //  'scheme' => 'tcp',
    //  'host'   => $host,
    //  'port'   => 6379,
    //]);
    $client = new Predis\Client([
      'scheme' => 'tcp',
      'host'   => $host,
      'port'   => 6379,
      'database'=>$db_no,
    ]);
    $key=$_GET['key'];
    $key=substr( $key, 0, 1 );
    if($client->exists($key)) {
      $prev = $client->get($key);
    } else {
      exit();
    }

    $value = $client->get($key . strval($prev));
    header("Content-Length: " . strlen($value));
    echo $value;
  }
} else {
  phpinfo();
} ?>